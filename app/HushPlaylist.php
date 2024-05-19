<?php

namespace App;
use Jenssegers\Mongodb\Eloquent\Model;
class HushPlaylist extends Model{
   
    protected $connection = 'HushPlayAtlas';

    protected $table = 'playlists';
    
    protected $collection = 'playlists';

    public $primaryKey = '_id';

    public $timestamps = false;
    
}