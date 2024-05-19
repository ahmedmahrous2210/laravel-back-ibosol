<?php

namespace App;
use Jenssegers\Mongodb\Eloquent\Model;
class FamilyPlayerPlaylist extends Model{
   
    protected $connection = 'FamilyPlayerAtlas';

    protected $table = 'playlists';
    
    protected $collection = 'playlists';

    public $primaryKey = '_id';

    public $timestamps = false;
    
}