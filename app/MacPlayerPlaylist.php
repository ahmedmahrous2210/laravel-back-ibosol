<?php

namespace App;
use Jenssegers\Mongodb\Eloquent\Model;
class MacPlayerPlaylist extends Model{
   
    protected $connection = 'macplayeratlas';

    protected $table = 'playlists';
    
    protected $collection = 'playlists';

    public $primaryKey = '_id';

    public $timestamps = false;
    
}