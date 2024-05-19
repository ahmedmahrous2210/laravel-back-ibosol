<?php

namespace App;
use Illuminate\Database\Eloquent\Model;

class ResellerApplications extends Model
{
    protected $table = 'reseller_applications';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'app_logo', 'app_name', 'app_phone', 'app_email',
        'app_location', 'status', 'app_tag_line', 'app_description',
        'app_place_location'
    ];

   
}