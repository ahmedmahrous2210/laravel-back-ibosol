<?php

namespace App;
use Jenssegers\Mongodb\Eloquent\Model;
class IboProTvPlaylist extends Model{
   
   
    
    protected $connection = 'iboProTv';

    protected $table = 'playlists';
    
    protected $collection = 'playlists';

    public $timestamps = false;
    
    public $fillable = ["name", "url"];
    
}