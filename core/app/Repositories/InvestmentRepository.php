<?php

namespace App\Repositories;

use AliAbdalla\Tafqeet\Core\Tafqeet;
use App\Enums\TransactionType;
use App\Enums\WalletType;
use App\Jobs\MakeInvestmentPdfJob;
use App\Models\AdminNotification;
use App\Models\GeneralSetting;
use App\Models\Invest;
use App\Models\Plan;
use App\Models\TimeSetting;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Mpdf\Output\Destination;

class InvestmentRepository extends BaseRepository
{

    private $walletRepo;

    public function __construct()
    {
        $this->walletRepo = new WalletRepository();
        parent::__construct(new Invest());
    }

    public function deleteInvestment(User $user, Invest $invest)
    {
        DB::transaction(function () use ($invest) {
            $adminNotification = new AdminNotification();
            $adminNotification->user_id = $invest->user->id;
            $adminNotification->title = $invest->cur_sym . $invest->amount . ' invest in ' . $invest->plan->name . '  has been removed by  ' . Auth::guard('admin')->user()->username;
            $adminNotification->click_url = urlPath('admin.users.invests', $invest->user->id);
            $adminNotification->save();
            $invest->delete();
        });
    }

    public function makeInvestmentRevenue(Invest $invest, GeneralSetting $gnl, ?float $revenueAmount = null)
    {
        $isInvestmentFinished = $invest->isInvestmentPeriodFinished();
        $user = $invest->user;
        if ($revenueAmount == null) {
            $revenueAmount = $invest->interest;
        }
        $this->walletRepo->registerWalletTransaction(
            $user,
            TransactionType::add(),
            $revenueAmount,
            WalletType::interest_wallet(),
            getAmount($invest->interest) . ' ' . $gnl->cur_text . ' Interest From ' . @$invest->plan->name
        );
        if ($gnl->invest_return_commission == 1) {
            levelCommission($user->id, $revenueAmount, 'interest');
        }
        if ($isInvestmentFinished && $invest->capitalAmountRefundable()) {
            $this->walletRepo->registerWalletTransaction(
                $user,
                TransactionType::add(),
                $invest->amount,
                WalletType::interest_wallet(),
                getAmount($revenueAmount) . ' ' . $gnl->cur_text . ' Capital Back From ' . @$invest->plan->name
            );
        }
        $invest->update([
            'status' => $isInvestmentFinished ? 0 : 1,
            'return_rec_time' => $invest->return_rec_time + 1,
            'next_time' => $invest->nextEligibleRevenueDate($gnl),
            'last_time' => Carbon::now()
        ]);
    }

    /**
     */
    public function approveInvestment(Invest $invest): ?Invest
    {
        return DB::transaction(function () use ($invest) {
            $gnl = GeneralSetting::getActiveSetting();
            $this->walletRepo->registerWalletTransaction(
                $invest->user,
                TransactionType::sub(),
                $invest->amount,
                WalletType::from($invest->wallet_type),
                'Invested On ' . $invest->plan->name
            );
            if ($gnl->invest_commission == 1) {
                levelCommission($invest->user->id, $invest->amount, "invest");
            }
            $invest->update([
                'status' => 1,
                'signed_at' => Carbon::now(),
                "pin_code" => null,
            ]);
            MakeInvestmentPdfJob::dispatch($invest->fresh());
            $adminNotification = new AdminNotification();
            $adminNotification->user_id = $invest->user->id;
            $adminNotification->title = $invest->cur_sym . $invest->amount . ' invested to ' . $invest->plan->name;
            $adminNotification->click_url = urlPath('admin.users.invests', $invest->user->id);
            $adminNotification->save();
            return $invest;
        });
        return null;
    }

    /**
     * @throws MpdfException
     */
    public function toPdf(Invest $invest): string
    {

        $user = $invest->user;
        $plan = $invest->plan;

        $mpdf = new Mpdf([
            'tempDir' => storage_path('tempdir'),
            'default_font' => 'frutiger'
        ]);
        $contractDayName = $invest->contractDayName();
        $contractDate = $invest->contractDate()->format("d/m/Y");
        $userBirthDay = $invest->user->birth_date;
        $investorCityName = $user->address && $user->address->city ? $user->address->city : "------";
        // $stamp = getImageBase64("assets/images/contract/image/signature2.png");
        $stamp3 = getImageBase64("assets/images/contract/image/signature3.png");
        $investmentAmount = number_format($invest->amount, 2);
        $investmentInverterAmount = number_format(round($invest->amount / 10, 2), 2);
        $investmentAmountInWords = Tafqeet::arablic(round($invest->amount), 'sar');
        $investmentWeight = round($invest->amount / 8530, 2);
        $monthlyRevenue = number_format(($invest->amount * 2.5) / 100, 2);
        $render = view('assets.investment_contract', compact('plan','stamp3', 'user', 'invest', 'contractDayName', 'contractDate',
            'userBirthDay', 'investorCityName', 'investmentAmount', 'investmentInverterAmount',
            'investmentAmountInWords',
            'investmentWeight',
            'monthlyRevenue'
        ))->render();
        // $mpdf->SetHTMLFooter("<img src=" . $stamp . " alt=\"signature\" width='200px' /> <hr>{PAGENO}");
        $mpdf->WriteHTML($render);
        $path = 'contracts/' . $invest->trx . '_' . Str::random(5) . '.pdf';
        Storage::put('public/' . $path, $mpdf->Output($invest->trx . '.pdf', Destination::STRING_RETURN));
        return $path;
    }
    /**
     * @throws MpdfException
     */
    public function toPdf2(Invest $invest): string
    {

        $user = $invest->user;
        $plan = $invest->plan;

        $mpdf = new Mpdf([
            'tempDir' => storage_path('tempdir'),
            'default_font' => 'frutiger'
        ]);
        $contractDayName = $invest->contractDayName();
        $contractDate = $invest->contractDate()->format("d/m/Y");
        $userBirthDay = $invest->user->birth_date;
        $investorCityName = $user->address && $user->address->city ? $user->address->city : "------";
        // $stamp = getImageBase64("assets/images/contract/image/signature2.png");
        $stamp3 = getImageBase64("assets/images/contract/image/signature3.png");
        $investmentAmount = number_format($invest->amount, 2);
        $investmentInverterAmount = number_format(round($invest->amount / 10, 2), 2);
        $investmentAmountInWords = Tafqeet::arablic(round($invest->amount), 'sar');
        $investmentWeight = round($invest->amount / 8530, 2);
        $monthlyRevenue = number_format(($invest->amount * 2.5) / 100, 2);
        $render = view('assets.investment_contract2', compact('plan','stamp3', 'user', 'invest', 'contractDayName', 'contractDate',
            'userBirthDay', 'investorCityName', 'investmentAmount', 'investmentInverterAmount',
            'investmentAmountInWords',
            'investmentWeight',
            'monthlyRevenue'
        ))->render();
        // $mpdf->SetHTMLFooter("<img src=" . $stamp . " alt=\"signature\" width='200px' /> <hr>{PAGENO}");
        $mpdf->WriteHTML($render);
        $path = 'contracts2/' . $invest->trx . '_' . Str::random(5) . '.pdf';
        Storage::put('public/' . $path, $mpdf->Output($invest->trx . '.pdf', Destination::STRING_RETURN));
        return $path;
    }

    public function nextEligibleInvestRevenueDate(float $hours, GeneralSetting $setting): Carbon
    {
        $nextRevenueDate = Carbon::now();
        while (true) {
            $nextRevenueDate = Carbon::parse($nextRevenueDate)->addHours($hours);
            if (!$setting->isHoliday($nextRevenueDate)) return $nextRevenueDate;
        }
    }

    public function createInvestment(Plan $plan, User $user, GeneralSetting $gnl, float $investmentAmount, $next, WalletType $walletType): Invest
    {
        $revenueAmount = $this->getInvestmentRevenueAmount($plan, $investmentAmount);
        $period = $this->getInvestmentPeriod($plan);
        $time_name = TimeSetting::where('time', $plan->times)->first();
        $invest = new $this->model;
        $invest->user_id = $user->id;
        $invest->plan_id = $plan->id;
        $invest->amount = getAmount($investmentAmount);
        $invest->interest = $revenueAmount;
        $invest->period = $period;
        $invest->time_name = $time_name->name;
        $invest->hours = $plan->times;
        $invest->next_time = $next;
        $invest->status = 2;
        $invest->wallet_type = $walletType->value;
        $invest->cur_text = $gnl->cur_text;
        $invest->capital_status = $plan->capital_back_status;
        $invest->trx = getTrx();
        $invest->save();
        return $invest;

    }

    public function getInvestmentRevenueAmount(Plan $plan, float $amount)
    {
        if ($plan->interest_status == 1)
            return ($amount * $plan->interest) / 100;
        return $plan->interest;
    }

    public function getInvestmentPeriod(Plan $plan)
    {
        return ($plan->lifetime_status == 1) ? '-1' : $plan->repeat_time;
    }

    /**
     * @throws ValidationException
     */
    public function hasValidBalance(Invest $invest)
    {
        $user = $invest->user;
        $amount = $invest->amount;
        $walletType = WalletType::from($invest->wallet_type);
        if ($user->{$walletType->value} < $amount) {
            throw ValidationException::withMessages([
                'balance' => 'invalid amount'
            ]);
        }
    }

    public function sendContractConfirmationOtp(Invest $invest)
    {
        $otp = rand(10000, 999999);
        $invest->update([
            'pin_code' => $otp
        ]);

        send_contract_otp($invest->user, $invest, $otp);

    }

    public function makeWithdraw(Invest $invest, ?float $withdrawAmount = null)
    {
        $gnl = GeneralSetting::getActiveSetting();
        $autoWithDrawMethod = $gnl->autoWithdrawMethod;
        if ($autoWithDrawMethod) {
            if ($withdrawAmount == null) {
                $days = $this->getDaysDueToLastWithdraw($invest);
                $withdrawAmount = getAmount($days * $invest->interest);
            }
            $this->walletRepo->registerWalletTransaction(
                $invest->user,
                TransactionType::sub(),
                $withdrawAmount,
                WalletType::interest_wallet(),
                getAmount($withdrawAmount) . ' Withdraw From ' . @$invest->plan->name
            );
            $w['method_id'] = $autoWithDrawMethod->id;
            $w['user_id'] = $invest->user->id;
            $w['amount'] = getAmount($withdrawAmount);
            $w['currency'] = $autoWithDrawMethod->currency;
            $w['rate'] = $autoWithDrawMethod->rate;
            $w['status'] = 1;
            $w['charge'] = 0;
            $w['final_amount'] = getAmount($withdrawAmount);
            $w['after_charge'] = getAmount($withdrawAmount);
            $w['trx'] = getTrx();
            $withdraw = Withdrawal::create($w);

            $adminNotification = new AdminNotification();
            $adminNotification->user_id = $invest->user->id;
            $adminNotification->title = 'New withdraw request from ' . $invest->user->username;
            $adminNotification->click_url = urlPath('admin.withdraw.details', $withdraw->id);
            $adminNotification->save();

            // notify($invest->user, 'WITHDRAW_APPROVE', [
            //     'method_name' => $withdraw->method->name,
            //     'method_currency' => $withdraw->currency,
            //     'method_amount' => getAmount($withdraw->final_amount),
            //     'amount' => getAmount($withdraw->amount),
            //     'charge' => getAmount($withdraw->charge),
            //     'currency' => $gnl->cur_text,
            //     'rate' => getAmount($withdraw->rate),
            //     'trx' => $withdraw->trx,
            //     'post_balance' => getAmount($invest->user->interest_wallet)
            // ]);


            $invest->update([
                'withdraw_count' => DB::raw('withdraw_count + 1'),
                'withdraw_amount' => DB::raw("withdraw_amount + $withdrawAmount"),
                'last_withdraw_at' => Carbon::now()
            ]);
        }

    }

    private function getDaysDueToLastWithdraw(Invest $invest)
    {
        if ($invest->withdraw_count == 0)
            return abs(Carbon::parse($invest->signed_at)->diffInDays(Carbon::now()));
        return abs(Carbon::parse($invest->last_withdraw_at)->diffInDays(Carbon::now()));
    }
}
