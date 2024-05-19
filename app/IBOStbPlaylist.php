<?php

namespace App;
use Jenssegers\Mongodb\Eloquent\Model;
class IBOStbPlaylist extends Model{
   
    protected $connection = 'IBOStb';

    protected $table = 'playlists';
    
    protected $collection = 'playlists';

    public $primaryKey = '_id';

    public $timestamps = false;
    
}