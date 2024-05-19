<?php

namespace App\Http\Helpers;

use App\Http\Services\BoxActivationService;
class BAYIPTVHelper{


    public static function checkMac($reqData){
        $reqData = ["mac" =>  $reqData['requestData']['macAddress']];
        $response = BoxActivationService::bayIptvCheckMac($reqData);
        $response = json_decode($response, true);
        return $response;
    }

    public static function activateUser($reqData){
        $response = BoxActivationService::bayIptvActivate($reqData);
        $response = json_decode($response, true);
        return $response;
    }
}
