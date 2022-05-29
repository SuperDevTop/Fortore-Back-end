<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed|string country
 * @property mixed os
 * @property mixed browser
 * @property mixed|string city
 * @property mixed|string country_code
 * @property mixed|string location
 * @property mixed|string latitude
 * @property mixed|string longitude
 * @property mixed|string|null user_ip
 * @property mixed user_id
 */
class UserLogin extends Model
{
    protected $guarded = ['id'];


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
