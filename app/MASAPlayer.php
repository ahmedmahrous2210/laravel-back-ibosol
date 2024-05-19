<?php

namespace App;
use Illuminate\Database\Eloquent\Model;

class MASAPlayer extends Model{

    protected $connection = 'mysql2';

    protected $table = 'play_lists';
    
    public function playlistUrl(){
        return $this->hasMany('App\MASAPlaylistUrl', 'playlist_id');
    }
}