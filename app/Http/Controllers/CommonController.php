<?php

namespace App\Http\Controllers;
//use Validator;

use App\ABEPlayerDevice;
use App\ABEPlayerPlaylist;
use App\AllPlayerDevice;
use App\AllPlayerPlaylist;
use Carbon\Carbon;
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
use MongoDB\Client as Mongo;
use App\ArchUserActiTransLogs;
use App\IBOReseller;
use App\MASAPlayer;
use App\IBOUser;
use App\MASAPlaylistUrl;
use App\VirginiaDevice;
use App\VirginiaPlaylistUrl;
use App\IBOAppPlayer;
use App\IBOAppPlaylist;
use App\ResellerCreditPointTranLogs;
use App\UserActiTranLogs;
use App\Applications;
use App\BOBPlayerDevice;
use App\BOBPlayerPlaylist;
use App\HushPlayDevice;
use App\HushPlaylist;
use App\ResellerApplications;
use App\KtnPlayerDevice;
use App\KtnPlayerPlaylist;
use App\IBOPlayerActivationCode;
use App\IBOPlayerNotification;
use App\MacPlayerDevice;
use App\MacPlayerPlaylist;
use App\ResellerNotification;
use App\FamilyPlayerDevice;
use App\FamilyPlayerPlaylist;
use App\King4kPlayerPlaylist;
use App\King4kPlayerDevice;
use App\IBOSSPlayerDevice;
use App\IBOSSPlayerPlaylist;
use App\IBOXXPlayerDevice;
use App\IBOXXPlayerPlaylist;
use App\IBOSocialWidget;
use App\BOBProTvDevice;
use App\BOBProTvPLaylist;
use App\IBOStbDevice;
use App\IBOStbPlaylist;
use App\IBOSOLDevice;
use App\IBOSOLPlaylist;
use App\DuplexDevice;
use App\DuplexPlaylist;
use App\FlixNetDevice;
use App\FlixNetPlaylist;
class CommonController extends Controller
{

    public function updateBoxExpiryBulk(Request $request){
        try {
            $requestData = $request->all();
            $validatedData = Validator::make($requestData, [
                'module' => ['required', Rule::in(['IBOAPP'])],
                'channelId' => ['required', Rule::in(['HIMANSHU'])],
                'requestId' => 'required'
            ]);
            if ($validatedData->fails()) {
                return new JsonResponse($validatedData->errors());
            }
            //SELECT * FROM `user_activation_trans_logs` WHERE module in ('IBOAPP') and credit_point='1' and created_at between '2024-02-02' and '2024-02-05' order by created_at desc;
            $macAddresses = UserActiTranLogs::select('mac_address')->
            where('module', 'BOBPLAYER')
            ->where('credit_point', '2')
            ->whereBetween('created_at', ['2024-02-02', '2024-02-05'])
            ->get()->toArray();
            $macArray = array_column($macAddresses, 'mac_address');
           return new JsonResponse(['counts' => count($macArray), 'mcVals' => $macArray], 200);
            $oldExpDate = date('Y-m-d');
            //1-year--date('Y-m-d', strtotime($oldExpDate . '+1 year'));
            //30 years --date('Y-m-d', strtotime($oldExpDate . '+30 years'));
            $updateCode = BOBPlayerDevice::whereIn('mac_address', $macArray)->update(['expire_date' => date('Y-m-d', strtotime($oldExpDate . '+30 years'))]);
            return new JsonResponse(["status" => $updateCode, 
            'mcVals' => $macArray], 200);
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage());
        }  
    }
    
    
    public function updateStatus(Request $request)
    {
        try {
            $requestData = $request->all();
            $validatedData = Validator::make($requestData, [
                'module' => ['required', Rule::in(['MASA', 'VIRGINIA', 'IBOAPP'])],
                'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER'])],
                'requestId' => 'required',
                'requestData.id' => 'required',
                'requestData.status' => 'required',
                'requestData.updatedBy' => 'required',
            ]);
            if ($validatedData->fails()) {
                return new JsonResponse($validatedData->errors());
            }
            $module = $requestData['module'];
            return new JsonResponse(\App\MASAPlayer::where('id', $requestData['requestData']['id'])->first());
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage());
        }
    }

    /**
     * 
     */
    public function addReseller(Request $request)
    {
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];
            if ($request->isJson()) {
                $validatedData = Validator::make($reqData, [
                    'module' => ['required', Rule::in(['MASA', 'VIRGINIA', 'IBOAPP'])],
                    'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER'])],
                    'requestId' => 'required',
                    'requestData.email' => 'required|email|unique:App\IBOReseller,email',
                    'requestData.name' => 'required',
                    'requestData.expiryDate' => 'required',
                    'requestData.createdBy' => 'required',
                    'requestData.creatorGroupId' => 'required',
                    'requestData.password' => ['required', Password::min(6)]
                ]);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }
                $iboreseller = new IBOReseller;
                $iboreseller->name = $reqData['requestData']['name'];
                $iboreseller->email = strtolower($reqData['requestData']['email']);
                $iboreseller->password = Hash::make($reqData['requestData']['password']);
                $iboreseller->created_by = $reqData['requestData']['createdBy'];
                $iboreseller->status = "1";
                $creditPoint = $reqData['requestData']['creditPoint'] ?? 0;
                if (!empty($creditPoint) && $reqData['requestData']['creatorGroupId'] == '2') {
                    $creatorReseller = IBOReseller::find($reqData['requestData']['createdBy']);
                    if ($creatorReseller->credit_point <= $creditPoint) {
                        $returnData["statusCode"] = "C10019";
                        $returnData["msg"] = "Credit point should not be equal or greator than your current credit point!";
                        $returnData["httpCode"] = "422";
                        return new JsonResponse($returnData, 422);
                    }
                }
                $iboreseller->credit_point = 0;
                if ($reqData['requestData']['creatorGroupId'] == '2') {
                    $iboreseller->parent_reseller_id = $reqData['requestData']['createdBy'];
                }
                $iboreseller->expiry_date = date('Y-m-d', strtotime($reqData['requestData']['expiryDate']));
                if ($iboreseller->save()) {
                    // if (!empty($creditPoint)) {
                    //     $reqData['createdBy'] = $reqData['requestData']['createdBy'];
                    //     $reqData['resellerId'] = $iboreseller->id;
                    //     $reqData['creditPoint'] = $creditPoint;
                    //     //update into reseller credit trans logs
                    //     $this->__addResellerCreditShareLogs($reqData);
                    //     //update into reseller credit trans logs
                    // }
                    $returnData["status"] = true;
                    $returnData["msg"] = "Reseller added successfully!";
                    $returnData["statusCode"] = "000000";
                    unset($iboreseller['password']);
                    unset($iboreseller['id']);
                    $returnData["data"] = ["IBOReseller" => $iboreseller];
                    $returnData["httpCode"] = "200";
                } else {
                    $returnData["msg"] = "Reseller can not be added this moment, Please try after sometime.!";
                    $returnData["statusCode"] = "C10012";
                    $returnData["httpCode"] = "501";
                }
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
    }

    public function editReseller(Request $request)
    {
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];
            if ($request->isJson()) {
                $validatedData = Validator::make($reqData, [
                    'module' => ['required', Rule::in(['MASA', 'VIRGINIA', 'IBOAPP'])],
                    'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER'])],
                    'requestId' => 'required',
                    'requestData.name' => 'required',
                    'requestData.createdBy' => 'required',
                    'requestData.creatorGroupId' => 'required',
                    'requestData.resellerId' => 'required'
                ]);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }
                $iboreseller = IBOReseller::find($reqData['requestData']['resellerId']);
                $oldCreditPoint = $iboreseller->credit_point;
                $iboreseller->name = $reqData['requestData']['name'];
                $iboreseller->updated_by = $reqData['requestData']['createdBy'];
                if(!empty($reqData['requestData']['expiryDate'])){
                    $iboreseller->expiry_date = date('Y-m-d', strtotime($reqData['requestData']['expiryDate']));
                }
                
                $creditPoint = $reqData['requestData']['creditPoint'] ?? "";
                //$iboreseller->credit_point = $creditPoint;
                if (!empty($creditPoint) && $reqData['requestData']['creatorGroupId'] == '2') {
                    $creatorReseller = IBOReseller::find($reqData['requestData']['createdBy']);
                    if ($creatorReseller->credit_point <= $creditPoint) {
                        $returnData["statusCode"] = "C10019";
                        $returnData["msg"] = "Credit point should not be equal or greator than your current credit point!";
                        $returnData["httpCode"] = "422";
                        return new JsonResponse($returnData, 422);
                    }
                }
                if ($reqData['requestData']['creatorGroupId'] == '2') {
                    $iboreseller->parent_reseller_id = $reqData['requestData']['createdBy'];
                }
                if(isset($reqData['requestData']['newPassword']) && !empty($reqData['requestData']['newPassword'])){
                    $iboreseller->password = Hash::make($reqData['requestData']['newPassword']);
                }
                if(isset($reqData['requestData']['activationPasscode']) && !empty($reqData['requestData']['activationPasscode'])){
                    $iboreseller->credit_share_passcode = $reqData['requestData']['activationPasscode'];
                }
                if ($iboreseller->save()) {
                   // if (!empty($creditPoint) && $oldCreditPoint !== $iboreseller->credit_point) {
                    //    $reqData['createdBy'] = $reqData['requestData']['createdBy'];
                    //    $reqData['resellerId'] = $reqData['requestData']['resellerId'];
                    //    $reqData['creditPoint'] = $creditPoint;
                        //update into reseller credit trans logs
                    //    $this->__addResellerCreditShareLogs($reqData);
                        //update into reseller credit trans logs
                  //  }
                    $returnData["status"] = true;
                    $returnData["msg"] = "Reseller updated successfully!";
                    $returnData["statusCode"] = "000000";
                    unset($iboreseller['password']);
                    unset($iboreseller['id']);
                    $returnData["data"] = ["IBOReseller" => $iboreseller];
                    $returnData["httpCode"] = "200";
                } else {
                    $returnData["msg"] = "Reseller can not be updated this moment, Please try after sometime.!";
                    $returnData["statusCode"] = "C10012";
                    $returnData["httpCode"] = "501";
                }
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
    }

    public function resellerList(Request $request)
    {
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];
            if ($request->isJson()) {
                $validatedData = Validator::make($reqData, [
                    'module' => ['required', Rule::in(['MASA', 'VIRGINIA', 'IBOAPP'])],
                    'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER'])],
                    'requestId' => 'required',
                    'userId' => "required",
                    'groupId' => "required"
                ]);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }

                if ($reqData['groupId'] == 2) {
                    $iboreseller =  IBOReseller::where('status', '!=', '2')->where('parent_reseller_id', $reqData['userId'])->where('group_id', '2')->get();
                } else {
                    $iboreseller =  IBOReseller::where('status', '!=', '2')->where('group_id', '2')->get();
                }

                if (!empty($iboreseller)) {
                    $returnData["status"] = true;
                    $returnData["msg"] = "Reseller list fetched successfully!";
                    $returnData["statusCode"] = "000000";
                    $returnData["data"] = ["IBOReseller" => $iboreseller];
                    $returnData["httpCode"] = "200";
                } else {
                    $returnData["msg"] = "Reseller list can not be fetched this moment, Please try after sometime.!";
                    $returnData["statusCode"] = "C10012";
                    $returnData["httpCode"] = "501";
                }
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
    }

    public function addUser(Request $request)
    {
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];
            if ($request->isJson()) {
                $validArr = [
                    'module' => ['required', Rule::in(config('modules.addUserModules'))],
                    'channelId' => ['required', Rule::in(config('channels.addUserChannel'))],
                    'requestId' => 'required',
                    'requestData.createdBy' => 'required',
                    'requestData.isTrail' => 'required'
                ];
                $addValidMac = [];
                if ($reqData['module'] == 'MASA') {
                    $addValidMac = ['requestData.macAddress' => 'required|unique:App\MASAPlayer,mac_address'];
                } else if ($reqData['module'] == 'VIRGINIA') {
                    $addValidMac = ['requestData.macAddress' => 'required|unique:App\VirginiaDevice,mac_address'];
                } else if ($reqData['module'] == 'IBOAPP') {
                    $addValidMac = ['requestData.macAddress' => 'required|unique:App\IBOAppDevice,mac_address'];
                }else if ($reqData['module'] == 'ABEPLAYERTV') {
                    $addValidMac = ['requestData.macAddress' => 'required|unique:App\ABEPlayerDevice,mac_address'];
                }else if ($reqData['module'] == 'BOBPLAYER') {
                    $addValidMac = ['requestData.macAddress' => 'required|unique:App\BOBPlayerDevice,mac_address'];
                }else if ($reqData['module'] == 'MACPLAYER') {
                    $addValidMac = ['requestData.macAddress' => 'required|unique:App\MacPlayerDevice,mac_address'];
                }else if ($reqData['module'] == 'KTNPLAYER') {
                    $addValidMac = ['requestData.macAddress' => 'required|unique:App\KtnPlayerDevice,mac_address'];
                }else if ($reqData['module'] == 'ALLPLAYER') {
                    $addValidMac = ['requestData.macAddress' => 'required|unique:App\AllPlayerDevice,mac_address'];
                }else if ($reqData['module'] == 'HUSHPLAY') {
                    $addValidMac = ['requestData.macAddress' => 'required|unique:App\HushPlayDevice,mac_address'];
                }else if ($reqData['module'] == 'FAMILYPLAYER') {
                    $addValidMac = ['requestData.macAddress' => 'required|unique:App\FamilyPlayerDevice,mac_address'];
                }else if ($reqData['module'] == 'KING4KPLAYER') {
                    $addValidMac = ['requestData.macAddress' => 'required|unique:App\King4kPlayerDevice,mac_address'];
                }else if ($reqData['module'] == 'IBOSSPLAYER') {
                    $addValidMac = ['requestData.macAddress' => 'required|unique:App\IBOSSPlayerDevice,mac_address'];
                }else if ($reqData['module'] == 'IBOXXPLAYER') {
                    $addValidMac = ['requestData.macAddress' => 'required|unique:App\IBOXXPlayerDevice,mac_address'];
                }else if ($reqData['module'] == 'BOBPROTV') {
                    $addValidMac = ['requestData.macAddress' => 'required|unique:App\BOBProTvDevice,mac_address'];
                }else if ($reqData['module'] == 'IBOSTB') {
                    $addValidMac = ['requestData.macAddress' => 'required|unique:App\IBOStbDevice,mac_address'];
                }else if ($reqData['module'] == 'IBOSOL') {
                    $addValidMac = ['requestData.macAddress' => 'required|unique:App\IBOSOLDevice,mac_address'];
                }else if ($reqData['module'] == 'DUPLEX') {
                    $addValidMac = ['requestData.macAddress' => 'required|unique:App\DuplexDevice,mac_address'];
                }else if ($reqData['module'] == 'FLIXNET') {
                    $addValidMac = ['requestData.macAddress' => 'required|unique:App\FlixNetDevice,mac_address'];
                }
                $validatedData = Validator::make($reqData, array_merge($validArr, $addValidMac));
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }


                // return new JsonResponse([
                //     "status" => false,
                //     "statusCode" => "C10019",
                //     "msg" => "Adding new mac id is disabled permanently! Please contact admin.",
                //     "requestId" => $reqData["requestId"] ?? "",
                //     "appRefId" => $reqData["appRefId"] ?? "",
                //     "channelId" => $reqData["channelId"] ?? "",
                //     "module" => $reqData["module"] ?? "",
                // ], 501);
                if ($reqData['module'] == 'MASA') {
                    $returnData = $this->__addInMasaPlayer($reqData, $returnData);
                } else if ($reqData['module'] == 'VIRGINIA') {
                    $returnData = $this->__addInVirginiaPlayer($reqData, $returnData);
                } else if ($reqData['module'] == 'IBOAPP') {
                    $returnData = $this->__addInIBOAPPPlayer($reqData, $returnData);
                }else if ($reqData['module'] == 'ABEPLAYERTV') {
                    $returnData = $this->__addInABEPlayerTV($reqData, $returnData);
                }else if ($reqData['module'] == 'BOBPLAYER') {
                    $returnData = $this->__addInBOBPlayer($reqData, $returnData);
                }else if ($reqData['module'] == 'MACPLAYER') {
                    $returnData = $this->__addInMacPlayerPlayer($reqData, $returnData);
                }else if ($reqData['module'] == 'KTNPLAYER') {
                    $returnData = $this->__addInKTNPlayerDevice($reqData, $returnData);
                }else if ($reqData['module'] == 'ALLPLAYER') {
                    $returnData = $this->__addInALLPlayerDevice($reqData, $returnData);
                }else if ($reqData['module'] == 'HUSHPLAY') {
                    $returnData = $this->__addInHushPlayDevice($reqData, $returnData);
                }else if ($reqData['module'] == 'FAMILYPLAYER') {
                    $returnData = $this->__addInFamilyPlayerDevice($reqData, $returnData);
                }else if ($reqData['module'] == 'KING4KPLAYER') {
                    $returnData = $this->__addInKing4KPlayerDevice($reqData, $returnData);
                }else if ($reqData['module'] == 'IBOSSPLAYER') {
                    $returnData = $this->__addInIBOSSPlayerDevice($reqData, $returnData);
                } else if ($reqData['module'] == 'IBOXXPLAYER') {
                    $returnData = $this->__addInIBOXXPlayerDevice($reqData, $returnData);
                }else if ($reqData['module'] == 'BOBPROTV') {
                    $returnData = $this->__addInBobProTvDevice($reqData, $returnData);
                }else if ($reqData['module'] == 'IBOSTB') {
                    $returnData = $this->__addInIBOSTBDevice($reqData, $returnData);
                }else if ($reqData['module'] == 'IBOSOL') {
                    $returnData = $this->__addInIBOSOLBDevice($reqData, $returnData);
                }else if ($reqData['module'] == 'DUPLEX') {
                    $returnData = $this->__addInDuplexDevice($reqData, $returnData);
                }else if ($reqData['module'] == 'FLIXNET') {
                    $returnData = $this->__addInFlixNetDevice($reqData, $returnData);
                }


                return new JsonResponse($returnData);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
    }
    
    
    public function __addInIBOSTBDevice($reqData, $returnData){
        $iboStbDevice = new IBOStbDevice();
        $iboStbDevice->mac_address = $reqData['requestData']['macAddress'];
        $iboStbDevice->device_key = (string)rand(100000, 999999);
        if(!empty($reqData['requestData']['appType'])){
            $iboStbDevice->app_type = $reqData['requestData']['appType'];
        }
        if(!empty($reqData['requestData']['email'])){
            $iboStbDevice->email = $reqData['requestData']['email'];
        }
        $iboStbDevice->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], date('Y-m-d'));
        $iboStbDevice->activated_by = $reqData['requestData']['createdBy'];
        $iboStbDevice->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 3 : 2);
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance 
        $resellerData = IBOReseller::find($reqData['requestData']['createdBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }
        //end checking 
        if ($iboStbDevice->save()) {
            if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && isset($reqData['requestData']['playlist'][0]['playListUrl'])) {
                $insertPlaylist = [];
                foreach ($reqData['requestData']['playlist'] as $key => $value) {
                    if (!empty($value['playListUrl'])) {
                        $insertPlaylist[] =
                            array(
                                'device_id' => $iboStbDevice->id,
                                'playlist_name' => $value['playListName'] ?? "",
                                'url' => $value['playListUrl'] ?? "",
                                'is_protected' => $value['is_protected'] ?? 0,
                                'pin' => $value['pin'] ?? null,
                                'epg_url' => $value['epg_url'] ?? "",
                                'playlist_type' => $value['playlist_type'] ?? "general",
                                'username' => $value['username'] ?? "",
                                'password' => $value['password'] ?? "",
                                '_v'    => 0
                            );
                    }
                }
                IBOStbPlaylist::insert($insertPlaylist);
            }
            // if (!empty($virPlaylistUrl)) {
            //update reseller credit point
            $this->__updateCreditPoint($reqData['requestData']['createdBy'], $reqData['requestData']['isTrail']);
            //end updating credit popint of reseller
            $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
            $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $iboStbDevice->expire_date, "reseller_id" => $iboStbDevice->activated_by, "user_id" => $iboStbDevice->id, "mac_address" => $iboStbDevice->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint]);
            //update here trans data

            //end updating trans data
            $returnData["status"] = true;
            $returnData["msg"] = "User added successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = [$reqData['module'] . "User" => $iboStbDevice];
            $returnData["httpCode"] = "200";
            // } else {
            //     $returnData["msg"] = "User's playlist detail could not be added this moment, Please try after sometime.!";
            //     $returnData["statusCode"] = "C10013";
            //     $returnData["httpCode"] = "501";
            // }
        } else {
            $returnData["msg"] = "User could not be added this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C10012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }

    
    public function __addInBOBProTvDevice($reqData, $returnData){
        $bobProTvDevice = new BOBProTvDevice();
        $bobProTvDevice->mac_address = $reqData['requestData']['macAddress'];
        $bobProTvDevice->device_key = (string)rand(100000, 999999);
        if(!empty($reqData['requestData']['appType'])){
            $bobProTvDevice->app_type = $reqData['requestData']['appType'];
        }
        if(!empty($reqData['requestData']['email'])){
            $bobProTvDevice->email = $reqData['requestData']['email'];
        }
        $bobProTvDevice->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], date('Y-m-d'));
        $bobProTvDevice->activated_by = $reqData['requestData']['createdBy'];
        $bobProTvDevice->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 3 : 2);
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance 
        $resellerData = IBOReseller::find($reqData['requestData']['createdBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }
        //end checking 
        if ($bobProTvDevice->save()) {
            if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && isset($reqData['requestData']['playlist'][0]['playListUrl'])) {
                $insertPlaylist = [];
                foreach ($reqData['requestData']['playlist'] as $key => $value) {
                    if (!empty($value['playListUrl'])) {
                        $insertPlaylist[] =
                            array(
                                'device_id' => $bobProTvDevice->id,
                                'playlist_name' => $value['playListName'] ?? "",
                                'url' => $value['playListUrl'] ?? "",
                                'is_protected' => $value['is_protected'] ?? 0,
                                'pin' => $value['pin'] ?? null,
                                'epg_url' => $value['epg_url'] ?? "",
                                'playlist_type' => $value['playlist_type'] ?? "general",
                                'username' => $value['username'] ?? "",
                                'password' => $value['password'] ?? "",
                                '_v'    => 0
                            );
                    }
                }
                BOBProTvPlaylist::insert($insertPlaylist);
            }
            // if (!empty($virPlaylistUrl)) {
            //update reseller credit point
            $this->__updateCreditPoint($reqData['requestData']['createdBy'], $reqData['requestData']['isTrail']);
            //end updating credit popint of reseller
            $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
            $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $bobProTvDevice->expire_date, "reseller_id" => $bobProTvDevice->activated_by, "user_id" => $bobProTvDevice->id, "mac_address" => $bobProTvDevice->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint]);
            //update here trans data

            //end updating trans data
            $returnData["status"] = true;
            $returnData["msg"] = "User added successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = [$reqData['module'] . "User" => $bobProTvDevice];
            $returnData["httpCode"] = "200";
            // } else {
            //     $returnData["msg"] = "User's playlist detail could not be added this moment, Please try after sometime.!";
            //     $returnData["statusCode"] = "C10013";
            //     $returnData["httpCode"] = "501";
            // }
        } else {
            $returnData["msg"] = "User could not be added this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C10012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }

    private function __addInMasaPlayer($reqData, $returnData)
    {   

        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        $resellerData = IBOReseller::find($reqData['requestData']['createdBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }
        //end checking 

        $serReq = [];
        $serReq['module'] = $reqData['module'];
        $serReq['channelId'] = 'IBOMASA';
        $serReq['requestId'] = $reqData['requestId'];
        $serReq['requestData']['macAddress'] = $reqData['requestData']['macAddress'];
        $serReq['requestData']['packageId'] = ($reqData['requestData']['isTrail']);
        
        $serReq['requestData']['creditPoint'] = $creditPoint;
        $serReq['requestData']['resellerId'] = $reqData['requestData']['createdBy'];
        $result  = MasaHelper::callMasaService($serReq);
        if(!empty($result) & isset($result['status']) && $result['status']){
            
            //update reseller credit point
            $this->__updateCreditPoint($reqData['requestData']['createdBy'], $reqData['requestData']['isTrail']);
            //end updating credit popint of reseller
            $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
            $this->__addActivationTranLogs(["activation_remarks" =>  $remarks, "reseller_id" => $result['data']['MASAUser']['reseller_id'], "user_id" => $result['data']['MASAUser']['id'], "mac_address" => $result['data']['MASAUser']['mac_address'],  "module" => $reqData['module'], "credit_point" => $creditPoint]);
            //update here trans data
            $returnData["status"] = true;
            $returnData["msg"] = "User activated successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = [$reqData['module'] . "User" => $result['data']['MASAUser']];
            $returnData["httpCode"] = "200";
            
        } else {
            $returnData["msg"] = "User could not be added this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C10012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }

    // private function __addInMasaPlayer($reqData, $returnData)
    // {
    //     $iboreseller = new MASAPlayer;
    //     $iboreseller->mac_address = $reqData['requestData']['macAddress'];
    //     $iboreseller->app_type = $reqData['requestData']['appType'] ?? "";
    //     $iboreseller->email = $reqData['requestData']['email'] ?? "";
    //     $iboreseller->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], date('Y-m-d'));
    //     $iboreseller->reseller_id = $reqData['requestData']['createdBy'];
    //     $iboreseller->is_trial = ($reqData['requestData']['isTrail'] > 1) ? 2 : 1;
    //     $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);

    //     //check for credit acount balance 
    //     $resellerData = IBOReseller::find($reqData['requestData']['createdBy']);
    //     if ($resellerData->groupId == '2' && $resellerData->credit_point < $creditPoint) {
    //         $returnData["msg"] = "You don not have enough credit balance to add/edit new user!";
    //         $returnData["statusCode"] = "C10019";
    //         $returnData["httpCode"] = "501";
    //         return new JsonResponse($returnData);
    //     }
    //     //end checking 

    //     if ($iboreseller->save()) {
    //         if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && isset($reqData['requestData']['playlist'][0]['playListUrl'])) {
    //             $insertPlaylist = [];
    //             foreach ($reqData['requestData']['playlist'] as $key => $value) {
    //                 if (!empty($value['playListUrl'])) {
    //                     $insertPlaylist[] =
    //                         array(
    //                             'playlist_id' => $iboreseller->id,
    //                             'name' => $value['playListName'] ?? "",
    //                             'url' => $value['playListUrl'] ?? ""
    //                         );
    //                 }
    //             }

    //             $iboPlaylistUrl = MASAPlaylistUrl::insert($insertPlaylist);
    //         }
    //         // if (!empty($iboPlaylistUrl)) {
    //         //update reseller credit point
    //         $this->__updateCreditPoint($reqData['requestData']['createdBy'], $reqData['requestData']['isTrail']);
    //         //end updating credit popint of reseller

    //         $this->__addActivationTranLogs(["reseller_id" => $iboreseller->reseller_id, "user_id" => $iboreseller->id, "mac_address" => $iboreseller->mac_address,  "module" => $reqData['module'], "credit_point" => $creditPoint]);
    //         //update here trans data

    //         //end updating trans data
    //         $returnData["status"] = true;
    //         $returnData["msg"] = "User added successfully!";
    //         $returnData["statusCode"] = "000000";
    //         $returnData["data"] = [$reqData['module'] . "User" => $iboreseller];
    //         $returnData["httpCode"] = "200";
    //         // } else {
    //         //     $returnData["msg"] = "User's playlist detail could not be added this moment, Please try after sometime.!";
    //         //     $returnData["statusCode"] = "C10013";
    //         //     $returnData["httpCode"] = "501";
    //         // }
    //     } else {
    //         $returnData["msg"] = "User could not be added this moment, Please try after sometime.!";
    //         $returnData["statusCode"] = "C10012";
    //         $returnData["httpCode"] = "501";
    //     }
    //     return $returnData;
    // }

    private function __addInVirginiaPlayer($reqData, $returnData)
    {
        $virginiaDevice = new VirginiaDevice;
        $virginiaDevice->mac_address = $reqData['requestData']['macAddress'];
        if(!empty($reqData['requestData']['appType'])){
            $virginiaDevice->app_type = $reqData['requestData']['appType'];
        }
        if(!empty($reqData['requestData']['email'])){
            $virginiaDevice->email = $reqData['requestData']['email'];
        }
        
        $virginiaDevice->created_at = date('Y-m-d H:i:s');
        $virginiaDevice->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], date('Y-m-d'));
        $virginiaDevice->activated_by = $reqData['requestData']['createdBy'];
        $virginiaDevice->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 3 : 2);

        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance 
        $resellerData = IBOReseller::find($reqData['requestData']['createdBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You don not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }
        //end checking 
        if ($virginiaDevice->save()) {
            if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && isset($reqData['requestData']['playlist'][0]['playListUrl'])) {
                $insertPlaylist = [];
                foreach ($reqData['requestData']['playlist'] as $key => $value) {
                    if (!empty($value['playListUrl'])) {
                        $insertPlaylist[] =
                            array(
                               'device_id' => $virginiaDevice->id,
                                'playlist_name' => $value['playListName'] ?? "",
                                'url' => $value['playListUrl'] ?? "",
                                'is_protected' => $value['is_protected'] ?? 0,
                                'pin' => $value['pin'] ?? null,
                                'epg_url' => $value['epg_url'] ?? "",
                                'playlist_type' => $value['playlist_type'] ?? "general",
                                'username' => $value['username'] ?? "",
                                'password' => $value['password'] ?? "",
                                '_v'    => 0
                            );
                    }
                }
                $virPlaylistUrl = VirginiaPlaylistUrl::insert($insertPlaylist);
            }
            // if (!empty($virPlaylistUrl)) {
            //update reseller credit point
            $this->__updateCreditPoint($reqData['requestData']['createdBy'], $reqData['requestData']['isTrail']);
            //end updating credit popint of reseller
            $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
            $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $virginiaDevice->expire_date, "reseller_id" => $virginiaDevice->activated_by, "user_id" => $virginiaDevice->id, "mac_address" => $virginiaDevice->mac_address,  "module" => $reqData['module'], "credit_point" => $creditPoint]);
            //update here trans data

            //end updating trans data
            $returnData["status"] = true;
            $returnData["msg"] = "User added successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = [$reqData['module'] . "User" => $virginiaDevice];
            $returnData["httpCode"] = "200";
            // } else {
            //     $returnData["msg"] = "User's playlist detail could not be added this moment, Please try after sometime.!";
            //     $returnData["statusCode"] = "C10013";
            //     $returnData["httpCode"] = "501";
            // }
        } else {
            $returnData["msg"] = "User could not be added this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C10012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }

    public function __addInIBOAPPPlayer($reqData, $returnData)
    {
        $virginiaDevice = new IBOAppDevice();
        $virginiaDevice->mac_address = $reqData['requestData']['macAddress'];
        $virginiaDevice->device_key = (string)rand(100000, 999999);
        if(!empty($reqData['requestData']['appType'])){
            $virginiaDevice->app_type = $reqData['requestData']['appType'];
        }
        if(!empty($reqData['requestData']['email'])){
            $virginiaDevice->email = $reqData['requestData']['email'];
        }
        $virginiaDevice->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], date('Y-m-d'));
        $virginiaDevice->activated_by = $reqData['requestData']['createdBy'];
        $virginiaDevice->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 3 : 2);
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance 
        $resellerData = IBOReseller::find($reqData['requestData']['createdBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }
        //end checking 
        if ($virginiaDevice->save()) {
            if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && isset($reqData['requestData']['playlist'][0]['playListUrl'])) {
                $insertPlaylist = [];
                foreach ($reqData['requestData']['playlist'] as $key => $value) {
                    if (!empty($value['playListUrl'])) {
                        $insertPlaylist[] =
                            array(
                                'device_id' => $virginiaDevice->id,
                                'playlist_name' => $value['playListName'] ?? "",
                                'url' => $value['playListUrl'] ?? "",
                                'is_protected' => $value['is_protected'] ?? 0,
                                'pin' => $value['pin'] ?? null,
                                'epg_url' => $value['epg_url'] ?? "",
                                'playlist_type' => $value['playlist_type'] ?? "general",
                                'username' => $value['username'] ?? "",
                                'password' => $value['password'] ?? "",
                                '_v'    => 0
                            );
                    }
                }
                $virPlaylistUrl = IBOAppPlaylist::insert($insertPlaylist);
            }
            // if (!empty($virPlaylistUrl)) {
            //update reseller credit point
            $this->__updateCreditPoint($reqData['requestData']['createdBy'], $reqData['requestData']['isTrail']);
            //end updating credit popint of reseller
            $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
            $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $virginiaDevice->expire_date, "reseller_id" => $virginiaDevice->activated_by, "user_id" => $virginiaDevice->id, "mac_address" => $virginiaDevice->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint]);
            //update here trans data

            //end updating trans data
            $returnData["status"] = true;
            $returnData["msg"] = "User added successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = [$reqData['module'] . "User" => $virginiaDevice];
            $returnData["httpCode"] = "200";
            // } else {
            //     $returnData["msg"] = "User's playlist detail could not be added this moment, Please try after sometime.!";
            //     $returnData["statusCode"] = "C10013";
            //     $returnData["httpCode"] = "501";
            // }
        } else {
            $returnData["msg"] = "User could not be added this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C10012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }
    
    public function __addInMacPlayerPlayer($reqData, $returnData)
    {
        $macPlayerDevice = new MacPlayerDevice();
        $macPlayerDevice->mac_address = $reqData['requestData']['macAddress'];
        $macPlayerDevice->device_key = (string)rand(100000, 999999);
        if(!empty($reqData['requestData']['appType'])){
            $macPlayerDevice->app_type = $reqData['requestData']['appType'];
        }
        if(!empty($reqData['requestData']['email'])){
            $macPlayerDevice->email = $reqData['requestData']['email'];
        }
        $macPlayerDevice->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], date('Y-m-d'));
        $macPlayerDevice->activated_by = $reqData['requestData']['createdBy'];
        $macPlayerDevice->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 3 : 2);
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance 
        $resellerData = IBOReseller::find($reqData['requestData']['createdBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }
        //end checking 
        if ($macPlayerDevice->save()) {
            if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && isset($reqData['requestData']['playlist'][0]['playListUrl'])) {
                $insertPlaylist = [];
                foreach ($reqData['requestData']['playlist'] as $key => $value) {
                    if (!empty($value['playListUrl'])) {
                        $insertPlaylist[] =
                            array(
                                'device_id' => $macPlayerDevice->id,
                                'playlist_name' => $value['playListName'] ?? "",
                                'url' => $value['playListUrl'] ?? "",
                                'is_protected' => $value['is_protected'] ?? 0,
                                'pin' => $value['pin'] ?? null,
                                'epg_url' => $value['epg_url'] ?? "",
                                'playlist_type' => $value['playlist_type'] ?? "general",
                                'username' => $value['username'] ?? "",
                                'password' => $value['password'] ?? "",
                                '_v'    => 0
                            );
                    }
                }
                $virPlaylistUrl = MacPlayerPlaylist::insert($insertPlaylist);
            }
            // if (!empty($virPlaylistUrl)) {
            //update reseller credit point
            $this->__updateCreditPoint($reqData['requestData']['createdBy'], $reqData['requestData']['isTrail']);
            //end updating credit popint of reseller
            $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
            $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $macPlayerDevice->expire_date, "reseller_id" => $macPlayerDevice->activated_by, "user_id" => $macPlayerDevice->id, "mac_address" => $macPlayerDevice->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint]);
            //update here trans data

            //end updating trans data
            $returnData["status"] = true;
            $returnData["msg"] = "User added successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = [$reqData['module'] . "User" => $macPlayerDevice];
            $returnData["httpCode"] = "200";
            // } else {
            //     $returnData["msg"] = "User's playlist detail could not be added this moment, Please try after sometime.!";
            //     $returnData["statusCode"] = "C10013";
            //     $returnData["httpCode"] = "501";
            // }
        } else {
            $returnData["msg"] = "User could not be added this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C10012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }
    
    public function __addInKTNPlayerDevice($reqData, $returnData)
    {
        $ktnPlayerDevice = new KtnPlayerDevice();
        $ktnPlayerDevice->mac_address = $reqData['requestData']['macAddress'];
        $ktnPlayerDevice->device_key = (string)rand(100000, 999999);
        if(!empty($reqData['requestData']['appType'])){
            $ktnPlayerDevice->app_type = $reqData['requestData']['appType'];
        }
        if(!empty($reqData['requestData']['email'])){
            $ktnPlayerDevice->email = $reqData['requestData']['email'];
        }
        $ktnPlayerDevice->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], date('Y-m-d'));
        $ktnPlayerDevice->activated_by = $reqData['requestData']['createdBy'];
        $ktnPlayerDevice->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 3 : 2);
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance 
        $resellerData = IBOReseller::find($reqData['requestData']['createdBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }
        //end checking 
        if ($ktnPlayerDevice->save()) {
            if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && isset($reqData['requestData']['playlist'][0]['playListUrl'])) {
                $insertPlaylist = [];
                foreach ($reqData['requestData']['playlist'] as $key => $value) {
                    if (!empty($value['playListUrl'])) {
                        $insertPlaylist[] =
                            array(
                                'device_id' => $ktnPlayerDevice->id,
                                'playlist_name' => $value['playListName'] ?? "",
                                'url' => $value['playListUrl'] ?? "",
                                'is_protected' => $value['is_protected'] ?? 0,
                                'pin' => $value['pin'] ?? null,
                                'epg_url' => $value['epg_url'] ?? "",
                                'playlist_type' => $value['playlist_type'] ?? "general",
                                'username' => $value['username'] ?? "",
                                'password' => $value['password'] ?? "",
                                '_v'    => 0
                            );
                    }
                }
                KtnPlayerPlaylist::insert($insertPlaylist);
            }
            // if (!empty($virPlaylistUrl)) {
            //update reseller credit point
            $this->__updateCreditPoint($reqData['requestData']['createdBy'], $reqData['requestData']['isTrail']);
            //end updating credit popint of reseller
            $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
            $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $ktnPlayerDevice->expire_date, "reseller_id" => $ktnPlayerDevice->activated_by, "user_id" => $ktnPlayerDevice->id, "mac_address" => $ktnPlayerDevice->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint]);
            //update here trans data

            //end updating trans data
            $returnData["status"] = true;
            $returnData["msg"] = "User added successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = [$reqData['module'] . "User" => $ktnPlayerDevice];
            $returnData["httpCode"] = "200";
            // } else {
            //     $returnData["msg"] = "User's playlist detail could not be added this moment, Please try after sometime.!";
            //     $returnData["statusCode"] = "C10013";
            //     $returnData["httpCode"] = "501";
            // }
        } else {
            $returnData["msg"] = "User could not be added this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C10012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }

    public function __addInALLPlayerDevice($reqData, $returnData)
    {
        $allPlayerDevice = new AllPlayerDevice();
        $allPlayerDevice->mac_address = $reqData['requestData']['macAddress'];
        $allPlayerDevice->device_key = (string)rand(100000, 999999);
        if(!empty($reqData['requestData']['appType'])){
            $allPlayerDevice->app_type = $reqData['requestData']['appType'];
        }
        if(!empty($reqData['requestData']['email'])){
            $allPlayerDevice->email = $reqData['requestData']['email'];
        }
        $allPlayerDevice->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], date('Y-m-d'));
        $allPlayerDevice->activated_by = $reqData['requestData']['createdBy'];
        $allPlayerDevice->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 3 : 2);
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance 
        $resellerData = IBOReseller::find($reqData['requestData']['createdBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }
        //end checking 
        if ($allPlayerDevice->save()) {
            if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && isset($reqData['requestData']['playlist'][0]['playListUrl'])) {
                $insertPlaylist = [];
                foreach ($reqData['requestData']['playlist'] as $key => $value) {
                    if (!empty($value['playListUrl'])) {
                        $insertPlaylist[] =
                            array(
                                'device_id' => $allPlayerDevice->id,
                                'playlist_name' => $value['playListName'] ?? "",
                                'url' => $value['playListUrl'] ?? "",
                                'is_protected' => $value['is_protected'] ?? 0,
                                'pin' => $value['pin'] ?? null,
                                'epg_url' => $value['epg_url'] ?? "",
                                'playlist_type' => $value['playlist_type'] ?? "general",
                                'username' => $value['username'] ?? "",
                                'password' => $value['password'] ?? "",
                                '_v'    => 0
                            );
                    }
                }
                AllPlayerPlaylist::insert($insertPlaylist);
            }
            // if (!empty($virPlaylistUrl)) {
            //update reseller credit point
            $this->__updateCreditPoint($reqData['requestData']['createdBy'], $reqData['requestData']['isTrail']);
            //end updating credit popint of reseller
            $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
            $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $allPlayerDevice->expire_date, "reseller_id" => $allPlayerDevice->activated_by, "user_id" => $allPlayerDevice->id, "mac_address" => $allPlayerDevice->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint]);
            //update here trans data

            //end updating trans data
            $returnData["status"] = true;
            $returnData["msg"] = "User added successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = [$reqData['module'] . "User" => $allPlayerDevice];
            $returnData["httpCode"] = "200";
            // } else {
            //     $returnData["msg"] = "User's playlist detail could not be added this moment, Please try after sometime.!";
            //     $returnData["statusCode"] = "C10013";
            //     $returnData["httpCode"] = "501";
            // }
        } else {
            $returnData["msg"] = "User could not be added this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C10012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }
    
    public function __addInHushPlayDevice($reqData, $returnData)
    {
        $hushPLayDevice = new HushPlayDevice();
        $hushPLayDevice->mac_address = $reqData['requestData']['macAddress'];
        $hushPLayDevice->device_key = (string)rand(100000, 999999);
        if(!empty($reqData['requestData']['appType'])){
            $hushPLayDevice->app_type = $reqData['requestData']['appType'];
        }
        if(!empty($reqData['requestData']['email'])){
            $hushPLayDevice->email = $reqData['requestData']['email'];
        }
        $hushPLayDevice->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], date('Y-m-d'));
        $hushPLayDevice->activated_by = $reqData['requestData']['createdBy'];
        $hushPLayDevice->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 3 : 2);
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance 
        $resellerData = IBOReseller::find($reqData['requestData']['createdBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }
        //end checking 
        if ($hushPLayDevice->save()) {
            if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && isset($reqData['requestData']['playlist'][0]['playListUrl'])) {
                $insertPlaylist = [];
                foreach ($reqData['requestData']['playlist'] as $key => $value) {
                    if (!empty($value['playListUrl'])) {
                        $insertPlaylist[] =
                            array(
                                'device_id' => $hushPLayDevice->id,
                                'playlist_name' => $value['playListName'] ?? "",
                                'url' => $value['playListUrl'] ?? "",
                                'is_protected' => $value['is_protected'] ?? 0,
                                'pin' => $value['pin'] ?? null,
                                'epg_url' => $value['epg_url'] ?? "",
                                'playlist_type' => $value['playlist_type'] ?? "general",
                                'username' => $value['username'] ?? "",
                                'password' => $value['password'] ?? "",
                                '_v'    => 0
                            );
                    }
                }
                HushPlaylist::insert($insertPlaylist);
            }
            // if (!empty($virPlaylistUrl)) {
            //update reseller credit point
            $this->__updateCreditPoint($reqData['requestData']['createdBy'], $reqData['requestData']['isTrail']);
            //end updating credit popint of reseller
            $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
            $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $hushPLayDevice->expire_date, "reseller_id" => $hushPLayDevice->activated_by, "user_id" => $hushPLayDevice->id, "mac_address" => $hushPLayDevice->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint]);
            //update here trans data

            //end updating trans data
            $returnData["status"] = true;
            $returnData["msg"] = "User added successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = [$reqData['module'] => $hushPLayDevice];
            $returnData["httpCode"] = "200";
            // } else {
            //     $returnData["msg"] = "User's playlist detail could not be added this moment, Please try after sometime.!";
            //     $returnData["statusCode"] = "C10013";
            //     $returnData["httpCode"] = "501";
            // }
        } else {
            $returnData["msg"] = "User could not be added this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C10012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }

    public function editUser(Request $request)
    {
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];
            if ($request->isJson()) {
                $validArr = [
                    'module' => ['required', Rule::in(config('modules.editUserModules'))],
                    'channelId' => ['required', Rule::in(config('channels.editUserChannel'))],
                    'requestId' => 'required',
                    'requestData.updatedBy' => 'required',
                    'requestData.isTrail' => 'required',
                    'requestData.userId' => 'required'
                ];
                $addValidMac = [];
                $validatedData = Validator::make($reqData, array_merge($validArr, $addValidMac));
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C20011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }
                if ($reqData['module'] == 'MASA') {
                    $reqData['requestData']['createdBy'] = $reqData['requestData']['updatedBy'];
                    $returnData = $this->__addInMasaPlayer($reqData, $returnData);
                } else if ($reqData['module'] == 'VIRGINIA') {
                    $returnData = $this->__editVirginiaUser($reqData, $returnData);
                } else if ($reqData['module'] == 'IBOAPP') {
                    $returnData = $this->__editIBOAppUser($reqData, $returnData);
                } else if ($reqData['module'] == 'BAYIPTV') {
                    $returnData = $this->__addBAYIPTVAppUser($reqData, $returnData);
                } else if ($reqData['module'] == 'ABEPLAYERTV') {
                    $returnData = $this->__editABEPlayerUser($reqData, $returnData);
                } else if ($reqData['module'] == 'BOBPLAYER') {
                    $returnData = $this->__editBOBPlayerUser($reqData, $returnData);
                } else if ($reqData['module'] == 'MACPLAYER') {
                    $returnData = $this->__editMacPlayer($reqData, $returnData);
                } else if ($reqData['module'] == 'KTNPLAYER') {
                    $returnData = $this->__editKTNPlayer($reqData, $returnData);
                } else if ($reqData['module'] == 'ALLPLAYER') {
                    $returnData = $this->__editALLPlayer($reqData, $returnData);
                } else if ($reqData['module'] == 'HUSHPLAY') {
                    $returnData = $this->__editHushPlay($reqData, $returnData);
                } else if ($reqData['module'] == 'FAMILYPLAYER') {
                    $returnData = $this->__editFamilyPlayer($reqData, $returnData);
                }  else if ($reqData['module'] == 'KING4KPLAYER') {
                    $returnData = $this->__editKing4KPlayer($reqData, $returnData);
                } else if ($reqData['module'] == 'IBOSSPLAYER') {
                    $returnData = $this->__editIBOSSPlayer($reqData, $returnData);
                } else if ($reqData['module'] == 'IBOXXPLAYER') {
                    $returnData = $this->__editIBOXXPlayer($reqData, $returnData);
                } else if ($reqData['module'] == 'BOBPROTV') {
                    $returnData = $this->__editBOBProTV($reqData, $returnData);
                } else if ($reqData['module'] == 'IBOSTB') {
                    $returnData = $this->__editIBOSTB($reqData, $returnData);
                } else if ($reqData['module'] == 'IBOSOL') {
                    $returnData = $this->__editIBOSOL($reqData, $returnData);
                } else if ($reqData['module'] == 'DUPLEX') {
                    $returnData = $this->__editDuplex($reqData, $returnData);
                } else if ($reqData['module'] == 'FLIXNET') {
                    $returnData = $this->__editFlixNet($reqData, $returnData);
                } 
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C20010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
    }

    private function __editFlixNet($reqData, $returnData){
        $FlixNetDevice = FlixNetDevice::find($reqData['requestData']['userId']);
        $FlixNetDevice->mac_address = $reqData['requestData']['macAddress'];
        if(!empty($reqData['requestData']['appType'])){
            $FlixNetDevice->app_type = $reqData['requestData']['appType'];
        }
        if(!empty($reqData['requestData']['email'])){
            $FlixNetDevice->email = $reqData['requestData']['email'];
        }
        $FlixNetDevice->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], $FlixNetDevice->expire_date);
        $FlixNetDevice->activated_by = $reqData['requestData']['updatedBy'];
        if ($reqData['requestData']['isTrail'] !== $FlixNetDevice->is_trial) {
            $FlixNetDevice->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 3 : 2);
        }
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance 
        $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }
        if ($FlixNetDevice->save()) {
            if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && $reqData['requestData']['playlist'][0]['playListUrl'] !== '') {
                $deleted = FlixNetPlaylist::where('device_id', $FlixNetDevice->_id)->delete();

                $insertPlaylist = [];
                foreach ($reqData['requestData']['playlist'] as $key => $value) {
                    if (!empty($value['playListUrl'])) {
                        $insertPlaylist[] =
                            array(
                                'device_id' => $FlixNetDevice->_id,
                                'playlist_name' => $value['playListName'] ?? "",
                                'url' => $value['playListUrl'] ?? "",
                                'created_at' => date('Y-m-d H:i:s')
                            );
                    }
                }
                $iboPlaylistUrl = FlixNetPlaylist::insert($insertPlaylist);
            }
            // if (!empty($iboPlaylistUrl)) {
            if ($reqData['requestData']['isTrail'] !== $FlixNetDevice->is_trial) {
                //update reseller credit point
                $this->__updateCreditPoint($reqData['requestData']['updatedBy'], $reqData['requestData']['isTrail']);
                //end updating credit popint of reseller

                //update here trans data
                $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
                $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $FlixNetDevice->expire_date, "reseller_id" => $FlixNetDevice->activated_by, "user_id" => $FlixNetDevice->id, "mac_address" => $FlixNetDevice->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint]);
                //end updating trans data
            }
            $returnData["status"] = true;
            $returnData["msg"] = "User updated successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" => $FlixNetDevice];
            $returnData["httpCode"] = "200";
            
        } else {
            $returnData["msg"] = "User could not be updated this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C20012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }

    private function __editDuplex($reqData, $returnData){
        $duplexDevice = DuplexDevice::find($reqData['requestData']['userId']);
        //print_r($iboreseller);exit;
        $duplexDevice->mac_address = $reqData['requestData']['macAddress'];
        if(!empty($reqData['requestData']['appType'])){
            $duplexDevice->app_type = $reqData['requestData']['appType'];
        }
        if(!empty($reqData['requestData']['email'])){
            $duplexDevice->email = $reqData['requestData']['email'];
        }
        $duplexDevice->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], $duplexDevice->expire_date);
        $duplexDevice->activated_by = $reqData['requestData']['updatedBy'];
        if ($reqData['requestData']['isTrail'] !== $duplexDevice->is_trial) {
            $duplexDevice->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 3 : 2);
        }
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance 
        $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }
        if ($duplexDevice->save()) {
            if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && $reqData['requestData']['playlist'][0]['playListUrl'] !== '') {
                $deleted = DuplexPlaylist::where('device_id', $duplexDevice->_id)->delete();

                $insertPlaylist = [];
                foreach ($reqData['requestData']['playlist'] as $key => $value) {
                    if (!empty($value['playListUrl'])) {
                        $insertPlaylist[] =
                            array(
                                'device_id' => $duplexDevice->_id,
                                'playlist_name' => $value['playListName'] ?? "",
                                'url' => $value['playListUrl'] ?? "",
                                'created_at' => date('Y-m-d H:i:s')
                            );
                    }
                }
                $iboPlaylistUrl = DuplexPlaylist::insert($insertPlaylist);
            }
            // if (!empty($iboPlaylistUrl)) {
            if ($reqData['requestData']['isTrail'] !== $duplexDevice->is_trial) {
                //update reseller credit point
                $this->__updateCreditPoint($reqData['requestData']['updatedBy'], $reqData['requestData']['isTrail']);
                //end updating credit popint of reseller

                //update here trans data
                $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
                $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $duplexDevice->expire_date, "reseller_id" => $duplexDevice->activated_by, "user_id" => $duplexDevice->id, "mac_address" => $duplexDevice->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint]);
                //end updating trans data
            }
            $returnData["status"] = true;
            $returnData["msg"] = "User updated successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" => $duplexDevice];
            $returnData["httpCode"] = "200";
            
        } else {
            $returnData["msg"] = "User could not be updated this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C20012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }


    
    private function __editIBOSOL($reqData, $returnData){
        $iboSolDevice = IBOSOLDevice::find($reqData['requestData']['userId']);
        //print_r($iboreseller);exit;
        $iboSolDevice->mac_address = $reqData['requestData']['macAddress'];
        if(!empty($reqData['requestData']['appType'])){
            $iboSolDevice->app_type = $reqData['requestData']['appType'];
        }
        if(!empty($reqData['requestData']['email'])){
            $iboSolDevice->email = $reqData['requestData']['email'];
        }
        $iboSolDevice->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], $iboSolDevice->expire_date);
        $iboSolDevice->activated_by = $reqData['requestData']['updatedBy'];
        if ($reqData['requestData']['isTrail'] !== $iboSolDevice->is_trial) {
            $iboSolDevice->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 3 : 2);
        }
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance 
        $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }
        if ($iboSolDevice->save()) {
            if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && $reqData['requestData']['playlist'][0]['playListUrl'] !== '') {
                $deleted = IBOStbPlaylist::where('device_id', $iboSolDevice->_id)->delete();

                $insertPlaylist = [];
                foreach ($reqData['requestData']['playlist'] as $key => $value) {
                    if (!empty($value['playListUrl'])) {
                        $insertPlaylist[] =
                            array(
                                'device_id' => $iboSolDevice->_id,
                                'playlist_name' => $value['playListName'] ?? "",
                                'url' => $value['playListUrl'] ?? "",
                                'created_at' => date('Y-m-d H:i:s')
                            );
                    }
                }
                $iboPlaylistUrl = IBOSOLPlaylist::insert($insertPlaylist);
            }
            // if (!empty($iboPlaylistUrl)) {
            if ($reqData['requestData']['isTrail'] !== $iboSolDevice->is_trial) {
                //update reseller credit point
                $this->__updateCreditPoint($reqData['requestData']['updatedBy'], $reqData['requestData']['isTrail']);
                //end updating credit popint of reseller

                //update here trans data
                $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
                $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $iboSolDevice->expire_date, "reseller_id" => $iboSolDevice->activated_by, "user_id" => $iboSolDevice->id, "mac_address" => $iboSolDevice->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint]);
                //end updating trans data
            }
            $returnData["status"] = true;
            $returnData["msg"] = "User updated successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" => $iboSolDevice];
            $returnData["httpCode"] = "200";
            
        } else {
            $returnData["msg"] = "User could not be updated this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C20012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }


    private function __editIBOSTB($reqData, $returnData)
    {
        $iboStbDevice = IBOStbDevice::find($reqData['requestData']['userId']);
        //print_r($iboreseller);exit;
        $iboStbDevice->mac_address = $reqData['requestData']['macAddress'];
        if(!empty($reqData['requestData']['appType'])){
            $iboStbDevice->app_type = $reqData['requestData']['appType'];
        }
        if(!empty($reqData['requestData']['email'])){
            $iboStbDevice->email = $reqData['requestData']['email'];
        }
        $iboStbDevice->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], $iboStbDevice->expire_date);
        $iboStbDevice->activated_by = $reqData['requestData']['updatedBy'];
        if ($reqData['requestData']['isTrail'] !== $iboStbDevice->is_trial) {
            $iboStbDevice->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 3 : 2);
        }
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance 
        $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }
        if ($iboStbDevice->save()) {
            if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && $reqData['requestData']['playlist'][0]['playListUrl'] !== '') {
                $deleted = IBOStbPlaylist::where('device_id', $iboStbDevice->_id)->delete();

                $insertPlaylist = [];
                foreach ($reqData['requestData']['playlist'] as $key => $value) {
                    if (!empty($value['playListUrl'])) {
                        $insertPlaylist[] =
                            array(
                                'device_id' => $iboStbDevice->_id,
                                'playlist_name' => $value['playListName'] ?? "",
                                'url' => $value['playListUrl'] ?? "",
                                'created_at' => date('Y-m-d H:i:s')
                            );
                    }
                }
                $iboPlaylistUrl = IBOStbPlaylist::insert($insertPlaylist);
            }
            // if (!empty($iboPlaylistUrl)) {
            if ($reqData['requestData']['isTrail'] !== $iboStbDevice->is_trial) {
                //update reseller credit point
                $this->__updateCreditPoint($reqData['requestData']['updatedBy'], $reqData['requestData']['isTrail']);
                //end updating credit popint of reseller

                //update here trans data
                $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
                $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $iboStbDevice->expire_date, "reseller_id" => $iboStbDevice->activated_by, "user_id" => $iboStbDevice->id, "mac_address" => $iboStbDevice->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint]);
                //end updating trans data
            }
            $returnData["status"] = true;
            $returnData["msg"] = "User updated successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" => $iboStbDevice];
            $returnData["httpCode"] = "200";
            
        } else {
            $returnData["msg"] = "User could not be updated this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C20012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }

    private function __editMasaUser($reqData, $returnData)
    {
        $iboreseller = MASAPlayer::find($reqData['requestData']['userId']);
        $iboreseller->mac_address = $reqData['requestData']['macAddress'];
        if(!empty($reqData['requestData']['appType'])){
            $iboreseller->app_type = $reqData['requestData']['appType'];
        }
        if(!empty($reqData['requestData']['email'])){
            $iboreseller->email = $reqData['requestData']['email'];
        }
        $iboreseller->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], $iboreseller->expire_date);
        $iboreseller->reseller_id = $reqData['requestData']['updatedBy'];
        if ($reqData['requestData']['isTrail'] !== $iboreseller->is_trial) {
            $iboreseller->is_trial = ($reqData['requestData']['isTrail'] > 1) ? 2 : 1;
        }

        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance 
        $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }

        if ($iboreseller->save()) {

            //return new JsonResponse($deleted);
            if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && isset($reqData['requestData']['playlist'][0]['playListUrl'])) {
                $deleted = MASAPlaylistUrl::where('playlist_id', $iboreseller->id)->delete();
                $insertPlaylist = [];
                foreach ($reqData['requestData']['playlist'] as $key => $value) {
                    if (!empty($value['playListUrl'])) {
                        $insertPlaylist[] =
                            array(
                                'playlist_id' => $iboreseller->id,
                                'name' => $value['playListName'] ?? "",
                                'url' => $value['playListUrl'] ?? "",
                                'created_at' => date('Y-m-d H:i:s')
                            );
                    }
                }
                $iboPlaylistUrl = MASAPlaylistUrl::insert($insertPlaylist);
            }
            // if (!empty($iboPlaylistUrl)) {
            if ($reqData['requestData']['isTrail'] !== $iboreseller->is_trial) {
                //update reseller credit point
                $this->__updateCreditPoint($reqData['requestData']['updatedBy'], $reqData['requestData']['isTrail']);
                //end updating credit popint of reseller

                //update here trans data
                $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
                $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $iboreseller->expire_date, "reseller_id" => $iboreseller->reseller_id, "user_id" => $iboreseller->id, "mac_address" => $iboreseller->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint]);
                //end updating trans data
            }
            $returnData["status"] = true;
            $returnData["msg"] = "User updated successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" => $iboreseller];
            $returnData["httpCode"] = "200";
            // } else {
            //     $returnData["msg"] = "User's playlist detail could not be added this moment, Please try after sometime.!";
            //     $returnData["statusCode"] = "C20013";
            //     $returnData["httpCode"] = "501";
            // }
        } else {
            $returnData["msg"] = "User could not be updated this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C20012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }

    private function __editVirginiaUser($reqData, $returnData)
    {
        $iboreseller = VirginiaDevice::find($reqData['requestData']['userId']);
        //print_r($iboreseller);exit;
        $iboreseller->mac_address = $reqData['requestData']['macAddress'];
        if(!empty($reqData['requestData']['appType'])){
            $iboreseller->app_type = $reqData['requestData']['appType'];
        }
        if(!empty($reqData['requestData']['email'])){
            $iboreseller->email = $reqData['requestData']['email'];
        }
        $iboreseller->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], $iboreseller->expire_date);
        $iboreseller->activated_by = $reqData['requestData']['updatedBy'];
        if ($reqData['requestData']['isTrail'] !== $iboreseller->is_trial) {
            $iboreseller->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 3 : 2);
        }
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance 
        $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }
        if ($iboreseller->save()) {


            if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && isset($reqData['requestData']['playlist'][0]['playListUrl'])) {
                $insertPlaylist = [];
                $deleted = VirginiaPlaylistUrl::where('device_id', $iboreseller->_id)->delete();
                foreach ($reqData['requestData']['playlist'] as $key => $value) {
                    if (!empty($value['playListUrl'])) {
                        $insertPlaylist[] =
                            array(
                                'device_id' => $iboreseller->_id,
                                'playlist_name' => $value['playListName'] ?? "",
                                'url' => $value['playListUrl'] ?? "",
                                'created_at' => date('Y-m-d H:i:s')
                            );
                    }
                }
                $iboPlaylistUrl = VirginiaPlaylistUrl::insert($insertPlaylist);
            }

            // if (1) {
            if ($reqData['requestData']['isTrail'] !== $iboreseller->is_trial) {
                //update reseller credit point
                $this->__updateCreditPoint($reqData['requestData']['updatedBy'], $reqData['requestData']['isTrail']);
                //end updating credit popint of reseller

                //update here trans data
                $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
                $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $iboreseller->expire_date, "reseller_id" => $iboreseller->activated_by, "user_id" => $iboreseller->id, "mac_address" => $iboreseller->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint]);
                //end updating trans data
            }
            $returnData["status"] = true;
            $returnData["msg"] = "User updated successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" => $iboreseller];
            $returnData["httpCode"] = "200";
            // } else {
            //     $returnData["msg"] = "User's playlist detail could not be added this moment, Please try after sometime.!";
            //     $returnData["statusCode"] = "C20013";
            //     $returnData["httpCode"] = "501";
            // }
        } else {
            $returnData["msg"] = "User could not be updated this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C20012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }

    private function __addBAYIPTVAppUser($reqData, $returnData){
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }
        $bayReq = [];
        $bayReq['mac'] = $reqData['requestData']['macAddress'];
        $bayReq['email'] = 'Akoum@live.de';
        $bayReq['password'] = 'linux4all';
        $nmbrOfDays = '7';
        if($reqData['requestData']['isTrail'] == '2'){
            $nmbrOfDays = '365';
        }else if($reqData['requestData']['isTrail'] == '3'){
            $nmbrOfDays = '36500';
        }
        $bayReq['nombreJour'] = $nmbrOfDays;
        $response = BAYIPTVHelper::activateUser($bayReq);
        if(!empty($response)){
            
            if(isset($response['message']['status']) && $response['message']['status'] == '200'){
                $returnData["status"] = true;
                $returnData["msg"] = $response['message']['txt'];
                $returnData["statusCode"] = "000000";
                $returnData["data"] = ["IBOMasaUser" => [["mac_address"=> $reqData['requestData']['macAddress'], "msg"=>$response['message']['txt']]]];
                $returnData["httpCode"] = "200";
                //update reseller credit point
                $this->__updateCreditPoint($reqData['requestData']['updatedBy'], $reqData['requestData']['isTrail']);
                //end updating credit popint of reseller

                //update here trans data
                $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
                $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => date('Y-m-d',strtotime('+'.$nmbrOfDays. ' days')),"reseller_id" => $resellerData->id, "user_id" => 1, "mac_address" => $reqData['requestData']['macAddress'], "module" => $reqData['module'], "credit_point" => $creditPoint]);
                //end updating trans data
            }else if(isset($response['message']['status']) && $response['message']['status'] == '202'){
                
                $returnData["msg"] = $response['message']['txt'];
                $returnData["statusCode"] = "CBAY103";
                //$returnData["data"] = ["IBOMasaUser" => [["mac_address"=> $reqData['requestData']['macAddress'], "msg"=>$response['message']['txt']]]];
                $returnData["httpCode"] = "200";
            }else if(isset($response['message']['status']) && $response['message']['status'] == '400'){
            
                $returnData["msg"] = $response['message']['txt'];
                $returnData["statusCode"] = "CBAY104";
                //$returnData["data"] = ["IBOMasaUser" => [["mac_address"=> $reqData['requestData']['macAddress'], "msg"=>$response['message']['txt']]]];
                $returnData["httpCode"] = "200";
            }
        }else{
            $returnData["statusCode"] = "CBAY102";
            $returnData["msg"] = "User can not be activated!";
            $returnData["httpCode"] = "202";
        }
        return $returnData;
    }


    private function __editIBOAppUser($reqData, $returnData)
    {
        $iboreseller = IBOAppDevice::find($reqData['requestData']['userId']);
        //print_r($iboreseller);exit;
        $iboreseller->mac_address = $reqData['requestData']['macAddress'];
        if(!empty($reqData['requestData']['appType'])){
            $iboreseller->app_type = $reqData['requestData']['appType'];
        }
        if(!empty($reqData['requestData']['email'])){
            $iboreseller->email = $reqData['requestData']['email'];
        }
        $iboreseller->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], $iboreseller->expire_date);
        $iboreseller->activated_by = $reqData['requestData']['updatedBy'];
        if ($reqData['requestData']['isTrail'] !== $iboreseller->is_trial) {
            $iboreseller->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 3 : 2);
        }
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance 
        $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }
        if ($iboreseller->save()) {
            if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && $reqData['requestData']['playlist'][0]['playListUrl'] !== '') {
                $deleted = IBOAppPlaylist::where('device_id', $iboreseller->_id)->delete();

                $insertPlaylist = [];
                foreach ($reqData['requestData']['playlist'] as $key => $value) {
                    if (!empty($value['playListUrl'])) {
                        $insertPlaylist[] =
                            array(
                                'device_id' => $iboreseller->_id,
                                'playlist_name' => $value['playListName'] ?? "",
                                'url' => $value['playListUrl'] ?? "",
                                'created_at' => date('Y-m-d H:i:s')
                            );
                    }
                }
                $iboPlaylistUrl = IBOAppPlaylist::insert($insertPlaylist);
            }
            // if (!empty($iboPlaylistUrl)) {
            if ($reqData['requestData']['isTrail'] !== $iboreseller->is_trial) {
                //update reseller credit point
                $this->__updateCreditPoint($reqData['requestData']['updatedBy'], $reqData['requestData']['isTrail']);
                //end updating credit popint of reseller

                //update here trans data
                $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
                $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $iboreseller->expire_date, "reseller_id" => $iboreseller->activated_by, "user_id" => $iboreseller->id, "mac_address" => $iboreseller->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint]);
                //end updating trans data
            }
            $returnData["status"] = true;
            $returnData["msg"] = "User updated successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" => $iboreseller];
            $returnData["httpCode"] = "200";
            // } else {
            //     $returnData["msg"] = "User's playlist detail could not be added this moment, Please try after sometime.!";
            //     $returnData["statusCode"] = "C20013";
            //     $returnData["httpCode"] = "501";
            // }
        } else {
            $returnData["msg"] = "User could not be updated this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C20012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }
    
    private function __editMacPLayer($reqData, $returnData)
    {
        $macPlayer = MacPlayerDevice::find($reqData['requestData']['userId']);
        //print_r($iboreseller);exit;
        $macPlayer->mac_address = $reqData['requestData']['macAddress'];
        if(!empty($reqData['requestData']['appType'])){
            $macPlayer->app_type = $reqData['requestData']['appType'];
        }
        if(!empty($reqData['requestData']['email'])){
            $macPlayer->email = $reqData['requestData']['email'];
        }
        $macPlayer->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], $macPlayer->expire_date);
        $macPlayer->activated_by = $reqData['requestData']['updatedBy'];
        if ($reqData['requestData']['isTrail'] !== $macPlayer->is_trial) {
            $macPlayer->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 3 : 2);
        }
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance 
        $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }
        if ($macPlayer->save()) {
            if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && $reqData['requestData']['playlist'][0]['playListUrl'] !== '') {
                $deleted = MacPlayerPlaylist::where('device_id', $macPlayer->_id)->delete();

                $insertPlaylist = [];
                foreach ($reqData['requestData']['playlist'] as $key => $value) {
                    if (!empty($value['playListUrl'])) {
                        $insertPlaylist[] =
                            array(
                                'device_id' => $macPlayer->_id,
                                'playlist_name' => $value['playListName'] ?? "",
                                'url' => $value['playListUrl'] ?? "",
                                'created_at' => date('Y-m-d H:i:s')
                            );
                    }
                }
                $iboPlaylistUrl = MacPlayerPlaylist::insert($insertPlaylist);
            }
            // if (!empty($iboPlaylistUrl)) {
            if ($reqData['requestData']['isTrail'] !== $macPlayer->is_trial) {
                //update reseller credit point
                $this->__updateCreditPoint($reqData['requestData']['updatedBy'], $reqData['requestData']['isTrail']);
                //end updating credit popint of reseller

                //update here trans data
                $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
                $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $macPlayer->expire_date, "reseller_id" => $macPlayer->activated_by, "user_id" => $macPlayer->id, "mac_address" => $macPlayer->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint]);
                //end updating trans data
            }
            $returnData["status"] = true;
            $returnData["msg"] = "User updated successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" => $macPlayer];
            $returnData["httpCode"] = "200";
            
        } else {
            $returnData["msg"] = "User could not be updated this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C20012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }
    
    private function __editKTNPlayer($reqData, $returnData)
    {
        $ktnPlayer = KtnPlayerDevice::find($reqData['requestData']['userId']);
        //print_r($iboreseller);exit;
        $ktnPlayer->mac_address = $reqData['requestData']['macAddress'];
        if(!empty($reqData['requestData']['appType'])){
            $ktnPlayer->app_type = $reqData['requestData']['appType'];
        }
        if(!empty($reqData['requestData']['email'])){
            $ktnPlayer->email = $reqData['requestData']['email'];
        }
        $ktnPlayer->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], $ktnPlayer->expire_date);
        $ktnPlayer->activated_by = $reqData['requestData']['updatedBy'];
        if ($reqData['requestData']['isTrail'] !== $ktnPlayer->is_trial) {
            $ktnPlayer->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 3 : 2);
        }
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance 
        $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }
        if ($ktnPlayer->save()) {
            if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && $reqData['requestData']['playlist'][0]['playListUrl'] !== '') {
                $deleted = KtnPlayerPlaylist::where('device_id', $ktnPlayer->_id)->delete();

                $insertPlaylist = [];
                foreach ($reqData['requestData']['playlist'] as $key => $value) {
                    if (!empty($value['playListUrl'])) {
                        $insertPlaylist[] =
                            array(
                                'device_id' => $ktnPlayer->_id,
                                'playlist_name' => $value['playListName'] ?? "",
                                'url' => $value['playListUrl'] ?? "",
                                'created_at' => date('Y-m-d H:i:s')
                            );
                    }
                }
                $iboPlaylistUrl = KtnPlayerPlaylist::insert($insertPlaylist);
            }
            // if (!empty($iboPlaylistUrl)) {
            if ($reqData['requestData']['isTrail'] !== $ktnPlayer->is_trial) {
                //update reseller credit point
                $this->__updateCreditPoint($reqData['requestData']['updatedBy'], $reqData['requestData']['isTrail']);
                //end updating credit popint of reseller

                //update here trans data
                $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
                $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $ktnPlayer->expire_date, "reseller_id" => $ktnPlayer->activated_by, "user_id" => $ktnPlayer->id, "mac_address" => $ktnPlayer->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint]);
                //end updating trans data
            }
            $returnData["status"] = true;
            $returnData["msg"] = "User updated successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" => $ktnPlayer];
            $returnData["httpCode"] = "200";
            
        } else {
            $returnData["msg"] = "User could not be updated this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C20012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }

    private function __editALLPlayer($reqData, $returnData)
    {
        $allPlayer = AllPlayerDevice::find($reqData['requestData']['userId']);
        //print_r($iboreseller);exit;
        $allPlayer->mac_address = $reqData['requestData']['macAddress'];
        if(!empty($reqData['requestData']['appType'])){
            $allPlayer->app_type = $reqData['requestData']['appType'];
        }
        if(!empty($reqData['requestData']['email'])){
            $allPlayer->email = $reqData['requestData']['email'];
        }
        $allPlayer->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], $allPlayer->expire_date);
        $allPlayer->activated_by = $reqData['requestData']['updatedBy'];
        if ($reqData['requestData']['isTrail'] !== $allPlayer->is_trial) {
            $allPlayer->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 3 : 2);
        }
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance 
        $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }
        if ($allPlayer->save()) {
            if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && $reqData['requestData']['playlist'][0]['playListUrl'] !== '') {
                $deleted = AllPlayerPlaylist::where('device_id', $allPlayer->_id)->delete();

                $insertPlaylist = [];
                foreach ($reqData['requestData']['playlist'] as $key => $value) {
                    if (!empty($value['playListUrl'])) {
                        $insertPlaylist[] =
                            array(
                                'device_id' => $allPlayer->_id,
                                'playlist_name' => $value['playListName'] ?? "",
                                'url' => $value['playListUrl'] ?? "",
                                'created_at' => date('Y-m-d H:i:s')
                            );
                    }
                }
                $iboPlaylistUrl = AllPlayerPlaylist::insert($insertPlaylist);
            }
            // if (!empty($iboPlaylistUrl)) {
            if ($reqData['requestData']['isTrail'] !== $allPlayer->is_trial) {
                //update reseller credit point
                $this->__updateCreditPoint($reqData['requestData']['updatedBy'], $reqData['requestData']['isTrail']);
                //end updating credit popint of reseller

                //update here trans data
                $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
                $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $allPlayer->expire_date, "reseller_id" => $allPlayer->activated_by, "user_id" => $allPlayer->id, "mac_address" => $allPlayer->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint]);
                //end updating trans data
            }
            $returnData["status"] = true;
            $returnData["msg"] = "User updated successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" => $allPlayer];
            $returnData["httpCode"] = "200";
            
        } else {
            $returnData["msg"] = "User could not be updated this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C20012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }
    
    private function __editHushPlay($reqData, $returnData)
    {
        $hushPlay = HushPlayDevice::find($reqData['requestData']['userId']);
        //print_r($iboreseller);exit;
        $hushPlay->mac_address = $reqData['requestData']['macAddress'];
        if(!empty($reqData['requestData']['appType'])){
            $hushPlay->app_type = $reqData['requestData']['appType'];
        }
        if(!empty($reqData['requestData']['email'])){
            $hushPlay->email = $reqData['requestData']['email'];
        }
        $hushPlay->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], $hushPlay->expire_date);
        $hushPlay->activated_by = $reqData['requestData']['updatedBy'];
        if ($reqData['requestData']['isTrail'] !== $hushPlay->is_trial) {
            $hushPlay->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 3 : 2);
        }
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance 
        $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }
        if ($hushPlay->save()) {
            if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && $reqData['requestData']['playlist'][0]['playListUrl'] !== '') {
                $deleted = HushPlaylist::where('device_id', $hushPlay->_id)->delete();

                $insertPlaylist = [];
                foreach ($reqData['requestData']['playlist'] as $key => $value) {
                    if (!empty($value['playListUrl'])) {
                        $insertPlaylist[] =
                            array(
                                'device_id' => $hushPlay->_id,
                                'playlist_name' => $value['playListName'] ?? "",
                                'url' => $value['playListUrl'] ?? "",
                                'created_at' => date('Y-m-d H:i:s')
                            );
                    }
                }
                $iboPlaylistUrl = HushPlaylist::insert($insertPlaylist);
            }
            // if (!empty($iboPlaylistUrl)) {
            if ($reqData['requestData']['isTrail'] !== $hushPlay->is_trial) {
                //update reseller credit point
                $this->__updateCreditPoint($reqData['requestData']['updatedBy'], $reqData['requestData']['isTrail']);
                //end updating credit popint of reseller

                //update here trans data
                $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
                $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $hushPlay->expire_date, "reseller_id" => $hushPlay->activated_by, "user_id" => $hushPlay->id, "mac_address" => $hushPlay->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint]);
                //end updating trans data
            }
            $returnData["status"] = true;
            $returnData["msg"] = "User updated successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" => $hushPlay];
            $returnData["httpCode"] = "200";
            
        } else {
            $returnData["msg"] = "User could not be updated this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C20012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }

    public function login(Request $request)
    {
        $returnData = array("status" => false, "statusCode" => "10014", "data" => false, "msg" => "Something went wrong.", "httpCode" => 501);
        try {
            if ($request->isJson()) {
                $reqData = $request->all();
                if (!empty($reqData)) {
                    $email = isset($reqData['email']) ? $reqData['email'] : "";
                    $password = isset($reqData['password']) ? $reqData['password'] : "";
                    if (!empty($email) && !empty($password)) {
                        $reseller = IBOReseller::where('email', $email)->first();
                        if(!empty($reseller) && $reseller->is_disable_by_admin == 1){
                            $returnData = array("status" => false, "statusCode" => "10091", "data" => $reseller, "msg" => "You are blocked by Admin Kindly contact admin support team for your resolution - WhatsApp No: +971557660041, Email ID: dev@iboplayerapp.de  ", "httpCode" => 401);
                             return new JsonResponse($returnData);
                        }
                        if (!empty($reseller) && in_array($reseller->group_id, ['1', '2']) && Hash::check($password, $reseller->password)) {

                            if ($reseller->group_id == '2' && $this->__checkExpireDate($reseller->expiry_date) == false) {
                                $returnData = array("status" => false, "statusCode" => "10091", "data" => false, "msg" => "Your subscription is expired!", "httpCode" => 422);
                                return new JsonResponse($returnData);
                            }
                            if (in_array($reseller->status, [0, 2]) ) {
                                $returnData = array("status" => false, "statusCode" => "10091", "data" => false, "msg" => ($reseller->status == 0) ? "Your account has been disabled!" : "Your account has been blocked, kindly contact admin to activate!", "httpCode" => 403);
                                return new JsonResponse($returnData);
                            }
                            
                            //for reseller weblogo
                            // if ($reseller->group_id == '2' && $reseller->parent_reseller_id != '0'){
                            //     $parentReseller = IBOReseller::select('web_logo')->where('id', $reseller->parent_reseller_id)->first();
                            //     $reseller->web_logo = $parentReseller->web_logo;
                            // }
                            //end for reseller web logo
                            
                                //update here last login time 
                            IBOReseller::where('id', $reseller->id)->update([
                                'last_login_time' => date('Y-m-d H:i:s'),
                                'login_ip' => $request->ip()
                            ]);
                            
                            $returnData = array("status" => true, "statusCode" => "00000", "data" => ['IBOReseller' => $reseller], "msg" => "User Logged in Successfully.", "token" => md5("Iboplayer") . date('ymdhis'), "httpCode" => 200);
                        } else {
                            $returnData = array("status" => false, "statusCode" => "10091", "data" => false, "msg" => "Failed to login!", "httpCode" => 403);
                        }
                    } else {
                        $returnData = array("status" => false, "statusCode" => "10092", "data" => false, "msg" => "Invalid inputs!", "httpCode" => 422);
                    }
                }
            } else {
                $returnData = array("status" => false, "statusCode" => "10093", "data" => false, "msg" => "Invalid request type!", "httpCode" => 422);
            }
        } catch (\Exception $e) {
            return new JsonResponse(array("status" => false, "statusCode" => "10094", "data" => false, "msg" => env('APP_DEBUG') ? $e->getMessage() : "Something went wrong, please try after some time!"), 502);
        }
        return new JSonResponse($returnData, $returnData['httpCode']);
    }

    private function __checkExpireDate($checkDate)
    {
        $today = date('Y-m-d');
        $expireDate    = date('Y-m-d', strtotime($checkDate));
        if ($expireDate < $today) {
            return false;
        }
        return true;
    }

    private function __setExpiryDate($isTrail, $oldExpDate, $module = '')
    {
        if(strtotime($oldExpDate) < strtotime(date('Y-m-d'))){
            $oldExpDate = date('Y-m-d');
        }
        
        switch ($isTrail) {
            case '1':
                $date = date('Y-m-d', strtotime($oldExpDate . '+1 week'));
                break;
            case '2':
                $date = date('Y-m-d', strtotime($oldExpDate . '+1 year'));
                break;
            case '3':
                if($module == 'ABEPLAYERTV'){
                    $date = date('Y-m-d', strtotime($oldExpDate . '+10 years'));
                }else{
                    $date = date('Y-m-d', strtotime($oldExpDate . '+30 years'));
                }
                break;
            default:
                $date = date('Y-m-d', strtotime($oldExpDate . '+1 week'));
                break;
        }
        return $date;
    }

    private function __updateCreditPoint($resellerId, $trailPackage)
    {

        $reseller = IBOReseller::find($resellerId);
        $minusCredit  = $this->__getCreditPointVal($trailPackage);
        $reseller->credit_point = ($reseller->credit_point - $minusCredit);
        return $reseller->save();
    }

    private function __getCreditPointVal($trailPackage)
    {
        $minusCredit = 0;
        if ($trailPackage == 1) {
            $minusCredit = 0;
        } else if ($trailPackage == 2) {
            $minusCredit = 1;
        } else if ($trailPackage == 3) {
            $minusCredit = 2;
        }
        return $minusCredit;
    }

    public function getUserList(Request $request)
    {
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];
            if ($request->isJson()) {
                $validatedData = Validator::make($reqData, [
                    'module' => ['required', Rule::in(config('modules.userListModules'))],
                    'channelId' => ['required', Rule::in(config('channels.userListChannel'))],
                    'requestId' => 'required',
                    'requestData.resellerId' => 'required',
                    'requestData.groupId' => 'required',
                ]);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }
                if ($reqData['requestData']['groupId'] == "2") {
                    $usersList = UserActiTranLogs::where('reseller_id', $reqData['requestData']['resellerId'])->with('resellerDetail:ibocdn_resellers.id,email')->orderBy('created_at', 'desc');
                    $archSearchMac = ArchUserActiTransLogs::where('reseller_id', $reqData['requestData']['resellerId'])->with('resellerDetail:ibocdn_resellers.id,email')->orderBy('created_at', 'desc');
                   $usersList = $usersList->union($archSearchMac)->get();
                    //$iboMasaUser =  MASAPlayer::where('reseller_id', $reqData['requestData']['resellerId'])->with('playlistUrl')->limit('500')->get()->toArray();
                    // $virginiaUser =  VirginiaDevice::where('activated_by', $reqData['requestData']['resellerId'])->with('playlistUrl')->limit('500')->orderBy('_id desc')->get()->toArray();
                    // $iboAppDevice =  IBOAppDevice::where('activated_by', $reqData['requestData']['resellerId'])->with('playlistUrl')->limit('500')->orderBy('_id desc')->get()->toArray();
                    //$iboAppDevice = [];
                } else {
                   // $iboMasaUser =  MASAPlayer::with('playlistUrl')->limit('500')->get()->toArray();
                   $usersList = UserActiTranLogs::with('resellerDetail')->limit('500')->orderBy('created_at', 'desc');
                   $archSearchMac = ArchUserActiTransLogs::with('resellerDetail')->limit('500')->orderBy('created_at', 'desc');
                   $usersList = $usersList->union($archSearchMac)->get();
                    // $virginiaUser =  VirginiaDevice::with('playlistUrl')->limit('500')->orderBy('_id desc')->get()->toArray();
                    // $iboAppDevice =  IBOAppDevice::with('playlistUrl')->limit('500')->orderBy('_id desc')->get()->toArray();
                    //$iboAppDevice = [];
                }

                if (!empty($usersList) ) {
                    $newIboMasa = [];
                    // foreach ($iboMasaUser as $mKey => $mUser) {
                    //     $newIboMasa[] = $this->__mapCustomKeyPair($mUser, 'MASA');
                    // }
                    $iboMasaUser = $usersList;
                    // if (!empty($virginiaUser)) {
                    //     $newvir = [];
                    //     foreach ($virginiaUser as $key => $user) {
                    //         $newvir[] = $this->__mapCustomKeyPair($user, 'VIRGINIA');
                    //     }
                    //     $iboMasaUser = array_merge($iboMasaUser, $newvir);
                    // }
                    // if (!empty($iboAppDevice)) {
                    //     $newIboApp = [];
                    //     foreach ($iboAppDevice as $key => $user) {
                    //         $newIboApp[] = $this->__mapCustomKeyPair($user, 'IBOAPP');
                    //     }
                    //     $iboMasaUser = array_merge($iboMasaUser, $newIboApp);
                    // }
                    $returnData["status"] = true;
                    $returnData["msg"] = "User list fetched successfully!";
                    $returnData["statusCode"] = "000000";
                    $returnData["data"] = ["IBOMasaUser" => $iboMasaUser];
                    $returnData["httpCode"] = "200";
                } else {
                    $returnData["msg"] = "User list can not be fetched this moment, Please try after sometime.!";
                    $returnData["statusCode"] = "C10012";
                    $returnData["httpCode"] = "501";
                }
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
    }

    public function __mapCustomKeyPair($user, $module = 'VIRGINIA')
    {

        if (isset($user['_id'])) {

            $user['id'] = $user['_id'];
            unset($user['_id']);
        }
        if (isset($user['activated_by'])) {
            $user['reseller_id'] = $user['activated_by'];
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
        return $user;
    }

    public function getUserById(Request $request)
    {
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];
            if ($request->isJson()) {
                $validatedData = Validator::make($reqData, [
                    'module' => ['required', Rule::in(config('modules.userDetailModules'))],
                    'channelId' => ['required', Rule::in(config('channels.userDetailChannel'))],
                    'requestId' => 'required',
                    'requestData.resellerId' => 'required',
                    'requestData.userId' => 'required',
                ]);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }
                if ($reqData['module'] == 'VIRGINIA') {
                    $iboMasaUser =  VirginiaDevice::where('_id', $reqData['requestData']['userId'])->with('playlistUrl')->first();
                    $iboMasaUser = !empty($iboMasaUser) ? $this->__mapCustomKeyPair($iboMasaUser, 'VIRGINIA') : [];
                } else if ($reqData['module'] == 'MASA') {
                    $iboMasaUser =  MASAPlayer::where('id', $reqData['requestData']['userId'])->with('playlistUrl')->first();
                    $iboMasaUser = !empty($iboMasaUser) ? $this->__mapCustomKeyPair($iboMasaUser, 'MASA') : [];
                } else if ($reqData['module'] == 'IBOAPP') {
                    $iboMasaUser =  IBOAppDevice::where('_id', $reqData['requestData']['userId'])->with('playlistUrl')->first();
                    $iboMasaUser = !empty($iboMasaUser) ? $this->__mapCustomKeyPair($iboMasaUser, 'IBOAPP') : [];
                }

                if (!empty($iboMasaUser)) {

                    $returnData["status"] = true;
                    $returnData["msg"] = "User details fetched successfully!";
                    $returnData["statusCode"] = "000000";
                    $returnData["data"] = ["IBOMasaUser" => $iboMasaUser];
                    $returnData["httpCode"] = "200";
                } else {
                    $returnData["msg"] = "User details not found, Please try after sometime.!";
                    $returnData["statusCode"] = "C10012";
                    $returnData["httpCode"] = "501";
                }
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
    }

    public function getResellerById(Request $request)
    {
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];
            if ($request->isJson()) {
                $validatedData = Validator::make($reqData, [
                    'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER'])],
                    'requestId' => 'required',
                    'requestData.resellerId' => 'required'
                ]);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }
                $IBOReseller =  IBOReseller::where('id', $reqData['requestData']['resellerId'])->first();
                if (!empty($IBOReseller)) {
                    $IBOReseller['application_allowed'] = json_decode( $IBOReseller['application_allowed']);
                    $applications = [];
                    if(!empty($reqData['requestData']['createdBy'])){
                        $creatorReseller =  IBOReseller::where('id', $reqData['requestData']['createdBy'])->first();
                        if($creatorReseller['group_id'] == '2'){
                            if(!empty($creatorReseller['application_allowed'])){
                                $applications = Applications::select('id', 'app_name')->where('status', 1)
                                ->whereIn('id', json_decode($creatorReseller['application_allowed']))->get();
                            }else{
                                $applications = [];
                            }
                            
                        }else{
                            $applications = Applications::select('id', 'app_name')->where('status', 1)->get();
                        }
                    }
                    
                    $IBOReseller['applications'] = $applications;
                    $returnData["status"] = true;
                    $returnData["msg"] = "Reseller detail fetched successfully!";
                    $returnData["statusCode"] = "000000";
                    unset($IBOReseller['password']);
                    $returnData["data"] = ["IBOReseller" => $IBOReseller];
                    $returnData["httpCode"] = "200";
                } else {
                    $returnData["msg"] = "Reseller detail can not be fetched this moment, Please try after sometime.!";
                    $returnData["statusCode"] = "C10012";
                    $returnData["httpCode"] = "501";
                }
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
    }

    /**
     * 
     */
    public function searchUser(Request $request)
    {
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];
            if ($request->isJson()) {
                $validatedData = Validator::make($reqData, [
                    'module' => ['required', Rule::in(config('modules.searchUserModules'))],
                    'channelId' => ['required', Rule::in(config('channels.searchUserChannel'))],
                    'requestId' => 'required',
                    'requestData.macAddress' => 'required|min:3',
                ]);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }
                $reqData['requestData']['macAddress'] = strtolower($reqData['requestData']['macAddress']);
                if ($reqData['module'] == 'MASA') {
                    $iboMasaUser =  MASAPlayer::where('mac_address', $reqData['requestData']['macAddress'])->with('playlistUrl')->get();
                } else if ($reqData['module'] == 'VIRGINIA') {
                    $iboMasaUser =  VirginiaDevice::where('mac_address', $reqData['requestData']['macAddress'])->with('playlistUrl')->get();
                } else if ($reqData['module'] == 'IBOAPP') {
                    $iboMasaUser =  IBOAppDevice::where('mac_address', $reqData['requestData']['macAddress'])->with('playlistUrl')->get();
                } else if ($reqData['module'] == 'BOBPLAYER') {
                    $iboMasaUser =  BOBPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])->with('playlistUrl')->get();
                }else if($reqData['module'] == 'BAYIPTV'){
                    $iboMasaUser = $this->__searchBayIPTVUser($reqData);
                    return new JsonResponse($iboMasaUser, $iboMasaUser['httpCode']);
                }else if ($reqData['module'] == 'ABEPLAYERTV') {
                    $iboMasaUser =  ABEPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])->
                    whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))->
                    whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))->with('playlistUrl')->get();
                }else if ($reqData['module'] == 'MACPLAYER') {
                    $iboMasaUser =  MacPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])->with('playlistUrl')->get();
                }else if ($reqData['module'] == 'KTNPLAYER') {
                    $iboMasaUser =  KtnPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])->
                    whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))->
                    whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))->with('playlistUrl')->get();
                }else if ($reqData['module'] == 'ALLPLAYER') {
                    $iboMasaUser =  AllPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])->
                    whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))->
                    whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))->with('playlistUrl')->get();
                }else if ($reqData['module'] == 'HUSHPLAY') {
                    $iboMasaUser =  HushPlayDevice::where('mac_address', $reqData['requestData']['macAddress'])->
                    whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))->
                    whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))->with('playlistUrl')->get();
                }else if ($reqData['module'] == 'FAMILYPLAYER') {
                    $iboMasaUser =  FamilyPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])->
                    whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))->
                    whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))->with('playlistUrl')->get();
                } else if ($reqData['module'] == 'IBOSSPLAYER') {
                    $iboMasaUser =  IBOSSPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])->
                    whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))->
                    whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))->with('playlistUrl')->get();
                }else if ($reqData['module'] == 'KING4KPLAYER') {
                    $iboMasaUser =  King4kPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])->
                    whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))->
                    whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))->with('playlistUrl')->get();
                } else if ($reqData['module'] == 'IBOXXPLAYER') {
                    $iboMasaUser =  IBOXXPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])->
                    whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))->
                    whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))->with('playlistUrl')->get();
                }else if ($reqData['module'] == 'BOBPROTV') {
                    $iboMasaUser =  BOBProTvDevice::where('mac_address', $reqData['requestData']['macAddress'])->
                    whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))->
                    whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))->with('playlistUrl')->get();
                } else if ($reqData['module'] == 'IBOSTB') {
                    $iboMasaUser =  IBOStbDevice::where('mac_address', $reqData['requestData']['macAddress'])->
                    whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))->
                    whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))->with('playlistUrl')->get();
                }else if ($reqData['module'] == 'IBOSOL') {
                    $iboMasaUser =  IBOSOLDevice::where('mac_address', $reqData['requestData']['macAddress'])->
                    whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))->
                    whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))->with('playlistUrl')->get();
                }else if ($reqData['module'] == 'DUPLEX') {
                    $iboMasaUser =  DuplexDevice::where('mac_address', $reqData['requestData']['macAddress'])->
                    whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))->
                    whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))->with('playlistUrl')->get();
                }else if ($reqData['module'] == 'FLIXNET') {
                    //return new JsonResponse($reqData, 200);
                    $iboMasaUser =  FlixNetDevice::where('mac_address', $reqData['requestData']['macAddress'])->
                    whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))->
                    whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))->with('playlistUrl')->get();
                }
            
                if (sizeof($iboMasaUser) !== 0) {
                    $newIboMasa = [];
                    //check here if already activated
                    $alreadyActivated = UserActiTranLogs::where('module', $reqData['module'])->
                    where('mac_address', $reqData['requestData']['macAddress'])->
                    whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))->
                    whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))->count();
                    //end checking here already activated from our system
                    
                    
                    foreach ($iboMasaUser as $mKey => $mUser) {
                        $newIboMasa[] = $this->__mapCustomKeyPair($mUser, $reqData['module']);
                    }
                    $iboMasaUser = $newIboMasa;
                    $returnData["status"] = true;
                    $returnData["msg"] = "User found successfully!";
                    $returnData["statusCode"] = "000000";
                    $returnData["data"] = ["IBOMasaUser" => $iboMasaUser, "alreadyActivated" => $alreadyActivated];
                    $returnData["httpCode"] = "200";
                } else {
                    $returnData["msg"] = "User not found, Please input correct Mac Address!";
                    $returnData["statusCode"] = "C10012";
                    $returnData["httpCode"] = "202";
                }
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
    }

    protected function __searchBayIPTVUser($reqData){
        $response = BAYIPTVHelper::checkMac($reqData);
        if(!empty($response)){
            if(isset($response['message']['status']) && $response['message']['status'] == '200'){
                $returnData["status"] = true;
                $returnData["msg"] = "No device found in system!";
                $returnData["statusCode"] = "000000";
                $returnData["data"] = ["IBOMasaUser" => [["mac_address"=> $reqData['requestData']['macAddress'], "msg"=>"No Device found in system!"]]];
                $returnData["httpCode"] = "200";
            }else if(isset($response['message']['status']) && $response['message']['status'] == '202'){
                $returnData["status"] = true;
                $returnData["msg"] = $response['message']['txt'];
                $returnData["statusCode"] = "CBAY202";
                $returnData["data"] = ["IBOMasaUser" => [["mac_address"=> $reqData['requestData']['macAddress'], "msg"=>$response['message']['txt']]]];
                $returnData["httpCode"] = "202";
            }else if(isset($response['message']['status']) && $response['message']['status'] == '400'){
                $returnData["status"] = false;
                $returnData["msg"] = $response['message']['txt'];
                $returnData["statusCode"] = "CBAY400";
                //$returnData["data"] = ["IBOMasaUser" => [["mac_address"=> $reqData['requestData']['macAddress'], "msg"=>$response['message']['txt']]]];
                $returnData["httpCode"] = "400";
            }
        }else{
            $returnData["statusCode"] = "CBAY422";
            $returnData["msg"] = "User can not be found!";
            $returnData["httpCode"] = "422";
        }
        return $returnData;
    }

    protected function __addActivationTranLogs($tranData)
    {
        //$resId, $userId, $module, $creditPoint
        $userTran = new \App\UserActiTranLogs;
        $userTran->reseller_id = $tranData['reseller_id'];
        $userTran->user_id = $tranData['user_id'];
        $userTran->module = $tranData['module'];
        $userTran->credit_point = $tranData['credit_point'];
        $userTran->mac_address = $tranData['mac_address'] ?? "";
        $userTran->box_expiry_date = $tranData['expiry_date'] ?? "";
        $userTran->comment = $tranData['activation_remarks'] ?? "";
        return $userTran->save();
    }

    public function addCreditPoint(Request $request)
    {
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];
            if ($request->isJson()) {
                $validatedData = Validator::make($reqData, [
                    'module' => ['required', Rule::in(['MASA', 'VIRGINIA', 'IBOAPP', 'ABEPLAYERTV', "BOBPLAYER"])],
                    'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER'])],
                    'requestId' => 'required',
                    'resellerId' => 'required',
                    'createdBy' => 'required',
                    'creditPoint' => 'required',
                    'groupId' => 'required'
                ]);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }
                
                $iboreseller = IBOReseller::find($reqData['resellerId']);
                $iboreseller->credit_point = ($iboreseller->credit_point == 0) ? $reqData['creditPoint'] : ($iboreseller->credit_point + $reqData['creditPoint']);
				$creditorRes = IBOReseller::find($reqData['createdBy']);
				
				if($creditorRes->group_id == '2' && $iboreseller->parent_reseller_id != $creditorRes->id){
			    	$returnData["msg"] = 'Invalid activity perfomed, you will be blocked forever!';
                    $returnData["statusCode"] = "C10422";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
				    
				}
				
				if($creditorRes->credit_point < $reqData['creditPoint'] ){
					$returnData["msg"] = 'You do not have enough credit point balance to assign sub reseller!';
                    $returnData["statusCode"] = "C10422";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
				}
                if ($iboreseller->save()) {
                    //if ($reqData['groupId'] == 2) {
                        //$creditorRes = IBOReseller::find($reqData['createdBy']);
                        $creditorRes->credit_point = ($creditorRes->credit_point - $reqData['creditPoint']);
                        $creditorRes->save();
                    //}
                    //update into resecrdittarans logs
                    $this->__addResellerCreditShareLogs($reqData);
                    //update into resecrdittarans logs
                    $returnData["status"] = true;
                    $returnData["msg"] = "Reseller credit  successfully added!";
                    $returnData["statusCode"] = "000000";
                    unset($iboreseller['password']);
                    unset($iboreseller['id']);
                    $returnData["data"] = ["IBOReseller" => $iboreseller];
                    $returnData["httpCode"] = "200";
                } else {
                    $returnData["msg"] = "Reseller credit point can not be added this moment, Please try after sometime.!";
                    $returnData["statusCode"] = "C10012";
                    $returnData["httpCode"] = "501";
                }
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
    }

    /**
     * Require fields createdBy,resellerId,creditPoint,createdBy
     */
    private function __addResellerCreditShareLogs($reqData, $trType = 'CREDIT')
    {
        $reseCredit = new ResellerCreditPointTranLogs;
        $reseCredit->debitor_id = $reqData['createdBy'];
        $reseCredit->creditor_id = $reqData['resellerId'];
        $reseCredit->credit_point = $reqData['creditPoint'];
        $reseCredit->created_by = $reqData['createdBy'];
        $reseCredit->tr_type = $trType;
        return $reseCredit->save();
    }

    public function resellerListAssigment(Request $request)
    {
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];
            if ($request->isJson()) {
                $validatedData = Validator::make($reqData, [
                    'module' => ['required', Rule::in(['MASA', 'VIRGINIA', 'IBOAPP', 'ABEPLAYERTV', "BOBPLAYER"])],
                    'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER'])],
                    'requestId' => 'required',
                    'createdBy' => 'required',
                    'subResellerId' => 'required',
                    'groupId' => 'required'
                ]);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }
                $iboreseller = IBOReseller::select('id', 'name', 'email')->where('status', '!=', '2')->where('id', '!=', $reqData['subResellerId'])->where('parent_reseller_id', 0)->where('group_id', '2')->get();
                if (!empty($iboreseller)) {
                    $returnData["status"] = true;
                    $returnData["msg"] = "Reseller list fetched  successfully added!";
                    $returnData["statusCode"] = "000000";
                    $returnData["data"] = ["IBOReseller" => $iboreseller];
                    $returnData["httpCode"] = "200";
                } else {
                    $returnData["msg"] = "Reseller credit point can not be added this moment, Please try after sometime.!";
                    $returnData["statusCode"] = "C10012";
                    $returnData["httpCode"] = "501";
                }
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
    }

    public function assigmentParentRes(Request $request)
    {
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];
            if ($request->isJson()) {
                $validatedData = Validator::make($reqData, [
                    'module' => ['required', Rule::in(['MASA', 'VIRGINIA', 'IBOAPP', 'ABEPLAYERTV', "BOBPLAYER"])],
                    'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER'])],
                    'requestId' => 'required',
                    'requestData.subResellerId' => 'required|exists:App\IBOReseller,id',
                    'requestData.resellerId' => 'required|exists:App\IBOReseller,id'
                ]);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }
                $iboreseller = IBOReseller::find($reqData['requestData']['subResellerId']);

                if (!empty($iboreseller)) {
					$isSwitch = $reqData['requestData']['isSwitchReseller'] ?? false;
					if($isSwitch){
						$oldParentReseller = IBOReseller::find($iboreseller->parent_reseller_id);
						$oldParentReseller->credit_point = ($iboreseller->credit_point + $oldParentReseller->credit_point);
						$oldParentReseller->save();
						$iboreseller->credit_point = 0;
					}
					$iboreseller->parent_reseller_id = $reqData['requestData']['resellerId'];
                    if ($iboreseller->save()) {
                        $returnData["status"] = true;
                        $returnData["msg"] = "SubReseller assignment successfully!";
                        $returnData["statusCode"] = "000000";
                        $returnData["data"] = ["IBOReseller" => $iboreseller];
                        $returnData["httpCode"] = "200";
                    } else {
                        $returnData["msg"] = "Reseller assignment failed, Please check your input data and retry after sometime.!";
                        $returnData["statusCode"] = "C10012";
                        $returnData["httpCode"] = "501";
                    }
                } else {
                    $returnData["msg"] = "Subreseller detail not found, Please try after sometime.!";
                    $returnData["statusCode"] = "C10012";
                    $returnData["httpCode"] = "501";
                }
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
    }
    
    public function resellerSwitchListAssigment(Request $request)
    {
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];
            if ($request->isJson()) {
                $validatedData = Validator::make($reqData, [
                    'module' => ['required', Rule::in(['MASA', 'VIRGINIA', 'IBOAPP', 'ABEPLAYERTV', "BOBPLAYER"])],
                    'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER'])],
                    'requestId' => 'required',
                    'createdBy' => 'required',
                    'subResellerId' => 'required',
                    'groupId' => 'required'
                ]);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }
                $iboreseller = IBOReseller::select('id', 'name', 'email')->where('status', '!=', '2')->where('id', '!=', $reqData['subResellerId'])->where('parent_reseller_id', 0)->where('group_id', '2')->get();
                if (!empty($iboreseller)) {
                    $returnData["status"] = true;
                    $returnData["msg"] = "Reseller list fetched  successfully added!";
                    $returnData["statusCode"] = "000000";
                    $returnData["data"] = ["IBOReseller" => $iboreseller];
                    $returnData["httpCode"] = "200";
                } else {
                    $returnData["msg"] = "Reseller list not found with provided criteria.!";
                    $returnData["statusCode"] = "C10012";
                    $returnData["httpCode"] = "501";
                }
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
    }



    public function getMongoWorking(Request $request)
    {

        $user = "baimaoli";
        $pwd = 'baimaoli1992';

        $mongo = new Mongo("mongodb://${user}:${pwd}@162.240.7.147:27017");
        $collection = $mongo->db_name->collection;
        $result = $collection->find()->toArray();

        print_r($result);
    }

    public function getUserActiTranLogs(Request $request)
    {

        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];
            if ($request->isJson()) {
                $validatedData = Validator::make($reqData, [
                    'module' => ['required', Rule::in(['MASA', 'VIRGINIA', 'IBOAPP', 'ABEPLAYERTV', "BOBPLAYER"])],
                    'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER'])],
                    'requestId' => 'required',
                    'userId' => 'required',
                    'groupId' => 'required'
                ]);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }
                if ($reqData['groupId'] == 2) {
                    $userActiLogs = UserActiTranLogs::where('reseller_id', $reqData['userId'])->with('resellerDetail')->orderBy('created_at', 'desc')->get();
                } else {
                    $userActiLogs = UserActiTranLogs::whereDate('created_at', Carbon::today())->with('resellerDetail')->orderBy('created_at', 'desc')->get();
                }

                if (sizeof($userActiLogs) > 0) {
                    $returnData["status"] = true;
                    $returnData["msg"] = "Transaction Logs found!";
                    $returnData["statusCode"] = "000000";
                    $returnData["data"] = ["UserActiTranLogs" => $userActiLogs];
                    $returnData["httpCode"] = "200";
                } else {
                    $returnData["msg"] = "User activation transaction logs not found!";
                    $returnData["statusCode"] = "C10012";
                    $returnData["httpCode"] = "501";
                }
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
    }

    public function getCreditShareTranLogs(Request $request)
    {

        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];
            if ($request->isJson()) {
                $validatedData = Validator::make($reqData, [
                    'module' => ['required', Rule::in(['MASA', 'VIRGINIA', 'IBOAPP', 'ABEPLAYERTV', "BOBPLAYER"])],
                    'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER'])],
                    'requestId' => 'required',
                    'userId' => 'required',
                    'groupId' => 'required'
                ]);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }


                if ($reqData['groupId'] == 2) {
                    $userActiLogs = ResellerCreditPointTranLogs::select('id','debitor_id','creditor_id','credit_point', 'tr_type', 'created_by', 'created_at')->where('created_by', $reqData['userId'])->with('creditorDetail')->with('debitorDetail')->with('creator')->orderBy('created_at', 'desc')->get();
                } else {
                    $userActiLogs = ResellerCreditPointTranLogs::select('id','debitor_id','creditor_id','credit_point', 'tr_type', 'created_by', 'created_at')->whereDate('created_at', Carbon::today())->with('creditorDetail')->with('debitorDetail')->with('creator')->orderBy('created_at', 'desc')->get();
                }

                if (sizeof($userActiLogs) > 0) {
                    $returnData["status"] = true;
                    $returnData["msg"] = "Transaction Logs found!";
                    $returnData["statusCode"] = "000000";
                    $returnData["data"] = ["ResellerCreditShareLog" => $userActiLogs];
                    $returnData["httpCode"] = "200";
                } else {
                    $returnData["msg"] = "User activation transaction logs not found!";
                    $returnData["statusCode"] = "C10012";
                    $returnData["httpCode"] = "501";
                }
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
    }


    public function addPlaylistDetail(Request $request)
    {
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];
            return new JsonResponse([
                "status" => false,
                "statusCode" => "CX0010",
                "msg" => "This feature has been disabled, please try after sometime!",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
            if ($request->isJson()) {
                $validArr = [
                    'module' => ['required', Rule::in(config('modules.addUserModules'))],
                    'channelId' => ['required', Rule::in(config('channels.addUserChannel'))],
                    'requestId' => 'required',
                    'requestData.createdBy' => 'required',
                    'requestData.macAddress' => 'required',
                    'requestData.playlist' => 'required'
                ];

                $addValidMac = [];

                $validatedData = Validator::make($reqData, array_merge($validArr, $addValidMac));
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }


                if ($reqData['module'] == 'MASA') {
                    $macDetails = MASAPlayer::where('mac_address', $reqData['requestData']['macAddress'])
                    ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                    ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))
                    ->with('playlistUrl')->first();
                    $returnData = $this->__addStreamInMasaPlayer($reqData, $macDetails);
                } else if ($reqData['module'] == 'VIRGINIA') {
                    $macDetails = VirginiaDevice::where('mac_address', $reqData['requestData']['macAddress'])
                    ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                    ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))
                    ->with('playlistUrl')->first();
                    $returnData = $this->__addStreamInVirginiaPlayer($reqData, $macDetails);
                } else if ($reqData['module'] == 'IBOAPP') {
                    $macDetails = IBOAppDevice::where('mac_address', $reqData['requestData']['macAddress'])
                    ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                    ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))
                    ->with('playlistUrl')->first();
                    $returnData = $this->__addStreamInIBOAPPPlayer($reqData, $macDetails);
                } else if ($reqData['module'] == 'KTNPLAYER') {
                    $macDetails = KtnPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])
                    ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                    ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))
                    ->with('playlistUrl')->first();
                    $returnData = $this->__addStreamInKTNPlayer($reqData, $macDetails);
                } else if ($reqData['module'] == 'ABEPLAYERTV') {
                    $macDetails = ABEPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])
                    ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                    ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))
                    ->with('playlistUrl')->first();
                    $returnData = $this->__addStreamInABEPlayer($reqData, $macDetails);
                }else if ($reqData['module'] == 'BOBPLAYER') {
                    $macDetails = BOBPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])
                    ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                    ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))
                    ->with('playlistUrl')->first();
                    $returnData = $this->__addStreamInBOBPlayer($reqData, $macDetails);
                }else if ($reqData['module'] == 'MACPLAYER') {
                    $macDetails = MacPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])
                    ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                    ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))
                    ->with('playlistUrl')->first();
                    $returnData = $this->__addStreamInMACPlayer($reqData, $macDetails);
                }else if ($reqData['module'] == 'ALLPLAYER') {
                    $macDetails = AllPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])
                    ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                    ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))
                    ->with('playlistUrl')->first();
                    $returnData = $this->__addStreamInALLPlayer($reqData, $macDetails);
                }else if ($reqData['module'] == 'HUSHPLAY') {
                    $macDetails = HushPlayDevice::where('mac_address', $reqData['requestData']['macAddress'])
                    ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                    ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))
                    ->with('playlistUrl')->first();
                    $returnData = $this->__addStreamInHUSHPlayer($reqData, $macDetails);
                }else if ($reqData['module'] == 'FAMILYPLAYER') {
                    $macDetails = FamilyPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])
                    ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                    ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))
                    ->with('playlistUrl')->first();
                    $returnData = $this->__addStreamInFAMILYPlayer($reqData, $macDetails);
                }else if ($reqData['module'] == 'KING4KPLAYER') {
                    $macDetails = King4kPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])
                    ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                    ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))
                    ->with('playlistUrl')->first();
                    $returnData = $this->__addStreamInKING4KPlayer($reqData, $macDetails);
                }else if ($reqData['module'] == 'IBOSSPLAYER') {
                    $macDetails = IBOSSPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])
                    ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                    ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))
                    ->with('playlistUrl')->first();
                    $returnData = $this->__addStreamInIBOSSPlayer($reqData, $macDetails);
                }

                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
    }

    private function __addStreamInMasaPlayer($reqData, $macDetail)
    {
        $returnData = ["status" => false, "msg" => "Failed to add playlist detail!", "statusCode" => "C30010", "httpCode" => "501"];
        $retFlag = false;
        if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && isset($reqData['requestData']['playlist'][0]['url'])) {
            $deleted = MASAPlaylistUrl::where('playlist_id', $macDetail->id)->delete();
            $insertPlaylist = [];
            foreach ($reqData['requestData']['playlist'] as $key => $value) {
                if (!empty($value['url'])) {
                    $insertPlaylist[] =
                        array(
                            'playlist_id' => $macDetail->id,
                            'name' => $value['name'] ?? "",
                            'url' => $value['url'] ?? ""
                        );
                }
            }

            $retFlag = MASAPlaylistUrl::insert($insertPlaylist);
        }
        if ($retFlag) {
            $returnData["status"] = $retFlag;
            $returnData["msg"] = "Playlist added successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" => $retFlag, "PlaylistUrl" => $macDetail->playlist_url];
            $returnData["httpCode"] = "200";
        }
        return $returnData;
    }
    
    private function __addStreamInIBOAPPPlayer($reqData, $macDetail)
    {
        $returnData = ["status" => false, "msg" => "Failed to add playlist detail!", "statusCode" => "C30010", "httpCode" => "501"];
        $retFlag = false;
        if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && isset($reqData['requestData']['playlist'][0]['url'])) {
            $deleted = IBOAppPlaylist::where('device_id', $macDetail->_id)->delete();
            $insertPlaylist = [];
            foreach ($reqData['requestData']['playlist'] as $key => $value) {
                if (!empty($value['url'])) {
                    $insertPlaylist[] =
                        array(
                            'device_id' => $macDetail->id,
                            'url' => isset($value['url']) ? str_replace('amp;', '', $value['url']): "",
                            'playlist_name' => $value['name'] ?? "",
                            'username' => $value['username'] ?? "",
                            'password' => $value['password'] ?? "",
                            'epg_url' => $value['epg_url'] ?? "",
                            'is_protected' => $value['is_protected'] ?? "",
                            'pin' => $value['pin'] ?? "",
                            'playlist_type' => $value['playlist_type'] ?? "general",
                            '__v' => 0
                        );
                }
            }
            $retFlag = IBOAppPlaylist::insert($insertPlaylist);
        }
        if ($retFlag) {
            $returnData["status"] = $retFlag;
            $returnData["msg"] = "Playlist added successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" => $retFlag, "PlaylistUrl" => $macDetail->playlist_url];
            $returnData["httpCode"] = "200";
        }
        return $returnData;
    }

    private function __addStreamInVirginiaPlayer($reqData, $macDetail)
    {
        $returnData = ["status" => false, "msg" => "Failed to add playlist detail!", "statusCode" => "C30010", "httpCode" => "501"];
        $retFlag = false;
        if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && isset($reqData['requestData']['playlist'][0]['url'])) {
            $deleted = VirginiaPlaylistUrl::where('device_id', $macDetail->_id)->delete();
            $insertPlaylist = [];
            foreach ($reqData['requestData']['playlist'] as $key => $value) {
                if (!empty($value['url'])) {
                    $insertPlaylist[] =
                        array(
                            'device_id' => $macDetail->id,
                            'url' => isset($value['url']) ? str_replace('amp;', '', $value['url']): "",
                            'playlist_name' => $value['name'] ?? "",
                            'username' => $value['username'] ?? "",
                            'password' => $value['password'] ?? "",
                            'epg_url' => $value['epg_url'] ?? "",
                            'is_protected' => $value['is_protected'] ?? "",
                            'pin' => $value['pin'] ?? "",
                            'playlist_type' => $value['playlist_type'] ?? "general",
                            '__v' => 0
                        );
                }
            }
            $retFlag = VirginiaPlaylistUrl::insert($insertPlaylist);
        }
        if ($retFlag) {
            $returnData["status"] = $retFlag;
            $returnData["msg"] = "Playlist added successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" => $retFlag, "PlaylistUrl" => $macDetail->playlist_url];
            $returnData["httpCode"] = "200";
        }
        return $returnData;
    }


    public function resetPlaylist(Request $request)
    {
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];

            if ($request->isJson()) {
                $validArr = [
                    'module' => ['required', Rule::in(['MASA', 'VIRGINIA', 'IBOAPP', 'ABEPLAYERTV', "BOBPLAYER"])],
                    'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER'])],
                    'requestId' => 'required',
                    'requestData.createdBy' => 'required',
                    'requestData.macAddress' => 'required'
                ];

                $addValidMac = [];

                $validatedData = Validator::make($reqData, array_merge($validArr, $addValidMac));
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }


                if ($reqData['module'] == 'MASA') {
                    $macDetails = MASAPlayer::where('mac_address', $reqData['requestData']['macAddress'])->with('playlistUrl')->first();
                    if (!empty($macDetails)) {
                        if (empty($macDetails['playlistUrl'])) {
                            $returnData['httpCode'] = '422';
                            $returnData['msg'] = 'No playlist found to be deleted for this mac!';
                            $returnData['statusCode'] = 'C40014';
                            return new JsonResponse($returnData);
                        }
                        $deleted = MASAPlaylistUrl::where('playlist_id', $macDetails->id)->delete();
                        if ($deleted) {
                            $returnData['httpCode'] = '200';
                            $returnData['status'] = true;
                            $returnData['msg'] = 'Playlist reset succefully!';
                            $returnData['statusCode'] = '00000';
                        } else {
                            $returnData['httpCode'] = '422';
                            $returnData['msg'] = 'Reset failed!';
                            $returnData['statusCode'] = 'C40002';
                        }
                    } else {
                        $returnData['httpCode'] = '402';
                        $returnData['msg'] = 'No device found for this mac!';
                        $returnData['statusCode'] = 'C40001';
                    }
                } else if ($reqData['module'] == 'VIRGINIA') {
                    $macDetails = VirginiaDevice::where('mac_address', $reqData['requestData']['macAddress'])->with('playlistUrl')->first();

                    if (!empty($macDetails)) {
                        if (empty($macDetails['playlistUrl'])) {
                            $returnData['httpCode'] = '422';
                            $returnData['msg'] = 'No playlist found to be deleted for this mac!';
                            $returnData['statusCode'] = 'C40014';
                            return new JsonResponse($returnData);
                        }
                        $deleted = VirginiaPlaylistUrl::where('device_id', $macDetails->_id)->delete();
                        if ($deleted) {
                            $returnData['httpCode'] = '200';
                            $returnData['status'] = true;
                            $returnData['msg'] = 'Playlist reset succefully!';
                            $returnData['statusCode'] = '00000';
                        } else {
                            $returnData['httpCode'] = '422';
                            $returnData['msg'] = 'Reset failed!';
                            $returnData['statusCode'] = 'C40002';
                        }
                    } else {
                        $returnData['httpCode'] = '402';
                        $returnData['msg'] = 'No device found for this mac!';
                        $returnData['statusCode'] = 'C40001';
                    }
                } else if ($reqData['module'] == 'IBOAPP') {
                    $macDetails = IBOAppDevice::where('mac_address', $reqData['requestData']['macAddress'])
                    ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                    ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))
                    ->with('playlistUrl')->first();
                    if (!empty($macDetails)) {
                        if (empty($macDetails['playlistUrl'])) {
                            $returnData['httpCode'] = '422';
                            $returnData['msg'] = 'No playlist found to be deleted for this mac!';
                            $returnData['statusCode'] = 'C40014';
                            return new JsonResponse($returnData);
                        }
                        $deleted = IBOAppPlaylist::where('device_id', $macDetails->_id)->delete();
                        if ($deleted) {
                            $returnData['httpCode'] = '200';
                            $returnData['status'] = true;
                            $returnData['msg'] = 'Playlist reset succefully!';
                            $returnData['statusCode'] = '00000';
                        } else {
                            $returnData['httpCode'] = '422';
                            $returnData['msg'] = 'Reset failed!';
                            $returnData['statusCode'] = 'C40002';
                        }
                    } else {
                        $returnData['httpCode'] = '402';
                        $returnData['msg'] = 'No device found for this mac!';
                        $returnData['statusCode'] = 'C40001';
                    }
                }else if ($reqData['module'] == 'ABEPLAYERTV') {
                    $macDetails = ABEPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])
                    ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                    ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))
                    ->with('playlistUrl')->first();
                    if (!empty($macDetails)) {
                        if (empty($macDetails['playlistUrl'])) {
                            $returnData['httpCode'] = '422';
                            $returnData['msg'] = 'No playlist found to be deleted for this mac!';
                            $returnData['statusCode'] = 'C40014';
                            return new JsonResponse($returnData);
                        }
                        $deleted = ABEPlayerPlaylist::where('device_id', $macDetails->_id)->delete();
                        if ($deleted) {
                            $returnData['httpCode'] = '200';
                            $returnData['status'] = true;
                            $returnData['msg'] = 'Playlist reset succefully!';
                            $returnData['statusCode'] = '00000';
                        } else {
                            $returnData['httpCode'] = '422';
                            $returnData['msg'] = 'Reset failed!';
                            $returnData['statusCode'] = 'C40002';
                        }
                    } else {
                        $returnData['httpCode'] = '402';
                        $returnData['msg'] = 'No device found for this mac!';
                        $returnData['statusCode'] = 'C40001';
                    }
                }else if ($reqData['module'] == 'BOBPLAYER') {
                    $macDetails = BOBPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])
                    ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                    ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))
                    ->with('playlistUrl')->first();
                    if (!empty($macDetails)) {
                        if (empty($macDetails['playlistUrl'])) {
                            $returnData['httpCode'] = '422';
                            $returnData['msg'] = 'No playlist found to be deleted for this mac!';
                            $returnData['statusCode'] = 'C40014';
                            return new JsonResponse($returnData);
                        }
                        $deleted = BOBPlayerPlaylist::where('device_id', $macDetails->_id)->delete();
                        if ($deleted) {
                            $returnData['httpCode'] = '200';
                            $returnData['status'] = true;
                            $returnData['msg'] = 'Playlist reset succefully!';
                            $returnData['statusCode'] = '00000';
                        } else {
                            $returnData['httpCode'] = '422';
                            $returnData['msg'] = 'Reset failed!';
                            $returnData['statusCode'] = 'C40002';
                        }
                    } else {
                        $returnData['httpCode'] = '402';
                        $returnData['msg'] = 'No device found for this mac!';
                        $returnData['statusCode'] = 'C40001';
                    }
                }else if ($reqData['module'] == 'MACPLAYER') {
                    $macDetails = MacPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])
                    ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                    ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))
                    ->with('playlistUrl')->first();
                    if (!empty($macDetails)) {
                        if (empty($macDetails['playlistUrl'])) {
                            $returnData['httpCode'] = '422';
                            $returnData['msg'] = 'No playlist found to be deleted for this mac!';
                            $returnData['statusCode'] = 'C40014';
                            return new JsonResponse($returnData);
                        }
                        $deleted = MacPlayerPlaylist::where('device_id', $macDetails->_id)->delete();
                        if ($deleted) {
                            $returnData['httpCode'] = '200';
                            $returnData['status'] = true;
                            $returnData['msg'] = 'Playlist reset succefully!';
                            $returnData['statusCode'] = '00000';
                        } else {
                            $returnData['httpCode'] = '422';
                            $returnData['msg'] = 'Reset failed!';
                            $returnData['statusCode'] = 'C40002';
                        }
                    } else {
                        $returnData['httpCode'] = '402';
                        $returnData['msg'] = 'No device found for this mac!';
                        $returnData['statusCode'] = 'C40001';
                    }
                } else if ($reqData['module'] == 'KTNPLAYER') {
                    $macDetails = KtnPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])
                    ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                    ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))
                    ->with('playlistUrl')->first();
                    if (!empty($macDetails)) {
                        if (empty($macDetails['playlistUrl'])) {
                            $returnData['httpCode'] = '422';
                            $returnData['msg'] = 'No playlist found to be deleted for this mac!';
                            $returnData['statusCode'] = 'C40014';
                            return new JsonResponse($returnData);
                        }
                        $deleted = KtnPlayerPlaylist::where('device_id', $macDetails->_id)->delete();
                        if ($deleted) {
                            $returnData['httpCode'] = '200';
                            $returnData['status'] = true;
                            $returnData['msg'] = 'Playlist reset succefully!';
                            $returnData['statusCode'] = '00000';
                        } else {
                            $returnData['httpCode'] = '422';
                            $returnData['msg'] = 'Reset failed!';
                            $returnData['statusCode'] = 'C40002';
                        }
                    } else {
                        $returnData['httpCode'] = '402';
                        $returnData['msg'] = 'No device found for this mac!';
                        $returnData['statusCode'] = 'C40001';
                    }
                } else if ($reqData['module'] == 'ALLPLAYER') {
                    $macDetails = AllPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])
                    ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                    ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))
                    ->with('playlistUrl')->first();
                    if (!empty($macDetails)) {
                        if (empty($macDetails['playlistUrl'])) {
                            $returnData['httpCode'] = '422';
                            $returnData['msg'] = 'No playlist found to be deleted for this mac!';
                            $returnData['statusCode'] = 'C40014';
                            return new JsonResponse($returnData);
                        }
                        $deleted = AllPlayerPlaylist::where('device_id', $macDetails->_id)->delete();
                        if ($deleted) {
                            $returnData['httpCode'] = '200';
                            $returnData['status'] = true;
                            $returnData['msg'] = 'Playlist reset succefully!';
                            $returnData['statusCode'] = '00000';
                        } else {
                            $returnData['httpCode'] = '422';
                            $returnData['msg'] = 'Reset failed!';
                            $returnData['statusCode'] = 'C40002';
                        }
                    } else {
                        $returnData['httpCode'] = '402';
                        $returnData['msg'] = 'No device found for this mac!';
                        $returnData['statusCode'] = 'C40001';
                    }
                }else if ($reqData['module'] == 'HUSHPLAY') {
                    $macDetails = HushPlayDevice::where('mac_address', $reqData['requestData']['macAddress'])
                    ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                    ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))
                    ->with('playlistUrl')->first();
                    if (!empty($macDetails)) {
                        if (empty($macDetails['playlistUrl'])) {
                            $returnData['httpCode'] = '422';
                            $returnData['msg'] = 'No playlist found to be deleted for this mac!';
                            $returnData['statusCode'] = 'C40014';
                            return new JsonResponse($returnData);
                        }
                        $deleted = HushPlaylist::where('device_id', $macDetails->_id)->delete();
                        if ($deleted) {
                            $returnData['httpCode'] = '200';
                            $returnData['status'] = true;
                            $returnData['msg'] = 'Playlist reset succefully!';
                            $returnData['statusCode'] = '00000';
                        } else {
                            $returnData['httpCode'] = '422';
                            $returnData['msg'] = 'Reset failed!';
                            $returnData['statusCode'] = 'C40002';
                        }
                    } else {
                        $returnData['httpCode'] = '402';
                        $returnData['msg'] = 'No device found for this mac!';
                        $returnData['statusCode'] = 'C40001';
                    }
                } else if ($reqData['module'] == 'FAMILYPLAYER') {
                    $macDetails = FamilyPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])
                    ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                    ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))
                    ->with('playlistUrl')->first();
                    if (!empty($macDetails)) {
                        if (empty($macDetails['playlistUrl'])) {
                            $returnData['httpCode'] = '422';
                            $returnData['msg'] = 'No playlist found to be deleted for this mac!';
                            $returnData['statusCode'] = 'C40014';
                            return new JsonResponse($returnData);
                        }
                        $deleted = FamilyPlayerPlaylist::where('device_id', $macDetails->_id)->delete();
                        if ($deleted) {
                            $returnData['httpCode'] = '200';
                            $returnData['status'] = true;
                            $returnData['msg'] = 'Playlist reset succefully!';
                            $returnData['statusCode'] = '00000';
                        } else {
                            $returnData['httpCode'] = '422';
                            $returnData['msg'] = 'Reset failed!';
                            $returnData['statusCode'] = 'C40002';
                        }
                    } else {
                        $returnData['httpCode'] = '402';
                        $returnData['msg'] = 'No device found for this mac!';
                        $returnData['statusCode'] = 'C40001';
                    }
                } else if ($reqData['module'] == 'KING4KPLAYER') {
                    $macDetails = King4kPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])
                    ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                    ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))
                    ->with('playlistUrl')->first();
                    if (!empty($macDetails)) {
                        if (empty($macDetails['playlistUrl'])) {
                            $returnData['httpCode'] = '422';
                            $returnData['msg'] = 'No playlist found to be deleted for this mac!';
                            $returnData['statusCode'] = 'C40014';
                            return new JsonResponse($returnData);
                        }
                        $deleted = King4kPlayerPlaylist::where('device_id', $macDetails->_id)->delete();
                        if ($deleted) {
                            $returnData['httpCode'] = '200';
                            $returnData['status'] = true;
                            $returnData['msg'] = 'Playlist reset succefully!';
                            $returnData['statusCode'] = '00000';
                        } else {
                            $returnData['httpCode'] = '422';
                            $returnData['msg'] = 'Reset failed!';
                            $returnData['statusCode'] = 'C40002';
                        }
                    } else {
                        $returnData['httpCode'] = '402';
                        $returnData['msg'] = 'No device found for this mac!';
                        $returnData['statusCode'] = 'C40001';
                    }
                }else if ($reqData['module'] == 'IBOSSPLAYER') {
                    $macDetails = IBOSSPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])
                    ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                    ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))
                    ->with('playlistUrl')->first();
                    if (!empty($macDetails)) {
                        if (empty($macDetails['playlistUrl'])) {
                            $returnData['httpCode'] = '422';
                            $returnData['msg'] = 'No playlist found to be deleted for this mac!';
                            $returnData['statusCode'] = 'C40014';
                            return new JsonResponse($returnData);
                        }
                        $deleted = IBOSSPlayerPlaylist::where('device_id', $macDetails->_id)->delete();
                        if ($deleted) {
                            $returnData['httpCode'] = '200';
                            $returnData['status'] = true;
                            $returnData['msg'] = 'Playlist reset succefully!';
                            $returnData['statusCode'] = '00000';
                        } else {
                            $returnData['httpCode'] = '422';
                            $returnData['msg'] = 'Reset failed!';
                            $returnData['statusCode'] = 'C40002';
                        }
                    } else {
                        $returnData['httpCode'] = '402';
                        $returnData['msg'] = 'No device found for this mac!';
                        $returnData['statusCode'] = 'C40001';
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
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
    }

    public function editMac(Request $request)
    {
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];

            if ($request->isJson()) {
                $validArr = [
                    'module' => ['required', Rule::in(['MASA', 'VIRGINIA', 'IBOAPP', 'ABEPLAYERTV', "BOBPLAYER"])],
                    'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER'])],
                    'requestId' => 'required',
                    'requestData.createdBy' => 'required',
                    'requestData.oldMacAddress' => 'required',
                    'requestData.newMacAddress' => 'required',
                ];

                $addValidMac = [];
                $reqData['requestData']['newMacAddress'] = strtolower($reqData['requestData']['newMacAddress']);
                if ($reqData['module'] == 'MASA') {
                    $addValidMac = ['requestData.newMacAddress' => 'required|unique:App\MASAPlayer,mac_address'];
                } else if ($reqData['module'] == 'VIRGINIA') {
                    $addValidMac = ['requestData.newMacAddress' => 'required|unique:App\VirginiaDevice,mac_address'];
                } else if ($reqData['module'] == 'IBOAPP') {
                    $addValidMac = ['requestData.newMacAddress' => 'required|unique:App\IBOAppDevice,mac_address'];
                }else if ($reqData['module'] == 'ABEPLAYERTV') {
                    $addValidMac = ['requestData.newMacAddress' => 'required|unique:App\ABEPlayerDevice,mac_address'];
                }
                $validatedData = Validator::make($reqData, array_merge($validArr, $addValidMac));
                if ($validatedData->fails()) {
                    //$returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["msg"] = "Mac Address can not be switched, Your New Mac address is already added in system!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }
                if ($reqData['module'] == 'MASA') {
                    $serReq = [];
                    $serReq['module'] = $reqData['module'];
                    $serReq['channelId'] = 'IBOMASA';
                    $serReq['requestId'] = $reqData['requestId'];
                    $serReq['requestData']['createdby'] = $reqData['requestData']['createdby'];
                    $serReq['requestData']['oldMacAddress'] = $reqData['requestData']['oldMacAddress'];
                    $serReq['requestData']['newMacAddress'] = $reqData['requestData']['newMacAddress'];
                    $result  = MasaHelper::getSwitchMac($serReq);
                    if(!empty($result) & isset($result['status']) && $result['status']){
                        $returnData['httpCode'] = '200';
                        $returnData['status'] = true;
                        $returnData['msg'] = 'Mac switched succefully!';
                        $returnData['statusCode'] = '00000';
                      
                    }else{
                        $returnData['httpCode'] = $result['httpCode'] ?? '422';
                        $returnData['msg'] = $result['msg'] ?? "Mac Switching failed!";
                        $returnData['statusCode'] = $result['statusCode'] ?? 'C40002';
                    }
                } else if ($reqData['module'] == 'VIRGINIA') {
                    $macDetails = VirginiaDevice::where('mac_address', $reqData['requestData']['oldMacAddress'])->first();

                    if (!empty($macDetails)) {
                        $macDetails->mac_address = $reqData['requestData']['newMacAddress'];
                        if ($macDetails->save()) {
                            $returnData['httpCode'] = '200';
                            $returnData['status'] = true;
                            $returnData['msg'] = 'Mac switched succefully!';
                            $returnData['statusCode'] = '00000';
                        } else {
                            $returnData['httpCode'] = '422';
                            $returnData['msg'] = 'Mac switching failed!';
                            $returnData['statusCode'] = 'C40002';
                        }
                    } else {
                        $returnData['httpCode'] = '402';
                        $returnData['msg'] = 'No device found for this mac in VIRGINIA system!';
                        $returnData['statusCode'] = 'C40001';
                    }
                } else if ($reqData['module'] == 'IBOAPP') {
                    $macDetails = IBOAppDevice::where('mac_address', $reqData['requestData']['oldMacAddress'])->first();
                    if (!empty($macDetails)) {
                        $macDetails->mac_address = $reqData['requestData']['newMacAddress'];
                        if ($macDetails->save()) {
                            $returnData['httpCode'] = '200';
                            $returnData['status'] = true;
                            $returnData['msg'] = 'Mac switched succefully!';
                            $returnData['statusCode'] = '00000';
                        } else {
                            $returnData['httpCode'] = '422';
                            $returnData['msg'] = 'Mac switching failed!';
                            $returnData['statusCode'] = 'C40002';
                        }
                    } else {
                        $returnData['httpCode'] = '402';
                        $returnData['msg'] = 'No device found for this mac in IBOAPP system!!';
                        $returnData['statusCode'] = 'C40001';
                    }
                }else if ($reqData['module'] == 'ABEPLAYERTV') {
                    $macDetails = ABEPlayerDevice::where('mac_address', $reqData['requestData']['oldMacAddress'])->first();
                    if (!empty($macDetails)) {
                        $macDetails->mac_address = $reqData['requestData']['newMacAddress'];
                        if ($macDetails->save()) {
                            $returnData['httpCode'] = '200';
                            $returnData['status'] = true;
                            $returnData['msg'] = 'Mac switched successfully!';
                            $returnData['statusCode'] = '00000';
                        } else {
                            $returnData['httpCode'] = '422';
                            $returnData['msg'] = 'Mac switching failed!';
                            $returnData['statusCode'] = 'C40002';
                        }
                    } else {
                        $returnData['httpCode'] = '402';
                        $returnData['msg'] = 'No device found for this mac in ABEPlayer system!';
                        $returnData['statusCode'] = 'C40001';
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
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
    }


    public function dashboardCounts(Request $request)
    {
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];

            if ($request->isJson()) {
                $validArr = [
                    'module' => ['required', Rule::in(['MASA', 'VIRGINIA', 'IBOAPP', 'ABEPLAYERTV', "BOBPLAYER"])],
                    'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER'])],
                    'requestId' => 'required',
                    'requestData.createdBy' => 'required',
                    'requestData.countSystems' => [
                        'required',
                        'array',
                        Rule::in(['MASA', 'VIRGINIA', 'IBOAPP']),
                    ]
                ];
                $validatedData = Validator::make($reqData, $validArr);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }

                //find count of 

                

                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
    }


    public function removeReseller(Request $request){
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];

            if ($request->isJson()) {
                $validArr = [
                    'module' => ['required', Rule::in(['MASA', 'VIRGINIA', 'IBOAPP', 'ABEPLAYERTV', "BOBPLAYER"])],
                    'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER'])],
                    'requestId' => 'required',
                    'requestData.createdBy' => 'required',
                    'requestData.resellerId' => 'required',
                    'requestData.status' => 'required'
                ];
                $validatedData = Validator::make($reqData, $validArr);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }

                //find count of 
                $reseller = IBOReseller::find($reqData['requestData']['resellerId']);
                //return new JsonResponse( $reseller);
                if(!empty($reseller)){
                   if($reqData['requestData']['isAdminDisableValue'] != 'undefined'){
                        $reseller->is_disable_by_admin = $reqData['requestData']['isAdminDisableValue'];
                    }else{
                        $reseller->status = $reqData['requestData']['status'];
                    }
                    
                    if($reseller->save()){
                        $returnData["msg"] = 'Reseller updated succesfully!';
                        $returnData["statusCode"] = "00000";
                        $returnData["status"] = true;
                        $returnData["httpCode"] = "200";
                        $returnData["data"] = ['IBOReseller' => $reseller];

                    }
                }else{
                    $returnData["msg"] = 'Reseller not found in system!';
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                }

                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
    }

    public function clientDetail(Request $request){

        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];

            if ($request->isJson()) {
                $validArr = [
                    'module' => ['required', Rule::in(['MASA', 'VIRGINIA', 'IBOAPP', 'ABEPLAYERTV', "BOBPLAYER"])],
                    'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER'])],
                    'requestId' => 'required',
                    'requestData.requestedBy' => 'required',
                    'requestData.requestedGroupId' => 'required'
                ];
                $validatedData = Validator::make($reqData, $validArr);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }

                //find count of 
                $serReq = [];
                $serReq['module'] = $reqData['module'];
                $serReq['channelId'] = 'IBOMASA';
                $serReq['requestId'] = $reqData['requestId'];
                $serReq['requestData']['clientId'] = 'MASA001';
                $result  = MasaHelper::getClientDetail($serReq);
                if(!empty($result) & isset($result['status']) && $result['status']){
                    
                    $returnData["msg"] = 'Detail fetched succesfully!';
                    $returnData["statusCode"] = "00000";
                    $returnData["status"] = true;
                    $returnData["httpCode"] = "200";
                    $returnData["data"] = ['MasaClient' => $result['data']['MasaClient']];

                }else{
                    $returnData["msg"] = 'Detail could not fetched this time please try again!';
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                }

                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
        
    }

    public function debitCreditPointFromReseller(Request $request){
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];
            if ($request->isJson()) {
                $validatedData = Validator::make($reqData, [
                    'module' => ['required', Rule::in(['MASA', 'VIRGINIA', 'IBOAPP', 'ABEPLAYERTV', "BOBPLAYER"])],
                    'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER'])],
                    'requestId' => 'required',
                    'resellerId' => 'required',
                    'createdBy' => 'required',
                    'creditPoint' => 'required',
                    'groupId' => 'required'
                ]);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }
                $iboreseller = IBOReseller::find($reqData['resellerId']);
                if($iboreseller->credit_point === null || $iboreseller->credit_point == 0 || $iboreseller->credit_point < $reqData['creditPoint']){
                    $returnData["msg"] = "Reseller do not have enough credit, to be debited!";
                    $returnData["statusCode"] = "C10202";
                    $returnData["httpCode"] = "202";
                    return new JsonResponse($returnData, $returnData['httpCode']);
                }
                $iboreseller->credit_point = ($iboreseller->credit_point == null) ? $reqData['creditPoint'] : ($iboreseller->credit_point - $reqData['creditPoint']);
                if ($iboreseller->save()) {
                    //if ($reqData['groupId'] == 2) {
                        $creditorRes = IBOReseller::find($reqData['createdBy']);
                        $creditorRes->credit_point = ($creditorRes->credit_point + $reqData['creditPoint']);
                        $creditorRes->save();
                    //}
                    //update into resecrdittarans logs
                    $this->__addResellerCreditShareLogs($reqData, 'DEBIT');
                    //update into resecrdittarans logs
                    $returnData["status"] = true;
                    $returnData["msg"] = "Reseller credit  successfully deducted!";
                    $returnData["statusCode"] = "000000";
                    unset($iboreseller['password']);
                    unset($iboreseller['id']);
                    $returnData["data"] = ["IBOReseller" => $iboreseller];
                    $returnData["httpCode"] = "200";
                } else {
                    $returnData["msg"] = "Reseller credit point can not be debit this moment, Please try after sometime.!";
                    $returnData["statusCode"] = "C10012";
                    $returnData["httpCode"] = "501";
                }
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
    }
    
    public function createApplication(Request $request){
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];
            if ($request->isJson()) {
                $validatedData = Validator::make($reqData, [
                    'module' => ['required', Rule::in(['MASA', 'VIRGINIA', 'IBOAPP', 'ABEPLAYERTV', "BOBPLAYER"])],
                    'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER'])],
                    'requestId' => 'required',
                    'requestData.appName' => 'required|unique:App\Applications,app_name',
                    'requestData.appLogo' => 'required',
                    'requestData.appPhone' => 'required',
                    'requestData.appEmail' => 'required',
                    'requestData.appPlaceLocation' => '',
                    'requestData.appDescription' => '',
                    'requestData.appTagLine' => '',
                    
                ]);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }
                $appSettings = new Applications;
                $appSettings->app_name = $reqData['requestData']['appName'] ?? "";
                $appSettings->app_phone = $reqData['requestData']['appPhone'] ?? "";
                $appSettings->app_email = $reqData['requestData']['appEmail'] ?? "";
                $appSettings->app_logo = $reqData['requestData']['appLogo'] ?? "";
                $appSettings->app_tag_line = $reqData['requestData']['appTagLine'] ?? "";
                $appSettings->app_description = $reqData['requestData']['appDescription'] ?? "";
                $appSettings->app_place_location = $reqData['requestData']['appPlaceLocation'] ?? "";
                if($appSettings->save()){
                    $returnData["status"] = true;
                    $returnData["msg"] = "Application successfully added!";
                    $returnData["statusCode"] = "000000";
                    $returnData["httpCode"] = "200";
                }else{
                    $returnData["msg"] = "Application successfully added!";
                    $returnData["statusCode"] = "C10401";
                    $returnData["httpCode"] = "401";
                }
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
    }

    public function applications(Request $request){
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];
            if ($request->isJson()) {
                $validatedData = Validator::make($reqData, [
                    'module' => ['required', Rule::in(['MASA', 'VIRGINIA', 'IBOAPP', 'ABEPLAYERTV', "BOBPLAYER"])],
                    'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER'])],
                    'requestId' => 'required',
                    'requestData.isApplication' => 'required'
                    
                ]);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }
                $appSettings = Applications::get();
                
                if(!empty($appSettings)){
                    $returnData["status"] = true;
                    $returnData["msg"] = "Application List fetched successfully!";
                    $returnData["statusCode"] = "000000";
                    $returnData["data"] = ["ApplicationList" => $appSettings];
                    $returnData["httpCode"] = "200";
                }else{
                    $returnData["msg"] = "Application list has no data!";
                    $returnData["statusCode"] = "C10401";
                    $returnData["httpCode"] = "401";
                }
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
    }

    
    
    public function applicationListTree(Request $request){
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];
            if ($request->isJson()) {
                $validatedData = Validator::make($reqData, [
                    'module' => ['required', Rule::in(['MASA', 'VIRGINIA', 'IBOAPP', 'ABEPLAYERTV', "BOBPLAYER"])],
                    'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER'])],
                    'requestId' => 'required',
                    'requestData.isApplication' => 'required',
                    'requestData.createdBy' => 'required'
                    
                ]);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }

                $findResDtls = IBOReseller::where('id', $reqData['requestData']['createdBy'])->first();
                if($findResDtls['group_id'] == '2'){
                    if(!empty($findResDtls['application_allowed'])){
                        $appSettings = Applications::select('id', 'app_name')->where('status', 1)
                        ->whereIn('id', json_decode($findResDtls['application_allowed']))->get();
                    }else{
                        $appSettings = [];
                    }
                    
                }else{
                    $appSettings = Applications::select('id', 'app_name')->where('status', 1)->get();
                }
                
                if(!empty($appSettings)){
                    $returnData["status"] = true;
                    $returnData["msg"] = "Application List fetched successfully!";
                    $returnData["statusCode"] = "000000";
                    $returnData["data"] = ["ApplicationList" => $appSettings];
                    $returnData["httpCode"] = "200";
                }else{
                    $returnData["msg"] = "Application list has no data!";
                    $returnData["statusCode"] = "C10401";
                    $returnData["httpCode"] = "403";
                }
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
    }

    public function createResellerApplication(Request $request){
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];
            if ($request->isJson()) {
                $validatedData = Validator::make($reqData, [
                    'module' => ['required', Rule::in(['MASA', 'VIRGINIA', 'IBOAPP', 'ABEPLAYERTV', "BOBPLAYER"])],
                    'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER'])],
                    'requestId' => 'required',
                    'requestData.appName' => 'required|unique:App\ResellerApplications,app_name',
                    'requestData.appLogo' => 'required',
                    'requestData.appPhone' => 'required',
                    'requestData.appEmail' => 'required',
                    'requestData.appPlaceLocation' => '',
                    'requestData.appDescription' => '',
                    'requestData.appTagLine' => '',
                    'requestData.appId' =>  'required',
                    'requestData.createdBy' =>  'required',
                    
                ]);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }
                $alreadyExist = ResellerApplications::where('reseller_id', $reqData['requestData']['createdBy'])
                ->where('app_id', $reqData['requestData']['appId'])->first();
                if(!empty($alreadyExist)){
                    $returnData["msg"] = "Already exist same app settings for selected Application!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }
                $appSettings = new ResellerApplications;
                $appSettings->app_id = $reqData['requestData']['appId'] ?? "";
                $appSettings->reseller_id = $reqData['requestData']['createdBy'] ?? "";
                $appSettings->app_name = $reqData['requestData']['appName'] ?? "";
                $appSettings->app_phone = $reqData['requestData']['appPhone'] ?? "";
                $appSettings->app_email = $reqData['requestData']['appEmail'] ?? "";
                $appSettings->app_logo = $reqData['requestData']['appLogo'] ?? "";
                $appSettings->app_tag_line = $reqData['requestData']['appTagLine'] ?? "";
                $appSettings->app_description = $reqData['requestData']['appDescription'] ?? "";
                $appSettings->app_place_location = $reqData['requestData']['appPlaceLocation'] ?? "";
                $appSettings->created_by = $reqData['requestData']['createdBy'] ?? "";
                if($appSettings->save()){
                    $returnData["status"] = true;
                    $returnData["msg"] = "Application successfully added!";
                    $returnData["statusCode"] = "000000";
                    $returnData["httpCode"] = "200";
                }else{
                    $returnData["msg"] = "Application successfully added!";
                    $returnData["statusCode"] = "C10401";
                    $returnData["httpCode"] = "401";
                }
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
    }

    public function resellerApplications(Request $request){
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];
            if ($request->isJson()) {
                $validatedData = Validator::make($reqData, [
                    'module' => ['required', Rule::in(['MASA', 'VIRGINIA', 'IBOAPP', 'ABEPLAYERTV', "BOBPLAYER"])],
                    'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER'])],
                    'requestId' => 'required',
                    'requestData.isApplication' => 'required'
                    
                ]);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }
                $appSettings = ResellerApplications::where('status', 1)->get();
                if(!empty($appSettings)){
                    $returnData["status"] = true;
                    $returnData["msg"] = "Application List fetched successfully!";
                    $returnData["statusCode"] = "000000";
                    $returnData["data"] = ["ApplicationList" => $appSettings];
                    $returnData["httpCode"] = "200";
                }else{
                    $returnData["msg"] = "Application list has no data!";
                    $returnData["statusCode"] = "C10401";
                    $returnData["httpCode"] = "401";
                }
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
    }

    public function getResApp(Request $request){
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];
            if ($request->isJson()) {
                $validatedData = Validator::make($reqData, [
                    'module' => ['required', Rule::in(['MASA', 'VIRGINIA', 'IBOAPP', 'ABEPLAYERTV', "BOBPLAYER"])],
                    'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER'])],
                    'requestId' => 'required',
                    'requestData.editResId' => 'required'
                    
                ]);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }
                $appSettings = ResellerApplications::where('id', $reqData['requestData']['editResId'])->first();
                
                if(!empty($appSettings)){
                    $returnData["status"] = true;
                    $returnData["msg"] = "Application List fetched successfully!";
                    $returnData["statusCode"] = "000000";
                    $returnData["data"] = ["ResellerApplication" => $appSettings];
                    $returnData["httpCode"] = "200";
                }else{
                    $returnData["msg"] = "Application list has no data!";
                    $returnData["statusCode"] = "C10401";
                    $returnData["httpCode"] = "401";
                }
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
    }


    public function editResApp(Request $request){
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];
            if ($request->isJson()) {
                $validatedData = Validator::make($reqData, [
                    'module' => ['required', Rule::in(['MASA', 'VIRGINIA', 'IBOAPP', 'ABEPLAYERTV', "BOBPLAYER"])],
                    'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER'])],
                    'requestId' => 'required',
                    'requestData.appLogo' => 'required',
                    'requestData.editResAppId' => 'required',
                    'requestData.appPhone' => 'required',
                    'requestData.appEmail' => 'required',
                    'requestData.appPlaceLocation' => '',
                    'requestData.appDescription' => '',
                    'requestData.appTagLine' => '',
                    'requestData.appId' =>  'required',
                    'requestData.createdBy' =>  'required',
                    
                ]);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }
                
                $appSettings = ResellerApplications::find($reqData['requestData']['editResAppId']);
                $appSettings->reseller_id = $reqData['requestData']['createdBy'] ?? "";
                $appSettings->app_name = $reqData['requestData']['appName'] ?? "";
                $appSettings->app_phone = $reqData['requestData']['appPhone'] ?? "";
                $appSettings->app_email = $reqData['requestData']['appEmail'] ?? "";
                $appSettings->app_logo = $reqData['requestData']['appLogo'] ?? "";
                $appSettings->app_tag_line = $reqData['requestData']['appTagLine'] ?? "";
                $appSettings->app_description = $reqData['requestData']['appDescription'] ?? "";
                $appSettings->app_place_location = $reqData['requestData']['appPlaceLocation'] ?? "";
                if($appSettings->save()){
                    $returnData["status"] = true;
                    $returnData["msg"] = "Application successfully updated!";
                    $returnData["statusCode"] = "000000";
                    $returnData["httpCode"] = "200";
                }else{
                    $returnData["msg"] = "Application could not be updated!";
                    $returnData["statusCode"] = "C10401";
                    $returnData["httpCode"] = "401";
                }
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
    }

    public function removeAppSetting(Request $request){
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];

            if ($request->isJson()) {
                $validArr = [
                    'module' => ['required', Rule::in(['MASA', 'VIRGINIA', 'IBOAPP', 'ABEPLAYERTV', "BOBPLAYER"])],
                    'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER'])],
                    'requestId' => 'required',
                    'requestData.createdBy' => 'required',
                    'requestData.resAppId' => 'required',
                    'requestData.status' => 'required'
                ];
                $validatedData = Validator::make($reqData, $validArr);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }

                //find count of 
                $reseller = ResellerApplications::find($reqData['requestData']['resAppId']);
                //return new JsonResponse( $reseller);
                if(!empty($reseller)){
                    $reseller->status = $reqData['requestData']['status'];
                    if($reseller->save()){
                        $returnData["msg"] = 'Setting updated succesfully!';
                        $returnData["statusCode"] = "00000";
                        $returnData["status"] = true;
                        $returnData["httpCode"] = "200";
                        $returnData["data"] = ['ResellerApplication' => $reseller];

                    }
                }else{
                    $returnData["msg"] = 'App Setting not found in system!';
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                }

                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
    }
    
    public function getResellerCreditPoint(Request $request){
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];

            if ($request->isJson()) {
                $validArr = [
                    'module' => ['required', Rule::in(['MASA', 'VIRGINIA', 'IBOAPP', 'ABEPLAYERTV', "BOBPLAYER"])],
                    'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER'])],
                    'requestId' => 'required',
                    'requestData.createdBy' => 'required',
                    'requestData.resellerId' => 'required',
                    'requestData.status' => 'required'
                ];

                $validatedData = Validator::make($reqData, $validArr);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }

                $reseller = IBOReseller::select('id', 'credit_point')->where('id', $reqData['requestData']['resellerId'])->first();
                if(!empty($reseller)){
                    $returnData["msg"] = 'Credit Point fetched succesfully!';
                    $returnData["statusCode"] = "00000";
                    $returnData["status"] = true;
                    $returnData["httpCode"] = "200";
                    $returnData["data"] = ['IBOReseller' => $reseller];
                }else{
                    $returnData["msg"] = 'No Credit found!';
                    $returnData["statusCode"] = "C10200";
                    $returnData["httpCode"] = "200";
                }

                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
    }
    
    
    public function boxActivationNew(Request $request){
        
        try {
            $reqData = $request->all();

            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "502"
            ];
            if ($request->isJson()) {

                $validArr = [
                    'module' => ['required', Rule::in(['IBO'])],
                    'channelId' => ['required', Rule::in(['IBOPLAYER'])],
                    'domainId' => ['required', Rule::in(['IBOAPP'])],
                    'requestData.macAddress' => 'required',
                    'requestData.activationCode' => 'required',
                ];

                $validatedData = Validator::make($reqData, $validArr);

                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }


                //check if mac valid 
                $validMac = IBOAppDevice::where('mac_address', $reqData['requestData']['macAddress'])->first();
                if($validMac){
                    $validActi = IBOPlayerActivationCode::where('value', $reqData['requestData']['activationCode'])
                    ->where('status', 0)->first();
                    if(!$validActi){
                        $returnData["msg"] = "Invalid activation code or used!";
                        $returnData["statusCode"] = "C10011";
                        $returnData["httpCode"] = "403";
                        return new JsonResponse($returnData, 403);
                    }
                    $validMac->is_trial = $validActi->type == 'yearly' ? 2 : 3;
                    $validMac->expire_date = $this->__setExpiryDate($validMac->is_trial, $validMac->expire_date);
                    if($validMac->save()){
                        $validActi->activated_by =  $validMac->mac_address;
                        $validActi->status = 1;
                        $validActi->used_at = Carbon::today();
                        if($validActi->save()){
                            $validMac['notifications'] = IBOPlayerNotification::where('status', 1)->get();
                            $validMac['settings'] =  [
                                "app_logo"     => "https://iboplayer.com/images/apps/app_logo.png",
                                "app_email" => "info@iboplayer.com",
                                "app_phone" => "+201014045819",
                                "app_title" => "IBO Player",
                                "app_caption" => "IBOPlayer Best IPTV"
                            ];
                            $validMac['themes'] = [
                                [ 
                                    "name" => 'wallpaper 1',
                                    "url" => 'https://iboplayer.com/images/upload/214434.jpg'
                                ],
                                [ 
                                    "name" =>  'wallpaper 2',
                                    "url" => 'https://iboplayer.com/images/upload/658308.png'
                                ],
                                [ 
                                    "name" =>  'wallpaper 3',
                                    "url" => 'https://iboplayer.com/images/upload/780989.jpg'
                                ],
                                [ 
                                    "name" =>  'wallpaper 5',
                                    "url" => 'https://iboplayer.com/images/upload/622859.jpg'
                                ],
                                [ 
                                    "name" =>  'wallpaper 6',
                                    "url" => 'https://iboplayer.com/images/upload/322486.jpg'
                                ],
                                [ 
                                    "name" =>  'wallpaper 7',
                                    "url" => 'https://iboplayer.com/images/upload/894381.jpg' ],
                                [ 
                                    "name" =>  'wallpaper 8', 
                                    "url" => 'https://iboplayer.com/images/upload/762024.jpg'
                                ]
                            ];
                            $validMac["languages"] = [];
                            $validMac["playlists"] = IBOAppPlaylist::where('device_id', $validMac->id)->get();
                            $returnData["data"] = $validMac;
                            $returnData["msg"] = "Successfully activated box!";
                            $returnData["statusCode"] = "000000";
                            $returnData["httpCode"] = "200";
                            return new JsonResponse($returnData, $returnData['httpCode']);
                        }else{
                            $returnData["msg"] = "Something went wrong, please try after sometime!!";
                            $returnData["statusCode"] = "C40403";
                            $returnData["httpCode"] = "403";
                            return new JsonResponse($returnData, 403);
                        }
                    }else{
                        $returnData["msg"] = "Something went wrong, please try after sometime!!";
                        $returnData["statusCode"] = "C30403";
                        $returnData["httpCode"] = "403";
                        return new JsonResponse($returnData, 403);
                    }
                }else{
                    $returnData["msg"] = "Invalid mac address!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "403";
                    return new JsonResponse($returnData, 403);
                }
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 500);
        }
    }

    public function __editBOBPlayerUser($reqData, $returnData){
        $bobPlayer = BOBPlayerDevice::find($reqData['requestData']['userId']);
        //print_r($iboreseller);exit;
        $bobPlayer->mac_address = $reqData['requestData']['macAddress'];
        if(!empty($reqData['requestData']['appType'])){
            $bobPlayer->app_type = $reqData['requestData']['appType'];
        }
        if(!empty($reqData['requestData']['email'])){
            $bobPlayer->email = $reqData['requestData']['email'];
        }
        $bobPlayer->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], $bobPlayer->expire_date, 'BOBPLAYER');
        $bobPlayer->reseller_id = $reqData['requestData']['updatedBy'];
        if ($reqData['requestData']['isTrail'] !== $bobPlayer->is_trial) {
            $bobPlayer->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 3 : 2);
        }
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance 
        $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }
        if ($bobPlayer->save()) {
            if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && $reqData['requestData']['playlist'][0]['playListUrl'] !== '') {
                $deleted = BOBPlayerPlaylist::where('device_id', $bobPlayer->id)->delete();

                $insertPlaylist = [];
                foreach ($reqData['requestData']['playlist'] as $key => $value) {
                    if (!empty($value['playListUrl'])) {
                        $insertPlaylist[] =
                            array(
                                'device_id' => $bobPlayer->_id,
                                'name' => $value['playListName'] ?? "",
                                'url' => $value['playListUrl'] ?? "",
                                'epg_url' => $value['epgUrl'] ?? "",
                                'is_protected' => $value['isProtected'] ?? 0,
                                'username' => $value['username'] ?? "",
                                'password' => $value['password'] ?? "",
                                'pin' => $value['pin'] ?? "",
                                'add_date' => date('Y-m-d H:i:s')
                            );
                    }
                }
                $iboPlaylistUrl = BOBPlayerPlaylist::insert($insertPlaylist);
            }
            // if (!empty($iboPlaylistUrl)) {
            if ($reqData['requestData']['isTrail'] !== $bobPlayer->is_trial) {
                //update reseller credit point
                $this->__updateCreditPoint($reqData['requestData']['updatedBy'], $reqData['requestData']['isTrail']);
                //end updating credit popint of reseller

                //update here trans data
                $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
                $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $bobPlayer->expire_date,"reseller_id" => $bobPlayer->reseller_id, "user_id" => $bobPlayer->id, "mac_address" => $bobPlayer->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint]);
                //end updating trans data
            }
            $returnData["status"] = true;
            $returnData["msg"] = "User updated successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" => $bobPlayer];
            $returnData["httpCode"] = "200";
            // } else {
            //     $returnData["msg"] = "User's playlist detail could not be added this moment, Please try after sometime.!";
            //     $returnData["statusCode"] = "C20013";
            //     $returnData["httpCode"] = "501";
            // }
        } else {
            $returnData["msg"] = "User could not be updated this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C20012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }
    
    public function __editABEPlayerUser($reqData, $returnData){
        $iboreseller = ABEPlayerDevice::find($reqData['requestData']['userId']);
        //print_r($iboreseller);exit;
        $iboreseller->mac_address = $reqData['requestData']['macAddress'];
        if(!empty($reqData['requestData']['appType'])){
            $iboreseller->app_type = $reqData['requestData']['appType'];
        }
        if(!empty($reqData['requestData']['email'])){
            $iboreseller->email = $reqData['requestData']['email'];
        }
        $iboreseller->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], $iboreseller->expire_date, 'ABEPLAYERTV');
        $iboreseller->reseller_id = $reqData['requestData']['updatedBy'];
        if ($reqData['requestData']['isTrail'] !== $iboreseller->is_trail) {
            $iboreseller->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 3 : 2);
        }
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance 
        $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }
        if ($iboreseller->save()) {
            if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && $reqData['requestData']['playlist'][0]['playListUrl'] !== '') {
                $deleted = ABEPlayerPlaylist::where('playlist_id', $iboreseller->id)->delete();

                $insertPlaylist = [];
                foreach ($reqData['requestData']['playlist'] as $key => $value) {
                    if (!empty($value['playListUrl'])) {
                        $insertPlaylist[] =
                            array(
                                'playlist_id' => $iboreseller->id,
                                'name' => $value['playListName'] ?? "",
                                'url' => $value['playListUrl'] ?? "",
                                'epg_url' => $value['epgUrl'] ?? "",
                                'is_protected' => $value['isProtected'] ?? 0,
                                'username' => $value['username'] ?? "",
                                'password' => $value['password'] ?? "",
                                'pin' => $value['pin'] ?? "",
                                'add_date' => date('Y-m-d H:i:s')
                            );
                    }
                }
                $iboPlaylistUrl = ABEPlayerPlaylist::insert($insertPlaylist);
            }
            // if (!empty($iboPlaylistUrl)) {
            if ($reqData['requestData']['isTrail'] !== $iboreseller->is_trial) {
                //update reseller credit point
                $this->__updateCreditPoint($reqData['requestData']['updatedBy'], $reqData['requestData']['isTrail']);
                //end updating credit popint of reseller

                //update here trans data
                $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
                $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $iboreseller->expire_date,"reseller_id" => $iboreseller->reseller_id, "user_id" => $iboreseller->_id, "mac_address" => $iboreseller->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint]);
                //end updating trans data
            }
            $returnData["status"] = true;
            $returnData["msg"] = "User updated successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" => $iboreseller];
            $returnData["httpCode"] = "200";
            // } else {
            //     $returnData["msg"] = "User's playlist detail could not be added this moment, Please try after sometime.!";
            //     $returnData["statusCode"] = "C20013";
            //     $returnData["httpCode"] = "501";
            // }
        } else {
            $returnData["msg"] = "User could not be updated this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C20012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }

    public function __addInBOBPlayer($reqData, $returnData){
        $bobplayer = new BOBPlayerDevice();
        $bobplayer->mac_address = $reqData['requestData']['macAddress'];
        $bobplayer->device_key = (string)rand(100000, 999999);
        if(!empty($reqData['requestData']['appType'])){
            $bobplayer->app_type = $reqData['requestData']['appType'];
        }
        if(!empty($reqData['requestData']['email'])){
            $bobplayer->email = $reqData['requestData']['email'];
        }
        
        $bobplayer->reseller_id = $reqData['requestData']['createdBy'];
        $bobplayer->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 3 : 2);
        $bobplayer->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], date('Y-m-d'), 'BOBPLAYER');
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance 
        $resellerData = IBOReseller::find($reqData['requestData']['createdBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }
        //end checking 
        if ($bobplayer->save()) {
            if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && isset($reqData['requestData']['playlist'][0]['playListUrl'])) {
                $insertPlaylist = [];
                foreach ($reqData['requestData']['playlist'] as $key => $value) {
                    if (!empty($value['playListUrl'])) {
                        $insertPlaylist[] =
                            array(
                                'device_id' => $bobplayer->id,
                                'playlist_name' => $value['playListName'] ?? "",
                                'url' => $value['playListUrl'] ?? "",
                                'is_protected' => $value['is_protected'] ?? "",
                                'username' => $value['username'] ?? "",
                                'password' => $value['password'] ?? "",
                                'pin' => $value['pin'] ?? ""
                            );
                    }
                }
                $bobplayerPlaylist = BOBPlayerPlaylist::insert($insertPlaylist);
            }
            
            //update reseller credit point
            $this->__updateCreditPoint($reqData['requestData']['createdBy'], $reqData['requestData']['isTrail']);
            //end updating credit popint of reseller
            $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
            $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $bobplayer->expire_date, "reseller_id" => $bobplayer->reseller_id, "user_id" => $bobplayer->id, "mac_address" => $bobplayer->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint]);
            //update here trans data

            //end updating trans data
            $returnData["status"] = true;
            $returnData["msg"] = "User added successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = [$reqData['module'] . "User" => $bobplayer];
            $returnData["httpCode"] = "200";
        } else {
            $returnData["msg"] = "User could not be added this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C10012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }
    
    public function __addInABEPlayerTV($reqData, $returnData){
        $abeplayerDevice = new ABEPlayerDevice();
        $abeplayerDevice->mac_address = $reqData['requestData']['macAddress'];
        $abeplayerDevice->device_key = (string)rand(100000, 999999);
        if(!empty($reqData['requestData']['appType'])){
            $abeplayerDevice->app_type = $reqData['requestData']['appType'];
        }
        if(!empty($reqData['requestData']['email'])){
            $abeplayerDevice->email = $reqData['requestData']['email'];
        }
        
        $abeplayerDevice->reseller_id = $reqData['requestData']['createdBy'];
        $abeplayerDevice->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 3 : 2);
        $abeplayerDevice->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], date('Y-m-d'), 'ABEPLAYERTV');
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance 
        $resellerData = IBOReseller::find($reqData['requestData']['createdBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }
        //end checking 
        if ($abeplayerDevice->save()) {
            if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && isset($reqData['requestData']['playlist'][0]['playListUrl'])) {
                $insertPlaylist = [];
                foreach ($reqData['requestData']['playlist'] as $key => $value) {
                    if (!empty($value['playListUrl'])) {
                        $insertPlaylist[] =
                            array(
                                'device_id' => $abeplayerDevice->id,
                                'playlist_name' => $value['playListName'] ?? "",
                                'url' => $value['playListUrl'] ?? "",
                                'is_protected' => $value['is_protected'] ?? "",
                                'username' => $value['username'] ?? "",
                                'password' => $value['password'] ?? "",
                                'pin' => $value['pin'] ?? ""
                            );
                    }
                }
                $virPlaylistUrl = ABEPlayerPlaylist::insert($insertPlaylist);
            }
            
            //update reseller credit point
            $this->__updateCreditPoint($reqData['requestData']['createdBy'], $reqData['requestData']['isTrail']);
            //end updating credit popint of reseller
            $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
            $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $abeplayerDevice->expire_date, "reseller_id" => $abeplayerDevice->reseller_id, "user_id" => $abeplayerDevice->_id, "mac_address" => $abeplayerDevice->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint]);
            //update here trans data

            //end updating trans data
            $returnData["status"] = true;
            $returnData["msg"] = "User added successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = [$reqData['module'] . "User" => $abeplayerDevice];
            $returnData["httpCode"] = "200";
        } else {
            $returnData["msg"] = "User could not be added this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C10012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }
    
    public function UpdatePassword(Request $request){
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "httpCode" => "501"
            ];

            if ($request->isJson()) {
                $validArr = [
                    'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER'])],
                    'requestId' => 'required',
                    'requestData.groupId' => 'required',
                    'requestData.userId' => 'required',
                    'requestData.password' => 'required'
                ];

                $validatedData = Validator::make($reqData, $validArr);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }

                $reseller = IBOReseller::select('id', 'email')->where('id', $reqData['requestData']['userId'])->first();
                if(!empty($reseller)){
                    $reseller->password = Hash::make($reqData['requestData']['password']);
                    if($reseller->save()){
                        $returnData["msg"] = 'Reseller password updates succesfully!';
                        $returnData["statusCode"] = "00000";
                        $returnData["status"] = true;
                        $returnData["httpCode"] = "200";
                    }else{
                        $returnData["msg"] = 'Can not update password!';
                        $returnData["statusCode"] = "C10500";
                        $returnData["httpCode"] = "500";
                    }
                } else {
                    $returnData["msg"] = 'No User found with this id!';
                    $returnData["statusCode"] = "C10200";
                    $returnData["httpCode"] = "200";
                }
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? ""
            ], 501);
        }
    }
    
    public function getUserActiReports(Request $request){
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];
            if ($request->isJson()) {
                $validatedData = Validator::make($reqData, [
                    'module' => ['required', Rule::in(['MASA', 'VIRGINIA', 'IBOAPP'])],
                    'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER'])],
                    'requestId' => 'required',
                    'requestData.adminId' => 'required',
                    'requestData.groupId' => 'required',
                    'requestData.moduleType' => ['required', Rule::in(['all', 'iboapp', 'virginia', 'bayiptv', 'masa', 'abeplayertv','bobplayer', 'ktnPlayer'])]
                ]);

                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }

                if($reqData['requestData']['groupId'] == '1'){
                    if($reqData['requestData']['moduleType'] == 'all'){
                        $tempData = UserActiTranLogs::select('id', 'reseller_id', 'box_expiry_date as Expiry Date',
                         'module as Product', 'credit_point as Used Point', 'mac_address as Mac', 'created_at as Activated On')
                        ->whereDate('created_at', '>=', $reqData['requestData']['startDate'])
                        ->whereDate('created_at', '<=', $reqData['requestData']['endDate'])
                        ->with('resellerDetail')
                        ->get()->toArray();
                    }else{
                        $tempData = UserActiTranLogs::select('id', 'reseller_id', 'box_expiry_date as Expiry Date',
                         'module as Product', 'credit_point as Used Point', 'mac_address as Mac', 'created_at as Activated On')
                        ->where('module', strtoupper($reqData['requestData']['moduleType']))
                        ->whereDate('created_at', '>=', $reqData['requestData']['endDate'])
                        ->whereDate('created_at', '<=', $reqData['requestData']['endDate'])
                        ->with('resellerDetail')
                        ->get()->toArray();
                    }             
                    
                }else{
                    if($reqData['requestData']['moduleType'] == 'all'){
                        $tempData = UserActiTranLogs::select('id', 'reseller_id', 'box_expiry_date as Expiry Date',
                        'module as Product', 'credit_point as Used Point', 'mac_address as Mac', 'created_at as Activated On')
                        ->where('reseller_id', $reqData['requestData']['adminId'])
                        ->whereDate('created_at', '>=', $reqData['requestData']['startDate'])
                        ->whereDate('created_at', '<=', $reqData['requestData']['endDate'])
                        ->with('resellerDetail')
                        ->get()->toArray();
                    }else{
                        $tempData = UserActiTranLogs::select('id', 'reseller_id', 'box_expiry_date as Expiry Date',
                        'module as Product', 'credit_point as Used Point', 'mac_address as Mac', 'created_at as Activated On')
                        ->where('reseller_id', $reqData['requestData']['adminId'])
                        ->where('module', strtoupper($reqData['requestData']['moduleType']))
                        ->whereDate('created_at', '>=', $reqData['requestData']['startDate'])
                        ->whereDate('created_at', '<=', $reqData['requestData']['endDate'])
                        ->with('resellerDetail')
                        ->get()->toArray();
                    }
                   
                }
                if(!empty($tempData)){
                    
                    foreach($tempData as $tArryKey => $tArryVal){
                        
                        if(isset($tArryVal['reseller_detail']) && !empty($tArryVal['reseller_detail'])){
                            
                            $tempData[$tArryKey]['Reseller Email'] = $tArryVal['reseller_detail']['email'];
                            unset($tempData[$tArryKey]['reseller_detail']);
                            
                        }
                    }
                }
                $returnData["data"] = $tempData;
                $returnData["httpCode"] = "200";
                $returnData["status"] = true;
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        } 
    }

    public function getResTranReports(Request $request){
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];
            if ($request->isJson()) {
                $validatedData = Validator::make($reqData, [
                    'module' => ['required', Rule::in(['MASA', 'VIRGINIA', 'IBOAPP'])],
                    'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER'])],
                    'requestId' => 'required',
                    'requestData.adminId' => 'required',
                    'requestData.groupId' => 'required'
                ]);

                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }

                if($reqData['requestData']['groupId'] == '1'){
                    
                    $tempData = ResellerCreditPointTranLogs::select('id', 'debitor_id', 'creditor_id',
                    'tr_type as Transaction Type', 'credit_point as Amount', 'created_at as Transaction Date')
                    ->whereDate('created_at', '>=', date('Y-m-d', strtotime($reqData['requestData']['startDate'])))
                    ->whereDate('created_at', '<=', date('Y-m-d', strtotime($reqData['requestData']['endDate'])))
                    ->with('creditorDetail')
                    ->with('debitorDetail')
                    ->get()->toArray();      
                    
                }else{
                    
                    $tempData = ResellerCreditPointTranLogs::select('id', 'debitor_id', 'creditor_id',
                    'tr_type as Transaction Type', 'credit_point as Amount', 'created_at as Transaction Date')
                    ->where('created_by', $reqData['requestData']['adminId'])
                    ->whereDate('created_at', '>=', date('Y-m-d', strtotime($reqData['requestData']['startDate'])))
                    ->whereDate('created_at', '<=', date('Y-m-d', strtotime($reqData['requestData']['endDate'])))
                    ->with('creditorDetail')
                    ->with('debitorDetail')
                    ->get()->toArray();
                }
                if(!empty($tempData)){
                    
                    foreach($tempData as $tArryKey => $tArryVal){
                        
                        if(isset($tArryVal['creditor_detail']) && !empty($tArryVal['creditor_detail'])){
                            
                            $tempData[$tArryKey]['Reseller Email'] = $tArryVal['creditor_detail']['email'];
                            unset($tempData[$tArryKey]['creditor_detail']);
                            
                        }
                        if(isset($tArryVal['debitor_detail']) && !empty($tArryVal['debitor_detail'])){
                            
                            $tempData[$tArryKey]['Deposit By'] = $tArryVal['debitor_detail']['email'];
                            unset($tempData[$tArryKey]['debitor_detail']);
                            
                        }
                        if(isset($tArryVal['debitor_id'])){
                            unset($tempData[$tArryKey]['debitor_id']);
                        }
                        if(isset($tArryVal['creditor_id'])){
                            unset($tempData[$tArryKey]['creditor_id']);
                        }
                        
                        if(isset($tArryVal['Transaction Type'])){
                            if($tArryVal['Transaction Type'] == 'CREDIT'){
                                $tempData[$tArryKey]['Notes'] = $tArryVal['Amount']. " points credited to ".$tempData[$tArryKey]['Reseller Email']. " by ". $tempData[$tArryKey]['Deposit By'] . " on " . $tempData[$tArryKey]['Transaction Date'];
                            }else{
                                $tempData[$tArryKey]['Notes'] = $tArryVal['Amount']. " points debited from ".$tempData[$tArryKey]['Reseller Email']. " by ". $tempData[$tArryKey]['Deposit By']. " on " . $tempData[$tArryKey]['Transaction Date'];
                            }
                            
                        }
                    }
                }
                $returnData["data"] = $tempData;
                $returnData["httpCode"] = "200";
                $returnData["status"] = true;
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        } 
    }
    
    public function boxActivationClient(Request $request)
    {
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];
            if ($request->isJson()) {

                if ($request->hasHeader('clientId') && $request->hasHeader('secretKey')) {
                    $validArr = [
                        'module' => ['required', Rule::in(['IBOAPP', 'VIRGINIA'])],
                        'channelId' => 'required|min:5|max:100',
                        'requestId' => 'required',
                        'requestData.macAddress' => 'required',
                        'requestData.packageId' => 'required|min:1|max:3',
                        'requestData.creditPoint' => 'required',
                        'requestData.resellerId' => 'required'
                    ];


                    $validatedData = Validator::make($reqData, $validArr);
                    if ($validatedData->fails()) {
                        $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                        $returnData["statusCode"] = "C10011";
                        $returnData["httpCode"] = "422";
                        return new JsonResponse($returnData, 422);
                    }
                    //validate client and secret
                    $validClient = IBOSOLClient::where('client_id', $request->header('clientId'))->where('secret_key', $request->header('secretKey'))->count();
                    if ($validClient === 0) {
                        $returnData["msg"] = 'Invalid client details!';
                        $returnData["statusCode"] = "C10422";
                        $returnData["httpCode"] = "422";
                        return new JsonResponse($returnData, 422);
                    }

                    $reqData['requestData']['macAddress'] = strtoupper($reqData['requestData']['macAddress']);
                    if($reqData['requestData']['module'] == 'IBOAPP'){
                        $exists = IBOUser::where('mac_address', $reqData['requestData']['macAddress'])->first();
                    if ($exists === null) {
                        $exists = new IBOUser;
                    }
                    }else if($reqData['requestData']['module'] == 'VIRGINIA'){
                        $exists = VirginiaDevice::where('mac_address', $reqData['requestData']['macAddress'])->first();
                        if ($exists === null) {
                            $exists = new VirginiaDevice;
                        }
                    }
                    

                    $exists->mac_address = $reqData['requestData']['macAddress'];
                    $exists->app_type = $reqData['requestData']['appType'] ?? "";
                    $exists->email = $reqData['requestData']['email'] ?? "";
                    $exists->expire_date = $this->__setExpiryDate($reqData['requestData']['packageId'], $exists->expire_date);
                    $exists->reseller_id = $reqData['requestData']['resellerId'];
                    if ($reqData['requestData']['packageId'] !== $exists->is_trial) {
                        $exists->is_trial = ($reqData['requestData']['packageId'] > 1) ? 2 : 1;
                    }

                    $creditPoint = $this->__getCreditPointVal($reqData['requestData']['packageId']);
                    //check for credit acount balance 
                    $resellerData = IBOSOLClient::where('channel_name', $reqData['channelId'])->first();
                    //return new JsonResponse($resellerData);
                    if ($resellerData->credit_point < $creditPoint) {
                        $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
                        $returnData["statusCode"] = "C13422";
                        $returnData["httpCode"] = "422";
                        return new JsonResponse($returnData);
                    }
                    if($exists->save()){
                        //update reseller credit point
                        $this->__updateClientCreditPoint($resellerData->id, $reqData['requestData']['packageId']);
                        //end updating credit popint of reseller

                        //update here trans data
                        $this->__addClientActivationTranLogs(["reseller_id" => $reqData['requestData']['resellerId'], "user_id" => $exists->id, "mac_address" => $exists->mac_address, "module" => $reqData['module'], "channelId" => $reqData['channelId'], "credit_point" => $creditPoint]);
                        
                        $returnData["msg"] = 'User activated succesfully!';
                        $returnData["statusCode"] = "00000";
                        $returnData["status"] = true;
                        $returnData["httpCode"] = "200";
                        $returnData["data"] = ['MASAUser' => $exists];
                    }else{
                        $returnData["msg"] = "Something went wrong, please try again lator!";
                        $returnData["statusCode"] = "C10060";
                        $returnData["httpCode"] = "501";
                    }
                    
                   
                } else {
                    $returnData["msg"] = "Invalid inputs (clientid and secret required)!";
                    $returnData["statusCode"] = "C12422";
                    $returnData["httpCode"] = "422";
                }
                   
            }else{
                $returnData["msg"] = "Invalid request type!";
                $returnData["statusCode"] = "C11422";
                $returnData["httpCode"] = "422";
            }
            
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
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
        return $userTran->save();
    }
    
    public function createResNotification(Request $request){
        $returnData = array("status" => false, "statusCode" => "10014", "data" => false, "msg" => "Something went wrong.");
        try {
            if ($request->isJson()) {
            
                $reqData = $request->all();
                if (!empty($reqData)) {
                    $reqData['title'] = isset($reqData['title']) ? $reqData['title'] : "";
                   $reqData['description'] = isset($reqData['description']) ?
                    str_replace('amp;', '&', str_replace('&gt;', '<', str_replace('&gt;', '>', str_replace('&quot;', '"', str_replace('nbsp;', ' ', str_replace('&amp;', '&', str_replace('&lt;', '<', $reqData['description']))))))) : "";
                      
                    
                    //$userData = IBOUser::select('id', 'mac_address', 'firstname', 'lastname', 'expiry_date', 'email', 'username', 'streamlist_url', 'streamlist_url2', 'streamlist_url3', 'streamlist_url4', 'streamlist_url5', 'is_activated', 'gender', 'group_id', 'status')->where('mac_address', $macAddress)->first();
                    if (!empty($reqData['title']) && !empty($reqData['description'])) {
                        $strmActi = new ResellerNotification;
                        $strmActi->title = $reqData['title'];
                        $strmActi->description = $reqData['description'];
                        $strmActi->status = 1;
                        if($strmActi->save() !== false){
                            $returnData = array("status" => true, "statusCode" => "00000", "data" => ['IBOMessage' => $strmActi], "msg" => "Message Added Succesfully.");    
                        }else{
                            $returnData = array("status" => false, "statusCode" => "13011", "data" => false, "msg" => "Failed to add message detail!");    
                        }
                    } else {
                        $returnData = array("status" => false, "statusCode" => "13012", "data" => false, "msg" => "Invalid inputs!");
                    }
                }
            } else {
                $returnData = array("status" => false, "statusCode" => "13013", "data" => false, "msg" => "Invalid request type!");
            }
        } catch (\Exception $e) {
            return new JsonResponse(array("status" => false, "statusCode" => "13014", "data" => false, "msg" => env('APP_DEBUG') ? $e->getMessage() : "Invalid request type!"), 501);
        }
        return new JSonResponse($returnData, 200);
    }

    public function resellerNotifList(Request $request){
        $returnData = array("status" => false, "statusCode" => "10014", "data" => false, "msg" => "Something went wrong.");
        try {
            if ($request->isJson()) {
            
                $reqData = $request->all();
                if (!empty($reqData)) {
                    $reqData['isValid'] = isset($reqData['isValid']) ? $reqData['isValid'] : "";
                    //$userData = IBOUser::select('id', 'mac_address', 'firstname', 'lastname', 'expiry_date', 'email', 'username', 'streamlist_url', 'streamlist_url2', 'streamlist_url3', 'streamlist_url4', 'streamlist_url5', 'is_activated', 'gender', 'group_id', 'status')->where('mac_address', $macAddress)->first();
                    if (!empty($reqData['isValid'])) {
                        $strmActi = ResellerNotification::all();
                        if(!empty($strmActi)){
                            $returnData = array("status" => true, "statusCode" => "00000", "data" => ['ResellerNotification' => $strmActi], "msg" => "Message fetched Succesfully.");    
                        }else{
                            $returnData = array("status" => false, "statusCode" => "12021", "data" => false, "msg" => "Failed to fetch app detail!");    
                        }
                    } else {
                        $returnData = array("status" => false, "statusCode" => "12022", "data" => false, "msg" => "Invalid inputs!");
                    }
                }
            } else {
                $returnData = array("status" => false, "statusCode" => "12023", "data" => false, "msg" => "Invalid request type!");
            }
        } catch (\Exception $e) {
            return new JsonResponse(array("status" => false, "statusCode" => "12024", "data" => false, "msg" => env('APP_DEBUG') ? $e->getMessage() : "Invalid request type!"), 501);
        }
        return new JSonResponse($returnData, 200);
    }


    public function getActiveResNotif(Request $request){
        $returnData = array("status" => false, "statusCode" => "10014", "data" => false, "msg" => "Something went wrong.");
        try {
            if ($request->isJson()) {
            
                $reqData = $request->all();
                if (!empty($reqData)) {
                    $reqData['isValid'] = isset($reqData['requestData']['isValid']) ? $reqData['requestData']['isValid'] : "";
                    
                    if (!empty($reqData['isValid'])) {
                        $strmActi = ResellerNotification::where('status', 1)->orderBy('created_at', 'desc')->first();
                        if(!empty($strmActi)){
                            $returnData = array("status" => true, "statusCode" => "00000", "data" => $strmActi, "msg" => "Notification fetched Succesfully.");    
                        }else{
                            $returnData = array("status" => false, "statusCode" => "12021", "data" => false, "msg" => "Failed to fetch notification detail!");    
                        }
                    } else {
                        $returnData = array("status" => false, "statusCode" => "12022", "data" => false, "msg" => "Invalid inputs!");
                    }
                }
            } else {
                $returnData = array("status" => false, "statusCode" => "12023", "data" => false, "msg" => "Invalid request type!");
            }
        } catch (\Exception $e) {
            return new JsonResponse(array("status" => false, "statusCode" => "12024", "data" => false, "msg" => env('APP_DEBUG') ? $e->getMessage() : "Invalid request type!"), 501);
        }
        return new JSonResponse($returnData, 200);
    }

    public function getActiveResNotifCount(Request $request){
        $returnData = array("status" => false, "statusCode" => "10014", "data" => false, "msg" => "Something went wrong.");
        try {
            if ($request->isJson()) {
            
                $reqData = $request->all();
                if (!empty($reqData)) {
                    $reqData['isValid'] = isset($reqData['requestData']['isValid']) ? $reqData['requestData']['isValid'] : "";
                    
                    if (!empty($reqData['isValid'])) {
                        $strmActi = ResellerNotification::where('status', 1)->count();
                        if(!empty($strmActi)){
                            $returnData = array("status" => true, "statusCode" => "00000", "data" => $strmActi, "msg" => "Message fetched Succesfully.");    
                        }else{
                            $returnData = array("status" => false, "statusCode" => "12021", "data" => false, "msg" => "Failed to fetch app detail!");    
                        }
                    } else {
                        $returnData = array("status" => false, "statusCode" => "12022", "data" => false, "msg" => "Invalid inputs!");
                    }
                }
            } else {
                $returnData = array("status" => false, "statusCode" => "12023", "data" => false, "msg" => "Invalid request type!");
            }
        } catch (\Exception $e) {
            return new JsonResponse(array("status" => false, "statusCode" => "12024", "data" => false, "msg" => env('APP_DEBUG') ? $e->getMessage() : "Invalid request type!"), 501);
        }
        return new JSonResponse($returnData, 200);
    }

    public function disableMac(Request $request){
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];
            if ($request->isJson()) {
                $validatedData = Validator::make($reqData, [
                    'module' => ['required', Rule::in(config('modules.searchUserModules'))],
                    'channelId' => ['required', Rule::in(config('channels.searchUserChannel'))],
                    'requestId' => 'required',
                    'requestData.macAddress' => 'required|min:3',
                ]);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }
                
                $returnData["msg"] = "This feature has been disabled, please try after sometime!";
                $returnData["statusCode"] = "C10202";
                $returnData["httpCode"] = "202";
                return new JsonResponse($returnData, 202);
                //$reqData['requestData']['macAddress'] = strtolower($reqData['requestData']['macAddress']);
                if ($reqData['module'] == 'VIRGINIA') {
                    $iboMasaUser =  VirginiaDevice::where('mac_address', $reqData['requestData']['macAddress'])
                    ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                    ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))
                    ->first();
                } else if ($reqData['module'] == 'IBOAPP') {
                    $iboMasaUser =  IBOAppDevice::where('mac_address', $reqData['requestData']['macAddress'])
                    ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                    ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))
                    ->first();
                }else if ($reqData['module'] == 'MACPLAYER') {
                    $iboMasaUser =  MacPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])
                    ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                    ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))
                    ->first();
                } else if ($reqData['module'] == 'KTNPLAYER') {
                    $iboMasaUser =  KtnPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])
                    ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                    ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))
                    ->first();
                } else if ($reqData['module'] == 'ABEPLAYER') {
                    $iboMasaUser =  ABEPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])
                    ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                    ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))
                    ->first();
                } else if ($reqData['module'] == 'BOBPLAYER') {
                    $iboMasaUser =  BOBPlayerDevice::where('mac_address', $reqData['requestData']['macAddress'])
                    ->whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))
                    ->whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))
                    ->first();
                }
            
                if (!empty($iboMasaUser)) {
                    $iboMasaUser->is_trial = 0;//to be considered as disabled
                    $iboMasaUser->expire_date = date('Y-m-d', strtotime(date('Y-m-d') . '-1 day'));//to be considered as disabled
                    $iboMasaUser->save();
                    $returnData["status"] = true;
                    $returnData["msg"] = "Mac disabled successfully!";
                    $returnData["statusCode"] = "000000";
                    $returnData["data"] = ["IBOMasaUser" => $iboMasaUser];
                    $returnData["httpCode"] = "200";
                } else {
                    $returnData["msg"] = "Mac not found, Please input correct Mac Address!";
                    $returnData["statusCode"] = "C10012";
                    $returnData["httpCode"] = "202";
                }
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
    }
    
    public function rechargeCredit(Request $request){
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "httpCode" => "501"
            ];

            if ($request->isJson()) {
                $validArr = [
                    'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER'])],
                    'requestId' => 'required',
                    'groupId' => 'required',
                    'createdBy' => 'required',
                    'creditPoint' => 'required|numeric|min:1|max:10000'
                ];

                $validatedData = Validator::make($reqData, $validArr);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }

                $reseller = IBOReseller::where('id', $reqData['createdBy'])->first();
                if(!empty($reseller)){
                    $reseller->credit_point = ($reseller->credit_point + $reqData['creditPoint']);
                    if($reseller->save()){
                        $returnData["msg"] = 'Super Admin credit point updated succesfully!';
                        $returnData["statusCode"] = "00000";
                        $returnData["status"] = true;
                        $returnData["httpCode"] = "200";
                    }else{
                        $returnData["msg"] = 'Can not update credit point!';
                        $returnData["statusCode"] = "C10500";
                        $returnData["httpCode"] = "500";
                    }
                } else {
                    $returnData["msg"] = 'Invalid access and inputs!';
                    $returnData["statusCode"] = "C10200";
                    $returnData["httpCode"] = "200";
                }
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? ""
            ], 501);
        }
    }
    
    public function getUserActiReportByReseller(Request $request){
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
                $validatedData = Validator::make($reqData, [
                    'module' => ['required', Rule::in(['MASA', 'VIRGINIA', 'IBOAPP'])],
                    'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER'])],
                    'requestId' => 'required',
                    'requestData.adminId' => 'required',
                    'requestData.groupId' => 'required',
                    'requestData.resellerId' => 'required',
                    'requestData.moduleType' => ['required', Rule::in(['all', 'iboapp', 'virginia', 'bayiptv', 'masa', 'abeplayertv','bobplayer', 'ktnPlayer'])]
                ]);

                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }
                //$reqData['requestData']['resellerId'] = '1';
                if($reqData['requestData']['moduleType'] == 'all'){
                    $tempData = UserActiTranLogs::select('id', 'reseller_id', 'box_expiry_date as Expiry Date',
                        'module as Product', 'credit_point as Used Point', 'mac_address as Mac', 'created_at as Activated On')
                    ->where('reseller_id', $reqData['requestData']['resellerId'])
                    ->with('resellerDetail')
                    ->get()->toArray();
                }       
                    
                if(!empty($tempData)){
                    
                    foreach($tempData as $tArryKey => $tArryVal){
                        
                        if(isset($tArryVal['reseller_detail']) && !empty($tArryVal['reseller_detail'])){
                            
                            $tempData[$tArryKey]['Reseller Email'] = $tArryVal['reseller_detail']['email'];
                            
                            $tempData[$tArryKey]['Notes'] = (!empty($tArryVal['reseller_detail']['name'])
                             ? $tArryVal['reseller_detail']['name']: $tArryVal['reseller_detail']['email'] ).
                              ' activated box - '. $tArryVal['Mac']. ' on '.$tArryVal['Activated On']. ' with using '.  $tArryVal['Used Point'] . ' points,  till '.  $tArryVal['Expiry Date'];
                            unset($tempData[$tArryKey]['reseller_detail']); 
                            unset($tempData[$tArryKey]['Reseller Email']); 
                            unset($tempData[$tArryKey]['reseller_id']); 
                            unset($tempData[$tArryKey]['Activated On']); 
                        }
                    }
                }
                $returnData["data"] = $tempData;
                $returnData["httpCode"] = "200";
                $returnData["msg"] = "Success";
                $returnData["statusCode"] = "000000";
                $returnData["status"] = true;
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        } 
    }
    
    
    public function subResellerList(Request $request){
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];
            if ($request->isJson()) {
                $validatedData = Validator::make($reqData, [
                    'module' => ['required', Rule::in(['MASA', 'VIRGINIA', 'IBOAPP'])],
                    'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER'])],
                    'requestId' => 'required',
                    'userId' => "required",
                    'groupId' => "required",
                    'resellerId' =>'required'
                ]);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }

                
                $iboreseller =  IBOReseller::where('status', '!=', '2')->where('group_id', '2')->where('parent_reseller_id', $reqData['resellerId'])->get();
                

                if (!empty($iboreseller)) {
                    $returnData["status"] = true;
                    $returnData["msg"] = "Reseller list fetched successfully!";
                    $returnData["statusCode"] = "000000";
                    $returnData["data"] = ["IBOReseller" => $iboreseller];
                    $returnData["httpCode"] = "200";
                } else {
                    $returnData["msg"] = "Reseller list can not be fetched this moment, Please try after sometime.!";
                    $returnData["statusCode"] = "C10012";
                    $returnData["httpCode"] = "501";
                }
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
    }
    
    public function editResellerWebLogo(Request $request)
    {
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];
            if ($request->isJson()) {
                $validatedData = Validator::make($reqData, [
                    'module' => ['required', Rule::in(['MASA', 'VIRGINIA', 'IBOAPP'])],
                    'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER'])],
                    'requestId' => 'required',
                    'requestData.userId' => 'required',
                    'requestData.resellerId' => 'required',
                    'requestData.resWebLogo' => 'required'
                ]);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }

                $iboreseller = IBOReseller::find($reqData['requestData']['resellerId']);
                $iboreseller->updated_by = $reqData['requestData']['userId'];
                $iboreseller->web_logo = $reqData['requestData']['resWebLogo'];

                if ($iboreseller->save()) {
                    $returnData["status"] = true;
                    $returnData["msg"] = "Reseller updated successfully!";
                    $returnData["statusCode"] = "000000";
                    unset($iboreseller['password']);
                    unset($iboreseller['id']);
                    $returnData["data"] = ["IBOReseller" => $iboreseller];
                    $returnData["httpCode"] = "200";
                } else {
                    $returnData["msg"] = "Reseller can not be updated this moment, Please try after sometime.!";
                    $returnData["statusCode"] = "C10012";
                    $returnData["httpCode"] = "501";
                }
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
    }
    
    private function __editFamilyPlayer($reqData, $returnData)
    {
        $familyPlayer = FamilyPlayerDevice::find($reqData['requestData']['userId']);
        //print_r($iboreseller);exit;
        $familyPlayer->mac_address = $reqData['requestData']['macAddress'];
        if(!empty($reqData['requestData']['appType'])){
            $familyPlayer->app_type = $reqData['requestData']['appType'];
        }
        if(!empty($reqData['requestData']['email'])){
            $familyPlayer->email = $reqData['requestData']['email'];
        }
        $familyPlayer->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], $familyPlayer->expire_date);
        $familyPlayer->activated_by = $reqData['requestData']['updatedBy'];
        if ($reqData['requestData']['isTrail'] !== $familyPlayer->is_trial) {
            $familyPlayer->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 3 : 2);
        }
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance 
        $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }
        if ($familyPlayer->save()) {
            if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && $reqData['requestData']['playlist'][0]['playListUrl'] !== '') {
                $deleted = FamilyPlayerPlaylist::where('device_id', $familyPlayer->_id)->delete();

                $insertPlaylist = [];
                foreach ($reqData['requestData']['playlist'] as $key => $value) {
                    if (!empty($value['playListUrl'])) {
                        $insertPlaylist[] =
                            array(
                                'device_id' => $familyPlayer->_id,
                                'playlist_name' => $value['playListName'] ?? "",
                                'url' => $value['playListUrl'] ?? "",
                                'created_at' => date('Y-m-d H:i:s')
                            );
                    }
                }
                $iboPlaylistUrl = HushPlaylist::insert($insertPlaylist);
            }
            // if (!empty($iboPlaylistUrl)) {
            if ($reqData['requestData']['isTrail'] !== $familyPlayer->is_trial) {
                //update reseller credit point
                $this->__updateCreditPoint($reqData['requestData']['updatedBy'], $reqData['requestData']['isTrail']);
                //end updating credit popint of reseller

                //update here trans data
                $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
                $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $familyPlayer->expire_date, "reseller_id" => $familyPlayer->activated_by, "user_id" => $familyPlayer->id, "mac_address" => $familyPlayer->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint]);
                //end updating trans data
            }
            $returnData["status"] = true;
            $returnData["msg"] = "User updated successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" => $familyPlayer];
            $returnData["httpCode"] = "200";
            
        } else {
            $returnData["msg"] = "User could not be updated this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C20012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }
    
    public function __addInFamilyPlayerDevice($reqData, $returnData)
    {
        $familyPlayerDevice = new FamilyPlayerDevice();
        $familyPlayerDevice->mac_address = $reqData['requestData']['macAddress'];
        $familyPlayerDevice->device_key = (string)rand(100000, 999999);
        if(!empty($reqData['requestData']['appType'])){
            $familyPlayerDevice->app_type = $reqData['requestData']['appType'];
        }
        if(!empty($reqData['requestData']['email'])){
            $familyPlayerDevice->email = $reqData['requestData']['email'];
        }
        $familyPlayerDevice->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], date('Y-m-d'));
        $familyPlayerDevice->activated_by = $reqData['requestData']['createdBy'];
        $familyPlayerDevice->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 3 : 2);
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance 
        $resellerData = IBOReseller::find($reqData['requestData']['createdBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }
        //end checking 
        if ($familyPlayerDevice->save()) {
            if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && isset($reqData['requestData']['playlist'][0]['playListUrl'])) {
                $insertPlaylist = [];
                foreach ($reqData['requestData']['playlist'] as $key => $value) {
                    if (!empty($value['playListUrl'])) {
                        $insertPlaylist[] =
                            array(
                                'device_id' => $familyPlayerDevice->id,
                                'playlist_name' => $value['playListName'] ?? "",
                                'url' => $value['playListUrl'] ?? "",
                                'is_protected' => $value['is_protected'] ?? 0,
                                'pin' => $value['pin'] ?? null,
                                'epg_url' => $value['epg_url'] ?? "",
                                'playlist_type' => $value['playlist_type'] ?? "general",
                                'username' => $value['username'] ?? "",
                                'password' => $value['password'] ?? "",
                                '_v'    => 0
                            );
                    }
                }
                FamilyPlayerPlaylist::insert($insertPlaylist);
            }
            // if (!empty($virPlaylistUrl)) {
            //update reseller credit point
            $this->__updateCreditPoint($reqData['requestData']['createdBy'], $reqData['requestData']['isTrail']);
            //end updating credit popint of reseller
            $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
            $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $familyPlayerDevice->expire_date, "reseller_id" => $familyPlayerDevice->activated_by, "user_id" => $familyPlayerDevice->id, "mac_address" => $familyPlayerDevice->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint]);
            //update here trans data

            //end updating trans data
            $returnData["status"] = true;
            $returnData["msg"] = "User added successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = [$reqData['module'] . "User" => $familyPlayerDevice];
            $returnData["httpCode"] = "200";
            // } else {
            //     $returnData["msg"] = "User's playlist detail could not be added this moment, Please try after sometime.!";
            //     $returnData["statusCode"] = "C10013";
            //     $returnData["httpCode"] = "501";
            // }
        } else {
            $returnData["msg"] = "User could not be added this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C10012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }
    
    
    public function __addInKing4KPlayerDevice($reqData, $returnData)
    {
        $king4KPlayerDevice = new King4kPlayerDevice();
        $king4KPlayerDevice->mac_address = $reqData['requestData']['macAddress'];
        $king4KPlayerDevice->device_key = (string)rand(100000, 999999);
        if(!empty($reqData['requestData']['appType'])){
            $king4KPlayerDevice->app_type = $reqData['requestData']['appType'];
        }
        if(!empty($reqData['requestData']['email'])){
            $king4KPlayerDevice->email = $reqData['requestData']['email'];
        }
        $king4KPlayerDevice->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], date('Y-m-d'));
        $king4KPlayerDevice->activated_by = $reqData['requestData']['createdBy'];
        $king4KPlayerDevice->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 3 : 2);
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance 
        $resellerData = IBOReseller::find($reqData['requestData']['createdBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }
        //end checking 
        if ($king4KPlayerDevice->save()) {
            if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && isset($reqData['requestData']['playlist'][0]['playListUrl'])) {
                $insertPlaylist = [];
                foreach ($reqData['requestData']['playlist'] as $key => $value) {
                    if (!empty($value['playListUrl'])) {
                        $insertPlaylist[] =
                            array(
                                'device_id' => $king4KPlayerDevice->id,
                                'playlist_name' => $value['playListName'] ?? "",
                                'url' => $value['playListUrl'] ?? "",
                                'is_protected' => $value['is_protected'] ?? 0,
                                'pin' => $value['pin'] ?? null,
                                'epg_url' => $value['epg_url'] ?? "",
                                'playlist_type' => $value['playlist_type'] ?? "general",
                                'username' => $value['username'] ?? "",
                                'password' => $value['password'] ?? "",
                                '_v'    => 0
                            );
                    }
                }
                King4kPlayerPlaylist::insert($insertPlaylist);
            }
            // if (!empty($virPlaylistUrl)) {
            //update reseller credit point
            $this->__updateCreditPoint($reqData['requestData']['createdBy'], $reqData['requestData']['isTrail']);
            //end updating credit popint of reseller
            $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
            $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $king4KPlayerDevice->expire_date, "reseller_id" => $king4KPlayerDevice->activated_by, "user_id" => $king4KPlayerDevice->id, "mac_address" => $king4KPlayerDevice->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint]);
            //update here trans data

            //end updating trans data
            $returnData["status"] = true;
            $returnData["msg"] = "User added successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = [$reqData['module'] . "User" => $king4KPlayerDevice];
            $returnData["httpCode"] = "200";
            // } else {
            //     $returnData["msg"] = "User's playlist detail could not be added this moment, Please try after sometime.!";
            //     $returnData["statusCode"] = "C10013";
            //     $returnData["httpCode"] = "501";
            // }
        } else {
            $returnData["msg"] = "User could not be added this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C10012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }

    private function __addInFlixNetDevice($reqData, $returnData){
        $FlixNetDevice = new FlixNetDevice();
        $FlixNetDevice->mac_address = $reqData['requestData']['macAddress'];
        $FlixNetDevice->device_key = (string)rand(100000, 999999);
        if(!empty($reqData['requestData']['appType'])){
            $FlixNetDevice->app_type = $reqData['requestData']['appType'];
        }
        if(!empty($reqData['requestData']['email'])){
            $FlixNetDevice->email = $reqData['requestData']['email'];
        }
        $FlixNetDevice->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], date('Y-m-d'));
        $FlixNetDevice->activated_by = $reqData['requestData']['createdBy'];
        $FlixNetDevice->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 3 : 2);
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance 
        $resellerData = IBOReseller::find($reqData['requestData']['createdBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }
        //end checking 
        if ($FlixNetDevice->save()) {
            if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && isset($reqData['requestData']['playlist'][0]['playListUrl'])) {
                $insertPlaylist = [];
                foreach ($reqData['requestData']['playlist'] as $key => $value) {
                    if (!empty($value['playListUrl'])) {
                        $insertPlaylist[] =
                            array(
                                'device_id' => $FlixNetDevice->id,
                                'playlist_name' => $value['playListName'] ?? "",
                                'url' => $value['playListUrl'] ?? "",
                                'is_protected' => $value['is_protected'] ?? 0,
                                'pin' => $value['pin'] ?? null,
                                'epg_url' => $value['epg_url'] ?? "",
                                'playlist_type' => $value['playlist_type'] ?? "general",
                                'username' => $value['username'] ?? "",
                                'password' => $value['password'] ?? "",
                                '_v'    => 0
                            );
                    }
                }
                FlixNetPlaylist::insert($insertPlaylist);
            }
            
            //update reseller credit point
            $this->__updateCreditPoint($reqData['requestData']['createdBy'], $reqData['requestData']['isTrail']);
            //end updating credit popint of reseller
            $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
            $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $FlixNetDevice->expire_date, "reseller_id" => $FlixNetDevice->activated_by, "user_id" => $FlixNetDevice->id, "mac_address" => $FlixNetDevice->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint]);
            //update here trans data

            //end updating trans data
            $returnData["status"] = true;
            $returnData["msg"] = "User added successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = [$reqData['module'] . "User" => $FlixNetDevice];
            $returnData["httpCode"] = "200";
            
        } else {
            $returnData["msg"] = "User could not be added this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C10012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }

    public function __addInDuplexDevice($reqData, $returnData){
        $duplexDevice = new DuplexDevice();
        $duplexDevice->mac_address = $reqData['requestData']['macAddress'];
        $duplexDevice->device_key = (string)rand(100000, 999999);
        if(!empty($reqData['requestData']['appType'])){
            $duplexDevice->app_type = $reqData['requestData']['appType'];
        }
        if(!empty($reqData['requestData']['email'])){
            $duplexDevice->email = $reqData['requestData']['email'];
        }
        $duplexDevice->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], date('Y-m-d'));
        $duplexDevice->activated_by = $reqData['requestData']['createdBy'];
        $duplexDevice->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 3 : 2);
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance 
        $resellerData = IBOReseller::find($reqData['requestData']['createdBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }
        //end checking 
        if ($duplexDevice->save()) {
            if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && isset($reqData['requestData']['playlist'][0]['playListUrl'])) {
                $insertPlaylist = [];
                foreach ($reqData['requestData']['playlist'] as $key => $value) {
                    if (!empty($value['playListUrl'])) {
                        $insertPlaylist[] =
                            array(
                                'device_id' => $duplexDevice->id,
                                'playlist_name' => $value['playListName'] ?? "",
                                'url' => $value['playListUrl'] ?? "",
                                'is_protected' => $value['is_protected'] ?? 0,
                                'pin' => $value['pin'] ?? null,
                                'epg_url' => $value['epg_url'] ?? "",
                                'playlist_type' => $value['playlist_type'] ?? "general",
                                'username' => $value['username'] ?? "",
                                'password' => $value['password'] ?? "",
                                '_v'    => 0
                            );
                    }
                }
                DuplexPlaylist::insert($insertPlaylist);
            }
            
            //update reseller credit point
            $this->__updateCreditPoint($reqData['requestData']['createdBy'], $reqData['requestData']['isTrail']);
            //end updating credit popint of reseller
            $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
            $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $duplexDevice->expire_date, "reseller_id" => $duplexDevice->activated_by, "user_id" => $duplexDevice->id, "mac_address" => $duplexDevice->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint]);
            //update here trans data

            //end updating trans data
            $returnData["status"] = true;
            $returnData["msg"] = "User added successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = [$reqData['module'] . "User" => $duplexDevice];
            $returnData["httpCode"] = "200";
            
        } else {
            $returnData["msg"] = "User could not be added this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C10012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }

    public function __addInIBOSOLBDevice($reqData, $returnData){
        $iboSolDevice = new IBOSOLDevice();
        $iboSolDevice->mac_address = $reqData['requestData']['macAddress'];
        $iboSolDevice->device_key = (string)rand(100000, 999999);
        if(!empty($reqData['requestData']['appType'])){
            $iboSolDevice->app_type = $reqData['requestData']['appType'];
        }
        if(!empty($reqData['requestData']['email'])){
            $iboSolDevice->email = $reqData['requestData']['email'];
        }
        $iboSolDevice->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], date('Y-m-d'));
        $iboSolDevice->activated_by = $reqData['requestData']['createdBy'];
        $iboSolDevice->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 3 : 2);
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance 
        $resellerData = IBOReseller::find($reqData['requestData']['createdBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }
        //end checking 
        if ($iboSolDevice->save()) {
            if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && isset($reqData['requestData']['playlist'][0]['playListUrl'])) {
                $insertPlaylist = [];
                foreach ($reqData['requestData']['playlist'] as $key => $value) {
                    if (!empty($value['playListUrl'])) {
                        $insertPlaylist[] =
                            array(
                                'device_id' => $iboSolDevice->id,
                                'playlist_name' => $value['playListName'] ?? "",
                                'url' => $value['playListUrl'] ?? "",
                                'is_protected' => $value['is_protected'] ?? 0,
                                'pin' => $value['pin'] ?? null,
                                'epg_url' => $value['epg_url'] ?? "",
                                'playlist_type' => $value['playlist_type'] ?? "general",
                                'username' => $value['username'] ?? "",
                                'password' => $value['password'] ?? "",
                                '_v'    => 0
                            );
                    }
                }
                IBOSOLPlaylist::insert($insertPlaylist);
            }
            
            //update reseller credit point
            $this->__updateCreditPoint($reqData['requestData']['createdBy'], $reqData['requestData']['isTrail']);
            //end updating credit popint of reseller
            $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
            $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $iboSolDevice->expire_date, "reseller_id" => $iboSolDevice->activated_by, "user_id" => $iboSolDevice->id, "mac_address" => $iboSolDevice->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint]);
            //update here trans data

            //end updating trans data
            $returnData["status"] = true;
            $returnData["msg"] = "User added successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = [$reqData['module'] . "User" => $iboSolDevice];
            $returnData["httpCode"] = "200";
            
        } else {
            $returnData["msg"] = "User could not be added this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C10012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }

    public function __addInIBOSSPlayerDevice($reqData, $returnData)
    {
        $iBossPlayerDevice = new IBOSSPlayerDevice();
        $iBossPlayerDevice->mac_address = $reqData['requestData']['macAddress'];
        $iBossPlayerDevice->device_key = (string)rand(100000, 999999);
        if(!empty($reqData['requestData']['appType'])){
            $iBossPlayerDevice->app_type = $reqData['requestData']['appType'];
        }
        if(!empty($reqData['requestData']['email'])){
            $iBossPlayerDevice->email = $reqData['requestData']['email'];
        }
        $iBossPlayerDevice->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], date('Y-m-d'));
        $iBossPlayerDevice->activated_by = $reqData['requestData']['createdBy'];
        $iBossPlayerDevice->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 3 : 2);
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance 
        $resellerData = IBOReseller::find($reqData['requestData']['createdBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }
        //end checking 
        if ($iBossPlayerDevice->save()) {
            if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && isset($reqData['requestData']['playlist'][0]['playListUrl'])) {
                $insertPlaylist = [];
                foreach ($reqData['requestData']['playlist'] as $key => $value) {
                    if (!empty($value['playListUrl'])) {
                        $insertPlaylist[] =
                            array(
                                'device_id' => $iBossPlayerDevice->id,
                                'playlist_name' => $value['playListName'] ?? "",
                                'url' => $value['playListUrl'] ?? "",
                                'is_protected' => $value['is_protected'] ?? 0,
                                'pin' => $value['pin'] ?? null,
                                'epg_url' => $value['epg_url'] ?? "",
                                'playlist_type' => $value['playlist_type'] ?? "general",
                                'username' => $value['username'] ?? "",
                                'password' => $value['password'] ?? "",
                                '_v'    => 0
                            );
                    }
                }
                IBOSSPlayerPlaylist::insert($insertPlaylist);
            }
            // if (!empty($virPlaylistUrl)) {
            //update reseller credit point
            $this->__updateCreditPoint($reqData['requestData']['createdBy'], $reqData['requestData']['isTrail']);
            //end updating credit popint of reseller
            $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
            $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $iBossPlayerDevice->expire_date, "reseller_id" => $iBossPlayerDevice->activated_by, "user_id" => $iBossPlayerDevice->id, "mac_address" => $iBossPlayerDevice->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint]);
            //update here trans data

            //end updating trans data
            $returnData["status"] = true;
            $returnData["msg"] = "User added successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = [$reqData['module'] . "User" => $iBossPlayerDevice];
            $returnData["httpCode"] = "200";
            // } else {
            //     $returnData["msg"] = "User's playlist detail could not be added this moment, Please try after sometime.!";
            //     $returnData["statusCode"] = "C10013";
            //     $returnData["httpCode"] = "501";
            // }
        } else {
            $returnData["msg"] = "User could not be added this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C10012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }

    private function __editKing4KPlayer($reqData, $returnData)
    {
        $king4kplayer = King4kPlayerDevice::find($reqData['requestData']['userId']);
        //print_r($iboreseller);exit;
        $king4kplayer->mac_address = $reqData['requestData']['macAddress'];
        if(!empty($reqData['requestData']['appType'])){
            $king4kplayer->app_type = $reqData['requestData']['appType'];
        }
        if(!empty($reqData['requestData']['email'])){
            $king4kplayer->email = $reqData['requestData']['email'];
        }
        $king4kplayer->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], $king4kplayer->expire_date);
        $king4kplayer->activated_by = $reqData['requestData']['updatedBy'];
        if ($reqData['requestData']['isTrail'] !== $king4kplayer->is_trial) {
            $king4kplayer->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 3 : 2);
        }
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance 
        $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }
        if ($king4kplayer->save()) {
            if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && $reqData['requestData']['playlist'][0]['playListUrl'] !== '') {
                $deleted = King4kPlayerPlaylist::where('device_id', $king4kplayer->_id)->delete();

                $insertPlaylist = [];
                foreach ($reqData['requestData']['playlist'] as $key => $value) {
                    if (!empty($value['playListUrl'])) {
                        $insertPlaylist[] =
                            array(
                                'device_id' => $king4kplayer->_id,
                                'playlist_name' => $value['playListName'] ?? "",
                                'url' => $value['playListUrl'] ?? "",
                                'created_at' => date('Y-m-d H:i:s')
                            );
                    }
                }
                $iboPlaylistUrl = King4kPlayerPlaylist::insert($insertPlaylist);
            }
            // if (!empty($iboPlaylistUrl)) {
            if ($reqData['requestData']['isTrail'] !== $king4kplayer->is_trial) {
                //update reseller credit point
                $this->__updateCreditPoint($reqData['requestData']['updatedBy'], $reqData['requestData']['isTrail']);
                //end updating credit popint of reseller

                //update here trans data
                $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
                $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $king4kplayer->expire_date, "reseller_id" => $king4kplayer->activated_by, "user_id" => $king4kplayer->id, "mac_address" => $king4kplayer->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint]);
                //end updating trans data
            }
            $returnData["status"] = true;
            $returnData["msg"] = "User updated successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" => $king4kplayer];
            $returnData["httpCode"] = "200";
            
        } else {
            $returnData["msg"] = "User could not be updated this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C20012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }

    private function __editIBOSSPlayer($reqData, $returnData)
    {
        $ibossplayer = IBOSSPlayerDevice::find($reqData['requestData']['userId']);
        //print_r($iboreseller);exit;
        $ibossplayer->mac_address = $reqData['requestData']['macAddress'];
        if(!empty($reqData['requestData']['appType'])){
            $ibossplayer->app_type = $reqData['requestData']['appType'];
        }
        if(!empty($reqData['requestData']['email'])){
            $ibossplayer->email = $reqData['requestData']['email'];
        }
        $ibossplayer->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], $ibossplayer->expire_date);
        $ibossplayer->activated_by = $reqData['requestData']['updatedBy'];
        if ($reqData['requestData']['isTrail'] !== $ibossplayer->is_trial) {
            $ibossplayer->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 3 : 2);
        }
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance 
        $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }
        if ($ibossplayer->save()) {
            if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && $reqData['requestData']['playlist'][0]['playListUrl'] !== '') {
                $deleted = IBOSSPlayerPlaylist::where('device_id', $ibossplayer->_id)->delete();

                $insertPlaylist = [];
                foreach ($reqData['requestData']['playlist'] as $key => $value) {
                    if (!empty($value['playListUrl'])) {
                        $insertPlaylist[] =
                            array(
                                'device_id' => $ibossplayer->_id,
                                'playlist_name' => $value['playListName'] ?? "",
                                'url' => $value['playListUrl'] ?? "",
                                'created_at' => date('Y-m-d H:i:s')
                            );
                    }
                }
                $iboPlaylistUrl = IBOSSPlayerPlaylist::insert($insertPlaylist);
            }
            // if (!empty($iboPlaylistUrl)) {
            if ($reqData['requestData']['isTrail'] !== $ibossplayer->is_trial) {
                //update reseller credit point
                $this->__updateCreditPoint($reqData['requestData']['updatedBy'], $reqData['requestData']['isTrail']);
                //end updating credit popint of reseller

                //update here trans data
                $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
                $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $ibossplayer->expire_date, "reseller_id" => $ibossplayer->activated_by, "user_id" => $ibossplayer->id, "mac_address" => $ibossplayer->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint]);
                //end updating trans data
            }
            $returnData["status"] = true;
            $returnData["msg"] = "User updated successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" => $ibossplayer];
            $returnData["httpCode"] = "200";
            
        } else {
            $returnData["msg"] = "User could not be updated this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C20012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }
    
     private function __addStreamInKTNPlayer($reqData, $macDetail)
    {
        $returnData = ["status" => false, "msg" => "Failed to add playlist detail!", "statusCode" => "C30010", "httpCode" => "501"];
        $retFlag = false;
        if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && isset($reqData['requestData']['playlist'][0]['url'])) {
            $deleted = KtnPlayerPlaylist::where('device_id', $macDetail->_id)->delete();
            $insertPlaylist = [];
            foreach ($reqData['requestData']['playlist'] as $key => $value) {
                if (!empty($value['url'])) {
                    $insertPlaylist[] =
                        array(
                            'device_id' => $macDetail->id,
                            'url' => isset($value['url']) ? str_replace('amp;', '', $value['url']): "",
                            'playlist_name' => $value['name'] ?? "",
                            'username' => $value['username'] ?? "",
                            'password' => $value['password'] ?? "",
                            'epg_url' => $value['epg_url'] ?? "",
                            'is_protected' => $value['is_protected'] ?? "",
                            'pin' => $value['pin'] ?? "",
                            'playlist_type' => $value['playlist_type'] ?? "general",
                            '__v' => 0
                        );
                }
            }
            $retFlag = KtnPlayerPlaylist::insert($insertPlaylist);
        }
        if ($retFlag) {
            $returnData["status"] = $retFlag;
            $returnData["msg"] = "Playlist added successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" => $retFlag, "PlaylistUrl" => $macDetail->playlist_url];
            $returnData["httpCode"] = "200";
        }
        return $returnData;
    }

    private function __addStreamInABEPlayer($reqData, $macDetail)
    {
        $returnData = ["status" => false, "msg" => "Failed to add playlist detail!", "statusCode" => "C30010", "httpCode" => "501"];
        $retFlag = false;
        if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && isset($reqData['requestData']['playlist'][0]['url'])) {
            $deleted = ABEPlayerPlaylist::where('device_id', $macDetail->_id)->delete();
            $insertPlaylist = [];
            foreach ($reqData['requestData']['playlist'] as $key => $value) {
                if (!empty($value['url'])) {
                    $insertPlaylist[] =
                        array(
                            'device_id' => $macDetail->id,
                            'url' => isset($value['url']) ? str_replace('amp;', '', $value['url']): "",
                            'playlist_name' => $value['name'] ?? "",
                            'username' => $value['username'] ?? "",
                            'password' => $value['password'] ?? "",
                            'epg_url' => $value['epg_url'] ?? "",
                            'is_protected' => $value['is_protected'] ?? "",
                            'pin' => $value['pin'] ?? "",
                            'playlist_type' => $value['playlist_type'] ?? "general",
                            '__v' => 0
                        );
                }
            }
            $retFlag = ABEPlayerPlaylist::insert($insertPlaylist);
        }
        if ($retFlag) {
            $returnData["status"] = $retFlag;
            $returnData["msg"] = "Playlist added successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" => $retFlag, "PlaylistUrl" => $macDetail->playlist_url];
            $returnData["httpCode"] = "200";
        }
        return $returnData;
    }

    private function __addStreamInBOBPlayer($reqData, $macDetail)
    {
        $returnData = ["status" => false, "msg" => "Failed to add playlist detail!", "statusCode" => "C30010", "httpCode" => "501"];
        $retFlag = false;
        if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && isset($reqData['requestData']['playlist'][0]['url'])) {
            $deleted = BOBPlayerPlaylist::where('device_id', $macDetail->_id)->delete();
            $insertPlaylist = [];
            foreach ($reqData['requestData']['playlist'] as $key => $value) {
                if (!empty($value['url'])) {
                    $insertPlaylist[] =
                        array(
                            'device_id' => $macDetail->id,
                            'url' => isset($value['url']) ? str_replace('amp;', '', $value['url']): "",
                            'playlist_name' => $value['name'] ?? "",
                            'username' => $value['username'] ?? "",
                            'password' => $value['password'] ?? "",
                            'epg_url' => $value['epg_url'] ?? "",
                            'is_protected' => $value['is_protected'] ?? "",
                            'pin' => $value['pin'] ?? "",
                            'playlist_type' => $value['playlist_type'] ?? "general",
                            '__v' => 0
                        );
                }
            }
            $retFlag = BOBPlayerPlaylist::insert($insertPlaylist);
        }
        if ($retFlag) {
            $returnData["status"] = $retFlag;
            $returnData["msg"] = "Playlist added successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" => $retFlag, "PlaylistUrl" => $macDetail->playlist_url];
            $returnData["httpCode"] = "200";
        }
        return $returnData;
    }

    private function __addStreamInMACPlayer($reqData, $macDetail)
    {
        $returnData = ["status" => false, "msg" => "Failed to add playlist detail!", "statusCode" => "C30010", "httpCode" => "501"];
        $retFlag = false;
        if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && isset($reqData['requestData']['playlist'][0]['url'])) {
            $deleted = MacPlayerPlaylist::where('device_id', $macDetail->_id)->delete();
            $insertPlaylist = [];
            foreach ($reqData['requestData']['playlist'] as $key => $value) {
                if (!empty($value['url'])) {
                    $insertPlaylist[] =
                        array(
                            'device_id' => $macDetail->id,
                            'url' => isset($value['url']) ? str_replace('amp;', '', $value['url']): "",
                            'playlist_name' => $value['name'] ?? "",
                            'username' => $value['username'] ?? "",
                            'password' => $value['password'] ?? "",
                            'epg_url' => $value['epg_url'] ?? "",
                            'is_protected' => $value['is_protected'] ?? "",
                            'pin' => $value['pin'] ?? "",
                            'playlist_type' => $value['playlist_type'] ?? "general",
                            '__v' => 0
                        );
                }
            }
            $retFlag = MacPlayerPlaylist::insert($insertPlaylist);
        }
        if ($retFlag) {
            $returnData["status"] = $retFlag;
            $returnData["msg"] = "Playlist added successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" => $retFlag, "PlaylistUrl" => $macDetail->playlist_url];
            $returnData["httpCode"] = "200";
        }
        return $returnData;
    }
    private function __addStreamInALLPlayer($reqData, $macDetail)
    {
        $returnData = ["status" => false, "msg" => "Failed to add playlist detail!", "statusCode" => "C30010", "httpCode" => "501"];
        $retFlag = false;
        if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && isset($reqData['requestData']['playlist'][0]['url'])) {
            $deleted = AllPlayerPlaylist::where('device_id', $macDetail->_id)->delete();
            $insertPlaylist = [];
            foreach ($reqData['requestData']['playlist'] as $key => $value) {
                if (!empty($value['url'])) {
                    $insertPlaylist[] =
                        array(
                            'device_id' => $macDetail->id,
                            'url' => isset($value['url']) ? str_replace('amp;', '', $value['url']): "",
                            'playlist_name' => $value['name'] ?? "",
                            'username' => $value['username'] ?? "",
                            'password' => $value['password'] ?? "",
                            'epg_url' => $value['epg_url'] ?? "",
                            'is_protected' => $value['is_protected'] ?? "",
                            'pin' => $value['pin'] ?? "",
                            'playlist_type' => $value['playlist_type'] ?? "general",
                            '__v' => 0
                        );
                }
            }
            $retFlag = AllPlayerPlaylist::insert($insertPlaylist);
        }
        if ($retFlag) {
            $returnData["status"] = $retFlag;
            $returnData["msg"] = "Playlist added successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" => $retFlag, "PlaylistUrl" => $macDetail->playlist_url];
            $returnData["httpCode"] = "200";
        }
        return $returnData;
    }

    private function __addStreamInHUSHPlayer($reqData, $macDetail)
    {
        $returnData = ["status" => false, "msg" => "Failed to add playlist detail!", "statusCode" => "C30010", "httpCode" => "501"];
        $retFlag = false;
        if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && isset($reqData['requestData']['playlist'][0]['url'])) {
            $deleted = HushPlaylist::where('device_id', $macDetail->_id)->delete();
            $insertPlaylist = [];
            foreach ($reqData['requestData']['playlist'] as $key => $value) {
                if (!empty($value['url'])) {
                    $insertPlaylist[] =
                        array(
                            'device_id' => $macDetail->id,
                            'url' => isset($value['url']) ? str_replace('amp;', '', $value['url']): "",
                            'playlist_name' => $value['name'] ?? "",
                            'username' => $value['username'] ?? "",
                            'password' => $value['password'] ?? "",
                            'epg_url' => $value['epg_url'] ?? "",
                            'is_protected' => $value['is_protected'] ?? "",
                            'pin' => $value['pin'] ?? "",
                            'playlist_type' => $value['playlist_type'] ?? "general",
                            '__v' => 0
                        );
                }
            }
            $retFlag = HushPlaylist::insert($insertPlaylist);
        }
        if ($retFlag) {
            $returnData["status"] = $retFlag;
            $returnData["msg"] = "Playlist added successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" => $retFlag, "PlaylistUrl" => $macDetail->playlist_url];
            $returnData["httpCode"] = "200";
        }
        return $returnData;
    }

    private function __addStreamInFAMILYPlayer($reqData, $macDetail)
    {
        $returnData = ["status" => false, "msg" => "Failed to add playlist detail!", "statusCode" => "C30010", "httpCode" => "501"];
        $retFlag = false;
        if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && isset($reqData['requestData']['playlist'][0]['url'])) {
            $deleted = FamilyPlayerPlaylist::where('device_id', $macDetail->_id)->delete();
            $insertPlaylist = [];
            foreach ($reqData['requestData']['playlist'] as $key => $value) {
                if (!empty($value['url'])) {
                    $insertPlaylist[] =
                        array(
                            'device_id' => $macDetail->id,
                            'url' => isset($value['url']) ? str_replace('amp;', '', $value['url']): "",
                            'playlist_name' => $value['name'] ?? "",
                            'username' => $value['username'] ?? "",
                            'password' => $value['password'] ?? "",
                            'epg_url' => $value['epg_url'] ?? "",
                            'is_protected' => $value['is_protected'] ?? "",
                            'pin' => $value['pin'] ?? "",
                            'playlist_type' => $value['playlist_type'] ?? "general",
                            '__v' => 0
                        );
                }
            }
            $retFlag = FamilyPlayerPlaylist::insert($insertPlaylist);
        }
        if ($retFlag) {
            $returnData["status"] = $retFlag;
            $returnData["msg"] = "Playlist added successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" => $retFlag, "PlaylistUrl" => $macDetail->playlist_url];
            $returnData["httpCode"] = "200";
        }
        return $returnData;
    }

    private function __addStreamInKING4KPlayer($reqData, $macDetail)
    {
        $returnData = ["status" => false, "msg" => "Failed to add playlist detail!", "statusCode" => "C30010", "httpCode" => "501"];
        $retFlag = false;
        if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && isset($reqData['requestData']['playlist'][0]['url'])) {
            $deleted = King4kPlayerPlaylist::where('device_id', $macDetail->_id)->delete();
            $insertPlaylist = [];
            foreach ($reqData['requestData']['playlist'] as $key => $value) {
                if (!empty($value['url'])) {
                    $insertPlaylist[] =
                        array(
                            'device_id' => $macDetail->id,
                            'url' => isset($value['url']) ? str_replace('amp;', '', $value['url']): "",
                            'playlist_name' => $value['name'] ?? "",
                            'username' => $value['username'] ?? "",
                            'password' => $value['password'] ?? "",
                            'epg_url' => $value['epg_url'] ?? "",
                            'is_protected' => $value['is_protected'] ?? "",
                            'pin' => $value['pin'] ?? "",
                            'playlist_type' => $value['playlist_type'] ?? "general",
                            '__v' => 0
                        );
                }
            }
            $retFlag = King4kPlayerPlaylist::insert($insertPlaylist);
        }
        if ($retFlag) {
            $returnData["status"] = $retFlag;
            $returnData["msg"] = "Playlist added successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" => $retFlag, "PlaylistUrl" => $macDetail->playlist_url];
            $returnData["httpCode"] = "200";
        }
        return $returnData;
    }

    private function __addStreamInIBOSSPlayer($reqData, $macDetail)
    {
        $returnData = ["status" => false, "msg" => "Failed to add playlist detail!", "statusCode" => "C30010", "httpCode" => "501"];
        $retFlag = false;
        if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && isset($reqData['requestData']['playlist'][0]['url'])) {
            $deleted = IBOSSPlayerPlaylist::where('device_id', $macDetail->_id)->delete();
            $insertPlaylist = [];
            foreach ($reqData['requestData']['playlist'] as $key => $value) {
                if (!empty($value['url'])) {
                    $insertPlaylist[] =
                        array(
                            'device_id' => $macDetail->id,
                            'url' => isset($value['url']) ? str_replace('amp;', '', $value['url']): "",
                            'playlist_name' => $value['name'] ?? "",
                            'username' => $value['username'] ?? "",
                            'password' => $value['password'] ?? "",
                            'epg_url' => $value['epg_url'] ?? "",
                            'is_protected' => $value['is_protected'] ?? "",
                            'pin' => $value['pin'] ?? "",
                            'playlist_type' => $value['playlist_type'] ?? "general",
                            '__v' => 0
                        );
                }
            }
            $retFlag = IBOSSPlayerPlaylist::insert($insertPlaylist);
        }
        if ($retFlag) {
            $returnData["status"] = $retFlag;
            $returnData["msg"] = "Playlist added successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" => $retFlag, "PlaylistUrl" => $macDetail->playlist_url];
            $returnData["httpCode"] = "200";
        }
        return $returnData;
    }
    
    private function __editIBOXXPlayer($reqData, $returnData)
    {
        $iboxxplayer = IBOXXPlayerDevice::find($reqData['requestData']['userId']);
        //print_r($iboreseller);exit;
        $iboxxplayer->mac_address = $reqData['requestData']['macAddress'];
        if(!empty($reqData['requestData']['appType'])){
            $iboxxplayer->app_type = $reqData['requestData']['appType'];
        }
        if(!empty($reqData['requestData']['email'])){
            $iboxxplayer->email = $reqData['requestData']['email'];
        }
        $iboxxplayer->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], $iboxxplayer->expire_date);
        $iboxxplayer->activated_by = $reqData['requestData']['updatedBy'];
        if ($reqData['requestData']['isTrail'] !== $iboxxplayer->is_trial) {
            $iboxxplayer->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 3 : 2);
        }
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance 
        $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }
        if ($iboxxplayer->save()) {
            if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && $reqData['requestData']['playlist'][0]['playListUrl'] !== '') {
                $deleted = IBOXXPlayerPlaylist::where('device_id', $iboxxplayer->_id)->delete();

                $insertPlaylist = [];
                foreach ($reqData['requestData']['playlist'] as $key => $value) {
                    if (!empty($value['playListUrl'])) {
                        $insertPlaylist[] =
                            array(
                                'device_id' => $iboxxplayer->_id,
                                'playlist_name' => $value['playListName'] ?? "",
                                'url' => $value['playListUrl'] ?? "",
                                'created_at' => date('Y-m-d H:i:s')
                            );
                    }
                }
                $iboPlaylistUrl = IBOXXPlayerPlaylist::insert($insertPlaylist);
            }
            // if (!empty($iboPlaylistUrl)) {
            if ($reqData['requestData']['isTrail'] !== $iboxxplayer->is_trial) {
                //update reseller credit point
                $this->__updateCreditPoint($reqData['requestData']['updatedBy'], $reqData['requestData']['isTrail']);
                //end updating credit popint of reseller

                //update here trans data
                $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
                $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $iboxxplayer->expire_date, "reseller_id" => $iboxxplayer->activated_by, "user_id" => $iboxxplayer->id, "mac_address" => $iboxxplayer->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint]);
                //end updating trans data
            }
            $returnData["status"] = true;
            $returnData["msg"] = "User updated successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" => $iboxxplayer];
            $returnData["httpCode"] = "200";
            
        } else {
            $returnData["msg"] = "User could not be updated this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C20012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }
    
    public function __addInIBOXXPlayerDevice($reqData, $returnData)
    {
        $iBoxxPlayerDevice = new IBOXXPlayerDevice();
        $iBoxxPlayerDevice->mac_address = $reqData['requestData']['macAddress'];
        $iBoxxPlayerDevice->device_key = (string)rand(100000, 999999);
        if(!empty($reqData['requestData']['appType'])){
            $iBoxxPlayerDevice->app_type = $reqData['requestData']['appType'];
        }
        if(!empty($reqData['requestData']['email'])){
            $iBoxxPlayerDevice->email = $reqData['requestData']['email'];
        }
        $iBoxxPlayerDevice->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], date('Y-m-d'));
        $iBoxxPlayerDevice->activated_by = $reqData['requestData']['createdBy'];
        $iBoxxPlayerDevice->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 3 : 2);
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance 
        $resellerData = IBOReseller::find($reqData['requestData']['createdBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }
        //end checking 
        if ($iBoxxPlayerDevice->save()) {
            if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && isset($reqData['requestData']['playlist'][0]['playListUrl'])) {
                $insertPlaylist = [];
                foreach ($reqData['requestData']['playlist'] as $key => $value) {
                    if (!empty($value['playListUrl'])) {
                        $insertPlaylist[] =
                            array(
                                'device_id' => $iBoxxPlayerDevice->id,
                                'playlist_name' => $value['playListName'] ?? "",
                                'url' => $value['playListUrl'] ?? "",
                                'is_protected' => $value['is_protected'] ?? 0,
                                'pin' => $value['pin'] ?? null,
                                'epg_url' => $value['epg_url'] ?? "",
                                'playlist_type' => $value['playlist_type'] ?? "general",
                                'username' => $value['username'] ?? "",
                                'password' => $value['password'] ?? "",
                                '_v'    => 0
                            );
                    }
                }
                IBOXXPlayerPlaylist::insert($insertPlaylist);
            }
            // if (!empty($virPlaylistUrl)) {
            //update reseller credit point
            $this->__updateCreditPoint($reqData['requestData']['createdBy'], $reqData['requestData']['isTrail']);
            //end updating credit popint of reseller
            $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
            $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $iBoxxPlayerDevice->expire_date, "reseller_id" => $iBoxxPlayerDevice->activated_by, "user_id" => $iBoxxPlayerDevice->id, "mac_address" => $iBoxxPlayerDevice->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint]);
            //update here trans data

            //end updating trans data
            $returnData["status"] = true;
            $returnData["msg"] = "User added successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = [$reqData['module'] . "User" => $iBoxxPlayerDevice];
            $returnData["httpCode"] = "200";
            // } else {
            //     $returnData["msg"] = "User's playlist detail could not be added this moment, Please try after sometime.!";
            //     $returnData["statusCode"] = "C10013";
            //     $returnData["httpCode"] = "501";
            // }
        } else {
            $returnData["msg"] = "User could not be added this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C10012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }
    
    public function getActiveResNotifAll(Request $request){
        $returnData = array("status" => false, "statusCode" => "10014", "data" => false, "msg" => "Something went wrong.");
        try {
            if ($request->isJson()) {
            
                $reqData = $request->all();
                if (!empty($reqData)) {
                    $reqData['isValid'] = isset($reqData['requestData']['isValid']) ? $reqData['requestData']['isValid'] : "";
                    
                    if (!empty($reqData['isValid'])) {
                        $strmActi = ResellerNotification::where('status', 1)->orderBy('created_at', 'desc')->get();
                        if(!empty($strmActi)){
                            $returnData = array("status" => true, "statusCode" => "00000", "data" => $strmActi, "msg" => "Notification fetched Succesfully.");    
                        }else{
                            $returnData = array("status" => false, "statusCode" => "12021", "data" => false, "msg" => "Failed to fetch notification detail!");    
                        }
                    } else {
                        $returnData = array("status" => false, "statusCode" => "12022", "data" => false, "msg" => "Invalid inputs!");
                    }
                }
            } else {
                $returnData = array("status" => false, "statusCode" => "12023", "data" => false, "msg" => "Invalid request type!");
            }
        } catch (\Exception $e) {
            return new JsonResponse(array("status" => false, "statusCode" => "12024", "data" => false, "msg" => env('APP_DEBUG') ? $e->getMessage() : "Invalid request type!"), 501);
        }
        return new JSonResponse($returnData, 200);
    }

    public function changeRoleToReseller(Request $request){
		try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];
            if ($request->isJson()) {
                $validatedData = Validator::make($reqData, [
                    'module' => ['required', Rule::in(['MASA', 'VIRGINIA', 'IBOAPP', 'ABEPLAYERTV', "BOBPLAYER"])],
                    'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER'])],
                    'requestId' => 'required',
                    'requestData.resellerId' => 'required|exists:App\IBOReseller,id'
                ]);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }
                $iboreseller = IBOReseller::find($reqData['requestData']['resellerId']);
                if (!empty($iboreseller)) {
					$oldParentReseller = IBOReseller::find($iboreseller->parent_reseller_id);
					$oldParentReseller->credit_point = ($iboreseller->credit_point + $oldParentReseller->credit_point);
					$oldParentReseller->save();
					$iboreseller->credit_point = 0;
					$iboreseller->parent_reseller_id = 0;
                    if ($iboreseller->save()) {
                        $returnData["status"] = true;
                        $returnData["msg"] = "SubReseller changing role successful!";
                        $returnData["statusCode"] = "000000";
                        $returnData["data"] = ["IBOReseller" => $iboreseller];
                        $returnData["httpCode"] = "200";
                    } else {
                        $returnData["msg"] = "Reseller assignment failed, Please check your input data and retry after sometime.!";
                        $returnData["statusCode"] = "C10012";
                        $returnData["httpCode"] = "501";
                    }
                } else {
                    $returnData["msg"] = "Subreseller detail not found, Please try after sometime.!";
                    $returnData["statusCode"] = "C10012";
                    $returnData["httpCode"] = "501";
                }
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
	}
	
	public function socialWidget(Request $request){
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];
            if ($request->isJson()) {
                $validatedData = Validator::make($reqData, [
                    'module' => ['required', Rule::in(['MASA', 'VIRGINIA', 'IBOAPP', 'ABEPLAYERTV', "BOBPLAYER"])],
                    'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER'])],
                    'requestId' => 'required',
                    'requestData.createdBy' => 'required',
                    'requestData.whatsapp' => 'required',
                    'requestData.teligram' => 'required',
                    'requestData.id' => 'required',
                ]);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }
 
                $socialWidget = IBOSocialWidget::find($reqData['requestData']['id']);
                $socialWidget->whatsapp_number = $reqData['requestData']['whatsapp'];
                $socialWidget->teligram_number = $reqData['requestData']['teligram'];
                $socialWidget->created_by = $reqData['requestData']['createdBy'];
                if ($socialWidget->save()) {
                    $returnData["status"] = true;
                    $returnData["msg"] = "SocialWidget details updated successfull!";
                    $returnData["statusCode"] = "000000";
                    $returnData["data"] = ["SocialWidget" => $socialWidget];
                    $returnData["httpCode"] = "200";
                } else {
                    $returnData["msg"] = "SocialWidget failed, Please check your input data and retry after sometime.!";
                    $returnData["statusCode"] = "C10012";
                    $returnData["httpCode"] = "501";
                }
                
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }        
    }

    public function getSocialDetails(Request $request){
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];
            if ($request->isJson()) {
                $validatedData = Validator::make($reqData, [
                    'module' => ['required', Rule::in(['MASA', 'VIRGINIA', 'IBOAPP', 'ABEPLAYERTV', "BOBPLAYER"])],
                    'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER'])],
                    'requestId' => 'required'
                ]);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }
 
                $socialWidget = IBOSocialWidget::select('id', 'whatsapp_number', 'teligram_number', 'created_by')
                ->orderBy('created_at', 'desc')
                ->first();
                if ($socialWidget) {
                    $returnData["status"] = true;
                    $returnData["msg"] = "SocialWidget fetched successful!";
                    $returnData["statusCode"] = "000000";
                    $returnData["data"] = ["SocialWidget" => $socialWidget];
                    $returnData["httpCode"] = "200";
                } else {
                    $returnData["msg"] = "SocialWidget not found!";
                    $returnData["statusCode"] = "C10012";
                    $returnData["httpCode"] = "501";
                }
                
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        } 
    }
    
    
        private function __editBOBProTV($reqData, $returnData)
    {
        $bobProTvDevice = BOBProTvDevice::find($reqData['requestData']['userId']);
        //print_r($iboreseller);exit;
        $bobProTvDevice->mac_address = $reqData['requestData']['macAddress'];
        if(!empty($reqData['requestData']['appType'])){
            $bobProTvDevice->app_type = $reqData['requestData']['appType'];
        }
        if(!empty($reqData['requestData']['email'])){
            $bobProTvDevice->email = $reqData['requestData']['email'];
        }
        $bobProTvDevice->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], $bobProTvDevice->expire_date);
        $bobProTvDevice->activated_by = $reqData['requestData']['updatedBy'];
        if ($reqData['requestData']['isTrail'] !== $bobProTvDevice->is_trial) {
            $bobProTvDevice->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 3 : 2);
        }
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance 
        $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
        if ($resellerData->group_id == '2' && $resellerData->credit_point < $creditPoint) {
            $returnData["msg"] = "You do not have enough credit balance to add/edit new user!";
            $returnData["statusCode"] = "C10019";
            $returnData["httpCode"] = "501";
            return $returnData;
        }
        if ($bobProTvDevice->save()) {
            if (is_array($reqData['requestData']['playlist']) && sizeof($reqData['requestData']['playlist']) > 0 && $reqData['requestData']['playlist'][0]['playListUrl'] !== '') {
                $deleted = BOBProTvPlaylist::where('device_id', $bobProTvDevice->_id)->delete();

                $insertPlaylist = [];
                foreach ($reqData['requestData']['playlist'] as $key => $value) {
                    if (!empty($value['playListUrl'])) {
                        $insertPlaylist[] =
                            array(
                                'device_id' => $bobProTvDevice->_id,
                                'playlist_name' => $value['playListName'] ?? "",
                                'url' => $value['playListUrl'] ?? "",
                                'created_at' => date('Y-m-d H:i:s')
                            );
                    }
                }
                $iboPlaylistUrl = bobProTvDevice::insert($insertPlaylist);
            }
            // if (!empty($iboPlaylistUrl)) {
            if ($reqData['requestData']['isTrail'] !== $bobProTvDevice->is_trial) {
                //update reseller credit point
                $this->__updateCreditPoint($reqData['requestData']['updatedBy'], $reqData['requestData']['isTrail']);
                //end updating credit popint of reseller

                $remarks = $reqData["requestData"]["activationRemarks"] ?? ""; 
                //update here trans data
                $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $bobProTvDevice->expire_date, "reseller_id" => $bobProTvDevice->activated_by, "user_id" => $bobProTvDevice->id, "mac_address" => $bobProTvDevice->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint]);
                //end updating trans data
            }
            $returnData["status"] = true;
            $returnData["msg"] = "User updated successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" => $bobProTvDevice];
            $returnData["httpCode"] = "200";
            
        } else {
            $returnData["msg"] = "User could not be updated this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C20012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }
    
    
    public function searchMac(Request $request){
        try {
            $reqData = $request->all();
            $returnData = [
                "status" => false,
                "statusCode" => "C10000",
                "msg" => "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
                "httpCode" => "501"
            ];
            if ($request->isJson()) {
                $validatedData = Validator::make($reqData, [
                    'channelId' => ['required', Rule::in(config('channels.searchUserChannel'))],
                    'requestId' => 'required',
                    'requestData.macAddress' => 'required|min:3',
                ]);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }
                $searchMac = UserActiTranLogs::where('mac_address', $reqData['requestData']['macAddress'])->
                    whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))->
                    whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))->with('resellerDetail:ibocdn_resellers.id,email');
                $archSearchMac = ArchUserActiTransLogs::where('mac_address', $reqData['requestData']['macAddress'])->
                    whereOr('mac_address', strtolower($reqData['requestData']['macAddress']))->
                    whereOr('mac_address', strtoupper($reqData['requestData']['macAddress']))->with('resellerDetail:ibocdn_resellers.id,email');
                $results = $searchMac->union($archSearchMac)->get();
                if($results){
                    $returnData["status"] = true;
                    $returnData["msg"] = "User found successfully!";
                    $returnData["statusCode"] = "000000";
                    $returnData["data"] = ["Users" => $results];
                    $returnData["httpCode"] = "200";
                } else {
                    $returnData["msg"] = "User not found, Please input correct Mac Address!";
                    $returnData["statusCode"] = "C10012";
                    $returnData["httpCode"] = "202";
                }
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
    }

    
    
    
}
