<?php

namespace App;
//use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model;
class BOBProTvPlaylist extends Model{
   
    protected $connection = 'bobProTvAtlas';

    protected $table = 'playlists';

    // protected $fillable = [
    //     'mac_address',
    //     'app_type',
    //     'device_key',
    //     'expire_date',
    //     'activated_by',
    //     'is_trial',
    // ];

    public $timestamps = false;
    
}