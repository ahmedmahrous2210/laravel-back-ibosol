<?php

namespace App;
//use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model;
class IBONotification extends Model{
   
    protected $connection = 'iboappatlas';

    protected $table = 'notifications';
    
    protected $collection = 'notifications';

}