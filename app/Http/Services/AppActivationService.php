<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AppActivationService{

    public static function checkValidMacAddress( $app_platform , $reqData){
        $apikey = env('ACTIVATION_API_KEY');
        $app_url = env($app_platform.'_URL'). '/api/validate-mac-address';
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'token' => $apikey
            ])->post($app_url , $reqData);
            Log::info('Transmitting MAC Validation Request from' . $app_platform . json_encode($reqData));
            Log::info('Receiving MAC Validation Response' . $response);
            if ($response->status() == 200 && $response->mac_valid == 1){
                return $response;
            } else {
                return 0;
            }
        } catch (\Exception $exception) {
            return 0;
        }
    }
    public static function saveDeviceActivation( $app_platform , $device){
        $apikey = env('ACTIVATION_API_KEY');
        $app_url = env($app_platform.'_URL') . '/api/save-device-activation';
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'token' => $apikey
            ])->post($app_url , $device);
            Log::info('Transmitting Device Activation Request from' . $app_platform . json_encode($device));
            Log::info('Receiving Device Activation Response' . $response);
            if ($response->status() == 200 && $response->mac_activated == 1){
                return $response;
            } else {
                return 0;
            }
        } catch (\Exception $exception) {
            return 0;
        }
    }
}
