<?php

namespace App;
use Jenssegers\Mongodb\Eloquent\Model;
class DuplexPlaylist extends Model{
   
    protected $connection = 'duplexPlayer';

    protected $table = 'playlists';
    
    protected $collection = 'playlists';

    public $primaryKey = '_id';

    public $timestamps = false;
    
}