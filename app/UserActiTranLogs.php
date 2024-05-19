<?php

namespace App;
use Illuminate\Database\Eloquent\Model;

class UserActiTranLogs extends Model
{
    protected $table = 'user_activation_trans_logs';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'reseller_id', 'user_id', 'module', 'credit_point', 'mac_address'
    ];

    public function resellerDetail(){
        return $this->belongsTo('App\IBOReseller', 'reseller_id');
    }
}