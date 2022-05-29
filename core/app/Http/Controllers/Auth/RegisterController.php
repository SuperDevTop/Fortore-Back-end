<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;
use App\Models\Transaction;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
        $this->middleware('regStatus')->except('registrationNotAllowed');
        $this->userRepository = new UserRepository();
        $this->activeTemplate = activeTemplate();
    }

    public function referralRegister($reference)
    {
        $page_title = "Sign Up";
        session()->put('reference', $reference);
        $info = json_decode(json_encode(getIpInfo()), true);
        $mobile_code = @implode(',', $info['code']);
        $countries = json_decode(file_get_contents(resource_path('views/partials/country.json')));
        return view($this->activeTemplate . 'user.auth.register', compact('reference', 'page_title', 'mobile_code', 'countries'));
    }

    public function showRegistrationForm()
    {
        $page_title = "Sign Up";
        $info = json_decode(json_encode(getIpInfo()), true);
        $mobile_code = @implode(',', $info['code']);
        $countries = json_decode(file_get_contents(resource_path('views/partials/country.json')));
        return view($this->activeTemplate . 'user.auth.register', compact('page_title', 'mobile_code', 'countries'));
    }

    public function register(Request $request)
    {
        $this->validator($request->all())->validate();

        $exist = User::where('mobile', $request->mobile_code . $request->mobile)->first();
        if ($exist) {
            $notify[] = ['error', 'The mobile number already exists'];
            return back()->withNotify($notify)->withInput();
        }

        if (isset($request->captcha)) {
            if (!captchaVerify($request->captcha, $request->captcha_secret)) {
                $notify[] = ['error', "Invalid Captcha"];
                return back()->withNotify($notify)->withInput();
            }
        }
        $user = $this->userRepository->createUser($request->all());

        $this->guard()->login($user);

        return $this->registered($request, $user)
            ?: redirect($this->redirectPath());
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {

        $countryData = (array)json_decode(file_get_contents(resource_path('views/partials/country.json')));
        $countryCodes = implode(',', array_keys($countryData));
        $mobileCodes = implode(',', array_column($countryData, 'dial_code'));
        $countries = implode(',', array_column($countryData, 'country'));

        $validate = Validator::make($data, [
            'firstname' => 'sometimes|required|string|max:60',
            'lastname' => 'sometimes|required|string|max:60',
            'city' => 'string|min:2|required',
            'birth_day' => 'date|required|before:today',
            'email' => 'required|string|email|max:160|unique:users',
            'mobile' => 'required|string|max:30|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'username' => 'required|alpha_num|unique:users|min:6',
            'captcha' => 'sometimes|required',
            'mobile_code' => 'required|in:' . $mobileCodes,
            'country' => 'required|in:' . $countries,
            'terms' => 'required'
        ]);

        return $validate;
    }

    protected function registered(Request $request, $user)
    {
        $gnl = GeneralSetting::first();

        if ($gnl->signup_bonus_control == 1) {
            $userWallet = $user;
            $userWallet->deposit_wallet += getAmount($gnl->signup_bonus_amount);
            $userWallet->save();

            $transaction = new Transaction();
            $transaction->user_id = $user->id;
            $transaction->amount = getAmount($gnl->signup_bonus_amount);
            $transaction->charge = 0;
            $transaction->post_balance = getAmount($userWallet->deposit_wallet);
            $transaction->trx_type = '+';
            $transaction->trx = getTrx();
            $transaction->wallet_type = 'deposit_wallet';
            $transaction->details = 'You have got Sign Up Bonus';
            $transaction->save();
        }
        return redirect()->route('user.home');
    }

}
