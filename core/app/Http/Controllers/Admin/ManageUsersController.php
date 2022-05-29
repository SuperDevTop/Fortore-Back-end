<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TransactionType;
use App\Enums\WalletType;
use App\Http\Controllers\Controller;
use App\Models\CommissionLog;
use App\Models\Deposit;
use App\Models\EmailLog;
use App\Models\GeneralSetting;
use App\Models\Invest;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserLogin;
use App\Models\Withdrawal;
use App\Repositories\InvestmentRepository;
use App\Repositories\WalletRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ManageUsersController extends Controller
{

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
        $this->walletRepo = new WalletRepository();
        $this->investmentRepo = new InvestmentRepository();
    }

    public function allUsers()
    {
        $page_title = 'Manage Users';
        $empty_message = 'No user found';
        $users = User::latest()->paginate(getPaginate());
        return view('admin.users.list', compact('page_title', 'empty_message', 'users'));
    }

    public function activeUsers()
    {
        $page_title = 'Manage Active Users';
        $empty_message = 'No active user found';
        $users = User::active()->latest()->paginate(getPaginate());
        return view('admin.users.list', compact('page_title', 'empty_message', 'users'));
    }

    public function bannedUsers()
    {
        $page_title = 'Banned Users';
        $empty_message = 'No banned user found';
        $users = User::banned()->latest()->paginate(getPaginate());
        return view('admin.users.list', compact('page_title', 'empty_message', 'users'));
    }

    public function emailUnverifiedUsers()
    {
        $page_title = 'Email Unverified Users';
        $empty_message = 'No email unverified user found';
        $users = User::emailUnverified()->latest()->paginate(getPaginate());
        return view('admin.users.list', compact('page_title', 'empty_message', 'users'));
    }

    public function emailVerifiedUsers()
    {
        $page_title = 'Email Verified Users';
        $empty_message = 'No email verified user found';
        $users = User::emailVerified()->latest()->paginate(getPaginate());
        return view('admin.users.list', compact('page_title', 'empty_message', 'users'));
    }


    public function smsUnverifiedUsers()
    {
        $page_title = 'SMS Unverified Users';
        $empty_message = 'No sms unverified user found';
        $users = User::smsUnverified()->latest()->paginate(getPaginate());
        return view('admin.users.list', compact('page_title', 'empty_message', 'users'));
    }

    public function smsVerifiedUsers()
    {
        $page_title = 'SMS Verified Users';
        $empty_message = 'No sms verified user found';
        $users = User::smsVerified()->latest()->paginate(getPaginate());
        return view('admin.users.list', compact('page_title', 'empty_message', 'users'));
    }

    public function withDepositBalance()
    {
        $page_title = 'Users With Deposit Wallet';
        $empty_message = 'No sms verified user found';
        $users = User::where('deposit_wallet', '!=', 0)->latest()->paginate(getPaginate());
        return view('admin.users.list', compact('page_title', 'empty_message', 'users'));
    }

    public function withInterestBalance()
    {
        $page_title = 'Users With Interest Wallet';
        $empty_message = 'No sms verified user found';
        $users = User::where('interest_wallet', '!=', 0)->latest()->paginate(getPaginate());
        return view('admin.users.list', compact('page_title', 'empty_message', 'users'));
    }


    public function search(Request $request, $scope)
    {
        $search = $request->search;
        $users = User::where(function ($user) use ($search) {
            $user->where('username', 'like', "%$search%")
                ->orWhere('email', 'like', "%$search%")
                ->orWhere('mobile', 'like', "%$search%")
                ->orWhere('firstname', 'like', "%$search%")
                ->orWhere('lastname', 'like', "%$search%");
        });
        $page_title = '';
        switch ($scope) {
            case 'active':
                $page_title .= 'Active ';
                $users = $users->where('status', 1);
                break;
            case 'banned':
                $page_title .= 'Banned';
                $users = $users->where('status', 0);
                break;
            case 'emailUnverified':
                $page_title .= 'Email Unerified ';
                $users = $users->where('ev', 0);
                break;
            case 'smsUnverified':
                $page_title .= 'SMS Unverified ';
                $users = $users->where('sv', 0);
                break;
        }
        $users = $users->paginate(getPaginate());
        $page_title .= 'User Search - ' . $search;
        $empty_message = 'No search result found';
        return view('admin.users.list', compact('page_title', 'search', 'scope', 'empty_message', 'users'));
    }


    public function detail($id)
    {
        $page_title = 'User Detail';
        $user = User::findOrFail($id);
        $totalDeposit = Deposit::where('user_id', $user->id)->where('status', 1)->sum('amount');
        $totalWithdraw = $user->withdrawals_wallet;
        $totalTransaction = Transaction::where('user_id', $user->id)->count();
        $totalInvest = Invest::where('user_id', $user->id)->count();
        $totalReferral = User::where('ref_by', $user->id)->count();
        $totalCommission = CommissionLog::where('to_id', $user->id)->sum('commission_amount');
        $countries = json_decode(file_get_contents(resource_path('views/partials/country.json')));
        return view('admin.users.detail', compact('page_title', 'user', 'totalDeposit', 'totalWithdraw', 'totalTransaction', 'totalInvest', 'totalReferral', 'totalCommission', 'countries'));
    }


    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $countryData = json_decode(file_get_contents(resource_path('views/partials/country.json')));
        $request->validate([
            'firstname' => 'required|max:60',
            'lastname' => 'required|max:60',
            'city' => 'required|string',
            'birth_day' => 'required|date|before:today',
            'email' => 'required|email|max:160|unique:users,email,' . $user->id,
        ]);
        if ($request->email != $user->email && User::whereEmail($request->email)->whereId('!=', $user->id)->count() > 0) {
            $notify[] = ['error', 'Email already exists.'];
            return back()->withNotify($notify);
        }
        if ($request->mobile != $user->mobile && User::where('mobile', $request->mobile)->whereId('!=', $user->id)->count() > 0) {
            $notify[] = ['error', 'Phone number already exists.'];
            return back()->withNotify($notify);
        }

        if ($request->contract) {
            $user->addMedia(request()->file('contract'))->setFileName('new name.pdf')->toMediaCollection('users');
        }

        $countryCode = $request->country;
        $user->update([
            'mobile' => $request->mobile,
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'email' => $request->email,
            'birth_day' => Carbon::parse($request->input("birth_day")),
            'address' => [
                'address' => $request->address,
                'city' => $request->input("city"),
                'state' => $request->state,
                'zip' => $request->zip,
                'country' => @$countryData->$countryCode->country,
            ],
            'status' => $request->status ? 1 : 0,
            'ev' => $request->ev ? 1 : 0,
            'sv' => $request->sv ? 1 : 0,
            'ts' => $request->ts ? 1 : 0,
            'tv' => $request->tv ? 1 : 0,
        ]);

        if ($request->lbalance) {
            $user->loyaltyPoints()->create([
                'type' => "add",
                'description' => "",
                'pos_order_id' => "",
                'amount' => $request->input('lbalance'),
                'pin_code' => $user->pin_code,
            ]);
        }

        $notify[] = ['success', 'User detail has been updated'];
        return redirect()->back()->withNotify($notify);
    }

    public function delete_media($id)
    {
        Media::find($id)->delete();
        $notify[] = ['success', 'User contract has been deleted'];
        return redirect()->back()->withNotify($notify);
    }

    public function addSubBalance(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $addBalance = (bool)$request->input("act");
        $request->validate(['amount' => ['required', 'numeric', 'gt:0', function ($attribute, $value, $fail) use ($addBalance, $request, $user) {
            $value = (float)$value;
            if (!$addBalance) {
                $balance = (float)$user->{$request->input('wallet')};
                if ($balance < $value) {
                    $fail($user->username . ' has insufficient balance.');
                }
            }
        }], 'wallet' => 'string|in:deposit_wallet,interest_wallet,withdrawals_wallet']);

        $wallet = $request->input("wallet");
        $amount = getAmount($request->input("amount", 0));
        $general = GeneralSetting::first(['cur_text', 'cur_sym']);
        $trx = getTrx();
        $transactionType = $addBalance ? TransactionType::add() : TransactionType::sub();
        $description = $addBalance ? "'Added Balance Via Admin'" : "'Subtract Balance Via Admin'";

        if ($request->input('wallet') == 'withdrawals_wallet') {
            $this->walletRepo
                ->registerWithdrawalTransaction(
                    $user,
                    $transactionType,
                    $amount,
                    $description
                );
        } else {
            $this->walletRepo
                ->registerWalletTransaction(
                    $user,
                    $transactionType,
                    $amount,
                    WalletType::from($wallet),
                    $description
                );
        }


        if ($addBalance) {
            notify($user, 'BAL_ADD', [
                'trx' => $trx,
                'amount' => $amount,
                'currency' => $general->cur_text,
                'post_balance' => getAmount($user->$wallet),
            ]);
            $notify[] = ['success', $general->cur_sym . $amount . ' has been added to ' . $user->username . ' balance'];
        } else {
            notify($user, 'BAL_SUB', [
                'trx' => $trx,
                'amount' => $amount,
                'currency' => $general->cur_text,
                'post_balance' => getAmount($user->$wallet)
            ]);
            $notify[] = ['success', $general->cur_sym . $amount . ' has been subtracted from ' . $user->username . ' balance'];
        }

        return back()->withNotify($notify);
    }


    public function userLoginHistory($id)
    {
        $user = User::findOrFail($id);
        $page_title = 'User Login History - ' . $user->username;
        $empty_message = 'No users login found.';
        $login_logs = $user->login_logs()->latest()->paginate(getPaginate());
        return view('admin.users.logins', compact('page_title', 'empty_message', 'login_logs'));
    }

    public function referrals($id)
    {
        $user = User::findOrFail($id);
        $page_title = 'User Referrals - ' . $user->username;
        $referrals = User::where('ref_by', $user->id)->orderBy('id', 'desc')->paginate(getPaginate());
        return view('admin.users.referral', compact('page_title', 'user', 'referrals'));
    }

    public function loginHistory(Request $request)
    {
        if ($request->search) {
            $search = $request->search;
            $page_title = 'User Login History Search - ' . $search;
            $empty_message = 'No search result found.';
            $login_logs = UserLogin::whereHas('user', function ($query) use ($search) {
                $query->where('username', $search);
            })->latest()->paginate(getPaginate());
            return view('admin.users.logins', compact('page_title', 'empty_message', 'search', 'login_logs'));
        }
        $page_title = 'User Login History';
        $empty_message = 'No users login found.';
        $login_logs = UserLogin::latest()->paginate(getPaginate());
        return view('admin.users.logins', compact('page_title', 'empty_message', 'login_logs'));
    }

    public function loginIpHistory($ip)
    {
        $page_title = 'Login By - ' . $ip;
        $login_logs = UserLogin::where('user_ip', $ip)->latest()->paginate(getPaginate());
        $empty_message = 'No users login found.';
        return view('admin.users.logins', compact('page_title', 'empty_message', 'login_logs'));
    }


    public function showEmailSingleForm($id)
    {
        $user = User::findOrFail($id);
        $page_title = 'Send Email To: ' . $user->username;
        return view('admin.users.email_single', compact('page_title', 'user'));
    }

    public function sendEmailSingle(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string|max:65000',
            'subject' => 'required|string|max:190',
        ]);

        $user = User::findOrFail($id);
        send_general_email($user->email, $request->input("subject"), $request->input("message"), $user->username);
        $notify[] = ['success', $user->username . ' will receive an email shortly.'];
        return back()->withNotify($notify);
    }

    public function transactions(Request $request, $id)
    {
        $user = User::findOrFail($id);
        if ($request->search) {
            $search = $request->search;
            $page_title = 'Search User Transactions : ' . $user->username;
            $transactions = $user->transactions()->where('trx', $search)->with('user')->latest()->paginate(getPaginate());
            $empty_message = 'No transactions';
            return view('admin.reports.transactions', compact('page_title', 'search', 'user', 'transactions', 'empty_message'));
        }
        $page_title = 'User Transactions : ' . $user->username;
        $transactions = $user->transactions()->with('user')->latest()->paginate(getPaginate());
        $empty_message = 'No transactions';
        return view('admin.reports.transactions', compact('page_title', 'user', 'transactions', 'empty_message'));
    }

    public function invests(Request $request, $id)
    {
        $user = User::findOrFail($id);
        if ($request->search) {
            $search = $request->search;
            $page_title = 'Search User Invest : ' . $user->username;
            $logs = $user->invests()->where('trx', $search)->with('user')->latest()->paginate(getPaginate());
            $empty_message = 'No Data Found!';
            return view('admin.reports.plan-log', compact('page_title', 'search', 'user', 'logs', 'empty_message'));
        }
        $page_title = 'User Invest : ' . $user->username;
        $logs = $user->invests()->with('user')->latest()->paginate(getPaginate());
        $empty_message = 'No Data Found!';
        return view('admin.reports.plan-log', compact('page_title', 'user', 'logs', 'empty_message'));
    }

    public function deleteInvest(User $id, Invest $investId)
    {
        $this->investmentRepo->deleteInvestment($id, $investId);
        $notify[] = ['success', 'User Investment has been deleted'];
        return redirect()->back()->withNotify($notify);
    }

    public function referralCommissionsDeposit(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $page_title = 'User Commission Log: ' . $user->username;
        $logs = $user->commissions()->where('type', 'deposit')->with('user', 'bywho')->paginate(getPaginate());
        $empty_message = 'No Data Found!';
        return view('admin.users.commission-log', compact('page_title', 'user', 'logs', 'empty_message'));
    }


    public function referralCommissionsInvest(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $page_title = 'User Commission Log: ' . $user->username;
        $logs = $user->commissions()->where('type', 'invest')->with('user', 'bywho')->paginate(getPaginate());
        $empty_message = 'No Data Found!';
        return view('admin.users.commission-log', compact('page_title', 'user', 'logs', 'empty_message'));
    }


    public function referralCommissionsInterest(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $page_title = 'User Commission Log: ' . $user->username;
        $logs = $user->commissions()->where('type', 'interest')->with('user', 'bywho')->paginate(getPaginate());
        $empty_message = 'No Data Found!';
        return view('admin.users.commission-log', compact('page_title', 'user', 'logs', 'empty_message'));
    }


    public function deposits(Request $request, $id)
    {
        $user = User::findOrFail($id);
        if ($request->search) {
            $search = $request->search;
            $page_title = 'Search User Deposits : ' . $user->username;
            $deposits = $user->deposits()->where('trx', $search)->latest()->paginate(getPaginate());
            $empty_message = 'No deposits';
            return view('admin.deposit.log', compact('page_title', 'search', 'user', 'deposits', 'empty_message'));
        }

        $page_title = 'User Deposit : ' . $user->username;
        $deposits = $user->deposits()->latest()->paginate(getPaginate());
        $empty_message = 'No deposits';
        return view('admin.deposit.log', compact('page_title', 'user', 'deposits', 'empty_message'));
    }

    public function withdrawals(Request $request, $id)
    {
        $user = User::findOrFail($id);
        if ($request->search) {
            $search = $request->search;
            $page_title = 'Search User Withdrawals : ' . $user->username;
            $withdrawals = $user->withdrawals()->where('trx', 'like', "%$search%")->latest()->paginate(getPaginate());
            $empty_message = 'No withdrawals';
            return view('admin.withdraw.withdrawals', compact('page_title', 'user', 'search', 'withdrawals', 'empty_message'));
        }
        $page_title = 'User Withdrawals : ' . $user->username;
        $withdrawals = $user->withdrawals()->latest()->paginate(getPaginate());
        $empty_message = 'No withdrawals';
        return view('admin.withdraw.withdrawals', compact('page_title', 'user', 'withdrawals', 'empty_message'));
    }

    public function showEmailAllForm()
    {
        $page_title = 'Send Email To All Users';
        return view('admin.users.email_all', compact('page_title'));
    }

    public function sendEmailAll(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:65000',
            'subject' => 'required|string|max:190',
        ]);
        foreach (User::where('status', 1)->cursor() as $user) {
            send_general_email($user->email, $request->subject, $request->message, $user->username);
        }

        $notify[] = ['success', 'All users will receive an email shortly.'];
        return back()->withNotify($notify);
    }


    public function emailLog($id)
    {
        $user = User::findOrFail($id);
        $page_title = 'Email log of ' . $user->username;
        $logs = EmailLog::where('user_id', $id)->orderBy('id', 'desc')->paginate(getPaginate());
        $empty_message = 'No data found';
        return view('admin.users.email_log', compact('page_title', 'logs', 'empty_message'));
    }

    public function emailDetails($id)
    {
        $email = EmailLog::findOrFail($id);
        $page_title = 'Email details';
        return view('admin.users.email_details', compact('page_title', 'email'));
    }
}
