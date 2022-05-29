<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property mixed|string trx
 * @property mixed capital_status
 * @property int|mixed status
 * @property mixed|string next_time
 * @property mixed hours
 * @property mixed time_name
 * @property mixed|string period
 * @property float|int|mixed interest
 * @property mixed amount
 * @property mixed plan_id
 * @property mixed user_id
 * @property mixed plan
 * @property mixed wallet_type
 * @property mixed user
 * @property mixed cur_sym
 * @property mixed cur_text
 * @property mixed signed
 * @property mixed contract_path
 * @property mixed return_rec_time
 * @property mixed signed_at
 * @property mixed last_withdraw_at
 * @property mixed withdraw_count
 * @method static eligibleToRevenue(Carbon $now)
 */
class Invest extends Model
{
    protected $guarded = ['id'];
    use SoftDeletes;

    public function plan(): HasOne
    {
        return $this->hasOne(Plan::class, 'id', 'plan_id')->withDefault();
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id')->withDefault();
    }

    public function contractUrl(): string
    {
        return Storage::url($this->contract_path);
    }
    public function contractUrl2(): string
    {
        return Storage::url($this->contract_path2);
    }

    public function hasSignedByUser(): bool
    {
        return $this->signed_at != null && $this->status == 1;
    }

    public function scopeEligibleToRevenue(Builder $builder, Carbon $date): Builder
    {
        return $builder->where([
            ['next_time', '<=', $date],
            ['status', 1]
        ]);
    }

    public function nextEligibleRevenueDate(GeneralSetting $setting): Carbon
    {
        $nextRevenueDate = Carbon::now();
        while (true) {
            $nextRevenueDate = Carbon::parse($nextRevenueDate)->addHours($this->hours);
            if (!$setting->isHoliday($nextRevenueDate)) return $nextRevenueDate;
        }
    }

    public function isInvestmentPeriodFinished(): bool
    {
        return ($this->return_rec_time + 1) >= $this->period && !$this->isForever();
    }

    public function isForever(): bool
    {
        return $this->period == '-1';
    }

    public function capitalAmountRefundable(): bool
    {
        return $this->capital_status === 1;
    }

    public function contractDayName(): string
    {

        $day = $this->contractDate()->format("D");
        return collect(array(
            "Sat" => "السبت",
            "Sun" => "الأحد",
            "Mon" => "الإثنين",
            "Tue" => "الثلاثاء",
            "Wed" => "الأربعاء",
            "Thu" => "الخميس",
            "Fri" => "الجمعة"

        ))->get($day);
    }

    public function contractDate(): Carbon
    {
        if ($this->signed_at == null) {
            return Carbon::now();
        }
        return Carbon::parse($this->signed_at);
    }

}
