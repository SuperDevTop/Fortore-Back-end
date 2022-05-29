<?php

namespace App\Repositories;

use App\Models\GeneralSetting;
use App\Models\User;
use App\Models\UserLogin;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class UserRepository extends BaseRepository
{
    /**
     * @var AdminNotificationRepository
     */
    private $adminNotificationRepo;

    public function __construct()
    {
        parent::__construct(new User());
        $this->adminNotificationRepo = new AdminNotificationRepository();

    }

    public function createUser(array $data): User
    {
        $gnl = GeneralSetting::first();


        $referBy = session()->get('reference');
        if ($referBy != null) {
            $referUser = User::where('username', $referBy)->first();
        } else {
            $referUser = null;
        }


        $user = new User();
        $user->firstname = $data['firstname'] ?? null;
        $user->lastname = $data['lastname'] ?? null;
        $user->email = strtolower(trim($data['email']));
        $user->password = Hash::make($data['password']);
        $user->username = trim($data['username']);
        $user->ref_by = ($referUser != null) ? $referUser->id : null;
        $user->birth_day = Carbon::parse($data['birth_day']);
        $user->mobile = $data['mobile_code'] . $data['mobile'];
        $user->address = [
            'address' => '',
            'state' => '',
            'zip' => '',
            'country' => $data['country'] ?? null,
            'city' => $data['city'] ?? null,
        ];
        $user->status = 1;
        $user->ev = $gnl->ev ? 0 : 1;
        $user->sv = $gnl->sv ? 0 : 1;
        $user->ts = 0;
        $user->tv = 1;
        $user->save();
        $info = json_decode(json_encode(getIpInfo()), true);
        $userLogin = new UserLogin();
        $userLogin->user_id = $user->id;
        $userLogin->user_ip = request()->ip();
        $userLogin->longitude = @implode(',', $info['long']);
        $userLogin->latitude = @implode(',', $info['lat']);
        $userLogin->location = @implode(',', $info['city']) . (" - " . @implode(',', $info['area']) . "- ") . @implode(',', $info['country']) . (" - " . @implode(',', $info['code']) . " ");
        $userLogin->country_code = @implode(',', $info['code']);
        $userLogin->city = @implode(',', $info['city']);
        $userLogin->browser = @$info['browser'];
        $userLogin->os = @$info['os_platform'];
        $userLogin->country = @implode(',', $info['country']);
        $userLogin->save();
        $this->adminNotificationRepo->persistNotification([
            'title' => 'New member registered',
            'user_id' => $user->id,
            "click_url" => urlPath('admin.users.detail', $user->id)
        ]);
        event(new Registered($user));
        return $user;
    }
}
