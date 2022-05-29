<?php

namespace App\Repositories;

use App\Enums\TransactionType;
use App\Enums\WalletType;
use App\Models\GeneralSetting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Withdrawal;

class WalletRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Transaction());
    }

    public function registerWalletTransaction(User $user, TransactionType $txType, float $TxAmount, WalletType $walletType, $details = "")
    {
        $walletBalance = $this->calcWalletBalance($txType, $user, $walletType, $TxAmount);
        $transaction = new $this->model;
        $transaction->user_id = $user->id;
        $transaction->amount = getAmount($TxAmount);
        $transaction->charge = 0;
        $transaction->trx_type = $txType->value;
        $transaction->trx = getTrx();
        $transaction->wallet_type = $walletType->value;
        $transaction->details = $details;
        $transaction->post_balance = $walletBalance;
        $transaction->save();
        $this->updateUserWalletAmount($user, $walletType->value, $walletBalance);
        return $transaction;
    }

    private function calcWalletBalance(TransactionType $txType, User $user, WalletType $walletType, float $TxAmount)
    {
        $userWalletAmount = $user->{$walletType->value};
        if ($txType->equals(TransactionType::add()))
            $userWalletAmount = $userWalletAmount + $TxAmount;
        else
            $userWalletAmount = $userWalletAmount - $TxAmount;

        return getAmount($userWalletAmount);
    }

    private function updateUserWalletAmount(User $user, string $value, float $walletBalance)
    {
        $user->update([
            $value => $walletBalance
        ]);
    }

    public function registerWithdrawalTransaction(User $user, TransactionType $txType, float $TxAmount, $details = "")
    {
        $gnl = GeneralSetting::getActiveSetting();
        $autoWithDrawMethod = $gnl->autoWithdrawMethod;
        $w['method_id'] = $autoWithDrawMethod->id;
        $w['user_id'] = $user->id;
        $w['amount'] = getAmount($TxAmount);
        $w['currency'] = $autoWithDrawMethod->currency;
        $w['rate'] = $autoWithDrawMethod->rate;
        $w['status'] = 1;
        $w['charge'] = 0;
        $w['type'] = $txType->value;
        $w['final_amount'] = getAmount($TxAmount);
        $w['after_charge'] = getAmount($TxAmount);
        $w['trx'] = getTrx();
        return Withdrawal::create($w);

    }
}
