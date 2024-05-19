<?php

namespace App;
use Illuminate\Database\Eloquent\Model;

class Tickets extends Model{

    protected $connection = 'mysql';

    protected $table = 'tickets';
    
    public function resellers(){
        return $this->belongsTo('App\IBOReseller', 'created_by');
    }
    
    public function admin(){
        return $this->belongsTo('App\IBOReseller', 'attended_by');
    }

}