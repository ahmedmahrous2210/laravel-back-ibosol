<?php

namespace App;
//use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model;
class IBOAppPlaylist extends Model{
   
    protected $connection = 'iboappatlas';

    protected $table = 'playlists';
    
    protected $collection = 'playlists';

    //public $primaryKey = '_id';

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