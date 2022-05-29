<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed id
 * @property mixed times
 * @property mixed name
 * @property mixed capital_back_status
 * @property mixed fixed_amount
 * @property mixed maximum
 * @property mixed minimum
 * @property mixed repeat_time
 * @property mixed lifetime_status
 * @property mixed interest
 * @property mixed interest_status
 */
class Plan extends Model
{
    protected $guarded = ['id'];

    protected $table = "plans";
}
