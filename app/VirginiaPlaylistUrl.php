<?php

namespace App;
use Jenssegers\Mongodb\Eloquent\Model;

class VirginiaPlaylistUrl extends Model{
   
    protected $connection = 'virginiamongodb';

    protected $collection = 'playlists';
    protected $table = 'playlists';

    public $primaryKey = '_id';

    protected $fillable = [
        'is_protected',
        'url',
        'playlist_name',
        'pin',
        'device_id'
    ];

    public $timestamps = false;
}