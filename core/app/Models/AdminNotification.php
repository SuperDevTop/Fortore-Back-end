<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property array|mixed|string|string[] click_url
 * @property mixed|string title
 * @property mixed user_id
 */
class AdminNotification extends Model
{
    use HasFactory;

    public function user()
    {
    	return $this->belongsTo(User::class);
    }
}
