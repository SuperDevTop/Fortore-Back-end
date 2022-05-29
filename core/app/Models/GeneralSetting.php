<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property mixed cur_sym
 * @property mixed cur_text
 * @property mixed invest_commission
 * @property mixed off_day
 * @property mixed invest_return_commission
 */
class GeneralSetting extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'mail_config' => 'object',
        'sms_config' => 'object',
        'off_day' => 'object',
    ];

    public static function getActiveSetting()
    {
        return self::first();
    }

    public function scopeSitename($query, $page_title)
    {
        $page_title = empty($page_title) ? '' : ' - ' . $page_title;
        return $this->sitename . $page_title;
    }

    public function isHoliday(Carbon $carbon): bool
    {
        $day = strtolower($carbon->format("D"));
        $offDay = (array)$this->off_day;
        return array_key_exists($day, $offDay) || Holiday::where('date', $carbon->format('Y-m-d'))->count() != 0;
    }

    public function updateCronJobDate()
    {
        $this->update([
            'last_cron' => Carbon::now()
        ]);

    }

    public function autoWithdrawMethod()
    {
        return $this->hasOne(WithdrawMethod::class, 'id','auto_withdraw_method_id');
    }
}
