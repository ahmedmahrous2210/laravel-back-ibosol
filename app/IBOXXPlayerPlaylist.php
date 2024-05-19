<?php

namespace App;
use Jenssegers\Mongodb\Eloquent\Model;
class IBOXXPlayerPlaylist extends Model{
   
    protected $connection = 'IBOXXPlayer';

    protected $table = 'playlists';
    
    protected $collection = 'playlists';

    public $primaryKey = '_id';

    public $timestamps = false;
    
}