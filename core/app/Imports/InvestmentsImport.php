<?php

namespace App\Imports;

use Alkoumi\LaravelHijriDate\Hijri;
use App\Enums\TransactionType;
use App\Enums\WalletType;
use App\Models\GeneralSetting;
use App\Models\Plan;
use App\Models\User;
use App\Repositories\InvestmentRepository;
use App\Repositories\UserRepository;
use App\Repositories\WalletRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;

class InvestmentsImport implements ToCollection
{
    /**
     * @var UserRepository
     */
    private $userRepo;
    /**
     * @var WalletRepository
     */
    private $walletRepo;
    /**
     * @var InvestmentRepository
     */
    private $investmentRepo;

    public function __construct()
    {
        $this->userRepo = new UserRepository();
        $this->walletRepo = new WalletRepository();
        $this->investmentRepo = new InvestmentRepository();
    }

    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        DB::transaction(function () use ($collection) {
            $targetPlan = Plan::findOrFail(1);
            $gnl = GeneralSetting::getActiveSetting();
            $dataRaws = $collection->whereNotNull(2)->skip(1);
            foreach ($dataRaws as $item) {
                $user = $this->getUser($item);
                $investmentAmount = (float)$item->get(9);

                $transaction =
                    $this
                        ->walletRepo
                        ->registerWalletTransaction(
                            $user,
                            TransactionType::add(),
                            $investmentAmount,
                            WalletType::deposit_wallet(),
                            "Added Balance Via Admin"
                        );


                notify($user, 'BAL_ADD', [
                    'trx' => $transaction->trx,
                    'amount' => $transaction->amount,
                    'currency' => 'SAR',
                    'post_balance' => getAmount($user->deposit_wallet),
                ]);
                $nextEligibleInvestRevenueDate = $this->investmentRepo->nextEligibleInvestRevenueDate(
                    $targetPlan->times,
                    $gnl
                );
                $invest = $this->investmentRepo->createInvestment(
                    $targetPlan,
                    $user,
                    $gnl,
                    $investmentAmount,
                    $nextEligibleInvestRevenueDate,
                    WalletType::deposit_wallet()
                );

                $this->investmentRepo->approveInvestment($invest);
                $withdrawAmount = (float)$item->get(10);
                $days = ceil($withdrawAmount / $invest->interest);
                $this->investmentRepo->makeInvestmentRevenue($invest, $gnl, $withdrawAmount);
                $invest->update([
                    'return_rec_time' => $days
                ]);
                $this->investmentRepo->makeWithdraw($invest, $withdrawAmount);
            }

        });
    }

    private function getUser(Collection $collection): User
    {
        $user = User::where('username', $collection->get(2))->firstOr(function () use ($collection) {
            return $this->userRepo->createUser([
                'firstname' => $collection->get(0),
                'lastname' => $collection->get(1),
                'username' => $collection->get(2),
                'country' => $collection->get(3),
                'birth_day' => Hijri::Date($collection->get(4)),
                'mobile' => $collection->get(5),
                'email' => $collection->get(6),
                'city' => $collection->get(7),
                'password' => $collection->get(8),
                'mobile_code' => '966',

            ]);
        });
        $user->forceFill([
            'firstname' => $collection->get(0),
            'lastname' => $collection->get(1),
            'username' => $collection->get(2),
            'birth_day' => Hijri::Date($collection->get(4)),
            'mobile' => '966' . $collection->get(5),
            'email' => $collection->get(6) ?? Str::random(10) . '@fortore.com',
            "address" => [
                'address' => '',
                'state' => '',
                'zip' => '',
                'city' => $collection->get(7),
                'country' => $collection->get(3),
            ],
        ]);
        return $user;
    }

}
