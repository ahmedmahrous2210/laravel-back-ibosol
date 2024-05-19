<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IBOUser extends Model{

    
    public $table = 'xtream_users_details';
    
    protected $fillable = [
        'name', 'email',
    ];
}