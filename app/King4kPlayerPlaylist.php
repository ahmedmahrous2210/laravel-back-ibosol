<?php

namespace App;
use Jenssegers\Mongodb\Eloquent\Model;
class King4kPlayerPlaylist extends Model{
   
    protected $connection = 'King4kPlayer';

    protected $table = 'playlists';
    
    protected $collection = 'playlists';

    public $primaryKey = '_id';

    public $timestamps = false;
    
}