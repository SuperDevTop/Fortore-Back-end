<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property array|mixed|string|string[] message
 * @property mixed subject
 * @property mixed to
 * @property mixed|string from
 * @property mixed mail_sender
 * @property mixed user_id
 */
class EmailLog extends Model
{
    use HasFactory;
}
