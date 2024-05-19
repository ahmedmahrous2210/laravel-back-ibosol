<?php

namespace App;
use Jenssegers\Mongodb\Eloquent\Model;
class IBOSOLPlaylist extends Model{
   
    protected $connection = 'IBOSOL';

    protected $table = 'playlists';
    
    protected $collection = 'playlists';

    public $primaryKey = '_id';

    public $timestamps = false;
    
}