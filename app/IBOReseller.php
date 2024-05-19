<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IBOReseller extends Model{

    
    public $table = 'ibocdn_resellers';
    
    protected $fillable = [
        'name', 'email', 'password', 'status', 'credit_point', 'created_by'
    ];
}