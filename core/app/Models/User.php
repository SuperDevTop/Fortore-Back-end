<?php

namespace App\Models;


use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property mixed id
 * @property int|mixed tv
 * @property int|mixed ts
 * @property int|mixed sv
 * @property int|mixed ev
 * @property int|mixed status
 * @property array|mixed address
 * @property mixed|string mobile
 * @property mixed birth_day
 * @property mixed|null ref_by
 * @property mixed|string username
 * @property mixed password
 * @property mixed|string email
 * @property mixed|null lastname
 * @property mixed|null firstname
 */
class User extends Authenticatable implements HasMedia
{
    use Notifiable, HasApiTokens, InteractsWithMedia;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'pin_code'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'address' => 'object',
        'ver_code_send_at' => 'datetime'
    ];

    public static function findUsingPinCode($pinCode)
    {
        return self::where('pin_code', $pinCode)->firstOrFail();
    }

    public function lastLogin(): HasOne
    {
        return $this->hasOne(UserLogin::class)->latest();
    }

    public function login_logs(): HasMany
    {
        return $this->hasMany(UserLogin::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class)->orderBy('id', 'desc');
    }

    public function invests(): HasMany
    {
        return $this->hasMany(Invest::class)->orderBy('id', 'desc');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(CommissionLog::class, 'to_id')->orderBy('id', 'desc');
    }


    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class)->where('status', '!=', 0);
    }

    public function getFullnameAttribute(): string
    {
        return $this->firstname . ' ' . $this->lastname;
    }

    public function scopeActive()
    {
        return $this->where('status', 1);
    }

    public function scopeBanned()
    {
        return $this->where('status', 0);
    }

    public function scopeEmailUnverified()
    {
        return $this->where('ev', 0);
    }

    public function scopeSmsUnverified()
    {
        return $this->where('sv', 0);
    }

    public function scopeEmailVerified()
    {
        return $this->where('ev', 1);
    }

    public function scopeSmsVerified()
    {
        return $this->where('sv', 1);
    }

    public function loyaltyPointsBalance(): float
    {
        $this->load('loyaltyPoints');
        return (float)$this->loyaltyPoints()->where('type', 'add')->sum('amount') - (float)$this->loyaltyPoints()->where('type', 'sub')->sum('amount');
    }

    public function loyaltyPoints(): HasMany
    {
        return $this->hasMany(LoyaltyPoint::class, 'user_id');
    }

    public function fullname()
    {
        return "$this->firstname $this->lastname";
    }

    public function getWithdrawalsWalletAttribute()
    {
        $withdrawals = $this->withdrawals()->where('type', TransactionType::add()->value)->where('status', 1)->sum('amount');
        $withdrawalsRefund = $this->withdrawals()->where('type', TransactionType::sub()->value)->where('status', 1)->sum('amount');
        return $withdrawals - $withdrawalsRefund;
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class)->where('status', '!=', 0);
    }
}
