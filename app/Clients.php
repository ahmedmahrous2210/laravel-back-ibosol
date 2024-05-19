<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Clients extends Model{

    public $table='client_mapping';

    public function activatedBox(){
        return $this->hasMany('App\ClientActiTranLogs', 'reseller_id', 'client_id');
    }
}