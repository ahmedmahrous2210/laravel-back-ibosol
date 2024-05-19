<?php

namespace App;
use Illuminate\Database\Eloquent\Model;

class MASAPlaylistUrl extends Model{

    protected $connection = 'mysql2';

    protected $table = 'play_list_urls';
}