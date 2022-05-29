<?php

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int|mixed|string post_balance
 * @property mixed|string details
 * @property mixed wallet_type
 * @property mixed|string trx
 * @property int|mixed|string trx_type
 * @property int|mixed charge
 * @property int|mixed|string amount
 * @property mixed user_id
 */
class Transaction extends Model
{
    protected $table = "transactions";

    protected  $guarded = ['id'];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
