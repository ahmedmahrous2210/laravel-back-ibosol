<?php

namespace App;
use Jenssegers\Mongodb\Eloquent\Model;
class AllPlayerPlaylist extends Model{
   
    protected $connection = 'AllPlayerAtlas';

    protected $table = 'playlists';
    
    protected $collection = 'playlists';

    public $primaryKey = '_id';

    public $timestamps = false;
    
}