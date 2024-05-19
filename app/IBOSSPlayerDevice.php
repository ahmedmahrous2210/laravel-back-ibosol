<?php

namespace App;
use Jenssegers\Mongodb\Eloquent\Model;
class IBOSSPlayerDevice extends Model{
   
    protected $connection = 'IBOSSPlayer';

    protected $table = 'devices';
    
    protected $collection = 'devices';

    public $timestamps = false;

    public function playlistUrl(){
        return $this->hasMany('App\IBOSSPlayerPlaylist', 'device_id');
    }

    /**
     * function is used for the updating the status to disable for all mac provide in $macAddress
     */
    public static function updateToDisable(array $macAddress){
        return self::whereIn('mac_address', $macAddress)
        ->update(['is_trial' => 0, 'expire_date'=> date('Y-m-d', strtotime(date('Y-m-d') . '-1 day'))]);
    }
    
}