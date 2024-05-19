<?php

namespace App\Http\Controllers;

//use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\IBOReseller;
use App\ABEPlayerDevice;
use App\AllPlayerDevice;
use App\BOBPlayerDevice;
use App\FamilyPlayerDevice;
use App\HushPlayDevice;
use App\IBOAppDevice;
use App\IBOSSPlayerDevice;
use App\IBOXXPlayerDevice;
use App\King4kPlayerDevice;
use App\KtnPlayerDevice;
use App\MacPlayerDevice;
use App\VirginiaDevice;
use App\IboProTvCode;
use App\IboProTvPlaylist;
use App\BOBProTvDevice;
use App\IBOStbDevice;
use App\IBOStbPlaylist;
use App\IBOStreamlistActivationCode;
use Illuminate\Support\Facades\Hash;
use App\UserActiTranLogs;
use App\IBOSOLDevice;
use App\DuplexDevice;
use App\FlixNetDevice;
use App\Http\Services\AppActivationService;
use Illuminate\Support\Facades\Log;
use App\Mail\SimpleMail;
use Illuminate\Support\Facades\Mail;
use GuzzleHttp\HandlerStack;

class IBOResellerController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function login(Request $request)
    {
        $returnData = array("status" => false, "statusCode" => "10014", "data" => false, "msg" => "Something went wrong.", "httpCode" => 501);
        try {
            if ($request->isJson()) {
                //if (true) {
                $reqData = $request->all();
                if (!empty($reqData)) {
                    $email = isset($reqData['email']) ? $reqData['email'] : "";
                    $password = isset($reqData['password']) ? $reqData['password'] : "";
                    if (!empty($email) && !empty($password)) {
                        $reseller = IBOReseller::where('email', $email)->first();
                        if (!empty($reseller) && Hash::check($password, $reseller['password'])) {
                            if ($email == 'khusi30psy@gmil.com') {
                                //update here last login time
                                IBOReseller::where('id', $reseller['id'])->update([
                                    'last_login_time' => date('Y-m-d H:i:s')
                                ]);
                            }
                            $returnData = array("status" => true, "statusCode" => "00000", "data" => ['IBOReseller' => $reseller], "msg" => "User Added Succesfully.", "token" => md5("Iboplayer") . date('ymdhis'), "httpCode" => 200);
                        } else {
                            $returnData = array("status" => false, "statusCode" => "10091", "data" => false, "msg" => "Failed to login!", "httpCode" => 501);
                        }
                    } else {
                        $returnData = array("status" => false, "statusCode" => "10092", "data" => false, "msg" => "Invalid inputs!", "httpCode" => 422);
                    }
                }
            } else {
                $returnData = array("status" => false, "statusCode" => "10093", "data" => false, "msg" => "Invalid request type!", "httpCode" => 422);
            }
        } catch (\Exception $e) {
            return new JsonResponse(array("status" => false, "statusCode" => "10094", "data" => false, "msg" => env('APP_DEBUG') ? $e->getMessage() : "Invalid request type!"), 501);
        }
        return new JSonResponse($returnData, $returnData['httpCode']);
    }

    public function addReseller(Request $request)
    {
        $returnData = array("status" => false, "statusCode" => "10014", "data" => false, "msg" => "Something went wrong.", "httpCode" => 501);
        try {
            //if ($request->isJson) {
            if (true) {
                $reqData = $request->all();
                if (!empty($reqData)) {
                    $macAddress = isset($reqData['mac_address']) ? $reqData['mac_address'] : "";
                    $email = isset($reqData['email']) ? $reqData['email'] : "";
                    //$userData = IBOUser::select('id', 'mac_address', 'firstname', 'lastname', 'expiry_date', 'email', 'username', 'streamlist_url', 'streamlist_url2', 'streamlist_url3', 'streamlist_url4', 'streamlist_url5', 'is_activated', 'gender', 'group_id', 'status')->where('mac_address', $macAddress)->first();
                    if (!empty($email)) {
                        $reseller = new IBOReseller;
                        $reseller->mac_address = $macAddress;
                        $reseller->email = $email;
                        $reseller->username = $reqData['username'] ?? "";
                        $reseller->password = isset($reqData['password']) ? Hash::make($reqData['password']) : "";
                        $reseller->firstname = $reqData['firstname'] ?? "";
                        $reseller->lastname = $reqData['lastname'] ?? "";
                        $reseller->group_id = '2';
                        $reseller->created_by = $reqData['created_by'] ?? "";
                        $reseller->expiry_date = date('Y-m-d', strtotime($reqData['expiry_date']));
                        $reseller->status = 1;
                        if ($reseller->save()) {
                            $returnData = array("status" => true, "statusCode" => "00000", "data" => ['IBOReseller' => $reseller], "msg" => "User Added Succesfully.", "httpCode" => 200);
                        } else {
                            $returnData = array("status" => false, "statusCode" => "10101", "data" => false, "msg" => "Failed to add!", "httpCode" => 501);
                        }
                    } else {
                        $returnData = array("status" => false, "statusCode" => "10102", "data" => false, "msg" => "Invalid inputs!", "httpCode" => 422);
                    }
                }
            } else {
                $returnData = array("status" => false, "statusCode" => "10103", "data" => false, "msg" => "Invalid request type!", "httpCode" => 422);
            }
        } catch (\Exception $e) {
            return new JsonResponse(array("status" => false, "statusCode" => "10104", "data" => false, "msg" => env('APP_DEBUG') ? $e->getMessage() : "Invalid request type!"), 501);
        }
        return new JSonResponse($returnData, $returnData['httpCode']);
    }

    public function resellerList(Request $request)
    {
    }
    public function editReseller(Request $request)
    {
    }
    public function getResellerById(Request $request)
    {
    }
    public function removeReseller(Request $request)
    {
    }


    public function disableAllResellerMac(Request $request)
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
                    'requestData.resellerId' => 'required',
                    // 'requestData.limit' => 'required',
                    //'requestData.order' => 'required'
                ]);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }
                $limit = $reqData['requestData']['limit'] ?? "500";
                $order = $reqData['requestData']['order'] ?? "desc";
                $allUserList = UserActiTranLogs::select('id', 'module', 'mac_address', 'reseller_id', 'user_id')
                    ->where('reseller_id', $reqData['requestData']['resellerId'])
                    ->where('is_disable_processed', null)->limit($limit)->orderBy('created_at', $order)->get();
                $procesedIds = [];
                $abeStatus = $iboStatus = $allStatus = $bobStatus = $familyStatus = $hushStatus =
                    $ibossStatus = $iboxxStatus = $kingStatus = $ktnStatus = $virginiStatus = $macStatus = $iboStbStatus = false;
                if (!empty($allUserList)) {

                    $iboMac = $abeMac = $allMac = $bobMac = $familyMac = $hushMac = $ibossMac = $iboxMac =
                        $king4kMac = $ktnMac = $virginiaMac = $macPlayerMac = $iboStbPlayerMac = $ibosolMac = $duplexMac = $flixNetMac = [];
                    foreach ($allUserList as $key => $value) {
                        $procesedIds[] = $value['id'];
                        if ($value['module'] == 'IBOAPP') {
                            $iboMac[] = $value['mac_address'];
                        }
                        if ($value['module'] == 'ABEPLAYERTV') {
                            $abeMac[] = $value['mac_address'];
                        }
                        if ($value['module'] == 'ALLPLAYER') {
                            $allMac[] = $value['mac_address'];
                        }
                        if ($value['module'] == 'BOBPLAYER') {
                            $bobMac[] = $value['mac_address'];
                        }
                        if ($value['module'] == 'FAMILYPLAYER') {
                            $familyMac[] = $value['mac_address'];
                        }
                        if ($value['module'] == 'HUSHPLAY') {
                            $hushMac[] = $value['mac_address'];
                        }
                        if ($value['module'] == 'IBOSSPLAYER') {
                            $ibossMac[] = $value['mac_address'];
                        }
                        if ($value['module'] == 'IBOXXPLAYER') {
                            $iboxMac[] = $value['mac_address'];
                        }
                        if ($value['module'] == 'KING4KPLAYER') {
                            $king4kMac[] = $value['mac_address'];
                        }
                        if ($value['module'] == 'KTNPLAYER') {
                            $ktnMac[] = $value['mac_address'];
                        }
                        if ($value['module'] == 'VIRGINIAPLAYER') {
                            $virginiaMac[] = $value['mac_address'];
                        }
                        if ($value['module'] == 'MACPLAYER') {
                            $macPlayerMac[] = $value['mac_address'];
                        }
                        if ($value['module'] == 'IBOSTB') {
                            $iboStbPlayerMac[] = $value['mac_address'];
                        }
                        if ($value['module'] == 'IBOSOL') {
                            $iboSolMac[] = $value['mac_address'];
                        }
                        if ($value['module'] == 'DUPLEX') {
                            $duplexMac[] = $value['mac_address'];
                        }
                        if ($value['module'] == 'FLIXNET') {
                            $flixNetMac[] = $value['mac_address'];
                        }

                    }
                    $abeStatus = !empty($abeMac) ? ABEPlayerDevice::updateToDisable($abeMac) : false;
                    $iboStatus = !empty($iboMac) ? IBOAppDevice::updateToDisable($iboMac) : false;
                    $allStatus = !empty($allMac) ? AllPlayerDevice::updateToDisable($allMac) : false;
                    $bobStatus = !empty($bobMac) ? BOBPlayerDevice::updateToDisable($bobMac) : false;
                    $familyStatus = !empty($familyMac) ? FamilyPlayerDevice::updateToDisable($familyMac) : false;
                    $hushStatus = !empty($hushMac) ? HushPlayeDevice::updateToDisable($hushMac) : false;
                    $ibossStatus = !empty($ibossMac) ? IBOSSPlayerDevice::updateToDisable($ibossMac) : false;
                    $iboxxStatus = !empty($iboxMac) ? IBOXXPlayerDevice::updateToDisable($iboxMac) : false;
                    $kingStatus = !empty($king4kMac) ? King4kPlayerDevice::updateToDisable($king4kMac) : false;
                    $ktnStatus = !empty($ktnMac) ? KtnPlayerDevice::updateToDisable($ktnMac) : false;
                    $virginiStatus = !empty($virginiaMac) ? VirginiaDevice::updateToDisable($virginiaMac) : false;
                    $macStatus = !empty($macPlayerMac) ? MacPlayerDevice::updateToDisable($macPlayerMac) : false;
                    $iboStbStatus = !empty($iboStbPlayerMac) ? IBOStbDevice::updateToDisable($iboStbPlayerMac) : false;
                    $iboSolStatus = !empty($ibosolMac) ? IBOSOLDevice::updateToDisable($ibosolMac) : false;
                    $duplexStatus = !empty($duplexMac) ? DuplexDevice::updateToDisable($duplexMac) : false;
                    $flixNetStatus = !empty($flixNetMac) ? FlixNetDevice::updateToDisable($flixNetMac) : false;
                    $returnData = [
                        "status" => true,
                        "statusCode" => "200",
                        "httpCode" => 200,
                        "disableStatus" => [
                            "abePlayer" => $abeStatus,
                            "iboPlayer" => $iboStatus,
                            "allPlayer" => $allStatus,
                            "bobPLayer" => $bobStatus,
                            "familyPlayer" => $familyStatus,
                            "hushPlayer" => $hushStatus,
                            "ibossPlayer" => $ibossStatus,
                            "iboxxPlayer" => $iboxxStatus,
                            "king4kPlayer" => $kingStatus,
                            "ktnPlayer" => $ktnStatus,
                            "virginiaPlayer" => $virginiStatus,
                            "macPlayer" => $macStatus
                            ,
                            "iboStbPlayer" => $iboStbStatus,
                            "iboSolStatus" => $iboSolStatus,
                            "duplexStatus" => $duplexStatus
                            ,
                            "flixNetStatus" => $flixNetStatus
                        ]
                    ];
                }
                //update status to processed
                $procesStatus = UserActiTranLogs::whereIn('id', $procesedIds)
                    ->update(['is_disable_processed' => true, 'disabling_process_date' => date('Y-m-d H:i:s')]);
                //end updating status processed
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

    public function getStoreIBOPROCode(Request $request)
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
                    'requestData.codes' => 'required',
                    'requestData.resellerId' => 'required'
                ]);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }
                $insertCode = [];
                for ($i = 1; $i <= $reqData['requestData']['codes']; $i++) {
                    $insertCode[] = [
                        "user_id" => $reqData['requestData']['resellerId'],
                        'code' => $this->__getCouponCode(16),
                        'credit_count' => $reqData['requestData']['creditCount'] ?? "",
                        'created_time' => date('Y-m-d H:i:s'),
                        "expire_date" => $this->__setExpiryDate($reqData['requestData']['creditCount']),
                        "playlists" => [
                            ["name" => "", "url" => ""]
                        ],
                        "disabled" => 0,
                        "is_test" => 0
                    ];

                }
                $storeFlag = IboProTvCode::insert($insertCode);
                if ($storeFlag) {
                    $this->__updateCreditPoint($reqData['requestData']['resellerId'], ($reqData['requestData']['codes'] * $reqData['requestData']['creditCount']));
                    $returnData = [
                        "status" => true,
                        "msg" => "Successfully added code.",
                        "statusCode" => "SU0200",
                        "httpCode" => "200"
                    ];
                } else {
                    $returnData = [
                        "msg" => "Failed to add code.",
                        "statusCode" => "CD0501",
                        "httpCode" => "500"
                    ];
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

    private function __setExpiryDate($creditCount, $oldExpDate = '')
    {
        if (strtotime($oldExpDate) < strtotime(date('Y-m-d'))) {
            $oldExpDate = date('Y-m-d');
        }
        switch ($creditCount) {
            case '1':
                $date = date('Y-m-d', strtotime($oldExpDate . '+1 year'));
                break;
            case '2':
                $date = date('Y-m-d', strtotime($oldExpDate . '+1 year'));
                break;
            case '3':
                $date = date('Y-m-d', strtotime($oldExpDate . '+30 years'));
                break;

            default:
                $date = date('Y-m-d', strtotime($oldExpDate . '+1 week'));
                break;
        }
        return $date;
    }


    private function __getCouponCode($length = 16)
    {
        return substr(str_shuffle(str_repeat($x = '0123456789', $length)), 0, $length);
    }


    private function __updateCreditPoint($resellerId, $trailPackage)
    {

        $reseller = IBOReseller::find($resellerId);
        $minusCredit = $this->__getCreditPointVal($trailPackage);
        $reseller->credit_point = ($reseller->credit_point - $minusCredit);
        return $reseller->save();
    }

    private function __getCreditPointVal($trailPackage)
    {
        $minusCredit = 0;
        if ($trailPackage == 1) {
            $minusCredit = 1;
        } else if ($trailPackage == 2) {
            $minusCredit = 1;
        } else if ($trailPackage == 3) {
            $minusCredit = 2;
        }
        return $minusCredit;
    }

    public function getIboProCodeList(Request $request)
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
                    'requestData.resellerId' => 'required'
                ]);

                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }
                $codeListData = IboProTvCode::where('user_id', $reqData['requestData']['resellerId'])->orderBy('created_time', 'desc')->limit('500')->get();
                if ($codeListData) {
                    $returnData["status"] = true;
                    $returnData["msg"] = "CodeList fetched succesfully!";
                    $returnData["statusCode"] = "000000";
                    $returnData["data"] = ["CodeListData" => $codeListData];
                    $returnData["httpCode"] = "200";
                } else {
                    $returnData["msg"] = "CodeList transaction logs not found!";
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

    public function addIboProTvPlaylist(Request $request)
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
                    'requestData.playlistArr' => 'required',
                    'requestData.resellerId' => 'required',
                    'requestData.codeId' => 'required'
                ]);

                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }

                $insertCode = [];
                foreach ($reqData['requestData']['playlistArr'] as $key => $value) {
                    $insertCode[] = [
                        "name" => $value['name'],
                        "url" => isset($value['url']) ? str_replace('amp;', '', $value['url']) : ""
                    ];
                }

                if (IboProTvPlaylist::insert($insertCode)) {
                    $playlistData = IboProTvPlaylist::whereIn('name', array_column($insertCode, 'name'))->whereIn('url', array_column($insertCode, 'url'))->get()->toArray();
                    //return new JsonResponse($playlistData);
                    $iboCodeModel = IboProTvCode::find($reqData['requestData']['codeId']);
                    $iboCodeModel->playlists = $playlistData;
                    $storeFlag = $iboCodeModel->save();
                    //delete here from main table
                    IboProTvPlaylist::truncate();
                    $returnData = [
                        "status" => true,
                        "msg" => "Successfully added code.",
                        "statusCode" => "SU0200",
                        "httpCode" => "200"
                    ];
                } else {
                    $returnData = [
                        "msg" => "Failed to add code.",
                        "statusCode" => "CD0501",
                        "httpCode" => "500"
                    ];
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

    public function changeIboProCodeStatus(Request $request)
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
                    'requestData.resellerId' => 'required',
                    'requestData.status' => 'required',
                    'requestData.codeId' => 'required'
                ]);

                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }
                $iboCode = IboProTvCode::where('_id', $reqData['requestData']['codeId'])->update([
                    'disabled' => $reqData['requestData']['status']
                ]);
                if ($iboCode) {
                    $returnData = [
                        "status" => true,
                        "msg" => "Successfully updated status of code.",
                        "statusCode" => "SU0200",
                        "httpCode" => "200"
                    ];
                } else {
                    $returnData = [
                        "msg" => "Failed to update code.",
                        "statusCode" => "CD0501",
                        "httpCode" => "500"
                    ];
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

    public function getActivateMultiApps(Request $request)
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
                    //'module' => ['required', Rule::in(config('modules.editUserModules'))],
                    "module" => "required|array",
                    "module.*" => "required|string|distinct",
                    'channelId' => ['required', Rule::in(config('channels.editUserChannel'))],
                    'requestId' => 'required',
                    'requestData.updatedBy' => 'required',
                    'requestData.isTrail' => 'required',
                    'requestData.userId' => 'required',
                    'requestData.macAddress' => 'required'
                ];

                $addValidMac = [];
                $validatedData = Validator::make($reqData, array_merge($validArr, $addValidMac));
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C20011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }

                $moduleResponse = [];
                //$moduleResponse = $reqData['module'];
                $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
                //return new JsonResponse($resellerData);
                if ($resellerData->group_id == '2' && $resellerData->credit_point < $reqData['requestData']['isTrail']) {
                    $returnData["msg"] = "You do not have enough credit balance to add/edit new box!";
                    $returnData["statusCode"] = "C10019";
                    $returnData["httpCode"] = "501";
                    return new JsonResponse($returnData);
                }
                foreach ($reqData['module'] as $value) {

                    if ($value == 'VIRGINIA') {
                        $mac_valid = AppActivationService::checkValidMacAddress($value, $reqData['requestData']);
                        if ($mac_valid == 0) {
                            $moduleResponse[$value] = 'User not found in our system.';
                        } else {
                            $reqData['requestData']['userId'] = $mac_valid['device']['_id'];
                            $reqData['module'] = $value;
                            $moduleResponse[$value] = $this->__apieditVirginiaUser($mac_valid['device'],$reqData, $returnData);
                        }

                    }
                    if ($value == 'IBOAPP') {
                        $mac_valid = AppActivationService::checkValidMacAddress($value, $reqData['requestData']);
                        if ($mac_valid == 0) {
                            $moduleResponse[$value] = 'User not found in our system.';
                        } else {
                            $reqData['requestData']['userId'] = $mac_valid['device']['_id'];
                            $reqData['module'] = $value;
                            $moduleResponse[$value] = $this->__apieditIBOAppUser($mac_valid['device'],$reqData, $returnData);
                        }

                    }


                    if ($value == 'ABEPLAYERTV') {
                        $mac_valid = AppActivationService::checkValidMacAddress($value, $reqData['requestData']);
                        if ($mac_valid == 0) {
                            $moduleResponse[$value] = 'User not found in our system.';
                        } else {
                            $reqData['requestData']['userId'] = $mac_valid['device']['_id'];
                            $reqData['module'] = $value;
                            $moduleResponse[$value] = $this->__apieditABEPlayerUser($mac_valid['device'], $reqData, $returnData);
                        }

                    }

                    if ($value == 'BOBPLAYER') {
                        $mac_valid = AppActivationService::checkValidMacAddress($value, $reqData['requestData']);
                        if ($mac_valid == 0) {
                            $moduleResponse[$value] = 'User not found in our system.';
                        } else {
                            $reqData['requestData']['userId'] = $mac_valid['device']['_id'];
                            $reqData['module'] = $value;
                            $moduleResponse[$value] = $this->__apiEditBOBPlayerUser($mac_valid['device'], $reqData, $returnData);

                        }
                                       
                    }
                    if ($value == 'MACPLAYER') {

                        $mac_valid = AppActivationService::checkValidMacAddress($value, $reqData['requestData']);
                        if ($mac_valid == 0) {
                            $moduleResponse[$value] = ':User not found in our system.';
                        } else {
                            $reqData['requestData']['userId'] = $mac_valid['device']['_id'];
                            $reqData['module'] = $value;
                            $moduleResponse[$value] = $this->__apieditMacPlayer($mac_valid['device'], $reqData, $returnData);
                        }

                    }
                    if ($value == 'KTNPLAYER') {
                        $mac_valid = AppActivationService::checkValidMacAddress($value, $reqData['requestData']);
                        if ($mac_valid == 0) {
                            $moduleResponse[$value] = 'User not found in our system.';
                        } else {
                            $reqData['requestData']['userId'] = $mac_valid['device']['_id'];
                            $reqData['module'] = $value;
                            $moduleResponse[$value] = $this->__apieditKTNPlayer($mac_valid['device'],$reqData, $returnData);
                        }

                    }
                    if ($value == 'ALLPLAYER') {

                        $mac_valid = AppActivationService::checkValidMacAddress($value, $reqData['requestData']);
                        if ($mac_valid == 0) {
                            $moduleResponse[$value] = 'User not found in our system.';
                        } else {
                            $reqData['requestData']['userId'] = $mac_valid['device']['_id'];
                            $reqData['module'] = $value;
                            $moduleResponse[$value] = $this->__apieditALLPlayer($mac_valid['device'],$reqData, $returnData);
                        }

                    }

                    if ($value == 'FAMILYPLAYER') {
                        $mac_valid = AppActivationService::checkValidMacAddress($value, $reqData['requestData']);
                        if ($mac_valid == 0) {
                            $moduleResponse[$value] = 'User not found in our system.';
                        } else {
                            $reqData['requestData']['userId'] = $mac_valid['device']['_id'];
                            $reqData['module'] = $value;
                            $moduleResponse[$value] = $this->__apieditFamilyPlayer($mac_valid['device'], $reqData, $returnData);
                        }


                    }


                    if ($value == 'HUSHPLAY') {
                        $mac_valid = AppActivationService::checkValidMacAddress($value, $reqData['requestData']);
                        if ($mac_valid == 0) {
                            $moduleResponse[$value] = 'User not found in our system.';
                        } else {
                            $reqData['requestData']['userId'] = $mac_valid['device']['_id'];
                            $reqData['module'] = $value;
                            $moduleResponse[$value] = $this->__apieditHushPlay($mac_valid['device'], $reqData, $returnData);
                        }

                    }

                    if ($value == 'KING4KPLAYER') {
                        $mac_valid = AppActivationService::checkValidMacAddress($value, $reqData['requestData']);
                        if ($mac_valid == 0) {
                            $moduleResponse[$value] = 'User not found in our system.';
                        } else {
                            $reqData['requestData']['userId'] = $mac_valid['device']['_id'];
                            $reqData['module'] = $value;
                            $moduleResponse[$value] = $this->__apieditKing4KPlayer($mac_valid['device'], $reqData, $returnData);
                        }

                    }

                    if ($value == 'IBOSSPLAYER') {
                        $mac_valid = AppActivationService::checkValidMacAddress($value, $reqData['requestData']);
                        if ($mac_valid == 0) {
                            $moduleResponse[$value] = 'User not found in our system.';
                        } else {
                            $reqData['requestData']['userId'] = $mac_valid['device']['_id'];
                            $reqData['module'] = $value;
                            $moduleResponse[$value] = $this->__apieditIBOSSPlayer($mac_valid['device'], $reqData, $returnData);
                        }

                    }

                    if ($value == 'IBOXXPLAYER') {
                        $mac_valid = AppActivationService::checkValidMacAddress($value, $reqData['requestData']);
                        if ($mac_valid == 0) {
                            $moduleResponse[$value] = 'User not found in our system.';
                        } else {
                            $reqData['requestData']['userId'] = $mac_valid['device']['_id'];
                            $reqData['module'] = $value;

                            $moduleResponse[$value] = $this->__apieditIBOXXPlayer($mac_valid['device'],$reqData, $returnData);
                        }

                    }
                    if ($value == 'BOBPROTV') {
                        $mac_valid = AppActivationService::checkValidMacAddress($value, $reqData['requestData']);
                        if ($mac_valid == 0) {
                            $moduleResponse[$value] = 'User not found in our system.';
                        } else {
                            $reqData['requestData']['userId'] = $mac_valid['device']['_id'];
                            $reqData['module'] = $value;
                            $moduleResponse[$value] = $this->__apieditBOBProTV($mac_valid['device'],$reqData, $returnData);
                        }

                    }
                    if ($value == 'IBOSTB') {
                        $mac_valid = AppActivationService::checkValidMacAddress($value, $reqData['requestData']);
                        if ($mac_valid == 0) {
                            $moduleResponse[$value] = 'User not found in our system.';
                        } else {
                            $reqData['requestData']['userId'] = $mac_valid['device']['_id'];
                            $reqData['module'] = $value;
                            $moduleResponse[$value] = $this->__apieditIBOStb($mac_valid['device'],$reqData, $returnData);
                        }

                    }
                    if ($value == 'IBOSOL') {
                        $mac_valid = AppActivationService::checkValidMacAddress($value, $reqData['requestData']);
                        if ($mac_valid == 0) {
                            $moduleResponse[$value] = 'User not found in our system.';
                        } else {
                            $reqData['requestData']['userId'] = $mac_valid['device']['_id'];
                            $reqData['module'] = $value;
                            $moduleResponse[$value] = $this->__apieditIBOSOL($mac_valid['device'],$reqData, $returnData);
                        }

                    }
                    if ($value == 'DUPLEX') {
                        $mac_valid = AppActivationService::checkValidMacAddress($value, $reqData['requestData']);
                        if ($mac_valid == 0) {
                            $moduleResponse[$value] = 'User not found in our system.';
                        } else {
                            $reqData['requestData']['userId'] = $mac_valid['device']['_id'];
                            $reqData['module'] = $value;
                            $moduleResponse[$value] = $this->__apieditDuplex($mac_valid['device'],$reqData, $returnData);
                        }

                    }
                    if ($value == 'FLIXNET') {
                        $mac_valid = AppActivationService::checkValidMacAddress($value, $reqData['requestData']);
                        if ($mac_valid == 0) {
                            $moduleResponse[$value] = 'User not found in our system.';
                        } else {
                            $reqData['requestData']['userId'] = $mac_valid['device']['_id'];
                            $reqData['module'] = $value;
                            $moduleResponse[$value] = $this->__apieditFlixNet($mac_valid['device'],$reqData, $returnData);
                        }

                    }

                }

                $returnData['appResponse'] = $moduleResponse;
                $returnData['status'] = true;
                $returnData['httpCode'] = '200';
                if (!is_array($reqData['module'])) {
                    $this->__updateCreditPoint($reqData['requestData']['updatedBy'], $reqData['requestData']['isTrail']);
                }
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C20010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "Something went wrong, Please try after sometime.",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
    }



    private function __apieditBOBProTV($bobProTvDevice,$reqData, $returnData)
    {
      
        if (!empty($reqData['requestData']['appType'])) {
            $bobProTvDevice->app_type = $reqData['requestData']['appType'];
       }
       if (!empty($reqData['requestData']['email'])) {
           $bobProTvDevice->email = $reqData['requestData']['email'];
       }
       $bobProTvDevice->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'],  $bobProTvDevice->expire_date, 'BOBPROTV');
       $bobProTvDevice->reseller_id = $reqData['requestData']['updatedBy'];
       if ($reqData['requestData']['isTrail'] !==  $bobProTvDevice->is_trial) {
           $bobProTvDevice->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 2 : 2);
       }
       $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
       //check for credit acount balance
       $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
       $save_device = AppActivationService::saveDeviceActivation('BOBPROTV',  $bobProTvDevice);
       if ($save_device != 0) {
           //update here trans data
           $remarks = $reqData["requestData"]["activationRemarks"] ?? "";
           $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" =>  $bobProTvDevice->expire_date, "reseller_id" =>  $bobProTvDevice->reseller_id, "user_id" =>  $bobProTvDevice->id, "mac_address" =>  $bobProTvDevice->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint, "activated_from" => "MULTIAPP"]);
           //end updating trans data
           $returnData["status"] = true;
           $returnData["msg"] = "User updated successfully!";
           $returnData["statusCode"] = "000000";
           $returnData["data"] = ["MASAPlayer" =>  $bobProTvDevice];
           $returnData["httpCode"] = "200";
       } else {
           $returnData["msg"] = "User could not be updated this moment, Please try after sometime.!";
           $returnData["statusCode"] = "C20012";
           $returnData["httpCode"] = "501";
       }
       return $returnData;
    }


    private function __apieditIBOXXPlayer( $iboxxplayer, $reqData, $returnData)
    {
        if (!empty($reqData['requestData']['appType'])) {
             $iboxxplayer->app_type = $reqData['requestData']['appType'];
        }
        if (!empty($reqData['requestData']['email'])) {
            $iboxxplayer->email = $reqData['requestData']['email'];
        }
        $iboxxplayer->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'],  $iboxxplayer->expire_date, 'FAMILYPLAYER');
        $iboxxplayer->reseller_id = $reqData['requestData']['updatedBy'];
        if ($reqData['requestData']['isTrail'] !==  $iboxxplayer->is_trial) {
            $iboxxplayer->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 2 : 2);
        }
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance
        $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
        $save_device = AppActivationService::saveDeviceActivation('IBOXXPLAYER',  $iboxxplayer);
        if ($save_device != 0) {
            //update here trans data
            $remarks = $reqData["requestData"]["activationRemarks"] ?? "";
            $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" =>  $iboxxplayer->expire_date, "reseller_id" =>  $iboxxplayer->reseller_id, "user_id" =>  $iboxxplayer->id, "mac_address" =>  $iboxxplayer->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint, "activated_from" => "MULTIAPP"]);
            //end updating trans data
            $returnData["status"] = true;
            $returnData["msg"] = "User updated successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" =>  $iboxxplayer];
            $returnData["httpCode"] = "200";
        } else {
            $returnData["msg"] = "User could not be updated this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C20012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }

    private function __apieditFamilyPlayer($familyPlayer, $reqData, $returnData)
    {
        if (!empty($reqData['requestData']['appType'])) {
            $familyPlayer->app_type = $reqData['requestData']['appType'];
        }
        if (!empty($reqData['requestData']['email'])) {
            $familyPlayer->email = $reqData['requestData']['email'];
        }
        $familyPlayer->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], $familyPlayer->expire_date, 'FAMILYPLAYER');
        $familyPlayer->reseller_id = $reqData['requestData']['updatedBy'];
        if ($reqData['requestData']['isTrail'] !== $familyPlayer->is_trial) {
            $familyPlayer->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 2 : 2);
        }
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance
        $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
        $save_device = AppActivationService::saveDeviceActivation('FAMILYPLAYER', $familyPlayer);
        if ($save_device != 0) {
            //update here trans data
            $remarks = $reqData["requestData"]["activationRemarks"] ?? "";
            $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $familyPlayer->expire_date, "reseller_id" => $familyPlayer->reseller_id, "user_id" => $familyPlayer->id, "mac_address" => $familyPlayer->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint, "activated_from" => "MULTIAPP"]);
            //end updating trans data
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


  


    private function __apieditIBOSSPlayer($ibossplayer, $reqData, $returnData)
    {
        if (!empty($reqData['requestData']['appType'])) {
            $ibossplayer->app_type = $reqData['requestData']['appType'];
        }
        if (!empty($reqData['requestData']['email'])) {
            $ibossplayer->email = $reqData['requestData']['email'];
        }
        $ibossplayer->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], $ibossplayer->expire_date, 'IBOSSPLAYER');
        $ibossplayer->reseller_id = $reqData['requestData']['updatedBy'];
        if ($reqData['requestData']['isTrail'] !== $ibossplayer->is_trial) {
            $ibossplayer->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 2 : 2);
        }
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance
        $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
        $save_device = AppActivationService::saveDeviceActivation('IBOSSPLAYER', $ibossplayer);
        if ($save_device != 0) {
            //update here trans data
            $remarks = $reqData["requestData"]["activationRemarks"] ?? "";
            $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $ibossplayer->expire_date, "reseller_id" => $ibossplayer->reseller_id, "user_id" => $ibossplayer->id, "mac_address" => $ibossplayer->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint, "activated_from" => "MULTIAPP"]);
            //end updating trans data
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

  
    private function __apieditKing4KPlayer($king4kplayer, $reqData, $returnData)
    {
        if (!empty($reqData['requestData']['appType'])) {
            $king4kplayer->app_type = $reqData['requestData']['appType'];
        }
        if (!empty($reqData['requestData']['email'])) {
            $king4kplayer->email = $reqData['requestData']['email'];
        }
        $king4kplayer->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], $king4kplayer->expire_date, 'KING4KPLAYER');
        $king4kplayer->reseller_id = $reqData['requestData']['updatedBy'];
        if ($reqData['requestData']['isTrail'] !== $king4kplayer->is_trial) {
            $king4kplayer->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 2 : 2);
        }
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance
        $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
        $save_device = AppActivationService::saveDeviceActivation('KING4KPLAYER', $king4kplayer);
        if ($save_device != 0) {
            //update here trans data
            $remarks = $reqData["requestData"]["activationRemarks"] ?? "";
            $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $king4kplayer->expire_date, "reseller_id" => $king4kplayer->reseller_id, "user_id" => $king4kplayer->id, "mac_address" => $king4kplayer->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint, "activated_from" => "MULTIAPP"]);
            //end updating trans data
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




    public function __apiEditBOBPlayerUser($bobPlayer, $reqData, $returnData)
    {
        if (!empty($reqData['requestData']['appType'])) {
            $bobPlayer->app_type = $reqData['requestData']['appType'];
        }
        if (!empty($reqData['requestData']['email'])) {
            $bobPlayer->email = $reqData['requestData']['email'];
        }
        $bobPlayer->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], $bobPlayer->expire_date, 'BOBPLAYER');
        $bobPlayer->reseller_id = $reqData['requestData']['updatedBy'];
        if ($reqData['requestData']['isTrail'] !== $bobPlayer->is_trial) {
            $bobPlayer->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 2 : 2);
        }
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance
        $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
        $save_device = AppActivationService::saveDeviceActivation('BOBPLAYER', $bobPlayer);
        if ($save_device != 0) {
            //update here trans data
            $remarks = $reqData["requestData"]["activationRemarks"] ?? "";
            $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $bobPlayer->expire_date, "reseller_id" => $bobPlayer->reseller_id, "user_id" => $bobPlayer->id, "mac_address" => $bobPlayer->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint, "activated_from" => "MULTIAPP"]);
            //end updating trans data
            $returnData["status"] = true;
            $returnData["msg"] = "User updated successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" => $bobPlayer];
            $returnData["httpCode"] = "200";
        } else {
            $returnData["msg"] = "User could not be updated this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C20012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }
 
    private function __apieditHushPlay($hushPlay, $reqData, $returnData)
    {
        if (!empty($reqData['requestData']['appType'])) {
            $hushPlay->app_type = $reqData['requestData']['appType'];
        }
        if (!empty($reqData['requestData']['email'])) {
            $hushPlay->email = $reqData['requestData']['email'];
        }
        $hushPlay->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], $hushPlay->expire_date, 'HUSHPLAY');
        $hushPlay->reseller_id = $reqData['requestData']['updatedBy'];
        if (
            $reqData['requestData']['isTrail'] !== $hushPlay
                ->is_trial
        ) {
            $hushPlay->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 2 : 2);
        }
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance
        $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
        $save_device = AppActivationService::saveDeviceActivation('HUSHPLAY', $hushPlay);
        if ($save_device != 0) {
            //update here trans data
            $remarks = $reqData["requestData"]["activationRemarks"] ?? "";
            $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $hushPlay->expire_date, "reseller_id" => $hushPlay->reseller_id, "user_id" => $hushPlay->id, "mac_address" => $hushPlay->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint, "activated_from" => "MULTIAPP"]);
            //end updating trans data
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

    public function __apieditABEPlayerUser($iboreseller, $reqData, $returnData)
    {
        if (!empty($reqData['requestData']['appType'])) {
            $iboreseller->app_type = $reqData['requestData']['appType'];
        }
        if (!empty($reqData['requestData']['email'])) {
            $iboreseller->email = $reqData['requestData']['email'];
        }
        $iboreseller->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], $iboreseller->expire_date, 'ABEPLAYERTV');
        $iboreseller->reseller_id = $reqData['requestData']['updatedBy'];
        if (
            $reqData['requestData']['isTrail'] !== $iboreseller
                ->is_trial
        ) {
            $iboreseller->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 2 : 2);
        }
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance
        $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
        $save_device = AppActivationService::saveDeviceActivation('ABEPLAYERTV', $iboreseller);
        if ($save_device != 0) {
            //update here trans data
            $remarks = $reqData["requestData"]["activationRemarks"] ?? "";
            $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $iboreseller->expire_date, "reseller_id" => $iboreseller->reseller_id, "user_id" => $iboreseller->id, "mac_address" => $iboreseller->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint, "activated_from" => "MULTIAPP"]);
            //end updating trans data
            $returnData["status"] = true;
            $returnData["msg"] = "User updated successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" => $iboreseller];
            $returnData["httpCode"] = "200";
        } else {
            $returnData["msg"] = "User could not be updated this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C20012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }





    public function __addInBOBPlayer($reqData, $returnData)
    {
        $bobplayer = new BOBPlayerDevice();
        $bobplayer->mac_address = $reqData['requestData']['macAddress'];
        $bobplayer->device_key = (string) rand(100000, 999999);
        if (!empty($reqData['requestData']['appType'])) {
            $bobplayer->app_type = $reqData['requestData']['appType'];
        }
        if (!empty($reqData['requestData']['email'])) {
            $bobplayer->email = $reqData['requestData']['email'];
        }

        $bobplayer->reseller_id = $reqData['requestData']['createdBy'];
        $bobplayer->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 2 : 2);
        $bobplayer->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], $bobplayer->expire_date, 'BOBPLAYER');
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance
        $resellerData = IBOReseller::find($reqData['requestData']['createdBy']);

        //end checking
        if ($bobplayer->save()) {
            //end updating credit popint of reseller
            $remarks = $reqData["requestData"]["activationRemarks"] ?? "";
            $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $bobplayer->expire_date, "reseller_id" => $bobplayer->reseller_id, "user_id" => $bobplayer->id, "mac_address" => $bobplayer->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint, "activated_from" => "MULTIAPP"]);
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

    private function __apieditVirginiaUser($iboreseller, $reqData, $returnData)
    {
        if (!empty($reqData['requestData']['appType'])) {
            $iboreseller->app_type = $reqData['requestData']['appType'];
        }
        if (!empty($reqData['requestData']['email'])) {
            $iboreseller->email = $reqData['requestData']['email'];
        }
        $iboreseller->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], $iboreseller->expire_date, 'VIRGINIA');
        $iboreseller->reseller_id = $reqData['requestData']['updatedBy'];
        if (
            $reqData['requestData']['isTrail'] !== $iboreseller
                ->is_trial
        ) {
            $iboreseller->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 2 : 2);
        }
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance
        $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
        $save_device = AppActivationService::saveDeviceActivation('VIRGINIA', $iboreseller);
        if ($save_device != 0) {
            //update here trans data
            $remarks = $reqData["requestData"]["activationRemarks"] ?? "";
            $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $iboreseller->expire_date, "reseller_id" => $iboreseller->reseller_id, "user_id" => $iboreseller->id, "mac_address" => $iboreseller->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint, "activated_from" => "MULTIAPP"]);
            //end updating trans data
            $returnData["status"] = true;
            $returnData["msg"] = "User updated successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" => $iboreseller];
            $returnData["httpCode"] = "200";
        } else {
            $returnData["msg"] = "User could not be updated this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C20012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }


    private function __apieditIBOAppUser($iboreseller, $reqData, $returnData)
    {
        if (!empty($reqData['requestData']['appType'])) {
            $iboreseller->app_type = $reqData['requestData']['appType'];
        }
        if (!empty($reqData['requestData']['email'])) {
            $iboreseller->email = $reqData['requestData']['email'];
        }
        $iboreseller->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], $iboreseller->expire_date, 'IBOAPP');
        $iboreseller->reseller_id = $reqData['requestData']['updatedBy'];
        if (
            $reqData['requestData']['isTrail'] !== $iboreseller
                ->is_trial
        ) {
            $iboreseller->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 2 : 2);
        }
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance
        $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
        $save_device = AppActivationService::saveDeviceActivation('ABEPLAYERTV', $iboreseller);
        if ($save_device != 0) {
            //update here trans data
            $remarks = $reqData["requestData"]["activationRemarks"] ?? "";
            $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $iboreseller->expire_date, "reseller_id" => $iboreseller->reseller_id, "user_id" => $iboreseller->id, "mac_address" => $iboreseller->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint, "activated_from" => "MULTIAPP"]);
            //end updating trans data
            $returnData["status"] = true;
            $returnData["msg"] = "User updated successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" => $iboreseller];
            $returnData["httpCode"] = "200";
        } else {
            $returnData["msg"] = "User could not be updated this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C20012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }

    private function __apieditMacPLayer($macPlayer, $reqData, $returnData)
    {
       
        if (!empty($reqData['requestData']['appType'])) {
            $macPlayer->app_type = $reqData['requestData']['appType'];
        }
        if (!empty($reqData['requestData']['email'])) {
            $macPlayer->email = $reqData['requestData']['email'];
        }
        $macPlayer->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], $macPlayer->expire_date, 'MACPLAYER');
        $macPlayer->reseller_id = $reqData['requestData']['updatedBy'];
        if (
            $reqData['requestData']['isTrail'] !== $macPlayer
                ->is_trial
        ) {
            $macPlayer->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 2 : 2);
        }
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance
        $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
        $save_device = AppActivationService::saveDeviceActivation('MACPLAYER', $macPlayer);
        if ($save_device != 0) {
            //update here trans data
            $remarks = $reqData["requestData"]["activationRemarks"] ?? "";
            $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $macPlayer->expire_date, "reseller_id" => $macPlayer->reseller_id, "user_id" => $macPlayer->id, "mac_address" => $macPlayer->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint, "activated_from" => "MULTIAPP"]);
            //end updating trans data
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

    private function __apieditKTNPlayer($ktnPlayer, $reqData, $returnData)
    {
        if (!empty($reqData['requestData']['appType'])) {
            $ktnPlayer->app_type = $reqData['requestData']['appType'];
        }
        if (!empty($reqData['requestData']['email'])) {
            $ktnPlayer->email = $reqData['requestData']['email'];
        }
        $ktnPlayer->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], $ktnPlayer->expire_date, 'KTNPLAYER');
        $ktnPlayer->reseller_id = $reqData['requestData']['updatedBy'];
        if (
            $reqData['requestData']['isTrail'] !== $ktnPlayer
                ->is_trial
        ) {
            $ktnPlayer->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 2 : 2);
        }
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance
        $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
        $save_device = AppActivationService::saveDeviceActivation('KTNPLAYER', $ktnPlayer);
        if ($save_device != 0) {
            //update here trans data
            $remarks = $reqData["requestData"]["activationRemarks"] ?? "";
            $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $ktnPlayer->expire_date, "reseller_id" => $ktnPlayer->reseller_id, "user_id" => $ktnPlayer->id, "mac_address" => $ktnPlayer->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint, "activated_from" => "MULTIAPP"]);
            //end updating trans data
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

    private function __apieditALLPlayer($allPlayer,$reqData, $returnData)
    {
        if (!empty($reqData['requestData']['appType'])) {
            $allPlayer->app_type = $reqData['requestData']['appType'];
        }
        if (!empty($reqData['requestData']['email'])) {
            $allPlayer->email = $reqData['requestData']['email'];
        }
        $allPlayer->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], $allPlayer->expire_date, 'ALLPLAYER');
        $allPlayer->reseller_id = $reqData['requestData']['updatedBy'];
        if (
            $reqData['requestData']['isTrail'] !== $allPlayer
                ->is_trial
        ) {
            $allPlayer->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 2 : 2);
        }
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance
        $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
        $save_device = AppActivationService::saveDeviceActivation('ABEPLAYERTV', $allPlayer);
        if ($save_device != 0) {
            //update here trans data
            $remarks = $reqData["requestData"]["activationRemarks"] ?? "";
            $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $allPlayer->expire_date, "reseller_id" => $allPlayer->reseller_id, "user_id" => $allPlayer->id, "mac_address" => $allPlayer->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint, "activated_from" => "MULTIAPP"]);
            //end updating trans data
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

    private function __apieditFlixNet( $flixNetDevice,$reqData, $returnData)
    {
        if (!empty($reqData['requestData']['appType'])) {
            $flixNetDevice->app_type = $reqData['requestData']['appType'];
        }
        if (!empty($reqData['requestData']['email'])) {
            $flixNetDevice->email = $reqData['requestData']['email'];
        }
        $flixNetDevice->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], $flixNetDevice->expire_date, 'FLIXNET');
        $flixNetDevice->reseller_id = $reqData['requestData']['updatedBy'];
        if (
            $reqData['requestData']['isTrail'] !== $flixNetDevice
                ->is_trial
        ) {
            $flixNetDevice->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 2 : 2);
        }
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance
        $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
        $save_device = AppActivationService::saveDeviceActivation('FLIXNET', $flixNetDevice);
        if ($save_device != 0) {
            //update here trans data
            $remarks = $reqData["requestData"]["activationRemarks"] ?? "";
            $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $flixNetDevice->expire_date, "reseller_id" => $flixNetDevice->reseller_id, "user_id" => $flixNetDevice->id, "mac_address" => $flixNetDevice->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint, "activated_from" => "MULTIAPP"]);
            //end updating trans data
            $returnData["status"] = true;
            $returnData["msg"] = "User updated successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" => $flixNetDevice];
            $returnData["httpCode"] = "200";
        } else {
            $returnData["msg"] = "User could not be updated this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C20012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }

    private function __apieditDuplex($duplexDevice, $reqData, $returnData)
    {
        if (!empty($reqData['requestData']['appType'])) {
            $duplexDevice->app_type = $reqData['requestData']['appType'];
        }
        if (!empty($reqData['requestData']['email'])) {
            $duplexDevice->email = $reqData['requestData']['email'];
        }
        $duplexDevice->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], $duplexDevice->expire_date, 'DUPLEX');
        $duplexDevice->reseller_id = $reqData['requestData']['updatedBy'];
        if (
            $reqData['requestData']['isTrail'] !== $duplexDevice
                ->is_trial
        ) {
            $duplexDevice->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 2 : 2);
        }
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance
        $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
        $save_device = AppActivationService::saveDeviceActivation('DUPLEX', $duplexDevice);
        if ($save_device != 0) {
            //update here trans data
            $remarks = $reqData["requestData"]["activationRemarks"] ?? "";
            $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $duplexDevice->expire_date, "reseller_id" => $duplexDevice->reseller_id, "user_id" => $duplexDevice->id, "mac_address" => $duplexDevice->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint, "activated_from" => "MULTIAPP"]);
            //end updating trans data
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

    private function __apieditIBOSOL($ibosolDevice,$reqData, $returnData)
    {
        
        if (!empty($reqData['requestData']['appType'])) {
            $ibosolDevice->app_type = $reqData['requestData']['appType'];
        }
        if (!empty($reqData['requestData']['email'])) {
            $ibosolDevice->email = $reqData['requestData']['email'];
        }
        $ibosolDevice->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], $ibosolDevice->expire_date, 'IBOSOL');
        $ibosolDevice->reseller_id = $reqData['requestData']['updatedBy'];
        if (
            $reqData['requestData']['isTrail'] !== $ibosolDevice
                ->is_trial
        ) {
            $ibosolDevice->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 2 : 2);
        }
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance
        $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
        $save_device = AppActivationService::saveDeviceActivation('IBOSOL', $ibosolDevice);
        if ($save_device != 0) {
            //update here trans data
            $remarks = $reqData["requestData"]["activationRemarks"] ?? "";
            $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $ibosolDevice->expire_date, "reseller_id" => $ibosolDevice->reseller_id, "user_id" => $ibosolDevice->id, "mac_address" => $ibosolDevice->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint, "activated_from" => "MULTIAPP"]);
            //end updating trans data
            $returnData["status"] = true;
            $returnData["msg"] = "User updated successfully!";
            $returnData["statusCode"] = "000000";
            $returnData["data"] = ["MASAPlayer" => $ibosolDevice];
            $returnData["httpCode"] = "200";
        } else {
            $returnData["msg"] = "User could not be updated this moment, Please try after sometime.!";
            $returnData["statusCode"] = "C20012";
            $returnData["httpCode"] = "501";
        }
        return $returnData;
    }

    private function __apieditIBOStb($iboStbDevice, $reqData, $returnData)
    {
        
        if (!empty($reqData['requestData']['appType'])) {
            $iboStbDevice->app_type = $reqData['requestData']['appType'];
        }
        if (!empty($reqData['requestData']['email'])) {
            $iboStbDevice->email = $reqData['requestData']['email'];
        }
        $iboStbDevice->expire_date = $this->__setExpiryDate($reqData['requestData']['isTrail'], $iboStbDevice->expire_date, 'IBOSTB');
        $iboStbDevice->reseller_id = $reqData['requestData']['updatedBy'];
        if (
            $reqData['requestData']['isTrail'] !== $iboStbDevice
                ->is_trial
        ) {
            $iboStbDevice->is_trial = $reqData['requestData']['isTrail'] == 1 ? 0 : ($reqData['requestData']['isTrail'] == 2 ? 2 : 2);
        }
        $creditPoint = $this->__getCreditPointVal($reqData['requestData']['isTrail']);
        //check for credit acount balance
        $resellerData = IBOReseller::find($reqData['requestData']['updatedBy']);
        $save_device = AppActivationService::saveDeviceActivation('IBOSTB', $iboStbDevice);
        if ($save_device != 0) {
            //update here trans data
            $remarks = $reqData["requestData"]["activationRemarks"] ?? "";
            $this->__addActivationTranLogs(["activation_remarks" => $remarks, "expiry_date" => $iboStbDevice->expire_date, "reseller_id" => $iboStbDevice->reseller_id, "user_id" => $iboStbDevice->id, "mac_address" => $iboStbDevice->mac_address, "module" => $reqData['module'], "credit_point" => $creditPoint, "activated_from" => "MULTIAPP"]);
            //end updating trans data
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
        $userTran->activated_from = $tranData['activated_from'] ?? "";
        $userTran->comment = $tranData['activation_remarks'] ?? "";
        return $userTran->save();
    }

    public function validateResellerSharePassCode(Request $request)
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
                    'requestData.resellerId' => 'required|numeric',
                    'requestData.passcode' => 'required|numeric'
                ]);

                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }
                $reseller = IBOReseller::select('id', 'credit_share_passcode', 'email')->where('id', $reqData['requestData']['resellerId'])->first();
                if ($reseller) {
                    if ($reseller->credit_share_passcode == $reqData['requestData']['passcode']) {
                        $returnData = [
                            "msg" => "Validated Successfully.",
                            "status" => true,
                            "statusCode" => "SU0200",
                            "httpCode" => "200"
                        ];
                    } else {
                        $returnData = [
                            "msg" => "Passcode validation failed!",
                            "status" => false,
                            "statusCode" => "FS0402",
                            "httpCode" => "402"
                        ];
                    }
                } else {
                    $returnData = [
                        "msg" => "Reseller not found in our system.",
                        "statusCode" => "CD0501",
                        "httpCode" => "500"
                    ];
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

    public function setCreditSharePass(Request $request)
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
                    'requestData.resellerId' => 'required|numeric',
                    'requestData.passcode' => 'required|numeric'
                ]);

                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }
                $reseller = IBOReseller::find($reqData['requestData']['resellerId']);

                if ($reseller) {
                    $reseller->credit_share_passcode = $reqData['requestData']['passcode'];
                    if ($reseller->save()) {
                        $returnData = [
                            "msg" => "Registration of credit share passcode Successfully.",
                            "status" => true,
                            "statusCode" => "SU0200",
                            "httpCode" => "200"
                        ];
                    } else {
                        $returnData = [
                            "msg" => "Passcode Registration failed!",
                            "status" => false,
                            "statusCode" => "FS0402",
                            "httpCode" => "402"
                        ];
                    }
                } else {
                    $returnData = [
                        "msg" => "Reseller not found in our system.",
                        "statusCode" => "CD0501",
                        "httpCode" => "500"
                    ];
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

    public function searchReseller(Request $request)
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
                    'requestData.userId' => "required",
                    'requestData.groupId' => "required",
                    'requestData.searchValue' => 'required'
                ]);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }

                if ($reqData['requestData']['groupId'] == 2) {
                    $iboreseller = IBOReseller::where('email', $reqData['requestData']['searchValue'])
                        ->orWhere('email', 'like', '%' . $reqData['requestData']['searchValue'] . '%')->where('status', '!=', '2')->where('parent_reseller_id', $reqData['requestData']['userId'])->where('group_id', '2')->get();
                } else {
                    $iboreseller = IBOReseller::where('email', $reqData['requestData']['searchValue'])
                        ->orWhere('email', 'like', '%' . $reqData['requestData']['searchValue'] . '%')->where('status', '!=', '2')->where('group_id', '2')->get();
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


    public function sendVerifyLinkEmail(Request $request)
    {
        Mail::to('hmishra1509@gmail.com')->send(new SimpleMail('Sample MAIL'));
        return new JsonResponse(['message' => 'success']);
    }

    public function updateCreditSharePassword(Request $request)
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
                if (!empty($reseller)) {
                    $reseller->credit_share_passcode = $reqData['requestData']['password'];
                    if ($reseller->save()) {
                        $returnData["msg"] = 'Credit Share password updates succesfully!';
                        $returnData["statusCode"] = "00000";
                        $returnData["status"] = true;
                        $returnData["httpCode"] = "200";
                    } else {
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




}
