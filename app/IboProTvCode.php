<?php

namespace App;
use Jenssegers\Mongodb\Eloquent\Model;
class IboProTvCode extends Model{
   
    protected $connection = 'iboProTv';

    protected $table = 'codes';
    
    protected $collection = 'codes';

    public $timestamps = false;

    /*public static function SCHEMAS(){
        return [
            'user_id' => ['type' => 'string'],
            'code'   => ['type' => 'string'],
            'credit_count'  => ['type' => 'string'],
            'device_id'    => ['type' => 'string',   'default' => false],
            'notes'    => ['type' => 'string'],
            'created_time'    => ['type' => 'timestamp'],
            'expire_date'       => ['type' => 'string'],
            'playlists'   => ['type' => IboProTvPlaylist::class,  'default' => []],
            'disabled' => ['type' => 'int', 'default' => 0],
            'is_test' => ['type' => 'int', 'default' => 0]

            
        ];
    }
    
    public function playlistUrl(){
        return $this->hasMany('App\IboProTvCode', 'device_id');
    }
    */
}