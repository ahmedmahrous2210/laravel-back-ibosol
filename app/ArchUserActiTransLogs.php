<?php

namespace App;
use Illuminate\Database\Eloquent\Model;

class ArchUserActiTransLogs extends Model
{
    protected $table = 'arch_user_activation_trans_logs';
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