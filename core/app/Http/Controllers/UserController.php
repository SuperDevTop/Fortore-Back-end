<?php

namespace App\Http\Controllers;

use App\Lib\GoogleAuthenticator;
use App\Models\AdminNotification;
use App\Models\CommissionLog;
use App\Models\Deposit;
use App\Models\GeneralSetting;
use App\Models\Holiday;
use App\Models\Invest;
use App\Models\Plan;
use App\Models\PromotionTool;
use App\Models\SupportTicket;
use App\Models\TimeSetting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Withdrawal;
use App\Models\WithdrawMethod;
use App\Models\LoyaltyPoint;
use App\Models\Log;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Image;
use PDOException;
use Validator;


class UserController extends Controller
{
    static $user_name = '';
    static $pass = '';

    public function __construct()
    {
        $this->activeTemplate = activeTemplate();
    }

    public function home()
    {
        $data['page_title'] = 'Dashboard';
        $data['totalInvest'] = Invest::where('user_id', auth()->id())->where('status', 1)->sum('amount');
        $data['totalWithdraw'] = Withdrawal::where('user_id', Auth::id())->whereIn('status', [1])->sum('amount');
        $data['lastWithdraw'] = Withdrawal::where('user_id', Auth::id())->whereIn('status', [1])->latest()->first('amount');
        $data['totalDeposit'] = Deposit::where('user_id', Auth::id())->where('status', 1)->sum('amount');
        $data['lastDeposit'] = Deposit::where('user_id', Auth::id())->where('status', 1)->latest()->first('amount');
        $data['totalTicket'] = SupportTicket::where('user_id', Auth::id())->count();
        $data['user'] = Auth::user();
        $data['totalDailyRevenue'] = Invest::where('user_id', auth()->id())->where('status', 1)->sum('interest');
        $data['totalInterestAmount'] = Auth::user()->interest_wallet;
        $data['totalWithdrawals'] = Auth::user()->withdrawals_wallet;
        return view($this->activeTemplate . 'user.dashboard', $data);
    }

    public function profile()
    {
        $data['page_title'] = "Profile Setting";
        $data['user'] = Auth::user();
        return view($this->activeTemplate . 'user.profile-setting', $data);
    }

    public function submitProfile(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'firstname' => 'required|string|max:50',
            'lastname' => 'required|string|max:50',
            'address' => "sometimes|required|max:80",
            'state' => 'sometimes|required|max:80',
            'zip' => 'sometimes|required|max:40',
            'city' => 'required|max:50',
            'birth_day' => 'required|date|before:today',
            'image' => 'mimes:png,jpg,jpeg'
        ], [
            'firstname.required' => 'First Name Field is required',
            'lastname.required' => 'Last Name Field is required'
        ]);


        $in['firstname'] = $request->firstname;
        $in['lastname'] = $request->lastname;
        $in['birth_day'] = Carbon::parse($request->input('birth_day'));
        $country = @$user->address->country;
        $in['address'] = [
            'address' => $request->address,
            'state' => $request->state,
            'zip' => $request->zip,
            'country' => $country,
            'city' => $request->city,
        ];

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '_' . $user->username . '.jpg';
            $location = imagePath()['profile']['path'] . '/' . $filename;
            $in['image'] = $filename;

            $path = imagePath()['profile']['path'] . '/';
            $link = $path . $user->image;
            if (file_exists($link)) {
                @unlink($link);
            }
            $image = Image::make($image);
            $image->resize(800, 800);
            $image->save($location);
        }
        $user->fill($in)->save();
        $notify[] = ['success', 'Profile Updated successfully.'];
        return back()->withNotify($notify);
    }

    public function pinCode()
    {
        $data['page_title'] = "Pin Code";
        $data['pin_code'] = Auth::user()->pin_code;
        return view($this->activeTemplate . 'user.pin-code', $data);
    }

    public function submitPinCode()
    {
        $user = Auth::user();
        $pinCode = strstr($user->username, 2) . Str::random(3) . rand(1000, 9999);
        $user->update([
            'pin_code' => $pinCode
        ]);
        $notify[] = ['success', 'Pin Code Changes successfully.'];
        return back()->withNotify($notify);
    }

    public function loyaltyPoints()
    {
        $data['page_title'] = "Loyalty Points";
        $data['loyalty_points'] = Auth::user()->loyaltyPoints()->orderByDesc('id')->paginate(50);
        $data['user'] = Auth::user();
        return view($this->activeTemplate . 'user.loyalty-points', $data);
    }

    public function changePassword()
    {
        $data['page_title'] = "CHANGE PASSWORD";
        return view($this->activeTemplate . 'user.password', $data);
    }

    public function submitPassword(Request $request)
    {

        $this->validate($request, [
            'current_password' => 'required',
            'password' => 'required|min:5|confirmed'
        ]);
        try {
            $user = auth()->user();
            if (Hash::check($request->current_password, $user->password)) {
                $password = Hash::make($request->password);
                $user->password = $password;
                $user->save();
                $notify[] = ['success', 'Password Changes successfully.'];
                return back()->withNotify($notify);
            } else {
                $notify[] = ['error', 'Current password not match.'];
                return back()->withNotify($notify);
            }
        } catch (PDOException $e) {
            $notify[] = ['error', $e->getMessage()];
            return back()->withNotify($notify);
        }
    }

    /*
     * Deposit History
     */
    public function depositHistory()
    {
        $page_title = 'Deposit History';
        $empty_message = 'No history found.';
        $logs = auth()->user()->deposits()->with(['gateway'])->latest()->paginate(getPaginate());
        return view($this->activeTemplate . 'user.deposit_history', compact('page_title', 'empty_message', 'logs'));
    }

    /*
     * Withdraw Operation
     */

    public function withdrawMoney()
    {
        $data['withdrawMethod'] = WithdrawMethod::whereStatus(1)->get();
        $data['page_title'] = "Withdraw Money";
        return view(activeTemplate() . 'user.withdraw.methods', $data);
    }

    public function withdrawStore(Request $request)
    {
        $this->validate($request, [
            'method_code' => 'required',
            'amount' => 'required|numeric'
        ]);
        $method = WithdrawMethod::where('id', $request->method_code)->where('status', 1)->firstOrFail();
        $user = auth()->user();

        if ($request->amount < $method->min_limit) {
            $notify[] = ['error', 'Your Requested Amount is Smaller Than Minimum Amount.'];
            return back()->withNotify($notify);
        }
        if ($request->amount > $method->max_limit) {
            $notify[] = ['error', 'Your Requested Amount is Larger Than Maximum Amount.'];
            return back()->withNotify($notify);
        }

        if ($request->amount > $user->interest_wallet) {
            $notify[] = ['error', 'In Sufficient Balance In your Interest Wallet.'];
            return back()->withNotify($notify);
        }


        $charge = $method->fixed_charge + ($request->amount * $method->percent_charge / 100);
        $afterCharge = $request->amount - $charge;
        $finalAmount = getAmount($afterCharge * $method->rate);

        $w['method_id'] = $method->id; // wallet method ID
        $w['user_id'] = $user->id;
        $w['amount'] = getAmount($request->amount);
        $w['currency'] = $method->currency;
        $w['rate'] = $method->rate;
        $w['charge'] = $charge;
        $w['final_amount'] = $finalAmount;
        $w['after_charge'] = $afterCharge;
        $w['trx'] = getTrx();
        $result = Withdrawal::create($w);
        session()->put('wtrx', $result->trx);
        return redirect()->route('user.withdraw.preview');
    }

    public function withdrawPreview()
    {
        $data['withdraw'] = Withdrawal::where('trx', session()->get('wtrx'))->where('status', 0)->with('method', 'user')->latest()->firstOrFail();
        $data['page_title'] = "Withdraw Preview";
        return view($this->activeTemplate . 'user.withdraw.preview', $data);
    }


    public function withdrawSubmit(Request $request)
    {
        $general = GeneralSetting::first();
        $withdraw = Withdrawal::where('trx', session()->get('wtrx'))->where('status', 0)->with('method', 'user')->latest()->firstOrFail();
        $user = auth()->user();

        $rules = [];
        $inputField = [];
        if ($withdraw->method->user_data != null) {
            foreach ($withdraw->method->user_data as $key => $cus) {
                $rules[$key] = [$cus->validation];
                if ($cus->type == 'file') {
                    array_push($rules[$key], 'image');
                    array_push($rules[$key], 'mimes:jpeg,jpg,png');
                    array_push($rules[$key], 'max:2048');
                }
                if ($cus->type == 'text') {
                    array_push($rules[$key], 'max:191');
                }
                if ($cus->type == 'textarea') {
                    array_push($rules[$key], 'max:300');
                }
                $inputField[] = $key;
            }
        }
        $this->validate($request, $rules);
        $user = auth()->user();

        if (getAmount($withdraw->amount) > $user->interest_wallet) {
            $notify[] = ['error', 'Request Amount is Larger Then Your Interest Wallet Balance.'];
            return back()->withNotify($notify);
        }


        $directory = date("Y") . "/" . date("m") . "/" . date("d");
        $path = imagePath()['verify']['withdraw']['path'] . '/' . $directory;

        $collection = collect($request);
        $reqField = [];
        if ($withdraw->method->user_data != null) {
            foreach ($collection as $k => $v) {
                foreach ($withdraw->method->user_data as $inKey => $inVal) {
                    if ($k != $inKey) {
                        continue;
                    } else {
                        if ($inVal->type == 'file') {
                            if ($request->hasFile($inKey)) {
                                try {
                                    $reqField[$inKey] = [
                                        'field_name' => $directory . '/' . uploadImage($request[$inKey], $path),
                                        'type' => $inVal->type,
                                    ];
                                } catch (Exception $exp) {
                                    $notify[] = ['error', 'Could not upload your ' . $request[$inKey]];
                                    return back()->withNotify($notify)->withInput();
                                }
                            }
                        } else {
                            $reqField[$inKey] = $v;
                            $reqField[$inKey] = [
                                'field_name' => $v,
                                'type' => $inVal->type,
                            ];
                        }
                    }
                }
            }
            $withdraw['withdraw_information'] = $reqField;
        } else {
            $withdraw['withdraw_information'] = null;
        }

        $withdraw->status = 2;
        $withdraw->save();

        $user->interest_wallet -= $withdraw->amount;
        $user->update();


        $transaction = new Transaction();
        $transaction->user_id = $withdraw->user_id;
        $transaction->amount = getAmount($withdraw->amount);
        $transaction->post_balance = getAmount($user->interest_wallet);
        $transaction->charge = getAmount($withdraw->charge);
        $transaction->trx_type = '-';
        $transaction->details = getAmount($withdraw->final_amount) . ' ' . $withdraw->currency . ' Withdraw Via ' . $withdraw->method->name;
        $transaction->trx = $withdraw->trx;
        $transaction->wallet_type = 'interest_wallet';
        $transaction->save();


        $adminNotification = new AdminNotification();
        $adminNotification->user_id = $user->id;
        $adminNotification->title = 'New withdraw request from ' . $user->username;
        $adminNotification->click_url = urlPath('admin.withdraw.details', $withdraw->id);
        $adminNotification->save();

        notify($user, 'WITHDRAW_REQUEST', [
            'method_name' => $withdraw->method->name,
            'method_currency' => $withdraw->currency,
            'method_amount' => getAmount($withdraw->final_amount),
            'amount' => getAmount($withdraw->amount),
            'charge' => getAmount($withdraw->charge),
            'currency' => $general->cur_text,
            'rate' => getAmount($withdraw->rate),
            'trx' => $withdraw->trx,
            'post_balance' => getAmount($user->interest_wallet)
        ]);

        $notify[] = ['success', 'Withdraw Request Successfully Send'];
        return redirect()->route('user.withdraw.history')->withNotify($notify);
    }

    public function withdrawLog()
    {
        $data['page_title'] = "Withdraw Log";
        $data['withdraws'] = Withdrawal::where('user_id', Auth::id())->where('status', '!=', 0)->with('method')->latest()->paginate(getPaginate());
        $data['empty_message'] = "No Data Found!";
        return view($this->activeTemplate . 'user.withdraw.log', $data);
    }


    public function show2faForm()
    {
        $gnl = GeneralSetting::first();
        $ga = new GoogleAuthenticator();
        $user = auth()->user();
        $secret = $ga->createSecret();
        $qrCodeUrl = $ga->getQRCodeGoogleUrl($user->username . '@' . $gnl->sitename, $secret);
        $prevcode = $user->tsc;
        $prevqr = $ga->getQRCodeGoogleUrl($user->username . '@' . $gnl->sitename, $prevcode);
        $page_title = 'Two Factor';
        return view($this->activeTemplate . 'user.twofactor', compact('page_title', 'secret', 'qrCodeUrl', 'prevcode', 'prevqr'));
    }

    public function create2fa(Request $request)
    {
        $user = auth()->user();
        $this->validate($request, [
            'key' => 'required',
            'code' => 'required',
        ]);

        $ga = new GoogleAuthenticator();
        $secret = $request->key;
        $oneCode = $ga->getCode($secret);

        if ($oneCode === $request->code) {
            $user->tsc = $request->key;
            $user->ts = 1;
            $user->tv = 1;
            $user->save();


            $userAgent = getIpInfo();
            send_email($user, '2FA_ENABLE', [
                'operating_system' => $userAgent['os_platform'],
                'browser' => $userAgent['browser'],
                'ip' => $userAgent['ip'],
                'time' => $userAgent['time']
            ]);
            send_sms($user, '2FA_ENABLE', [
                'operating_system' => $userAgent['os_platform'],
                'browser' => $userAgent['browser'],
                'ip' => $userAgent['ip'],
                'time' => $userAgent['time']
            ]);


            $notify[] = ['success', 'Google Authenticator Enabled Successfully'];
            return back()->withNotify($notify);
        } else {
            $notify[] = ['error', 'Wrong Verification Code'];
            return back()->withNotify($notify);
        }
    }


    public function disable2fa(Request $request)
    {
        $this->validate($request, [
            'code' => 'required',
        ]);

        $user = auth()->user();
        $ga = new GoogleAuthenticator();

        $secret = $user->tsc;
        $oneCode = $ga->getCode($secret);
        $userCode = $request->code;

        if ($oneCode == $userCode) {

            $user->tsc = null;
            $user->ts = 0;
            $user->tv = 1;
            $user->save();

            $userAgent = getIpInfo();
            send_email($user, '2FA_DISABLE', [
                'operating_system' => $userAgent['os_platform'],
                'browser' => $userAgent['browser'],
                'ip' => $userAgent['ip'],
                'time' => $userAgent['time']
            ]);
            send_sms($user, '2FA_DISABLE', [
                'operating_system' => $userAgent['os_platform'],
                'browser' => $userAgent['browser'],
                'ip' => $userAgent['ip'],
                'time' => $userAgent['time']
            ]);


            $notify[] = ['success', 'Two Factor Authenticator Disable Successfully'];
            return back()->withNotify($notify);
        } else {
            $notify[] = ['error', 'Wrong Verification Code'];
            return back()->with($notify);
        }
    }


    public function transactionsDeposit()
    {
        $page_title = 'Deposit Wallet Transactions';
        $logs = auth()->user()->transactions()->orderBy('id', 'desc')->where('wallet_type', 'deposit_wallet')->paginate(getPaginate());
        $empty_message = "No result found";
        return view($this->activeTemplate . 'user.transactions', compact('page_title', 'logs', 'empty_message'));
    }


    public function transactionsInterest()
    {
        $page_title = 'Deposit Wallet Transactions';
        $logs = auth()->user()->transactions()->orderBy('id', 'desc')->where('wallet_type', 'interest_wallet')->paginate(getPaginate());
        $empty_message = "No result found";
        return view($this->activeTemplate . 'user.transactions', compact('page_title', 'logs', 'empty_message'));
    }

    public function refMy($lv_no = 1)
    {
        $page_title = "My referred Users";
        $empty_message = "No result found";
        $lev = 0;
        $user_id = auth()->user()->id;
        while ($user_id != null) {
            $user = User::where('ref_by', $user_id)->first();
            if ($user) {
                $lev++;
                $user_id = $user->id;
            } else {
                $user_id = null;
            }
        }
        return view($this->activeTemplate . 'user.my_referral', compact('page_title', 'empty_message', 'lv_no', 'lev'));
    }

    public function commissionsDeposit()
    {
        $page_title = "Deposit Referral Commissions";
        $logs = CommissionLog::where('type', 'deposit')->where('to_id', Auth::id())->with('user', 'bywho')->latest()->paginate(getPaginate());
        $empty_message = "No result found";
        return view($this->activeTemplate . 'user.referral_commissions', compact('page_title', 'logs', 'empty_message'));
    }

    public function commissionsInvest()
    {
        $page_title = "Invest Referral Commissions";
        $logs = CommissionLog::where('type', 'invest')->where('to_id', Auth::id())->with('user', 'bywho')->latest()->paginate(getPaginate());
        $empty_message = "No result found";
        return view($this->activeTemplate . 'user.referral_commissions', compact('page_title', 'logs', 'empty_message'));
    }

    public function commissionsInterest()
    {
        $page_title = "Interest Referral Commissions";
        $logs = CommissionLog::where('type', 'interest')->where('to_id', Auth::id())->with('user', 'bywho')->latest()->paginate(getPaginate());
        $empty_message = "No result found";
        return view($this->activeTemplate . 'user.referral_commissions', compact('page_title', 'logs', 'empty_message'));
    }

     public function interestLog()
    {
        $page_title = 'Interest log';
        $logs = Invest::where('user_id', Auth::id())->latest()->paginate(getPaginate());
        $empty_message = "No result found";
        return view($this->activeTemplate . 'user.interest_log', compact('page_title', 'logs', 'empty_message'));
    } 


    /*
     * USER PLAN
     */

     public function plan()
    {

        $data['page_title'] = "Investment Plan";
        $data['plans'] = Plan::where('status', 1)->get();
        $data['planContent'] = getContent('plan.content', true);
        $data['extend_blade'] = $this->activeTemplate . 'layouts.master';
        return view($this->activeTemplate . 'user.plan', $data);
    } 

    public function buyPlan(Request $request)
    {
        $request->validate([
            'amount' => 'required|min:0',
            'plan_id' => 'required',
            'wallet_type' => 'required',
        ]);
        $user = Auth::user();
        $gnl = GeneralSetting::first();
        $plan = Plan::where('id', $request->plan_id)->where('status', 1)->first();
        $wallet = $request->wallet_type;
        if ($wallet != 'deposit_wallet' && $wallet != 'interest_wallet' && $wallet != 'checkout') {
            $notify[] = ['error', 'Opps! Wallet is not valid'];
            return back()->withNotify($notify);
        }


        if ($request->wallet_type == "checkout") {
            if ($plan->fixed_amount == '0') {
                if ($request->amount < $plan->minimum) {
                    $notify[] = ['error', 'Minimum Invest ' . getAmount($plan->minimum) . ' ' . $gnl->cur_text];
                    return back()->withNotify($notify);
                }
                if ($request->amount > $plan->maximum) {
                    $notify[] = ['error', 'Maximum Invest ' . getAmount($plan->maximum) . ' ' . $gnl->cur_text];
                    return back()->withNotify($notify);
                }
            } else {
                if ($request->amount != $plan->fixed_amount) {
                    $notify[] = ['error', 'Please Invest must ' . getAmount($plan->fixed_amount) . ' ' . $gnl->cur_text];
                    return back()->withNotify($notify);
                }
            }
            session()->put('amount', encrypt($request->amount));
            session()->put('token', encrypt($request->plan_id));
            return redirect()->route('user.deposit');
        }

        $user = auth()->user();

        if (!$plan) {
            $notify[] = ['error', 'Invalid Plan!'];
            return back()->withNotify($notify);
        }

        if ($plan->fixed_amount == '0') {
            if ($request->amount < $plan->minimum) {
                $notify[] = ['error', 'Minimum Invest ' . getAmount($plan->minimum) . ' ' . $gnl->cur_text];
                return back()->withNotify($notify);
            }
            if ($request->amount > $plan->maximum) {
                $notify[] = ['error', 'Maximum Invest ' . getAmount($plan->maximum) . ' ' . $gnl->cur_text];
                return back()->withNotify($notify);
            }
        } else {
            if ($request->amount != $plan->fixed_amount) {
                $notify[] = ['error', 'Please Invest must ' . getAmount($plan->fixed_amount) . ' ' . $gnl->cur_text];
                return back()->withNotify($notify);
            }
        }
        if ($request->amount > $user->$wallet) {
            $notify[] = ['error', 'Insufficient Balance'];
            return back()->withNotify($notify);
        }

        $time_name = TimeSetting::where('time', $plan->times)->first();
        $now = Carbon::now();

        $new_balance = getAmount($user->$wallet - $request->amount);
        $user->$wallet = $new_balance;
        $user->save();

        $baseCurrency = currency()['fiat'];


        $transaction = new Transaction();
        $transaction->user_id = $user->id;
        $transaction->amount = getAmount($request->amount);
        $transaction->charge = 0;
        $transaction->trx_type = '-';
        $transaction->trx = getTrx();
        $transaction->wallet_type = $wallet;
        $transaction->details = 'Invested On ' . $plan->name;
        $transaction->post_balance = getAmount($user->$wallet);
        $transaction->save();


        //start
        if ($plan->interest_status == 1) {
            $interest_amount = ($request->amount * $plan->interest) / 100;
        } else {
            $interest_amount = $plan->interest;
        }
        $period = ($plan->lifetime_status == 1) ? '-1' : $plan->repeat_time;
        //end


        $now = Carbon::now();
        $offDay = (array)$gnl->off_day;
        while (0 == 0) {
            $nextPossible = Carbon::parse($now)->addHours($plan->times)->toDateTimeString();
            $dayName = strtolower(date('D', strtotime($nextPossible)));
            $holiday = Holiday::where('date', date('Y-m-d', strtotime($nextPossible)))->count();
            if (!array_key_exists($dayName, $offDay)) {
                if ($holiday == 0) {
                    $next = $nextPossible;
                    break;
                }
            }
            $now = $nextPossible;
        }


        if ($plan->fixed_amount == 0) {

            if ($plan->minimum <= $request->amount && $plan->maximum >= $request->amount) {

                $invest = new Invest();
                $invest->user_id = $user->id;
                $invest->plan_id = $plan->id;
                $invest->amount = $request->amount;
                $invest->interest = $interest_amount;
                $invest->period = $period;
                $invest->time_name = $time_name->name;
                $invest->hours = $plan->times;
                $invest->next_time = $next;
                $invest->status = 1;
                $invest->capital_status = $plan->capital_back_status;
                $invest->trx = getTrx();
                $invest->save();


                if ($gnl->invest_commission == 1) {
                    $commissionType = 'invest';
                    levelCommission($user->id, $request->amount, $commissionType);
                }

                notify($user, $type = 'INVESTMENT_PURCHASE', [
                    'trx' => $invest->trx,
                    'amount' => getAmount($request->amount),
                    'currency' => $gnl->cur_text,
                    'interest_amount' => $interest_amount,
                ]);


                $adminNotification = new AdminNotification();
                $adminNotification->user_id = $user->id;
                $adminNotification->title = $gnl->cur_sym . $request->amount . ' invested to ' . $plan->name;
                $adminNotification->click_url = urlPath('admin.users.invests', $user->id);
                $adminNotification->save();


                $notify[] = ['success', 'Invested Successfully'];
                return redirect()->route('user.interest.log')->withNotify($notify);
            }
            $notify[] = ['error', 'Invalid Amount'];
            return back()->withNotify($notify);
        } else {
            if ($plan->fixed_amount == $request->amount) {

                $invest = new Invest();
                $invest->user_id = $user->id;
                $invest->plan_id = $plan->id;
                $invest->amount = $request->amount;
                $invest->interest = $interest_amount;
                $invest->period = $period;
                $invest->time_name = $time_name->name;
                $invest->hours = $plan->times;
                $invest->next_time = $next;
                $invest->status = 1;
                $invest->capital_status = $plan->capital_back_status;
                $invest->trx = getTrx();
                $invest->save();


                if ($gnl->invest_commission == 1) {
                    $commissionType = 'invest';
                    levelCommission($user->id, $request->amount, $commissionType);
                }
                notify($user, $type = 'INVESTMENT_PURCHASE', [
                    'trx' => $invest->trx,
                    'amount' => getAmount($request->amount),
                    'currency' => $gnl->cur_text,
                    'interest_amount' => $interest_amount,
                ]);
                $user->save();

                $adminNotification = new AdminNotification();
                $adminNotification->user_id = $user->id;
                $adminNotification->title = $gnl->cur_sym . $request->amount . ' invested to ' . $plan->name;
                $adminNotification->click_url = urlPath('admin.users.invests', $user->id);
                $adminNotification->save();

                $notify[] = ['success', 'Package Purchased Successfully Complete'];
                return redirect()->route('user.interest.log')->withNotify($notify);
            }

            $notify[] = ['error', 'Something Went Wrong'];
            return back()->withNotify($notify);
        }


    }

    public function getCheckoutToken(Request $request)
    {
        $request->validate([
            'planId' => 'required'
        ]);
        $plan = Plan::where('id', $request->planId)->where('status', 1)->first();
        session()->put('token', $plan->id);
        return 0;
    }


    public function transfer()
    {
        $page_title = 'Transfer Balance';
        $gnl = GeneralSetting::first();
        if ($gnl->b_transfer == 0) {
            $notify[] = ['error', 'User Balance Transfer Currently Disabled'];
            return redirect()->route('user.home1')->withNotify($notify);
        }
        return view($this->activeTemplate . 'user.transfer_balance', compact('page_title'));
    }

    public function transferSubmit(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'amount' => 'required|numeric|gt:0',
            'wallet' => 'required|integer',
        ]);
        $user = auth()->user();
        if ($user->username == $request->username) {
            $notify[] = ['error', 'You cannot send money to your won account'];
            return back()->withNotify($notify);
        }
        $receiver = User::where('username', $request->username)->first();
        if (!$receiver) {
            $notify[] = ['error', 'Opps! Receiver not found'];
            return back()->withNotify($notify);
        }
        $gnl = GeneralSetting::first();
        $charge = $gnl->f_charge + ($request->amount * $gnl->p_charge) / 100;
        $afterCharge = $request->amount + $charge;
        if ($request->wallet == 1) {
            $wallet = 'deposit_wallet';
            $wallet_type = 'Deposit Wallet';
        } elseif ($request->wallet == 2) {
            $wallet = 'interest_wallet';
            $wallet_type = 'Interest Wallet';
        } else {
            $notify[] = ['error', 'Wallet not found'];
            return back()->withNotify($notify);
        }
        if ($user->$wallet < $afterCharge) {
            $notify[] = ['error', 'Opps! You have no sufficient balance to this wallet'];
            return back()->withNotify($notify);
        }
        $user->$wallet -= $afterCharge;
        $user->save();


        $transaction = new Transaction();
        $transaction->user_id = $user->id;
        $transaction->amount = getAmount($afterCharge);
        $transaction->charge = $charge;
        $transaction->trx_type = '-';
        $transaction->trx = getTrx();
        $transaction->wallet_type = $wallet;
        $transaction->details = 'Balance Transfer to ' . $receiver->username;
        $transaction->post_balance = getAmount($user->$wallet);
        $transaction->save();

        $receiver->deposit_wallet += $request->amount;
        $receiver->save();


        $transaction = new Transaction();
        $transaction->user_id = $receiver->id;
        $transaction->amount = getAmount($request->amount);
        $transaction->charge = 0;
        $transaction->trx_type = '+';
        $transaction->trx = getTrx();
        $transaction->wallet_type = 'deposit_wallet';
        $transaction->details = 'Balance Received from ' . $user->username;
        $transaction->post_balance = getAmount($user->deposit_wallet);
        $transaction->save();


        notify($user, 'BALANCE_TRANSFER', $shortCodes = [
            'wallet_type' => $wallet_type,
            'amount' => $request->amount,
            'charge' => $charge,
            'afterCharge' => $afterCharge,
            'post_balance' => $user->$wallet,
            'currency' => $gnl->cur_text,
            'receiver' => $receiver->username,
        ]);


        notify($user, 'BALANCE_RECEIVE', $shortCodes = [
            'wallet_type' => 'Deposit Wallet',
            'amount' => $request->amount,
            'post_balance' => $user->deposit_wallet,
            'currency' => $gnl->cur_text,
            'sender' => $user->username,
        ]);


        $notify[] = ['success', 'Balance Transfered Successfully'];
        return back()->withNotify($notify);

    }

    public function promotions()
    {
        $page_title = 'Promotional tools';
        $tools = PromotionTool::orderBy('id', 'desc')->paginate(getPaginate());
        $empty_message = "No Tools Yet";
        return view($this->activeTemplate . 'user.promotions', compact('page_title', 'empty_message', 'tools'));
    }

    public function app_login(Request $req)
    {
        $username = $req->username;
        $password = $req->password;
        $randnum = $req->randnum;
        $otpnumber = $req->otpnumber;
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              
        if($username =='' || $password == '' || $otpnumber == '')
        {
            return response()->json([
                'success' => false,
                'message' => 'Please fill out all fields'
            ]);
        }

        if($randnum != $otpnumber)
        {
            return response()->json([
                'success' => false,
                'message' => 'Please confirm otpnumber'
            ]);
        }

        $user = User::where('username', $username)->get();

        // If the user doesn't exist
        if(count($user) == 0)
        {
            return response()->json([
                'success'=> false,
                'message' => 'User no found. Please sign up'
            ]);
        }

        $db_password = $user[0]->password;
        
        if(Hash::check($password, $db_password) == true)
        {
            $id = $user[0]->id;

            // Call real-time invest 
            // real_time_invest(500, $id);

            return response()->json([
                'success'=> true,
                'user' => $user,
                'id' => $id
            ]);
        }
        else
        {
            return response() ->json([
                'success' => false,
                'message' => 'Please enter correct password'
            ], 401);
        }
    }

    public function app_register(Request $req)
    {
        $firstname = $req->firstname;
        $lastname = $req->lastname;
        $username = $req->username;
        $email = $req->email;
        $password = $req->password;
        $confirmPassword = $req->confirmPassword;
        $mobile = $req->mobile;
        $country = $req->country;
        $city =  $req->city;
        $otpnumber = $req->otpnumber;
        $randnum = $req->randnum;
        $birthday = $req->birthday;
        $address = null;

        if($username == '' ||
           $email == '' ||
           $password == '' ||
           $confirmPassword == '' ||
           $mobile == ''||
           $firstname == '' ||
           $lastname == '' ||
           $country == '' ||
           $city == ''||
           $otpnumber == ''||
           $birthday == '')
        {
            return response()->json([
                'success' => false,
                'message' => 'Please fill out all fields!'
            ]);
        }

        if($password != $confirmPassword)
        {
            return response()->json([
                'success' => false,
                'message' => 'No Matching Password'
            ]);
        }
        else if($otpnumber != $randnum)
        {
            return response()->json([
                'success' => false,
                'message' => 'Please confirm otpnumber'
            ]);
        }

        // If the user already exists
        $result =  User::where('username', $username)->get();
        
        if(count($result) == 1)
        {
            return response()->json([
                'success' => false,
                'message' => 'The same username already exists'
            ]);
        }

        $result =  User::where('email', $email)->get();
        
        if(count($result) == 1)
        {
            return response()->json([
                'success' => false,
                'message' => 'The same email already exists'
            ]);
        }

        $result =  User::where('mobile', $mobile)->get();
        
        if(count($result) == 1)
        {
            return response()->json([
                'success' => false,
                'message' => 'The same mobile number already exists'
            ]);
        }

        $password = Hash::make($req->password);

        $add = json_encode([
            "country" => $country,
            "city"=> $city,
            'address' => $address,
            'zip' => null
          ]);
      
        $temp = User::insert([
            'firstname' => $firstname,
            'lastname' => $lastname,
            'birth_day' => $birthday,
            'username' => $username,
            'email' => $email,
            'mobile' => $mobile,
            'password' => $password,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'address' => $add
        ]); 

        if($temp != null)
        {
            return response()->json([
                'success' => true,
            ]);
        }
        else
        {
            return response()->json([
                'success' => false,
                'message' => 'DB Operation Failed!'
            ]);
        }

    }

    public function app_logout(Request $req)
    {
        return response()->json([
            'success' => $req->session()->get('id')
        ]);
    }

    public function app_interestLog($user_id)
    {
        $id = $user_id;
        $logs = Invest::where('user_id', $id)
                        ->where('status', 1)
                        ->orderByDesc('id')->get();
        
       $current_time = Carbon::now()->toDateTimeString();

        for($i = 0; $i < count($logs); $i++)
        {
            $plan_id = $logs[$i]->plan_id;
            $name = Plan::where('id',$plan_id)->get()[0]->name;
            $logs[$i]['name'] = $name;
            $next_time = $logs[$i]->next_time;
            $remain = strtotime($next_time) - strtotime($current_time);
            
            if($remain < 0)
            {
                $remain = 35990;
            }

            $logs[$i]['remainingTime'] = strval($remain);
        }

        $empty_message = "No result found";
        
        if($logs != null)
        {
            return response()->json([
                'success' => true,
                'logs' => $logs,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => $empty_message
            ]);
        }
    }

    public function app_updateProfile(Request $req)
    {
        $id = $req->id;

        $firstname = $req->firstname;
        $lastname = $req->lastname;
        $email = $req->email;
        $username = $req->username;
        $mobile = $req->mobile;
        $birth_day = $req->birth;
        $country = $req->country;
        $city = $req->city;
        $zipcode = $req->zipcode;
        $address = $req->address;

        if($email == '')
        {
            return response()->json([
                'success' => false,
                'message' => 'Email field is necessary'
            ]);
        }

        $add = json_encode([
              "country" => $country,
              "address" => $address,
              "zip" => $zipcode,
              "city"=> $city
            ]);

        User::where('id', $id)->update([
            'firstname' => $firstname,
            "lastname" => $lastname,
            'email' => $email,
            'username' => $username,
            'mobile' => $mobile,
            "address" => $add,
            'birth_day' => $birth_day
        ]);

        return response()->json([
            'success' => true
        ]);
    }

    public function app_plan()
    {
        $data = Plan::all(['name', 'times', 'minimum', 'maximum', 'interest', 'repeat_time' ]);
        $order_change[0] = $data[4]; 
        $order_change[1] = $data[3]; 
        $order_change[2] = $data[5]; 
        $order_change[3] = $data[2]; 
        $order_change[4] = $data[1]; 
        $order_change[5] = $data[0]; 

        return response()->json([
            'success' => true,
            'data' => $order_change
        ]);
    }

    public function app_invest(Request $req)
    {
        $id = $req->id;
        $amount = $req->investamount;
        $membership = $req->membershipcard;
        $dates = $req->dates;

        $plan_id = Plan::where('name', $membership)->get()[0]->id;
        $result = User::where('id', $id)->get()[0];

        if($result != null)
        {
            $deposit_wallet = $result->deposit_wallet;
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No enough deposit wallet'
            ]);
        }

        if($amount > $deposit_wallet)
        {
            return response()->json([
                'success' => false,
                'message' => 'No enough deposit wallet'
            ]);
        }

        $deposit_wallet = $deposit_wallet - $amount;

        $transaction = new Transaction();
        $transaction->user_id = $id;
        $transaction->amount = getAmount($amount);
        $transaction->charge = 0;
        $transaction->trx_type = '-';
        $transaction->trx = getTrx();
        $transaction->wallet_type = 'deposit_wallet';
        $transaction->details = 'Invested On ' . $membership;
        $transaction->post_balance = getAmount($deposit_wallet);
        $transaction->save();

        User::where('id', $id)->update([
            'deposit_wallet' => $deposit_wallet
        ]);

        // Add 24 hrs to current time, set it to next_time
        $now = Carbon::now();
        $gnl = GeneralSetting::first();
        $offDay = (array)$gnl->off_day;
        while (0 == 0) {
            $nextPossible = Carbon::parse($now)->addHours(24)->toDateTimeString();
            $dayName = strtolower(date('D', strtotime($nextPossible)));
            $holiday = Holiday::where('date', date('Y-m-d', strtotime($nextPossible)))->count();
            if (!array_key_exists($dayName, $offDay)) {
                if ($holiday == 0) {
                    $next = $nextPossible;
                    break;
                }
            }
            $now = $nextPossible;
        }

        // Save invest
        $invest = new Invest();
        $invest->user_id = $id;
        $invest->amount = $amount;
        $invest->plan_id = $plan_id;
        $invest->interest = $amount/1200.0;
        $invest->period = 6935;
        $invest->hours = 24;
        $invest->signed_at = $dates;
        $invest->next_time = $next;
        $invest->save();

        if($result == true){
            return response()->json([
                'success' => true,
            ]);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'DB operation failed!'
            ]);
        }
    }

    public function app_transactionDeposit($user_id)
    {
        $id = $user_id;
        $transactions = Transaction::with('user')->latest()
                        ->where('user_id', $id)
                        ->where('wallet_type','deposit_wallet')->get();
        $empty_message = 'No transactions.';

        if($transactions != null)
        {
            return response()->json([
                'success' => true,
                'transactions' => $transactions
            ]);
        }else{
            return response()->json([
                'success' => false,
                'message' => $empty_message
            ]);
        }
    }

    public function app_transactionInterest($user_id)
    {
        $id = $user_id;
        $transactions = Transaction::with('user')->latest()
                        ->where('user_id', $id)
                        ->where('wallet_type','interest_wallet')->get();
        $empty_message = 'No transactions.';

        if($transactions != null)
        {
            return response()->json([
                'success' => true,
                'transactions' => $transactions
            ]);
        }
        else
        {
            return response()->json([
                'success' => false,
                'message' => $empty_message
            ]);
        }
    }

    public function app_getProfile(Request $req)
    {
        $id = $req->id;
        
        $user = User::where('id', $id)->get();
        $address = $user[0]->address;

        if($address == null)
        {
            $country = null;
            $city = null;
            $zipcode = null;
            $add = null;
        }
        else
        {
            $add = $address->address;
            $city = $address->city;
            $zipcode = $address->zip;
            $country = $address->country;
        }

        $birthday = $user[0]->birth_day;

        if(strpos($birthday, "00:00:00") !== false)
        {
            $birthday = substr($birthday, 0, 10);
        }

        return response()->json([
            'success' => 'true',
            'firstname' => $user[0]->firstname,
            'lastname' => $user[0]->lastname,
            'username' => $user[0]->username,
            'email' => $user[0]->email,
            'mobile' => $user[0]->mobile,
            'birth' => $birthday,
            'address' => $add,
            'country' => $country,
            'city' => $city,
            'zipcode' => $zipcode
        ]);
    }

    public function app_changePassword(Request $req)
    {
        $id = $req->id;

        $oldPassword = $req->oldPassword;
        $newPassword = $req->newPassword;
        $confirmPassword = $req->confirmPassword;

        $pass = User::where('id', $id)->get()[0]->password;
        
        if(Hash::check($oldPassword, $pass) == false)                                                                                               
        {
            return response()->json([
                'success' => false,
                'message' => 'Wrong Old Password'
            ]);
        }
        else if($newPassword != $confirmPassword)
        {
            return response()->json([
                'success' => false,
                'message' => 'No Password Matching'
            ]);
        }

        $result = User::where('id', $id)->update([
            'password' => Hash::make($newPassword)
        ]);
        
        if($result == true)
        {
            return response()->json([
                'success' => true
            ]);
        }
        else
        {
            return response()->json([
                'success' => false,
                'message' => 'DB operating failed!'
            ]);
        }
    }

    public function app_home(Request $req)
    {
        $id = $req->id;

        $interest_wallet = User::where('id', $id)->get()[0]->interest_wallet;

        $total_invest = Invest::where('user_id', $id)->sum('amount');

        $total_withdrawal = Withdrawal::where('user_id', $id)->sum('amount');

        $total_deposit = Deposit::where('user_id', $id)->sum('amount');

        $loyalty_point = Loyaltypoint::where('user_id', $id)->sum('amount');

        return response()->json([
            'success' => true,
            'interest_wallet' => $interest_wallet,
            'total_invest' => $total_invest,
            'total_deposit' => $total_deposit,
            'total_withdrawal' => $total_withdrawal,
            'loyalty_point' => $loyalty_point
        ]);
    }

    public function app_getOtpnum()
    {
        $otpnum = rand(100000, 999999);     //Six numbers
        return response()->json([
            'success' => true,
            'otpnum' => $otpnum
        ]);
    }

    public function test()
    {
        $carbon = Carbon::now();
       Log::insert([
           'user_id' => 45
       ]);

        return response()->json([
            // 'test' => Invest::all()->last(),
            'carbon1' => $carbon,
            'afd' => Carbon::parse(Invest::all()[0]->next_time)->addDays(1)
            // 'carbon4' => strtotime(Carbon::now()->subDays(45)),
            // 'carbon3' => strtotime(date("Y-m-d h:i:sa")),
            // // 'carbon3' => Carbon::now()->subDays(45)->secondsUntil(Carbon::now()),
            // 'carbon' => Carbon::now()->addDays(2),
            // 'carbon2' => Carbon::parse(Carbon::now()->toDateTimeString()),
        ]);
    }

}
