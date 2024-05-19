<?php

namespace App\Http\Controllers;
//use Validator;

use App\Http\Helpers\BayIPTV;
use App\Http\Helpers\BAYIPTVHelper;
use App\Http\Helpers\MasaHelper;
use App\IBOAppDevice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;
use App\IBOReseller;
use App\MASAPlayer;
use App\MASAPlaylistUrl;
use App\VirginiaDevice;
use App\VirginiaPlaylistUrl;
use App\IBOAppPlayer;
use App\IBOAppPlaylist;
use App\ABEPlayerDevice;
use App\AllPlayerDevice;
use App\BOBPlayerDevice;
use App\ResellerCreditPointTranLogs;
use App\UserActiTranLogs;
use MongoDB\Client as Mongo;

use App\ClientAppRef;
use App\IBOSOLClient;

class BoxActivationAPIController extends Controller
{

    /**
     * 
     */
    
    const ALGO = 'aes-256-cbc';
    const TOKEN_KEY = 'SASASASASASASASASASASASASASADADA';
    const PADDING = 'aes-256-cbc';//Assuming 128-bit key size
    const IV = '1212ASASASASASAS';
    const IV_SIZE = 16;
    
    //$iv = "IBOSOLDOTCOM2024";
      //  $encKey =  "DFVEVDG234!@51SD";
    
    
    public function searchUser(Request $request)
    {
        $appRefId = null;
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "400"
            ];
            if ($request->isJson()) {
                if ($request->hasHeader('clientId') && $request->hasHeader('secretKey')) {
                    $appRefId = ClientAppRef::genAppRefId($reqData);

                    $validatedData = Validator::make($reqData, [
                        'module' => ['required', Rule::in(['MASA', 'VIRGINIA', 'IBOAPP', 'BAYIPTV'])],
                        'channelId' => 'required|min:5|max:100',
                        'requestId' => 'required',
                        'clientId' => 'required',
                        'requestData.macAddress' => 'required|min:3|max:50',
                    ]);

                    if ($validatedData->fails()) {
                        $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                        $returnData["statusCode"] = "C10422";
                        $returnData["appRefId"] = $appRefId;
                        $returnData["httpCode"] = "422";
                        return new JsonResponse($returnData, 422);
                    }
                    //validate client and secret
                    $validClient = IBOSOLClient::where('client_id', $request->header('clientId'))->where('secret_key', $request->header('secretKey'))->first();
                    if (empty($validClient)) {
                        $returnData["msg"] = 'Invalid client details / Client Detail not found!';
                        $returnData["statusCode"] = "C10422";
                        $returnData["httpCode"] = "422";
                        $returnData["appRefId"] = $appRefId;
                        return new JsonResponse($returnData, 422);
                    }
                    $reqData['requestData']['macAddress'] = strtolower($reqData['requestData']['macAddress']);
                    if ($reqData['module'] == 'VIRGINIA') {
                        $iboMasaUser =  VirginiaDevice::where('mac_address', $reqData['requestData']['macAddress'])->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                        ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))->first();
                    } else if ($reqData['module'] == 'IBOAPP') {
                        $iboMasaUser =  IBOAppDevice::where('mac_address', $reqData['requestData']['macAddress'])
                        ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                        ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))->first();
                    }

                    if (!empty($iboMasaUser)) {
                        //$newIboMasa = [];
                        // foreach ($iboMasaUser as $mKey => $mUser) {
                        //     $newIboMasa[] = $this->__mapCustomKeyPair($mUser, $reqData['module']);
                        // }
                        //$iboMasaUser = $newIboMasa;
                        $iboMasaUser = $this->__mapCustomKeyPair($iboMasaUser, $reqData['module']);
                        $returnData["status"] = true;
                        $returnData["msg"] = "Mac detail found successfully!";
                        $returnData["statusCode"] = "000000";
                        $returnData["appRefId"] = $appRefId;
                        $returnData["data"] = [$reqData['module'] => $iboMasaUser];
                        $returnData["httpCode"] = "200";
                    } else {
                        $returnData["msg"] = "Mac not found in system, Please input correct Mac Address!";
                        $returnData["statusCode"] = "C10202";
                        $returnData["appRefId"] = $appRefId;
                        $returnData["httpCode"] = "202";
                    }
                }
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $appRefId,
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501",
            ], 501);
        }
    }

    public function __mapCustomKeyPair($user, $module = 'VIRGINIA')
    {

        if (isset($user['_id'])) {

            $user['id'] = $user['_id'];
            unset($user['_id']);
        }
        if (isset($user['is_trial'])) {

            $user['status'] = in_array($user['is_trial'], ['2', '3']) ? "active" : "trial";
            unset($user['is_trial']);
        }
        if (isset($user['activated_by'])) {
            //=[$user['reseller_id'] = $user['activated_by'];
            unset($user['activated_by']);
        }
        //print_r($user['playlistUrl']);exit;
        if (!empty($user['playlistUrl']) && ($module == 'VIRGINIA' || $module == 'IBOAPP')) {
            //print_r($user);exit;
            foreach ($user['playlistUrl'] as $key => $value) {
                if ($value['playlist_name']) {
                    $user['playlistUrl'][$key]['name'] = $value['playlist_name'];
                    unset($user['playlistUrl'][$key]['playlist_name']);
                }
                if ($value['_id']) {
                    $user['playlistUrl'][$key]['id'] = $value['_id'];
                    unset($user['playlistUrl'][$key]['_id']);
                }
                if ($value['device_id']) {
                    $user['playlistUrl'][$key]['playlist_id'] = $value['device_id'];
                    unset($user['playlistUrl'][$key]['device_id']);
                }
            }
        }
        $user['module'] = $module;
        if (isset($user['playlistUrl'])) {
            unset($user['playlistUrl']);
        }

        return $user;
    }

    public function encrypt(Request $request)
    {
        $reqData = $request->all();
        // //iv - CLIENTID
        // $iv = "1212ASASASASASAS";
        // $encKey =  "SASASASASASASASASASASASASASADADA";
        // $options = 0;
        // $ciphering = "AES-256-CBC";
        // $jecn = json_encode($reqData['requestData']);
        
        $request['requestEnc'] = $this->encryptAES(json_encode($reqData['requestData']));
        
        //$encryption = openssl_encrypt($jecn, $ciphering, $encKey, $options, $iv);
        //$request['requestEnc'] = bin2hex($encryption);
        unset($request['requestData']);
        return new JsonResponse($request, 200);
    }

    public function decrypt(Request $request)
    {
        $reqData = $request->all();
        // $iv = "1212ASASASASASAS";
        // $encKey =  "SASASASASASASASASASASASASASADADA";
        // $options = 0;
        // $ciphering = "AES-256-CBC";
        // $decryption = openssl_decrypt(hex2bin($reqData['requestEnc']), $ciphering, $encKey, $options, $iv);
        // $request['requestData'] = json_decode($decryption, true);
        // unset($request['requestEnc']);
        
        $request['requestData'] = $this->decryptAES($reqData['requestEnc']);
        unset($request['requestEnc']);
        return new JsonResponse($request, 200);
    }


    public function boxActivationClient(Request $request)
    {
        $appRefId = null;
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];
            if ($request->isJson()) {
                $appRefId = ClientAppRef::genAppRefId($reqData);
                if ($request->hasHeader('clientId') && $request->hasHeader('secretKey')) {

                    $validArr = [
                        'module' => ['required', Rule::in(config('modules.addUserModules'))],
                        'channelId' => 'required|min:5|max:100',
                        'requestId' => 'required|unique:client_app_ref,request_id',
                        'clientId' => 'required',
                        'requestData.macAddress' => 'required|max:24',
                        'requestData.noOfDays' => ['required', Rule::in(['365', '10950'])],
                    ];


                    $validatedData = Validator::make($reqData, $validArr);
                    if ($validatedData->fails()) {
                        $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                        $returnData["statusCode"] = "C10422";
                        $returnData["appRefId"] = $appRefId;
                        $returnData["httpCode"] = "422";
                        return new JsonResponse($returnData, $returnData["httpCode"]);
                    }
                    //validate client and secret
                    $validClient = IBOSOLClient::where('client_id', $request->header('clientId'))->where('secret_key', $request->header('secretKey'))->count();
                    if ($validClient === 0) {
                        $returnData["msg"] = 'Invalid client details!';
                        $returnData["statusCode"] = "C10422";
                        $returnData["appRefId"] = $appRefId;
                        $returnData["httpCode"] = "422";
                        return new JsonResponse($returnData, 422);
                    }

                    $reqData['requestData']['macAddress'] = strtolower($reqData['requestData']['macAddress']);
                    if($reqData['module'] == 'IBOAPP'){
                        $exists = IBOAppDevice::where('mac_address', $reqData['requestData']['macAddress'])->first();
                        if ($exists === null) {
                            $exists = new IBOAppDevice;
                        }
                    }
                    if($reqData['module'] == 'VIRGINIA'){
                        $exists = VirginiaDevice::where('mac_address', $reqData['requestData']['macAddress'])->first();
                        if ($exists === null) {
                            $exists = new VirginiaDevice;
                        }
                    }

                    //new for ktn,abe, bob, alliptv

                    if($reqData['module'] == 'BOBPLAYER'){
                        $exists = BOBPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])
                        ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                        ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))->first();
                        if ($exists === null) {
                            $exists = new BOBPlayerDevice;
                            $exists->device_key = (string)rand(100000, 999999);
                        }
                    }
                    if($reqData['module'] == 'KTNPLAYER'){
                        $exists = KtnPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])
                        ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                        ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))->first();
                        if ($exists === null) {
                            $exists = new KtnPlayerDevice;
                            $exists->device_key = (string)rand(100000, 999999);
                        }
                    }
                    if($reqData['module'] == 'ABEPLAYER'){
                        $exists = ABEPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])
                        ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                        ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))->first();
                        if ($exists === null) {
                            $exists = new ABEPlayerDevice;
                            $exists->device_key = (string)rand(100000, 999999);
                        }
                    }
                    if($reqData['module'] == 'ALLIPTVPLAYER'){
                        $exists = AllPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])
                        ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                        ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))->first();
                        if ($exists === null) {
                            $exists = new AllPlayerDevice;
                            $exists->device_key = (string)rand(100000, 999999);
                        }
                    }
                    

                    $exists->mac_address = $reqData['requestData']['macAddress'];
                    if(!empty($reqData['requestData']['appType'])){
                        $exists->app_type = $reqData['requestData']['appType'];
                    }
                    if(!empty($reqData['requestData']['email'])){
                        $exists->email = $reqData['requestData']['email'];
                    }
                    
                    $exists->expire_date = $this->__setExpiryDate($reqData['requestData']['noOfDays'], $exists->expire_date);
                    $exists->reseller_id = $reqData['clientId'] ?? "";
                    
                    $exists->is_trial = ($reqData['requestData']['noOfDays'] > 7) ? 2 : 1;
                    
                    $creditPoint = $this->__getCreditPointVal($reqData['requestData']['noOfDays']);
                    //check for credit acount balance 
                    $resellerData = IBOSOLClient::where('channel_name', $reqData['channelId'])->first();
                    //return new JsonResponse($resellerData);
                    if ($resellerData->credit_point < $creditPoint) {
                        $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
                        $returnData["statusCode"] = "C13422";
                        $returnData["appRefId"] = $appRefId;
                        $returnData["httpCode"] = "422";
                        return new JsonResponse($returnData);
                    }
                    if ($exists->save()) {
                        //update reseller credit point
                        $this->__updateClientCreditPoint($resellerData->id, $reqData['requestData']['noOfDays']);
                        //end updating credit popint of reseller

                        //update here trans data
                        $this->__addClientActivationTranLogs(["reseller_id" => $reqData['clientId'], "user_id" => $exists->id, "mac_address" => $exists->mac_address, "module" => $reqData['module'], "channelId" => $reqData['channelId'], "credit_point" => $creditPoint]);
                        unset($exists->_id);
                        unset($exists->reseller_id);
                        unset($exists->activated_by);
                        unset($exists->created_at);
                        $exists->status = in_array($exists->is_trial, ['2', '3']) ? "active" : "trial";
                        unset($exists->is_trial);
                        $returnData["msg"] = 'Box activated succesfully!';
                        $returnData["statusCode"] = "00000";
                        $returnData["status"] = true;
                        $returnData["httpCode"] = "200";
                        $returnData["appRefId"] = $appRefId;
                        $returnData["data"] = [ $reqData['module'] => $exists];
                    } else {
                        $returnData["msg"] = "Something went wrong, please try again lator!";
                        $returnData["statusCode"] = "C10060";
                        $returnData["httpCode"] = "501";
                        $returnData["appRefId"] = $appRefId;
                    }
                } else {
                    $returnData["msg"] = "Invalid inputs (clientid and secret required)!";
                    $returnData["statusCode"] = "C12422";
                    $returnData["appRefId"] = $appRefId;
                    $returnData["httpCode"] = "422";
                }
            } else {
                $returnData["msg"] = "Invalid request type!";
                $returnData["statusCode"] = "C11422";
                $returnData["appRefId"] = $appRefId;
                $returnData["httpCode"] = "422";
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "EXC-Something went wrong, Please try again lator!",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $appRefId,
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
        return new JsonResponse($returnData, $returnData['httpCode']);
    }

    private function __updateClientCreditPoint($clientId, $trailPackage)
    {

        $reseller = IBOSOLClient::find($clientId);
        $minusCredit  = $this->__getCreditPointVal($trailPackage);
        $reseller->credit_point = ($reseller->credit_point - $minusCredit);
        return $reseller->save();
    }

    private function __getCreditPointVal($trailPackage)
    {
        $minusCredit = 0;
        if ($trailPackage == 7) {
            $minusCredit = 0;
        } else if ($trailPackage == 365) {
            $minusCredit = 1;
        }else if ($trailPackage == 730) {
            $minusCredit = 3;
        } else if ($trailPackage == 10950) {
            $minusCredit = 3;
        }else if ($trailPackage == 36500) {
            $minusCredit = 3;
        }
        return $minusCredit;
    }

    protected function __addClientActivationTranLogs($tranData)
    {
        //$resId, $userId, $module, $creditPoint
        $userTran = new \App\ClientActiTranLogs;
        $userTran->reseller_id = $tranData['reseller_id'];
        $userTran->user_id = $tranData['user_id'];
        $userTran->module = $tranData['module'];
        $userTran->channel_id = $tranData['channelId'];
        $userTran->credit_point = $tranData['credit_point'];
        $userTran->mac_address = $tranData['mac_address'] ?? "";
        $userTran->box_expiry_date = $tranData['expiry_date'] ?? "";
        return $userTran->save();
    }

    private function __setExpiryDate($isTrail, $oldExpDate)
    {
        switch ($isTrail) {
            case '7':
                $date = date('Y-m-d', strtotime($oldExpDate . '+1 week'));
                break;
            case '365':
                $date = date('Y-m-d', strtotime($oldExpDate . '+1 year'));
                break;
            case '730':
                $date = date('Y-m-d', strtotime($oldExpDate . '+2 years'));
                break;
            case '10950':
                $date = date('Y-m-d', strtotime($oldExpDate . '+30 years'));
                break;
            case '36500':
                $date = date('Y-m-d', strtotime($oldExpDate . '+100 years'));
                break;
            default:
                $date = date('Y-m-d', strtotime($oldExpDate . '+1 week'));
                break;
        }
        return $date;
    }
    
    public function boxActivationZen(Request $request)
    {
        $appRefId = null;
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];
            if ($request->isJson()) {
                $appRefId = ClientAppRef::genAppRefId($reqData);
                if ($request->hasHeader('clientId') && $request->hasHeader('secretKey')) {

                    $validArr = [
                        'module' => ['required', Rule::in(['IBOAPP', 'VIRGINIA'])],
                        'channelId' => 'required|min:5|max:100',
                        'requestId' => 'required',
                        'clientId' => 'required',
                        'requestData.macAddress' => 'required|max:20',
                        'requestData.noOfDays' => ['required', Rule::in(['365', '10950'])],
                    ];


                    $validatedData = Validator::make($reqData, $validArr);
                    if ($validatedData->fails()) {
                        $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                        $returnData["statusCode"] = "C10011";
                        $returnData["appRefId"] = $appRefId;
                        $returnData["httpCode"] = "422";
                        return new JsonResponse($returnData, 422);
                    }
                    //validate client and secret
                    $validClient = IBOSOLClient::where('client_id', $request->header('clientId'))->where('secret_key', $request->header('secretKey'))->count();
                    if ($validClient === 0) {
                        $returnData["msg"] = 'Invalid client details!';
                        $returnData["statusCode"] = "C10422";
                        $returnData["appRefId"] = $appRefId;
                        $returnData["httpCode"] = "422";
                        return new JsonResponse($returnData, 422);
                    }

                    $reqData['requestData']['macAddress'] = strtolower($reqData['requestData']['macAddress']);
                    if($reqData['module'] == 'IBOAPP'){
                        $exists = IBOAppDevice::where('mac_address', $reqData['requestData']['macAddress'])->first();
                        if ($exists === null) {
                            $exists = new IBOAppDevice;
                        }
                    }
                    if($reqData['module'] == 'VIRGINIA'){
                        $exists = VirginiaDevice::where('mac_address', $reqData['requestData']['macAddress'])->first();
                        if ($exists === null) {
                            $exists = new VirginiaDevice;
                        }
                    }

                    //new for ktn,abe, bob, alliptv

                    if($reqData['module'] == 'BOBPLAYER'){
                        $exists = BOBPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])
                        ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                        ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))->first();
                        if ($exists === null) {
                            $exists = new BOBPlayerDevice;
                            $exists->device_key = (string)rand(100000, 999999);
                        }
                    }
                    if($reqData['module'] == 'KTNPLAYER'){
                        $exists = KtnPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])
                        ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                        ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))->first();
                        if ($exists === null) {
                            $exists = new KtnPlayerDevice;
                            $exists->device_key = (string)rand(100000, 999999);
                        }
                    }
                    if($reqData['module'] == 'ABEPLAYER'){
                        $exists = ABEPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])
                        ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                        ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))->first();
                        if ($exists === null) {
                            $exists = new ABEPlayerDevice;
                            $exists->device_key = (string)rand(100000, 999999);
                        }
                    }
                    if($reqData['module'] == 'ALLIPTVPLAYER'){
                        $exists = AllPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])
                        ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                        ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))->first();
                        if ($exists === null) {
                            $exists = new AllPlayerDevice;
                            $exists->device_key = (string)rand(100000, 999999);
                        }
                    }
                    

                    $exists->mac_address = $reqData['requestData']['macAddress'];
                    if(!empty($reqData['requestData']['appType'])){
                        $exists->app_type = $reqData['requestData']['appType'];
                    }
                    if(!empty($reqData['requestData']['email'])){
                        $exists->email = $reqData['requestData']['email'];
                    }
                    
                    $exists->expire_date = $this->__setExpiryDate($reqData['requestData']['noOfDays'], $exists->expire_date);
                    $exists->reseller_id = $reqData['clientId'] ?? "";
                    
                    $exists->is_trial = ($reqData['requestData']['noOfDays'] > 7) ? 2 : 1;
                    
                    $creditPoint = $this->__getCreditPointVal($reqData['requestData']['noOfDays']);
                    //check for credit acount balance 
                    $resellerData = IBOSOLClient::where('channel_name', $reqData['channelId'])->first();
                    //return new JsonResponse($resellerData);
                    if ($resellerData->credit_point < $creditPoint) {
                        $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
                        $returnData["statusCode"] = "C13422";
                        $returnData["appRefId"] = $appRefId;
                        $returnData["httpCode"] = "422";
                        return new JsonResponse($returnData);
                    }
                    if ($exists->save()) {
                        //update reseller credit point
                        $this->__updateClientCreditPoint($resellerData->id, $reqData['requestData']['noOfDays']);
                        //end updating credit popint of reseller

                        //update here trans data
                        $this->__addClientActivationTranLogs(["reseller_id" => $reqData['clientId'], "user_id" => $exists->id, "mac_address" => $exists->mac_address, "module" => $reqData['module'], "channelId" => $reqData['channelId'], "credit_point" => $creditPoint]);
                        unset($exists->_id);
                        unset($exists->reseller_id);
                        unset($exists->activated_by);
                        unset($exists->created_at);
                        $exists->status = in_array($exists->is_trial, ['2', '3']) ? "active" : "trial";
                        unset($exists->is_trial);
                        $returnData["msg"] = 'Box activated succesfully!';
                        $returnData["statusCode"] = "00000";
                        $returnData["status"] = true;
                        $returnData["httpCode"] = "200";
                        $returnData["appRefId"] = $appRefId;
                        $returnData["data"] = [ $reqData['module'] => $exists];
                    } else {
                        $returnData["msg"] = "Something went wrong, please try again lator!";
                        $returnData["statusCode"] = "C10060";
                        $returnData["httpCode"] = "501";
                        $returnData["appRefId"] = $appRefId;
                    }
                } else {
                    $returnData["msg"] = "Invalid inputs (clientid and secret required)!";
                    $returnData["statusCode"] = "C12422";
                    $returnData["appRefId"] = $appRefId;
                    $returnData["httpCode"] = "422";
                }
            } else {
                $returnData["msg"] = "Invalid request type!";
                $returnData["statusCode"] = "C11422";
                $returnData["appRefId"] = $appRefId;
                $returnData["httpCode"] = "422";
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "EXC-Something went wrong, Please try again lator!",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $appRefId,
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
        return new JsonResponse($returnData, $returnData['httpCode']);
    }
    
    // Encrypt the JSON request
    public function encryptAES($data)
    {
        $tokenBytes = utf8_encode(self::TOKEN_KEY);
        $key = hash('sha256', $tokenBytes, true);
        $iv = utf8_encode(self::IV);

        $cipherText = openssl_encrypt($data, self::ALGO, $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $cipherText);
    }
    
    public function decryptAES($encryptedData)
    {
        
        $tokenBytes = utf8_encode(self::TOKEN_KEY);
        $key = hash('sha256', $tokenBytes, true);
        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, 16); // IV size for AES-256-CBC is 16 bytes
        $cipherText = substr($data, 16);
        $decryptedData = openssl_decrypt($cipherText, self::ALGO, $key, OPENSSL_RAW_DATA, $iv);
        return $decryptedData;
    }

}
