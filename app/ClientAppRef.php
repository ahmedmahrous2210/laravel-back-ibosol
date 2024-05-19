<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClientAppRef extends Model{

    public $table='client_app_ref';

    protected $fillable = [
        'app_ref_id',
        'client_id',
        'channel_id',
        'req_json',
        'request_id'
    ];

    public static function genAppRefId($reqData){
        $pk = self::select('id')->orderBy('created_at', 'desc')->first();
        $model = new ClientAppRef;
        $model->app_ref_id = date('Ymd').$pk->id;
        $model->client_id = $reqData['clientId'] ?? "";
        $model->channel_id = $reqData['channelId'] ?? "";
        $model->request_id = $reqData['request_id'] ?? "";
        $model->req_json = json_encode($reqData);
        if($model->save()){
            return $model->app_ref_id;
        }
        return null;
    }
}