<?php

namespace App;
//use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model;
class BOBPlayerPlaylist extends Model{
   
    protected $connection = 'bobPlayer';

    protected $table = 'playlists';
    protected $collection = 'playlists';
    
    protected $primaryKey = '_id';

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