<?php

namespace App\Http\Services;

class BoxActivationService{

    private static $clientId = 'MASA001';
    private static $secret = 'TUFTQTIwMjJDTElFTlROTzAwMQ==';

    public static function callMasaService($reqData){
        if(env('APP_ENV') === 'PROD'){
            $url = 'https://api.masacdn.com/v2/box-activation';
        }else{
            $url = 'http://localhost:8083/box-activation';
        }
        $ch = curl_init($url); 
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($reqData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'clientId:'.self::$clientId, 'secretKey: '.self::$secret]);
        $result = curl_exec($ch);
        curl_close($ch);
        return ($result);
    }

    public static function callClientDetailApi($reqData){
        if(env('APP_ENV') === 'PROD'){
            $url = 'https://api.masacdn.com/v2/client-detail';
        }else{
            $url = 'http://localhost:8083/client-detail';
        }
        $ch = curl_init($url); 
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($reqData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'clientId:'.self::$clientId, 'secretKey: '.self::$secret]);
        $result = curl_exec($ch);
        curl_close($ch);
        return ($result);
    }

    public static function callMacSwitchApi($reqData){
        if(env('APP_ENV') === 'PROD'){
            $url = 'https://api.masacdn.com/v2/edit-mac';
        }else{
            $url = 'http://localhost:8083/edit-mac';
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($reqData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'clientId:'.self::$clientId, 'secretKey: '.self::$secret]);
        $result = curl_exec($ch);
        curl_close($ch);
        return ($result);
    }

    public static function bayIptvCheckMac($reqData){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://cms.bayip.tv/isForActivation");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $reqData);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
        
    }

    public static function bayIptvActivate($reqData){
        //return $reqData;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://cms.bayip.tv/externe/activation");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $reqData);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

}