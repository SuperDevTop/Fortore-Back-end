<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed|string trx
 * @property mixed percent
 * @property mixed|string type
 * @property mixed|string title
 * @property mixed trx_amo
 * @property int|mixed|string main_amo
 * @property int|mixed|string commission_amount
 * @property int|mixed level
 * @property mixed from_id
 * @property mixed to_id
 */
class CommissionLog extends Model
{
    protected $guarded = ['id'];

    protected $table = "commission_logs";

    public function user(){
        return $this->belongsTo('App\Models\User','to_id','id');
    }
    public function bywho(){
        return $this->belongsTo('App\Models\User','from_id','id');
    }
}
