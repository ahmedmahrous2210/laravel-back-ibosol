<?php

namespace App;
use Jenssegers\Mongodb\Eloquent\Model;
class IBOSSPlayerPlaylist extends Model{
   
    protected $connection = 'IBOSSPlayer';

    protected $table = 'playlists';
    
    protected $collection = 'playlists';

    public $primaryKey = '_id';

    public $timestamps = false;
    
}