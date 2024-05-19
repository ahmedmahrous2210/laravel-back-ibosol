<?php

namespace App;
use Illuminate\Database\Eloquent\Model;

class ClientActiTranLogs extends Model
{
    protected $table = 'client_activation_trans_logs';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'reseller_id', 'user_id', 'module', 'channel_id', 'credit_point', 'mac_address'
    ];

   
}