<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IBOSOLClient extends Model{

    
    public $table = 'client_mapping';
    
    protected $fillable = [
        'channel_name', 'client_id', 'email_id', 'secret_key', 'status', 'credit_point'
    ];
}