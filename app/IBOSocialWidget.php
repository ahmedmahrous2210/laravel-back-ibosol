<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IBOSocialWidget extends Model{

    
    public $table = 'ibocdn_social_widget';
    
    protected $fillable = [
        'whatsapp_number', 'teligram_number', 'created_by'
    ];
}