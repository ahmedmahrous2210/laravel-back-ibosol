<?php

namespace App;
use Jenssegers\Mongodb\Eloquent\Model;
class FlixNetPlaylist extends Model{
   
    protected $connection = 'flixNetPlayer';

    protected $table = 'playlists';
    
    protected $collection = 'playlists';

    public $primaryKey = '_id';

    public $timestamps = false;
    
}