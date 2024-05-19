<?php

namespace App\Http\Helpers;
use App\Http\Services\BoxActivationService;
class MasaHelper{


    public static function callMasaService($reqData){

        $response = BoxActivationService::callMasaService($reqData);
        $response = json_decode($response, true);
        return $response;
    }

    public static function getClientDetail($reqData){
        $response = BoxActivationService::callClientDetailApi($reqData);
        $response = json_decode($response, true);
        return $response;
    }
}
