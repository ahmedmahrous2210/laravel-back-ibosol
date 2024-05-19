<?php

namespace App\Http\Controllers;
//use Validator;
use Carbon\Carbon;
use App\IBOAppDevice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\IBOAppPlayer;
use App\IBOAppPlaylist;
use App\IBONotification;
use App\IBOPlayerNotification;
use App\IBOPlayerActivationCode;
use App\IBOIptvRegistrationLogs;

class IBOIPTVController extends Controller{

    public function getReg(Request $request){

        try{
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
                    'requestData.appType' => ['required', Rule::in(['samsung', 'lg', 'appletv', 'android'])],
                ];

                $addValidMac = [];

                if(!empty($reqData['requestData']['mac_address'])){
                    $reqData['requestData']['mac_address'] = strtolower($reqData['requestData']['mac_address']);
                }

                $addValidMac = ['requestData.mac_address' => 'required|unique:App\IBOAppDevice,mac_address'];
                $validatedData = Validator::make($reqData, array_merge($validArr, $addValidMac));

                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }

                $iboPlayerServer = new IBOAppDevice;
                $iboPlayerServer->mac_address = $reqData['requestData']['mac_address'];
                $iboPlayerServer->device_key = (string)rand(100000, 999999);
                $iboPlayerServer->is_trial = 1;
                $iboPlayerServer->hostname = $request->fullUrl();
                $iboPlayerServer->expire_date = date('Y-m-d', strtotime(date('Y-m-d') . '+1 week'));

                if ($iboPlayerServer->save()) {
                    $returnData["status"] = true;
                    $returnData["msg"] = "Succesfully registred!";
                    $returnData["statusCode"] = "000000";
                    unset($iboPlayerServer['_id']);
                    $returnData["data"] = [
                        "mac_address" => $iboPlayerServer->mac_address,
                        "device_key" => $iboPlayerServer->device_key,
                        "is_trial" => $iboPlayerServer->is_trial,
                        "expire_date" => $iboPlayerServer->expire_date,
                        "urls" => [
                            "playlist_name"=> "Demo Url",
                            "url"=>"http://ibocdn.com/ibo3.m3u"
                        ]
                    ];
                    $returnData["httpCode"] = "200";
                } else {
                    $returnData["msg"] = "Device can not be registred!";
                    $returnData["statusCode"] = "C10012";
                    $returnData["httpCode"] = "400";
                }
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        }catch(\Exception $e){
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

    public function getMacDetails(Request $request){
        try{
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
                    'requestData.mac_address' => 'required',
                ];

                $reqData['requestData']['mac_address'] = strtolower($reqData['requestData']['mac_address']);
                $validatedData = Validator::make($reqData, $validArr);

                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }

                $iboPlayerServer = IBOAppDevice::where('mac_address', $reqData['requestData']['mac_address'])->first();

                if (!empty($iboPlayerServer)) {
                    $returnData["status"] = true;
                    $returnData["msg"] = "Succesfully returned data!";
                    $returnData["statusCode"] = "000000";
                    $urls = IBOAppPlaylist::where('device_id', $iboPlayerServer->id)->get()->toArray();
                    //$urls = array_merge($urls, );
                    if(empty($urls)){
                        $urls = [
                            "id"=>"99999999",
                            "device_id"=>"99999999",
                            "playlist_name"=> "Demo Url",
                            "url"=> "http://ibocdn.com/ibo3.m3u",
                            "epg_ur"=> "",
                            "username"=> '0264279762',
                            "password"=> '0264279762',
                            "playlist_type"=> 'general',
                            "is_protected"=> 0
                        ];
                    }

                    $returnData["data"] = [
                        "device_id" => $iboPlayerServer->id,
                        "mac_address" => $iboPlayerServer->mac_address,
                        "device_key" => $iboPlayerServer->device_key,
                        "is_trial" => $iboPlayerServer->is_trial,
                        "expire_date" => $iboPlayerServer->expire_date,
                        "urls" => $urls
                    ];
                    $returnData["httpCode"] = "200";
                } else {
                    $returnData["msg"] = "Device details not found!";
                    $returnData["statusCode"] = "C10012";
                    $returnData["httpCode"] = "400";
                }
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        }catch(\Exception $e){
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

    public function getNotification(Request $request){
        try{
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
                    'requestData.isNotif' => 'required',
                ];

                $validatedData = Validator::make($reqData, $validArr);

                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }

                $abeNotif = IBONotification::where('status', '1')->get()->toArray();

                if (!empty($abeNotif)) {
                    $returnData["status"] = true;
                    $returnData["msg"] = "Succesfully returned data!";
                    $returnData["statusCode"] = "000000";

                    // /unset($abeNotif['id']);
                    $returnData["data"] = $abeNotif;
                    $returnData["httpCode"] = "200";
                } else {
                    $returnData["msg"] = "Notification details not found!";
                    $returnData["statusCode"] = "C10012";
                    $returnData["httpCode"] = "400";
                }
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        }catch(\Exception $e){
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

    public function updateStatus(Request $request){

    }

    public function commonApiData(Request $request)
    {

        try {
            $reqData = $request->all();
            // $blockedIps = \DB::raw("SELECT request_ip FROM iboiptv_registration_request WHERE request_ip IN (SELECT * FROM (SELECT request_ip FROM iboiptv_registration_request GROUP BY request_ip HAVING COUNT(request_ip) > 20) AS a)");
            // //blocllist here ips...

            // if (in_array($this->getIp($request->ip()), [])) {
            //     return new JsonResponse([
            //         "status" => false,
            //         "statusCode" => "CER403",
            //         "msg" => "You are restricted to access the site.",
            //         "requestId" => $reqData["requestId"] ?? "",
            //         "appRefId" => $reqData["appRefId"] ?? "",
            //         "channelId" => $reqData["channelId"] ?? "",
            //         "module" => $reqData["module"] ?? "",
            //         "httpCode" => "403"
            //     ], 403);
            //     //abort(403, "");
            // }


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
                    'requestData.appType' => ['required', Rule::in(['samsung', 'lg', 'appletv', 'android'])],
                    'requestData.mac_address' => 'required',
                ];

                $validatedData = Validator::make($reqData, $validArr);

                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }

                $foundMac = IBOAppDevice::where('mac_address', strtolower($reqData['requestData']['mac_address']))->first();

                if (!empty($foundMac)) {
                    //start check here if not valid status and is_trail expiry date

                    if(isset($foundMac->status) && $foundMac->status == 0){
                        $returnData["status"] = false;
                        $returnData["msg"] = "Your mac id is deactivated!";
                        $returnData["statusCode"] = "CS10001";
                        $returnData["httpCode"] = "401";
                        return new JsonResponse($returnData, $returnData['httpCode']);
                    }

                    if(strtotime($foundMac->expire_date) < strtotime(date('Y-m-d'))){
                        $returnData["status"] = false;
                        $returnData["msg"] = "Your subscription has expired!";
                        $returnData["statusCode"] = "CE10001";
                        $returnData["httpCode"] = "401";
                        return new JsonResponse($returnData, $returnData['httpCode']);
                    }
                    //end check here if not valid status and is_trail expiry date

                    $returnData["status"] = true;
                    $returnData["msg"] = "Succesfully returned details!";
                    $returnData["statusCode"] = "000000";


                    $urls = IBOAppPlaylist::select('id','device_id',  'playlist_name', 'url', 'epg_url','username', 'password', 'is_protected', 'pin')->where('device_id', $foundMac->id)->get()->toArray();
                    $urls = array_merge($urls, [
                        [
                            "_id" => "99999999",
                            "device_id" => "9999999999",
                            "playlist_name" => "Demo Url",
                            "url" => "http://demo.flysat.live:80/get.php?username=u6eaoj37TQ&password=WoXkUddtWC&type=m3u_plus&output=ts",
                            "epg_ur"=> "",
                            "username" => "",
                            "password" => "",
                            "is_protected" => "",
                            "pin" => "",
                    ]]);


                    $abeNotif = IBONotification::select('id', 'title', 'description', 'status', 'start_time', 'end_time')->where('status', '1')->get()->toArray();
                    $returnData["data"] = [
                        "mac_address" => $foundMac->mac_address,
                        "box_dtls" => $foundMac,
                        "device_key" => $foundMac->device_key,
                        "is_trial" => $foundMac->is_trial,
                        "expiry_date" => $foundMac->expire_date,
                        "app_type" => $foundMac->app_type,
                        "realtime" => 1,
                        "urls" => $urls,
                        "notifications" => $abeNotif,
                        "settings" => [
                            "app_logo"     => "https://iboplayer.com/images/apps/app_logo.png",
                            "app_email" => "info@iboplayer.com",
                            "app_phone" => "+201014045819",
                            "app_title" => "IBO Player",
                            "app_caption" => "IBOPlayer Best Player"
                        ],
                        "themes" => [
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
                    ],

                    ];
                    $returnData["httpCode"] = "200";
                }
                else {
                    //$addValidMac = ['requestData.mac_address' => 'required|unique:App\AbeDevice,mac_address'];
                    $iboPlayerServer = new IBOAppDevice();
                    $iboPlayerServer->mac_address = strtolower($reqData['requestData']['mac_address']);
                    $iboPlayerServer->device_key = (string)rand(100000, 999999);
                    $iboPlayerServer->is_trial = 1;
                    $iboPlayerServer->expire_date = date('Y-m-d', strtotime(date('Y-m-d') . '+1 week'));
                    $iboPlayerServer->app_type = $reqData['requestData']['appType'] ?? "";
                    $iboPlayerServer->status = 1;
                    $IBOIptvRegistrationLogs = new IBOIptvRegistrationLogs();
                    $IBOIptvRegistrationLogs->mac_id = strtolower($reqData['requestData']['mac_address']);
                    $IBOIptvRegistrationLogs->request_logs = json_encode($reqData);
                    $IBOIptvRegistrationLogs->request_ip = $this->getIp($request->ip());
                    $IBOIptvRegistrationLogs->user_agent = $request->header('User-Agent');
                   // $IBOIptvRegistrationLogs->save();
                    //if ($iboPlayerServer->save()) {
                    if (false) {
                        $returnData["status"] = true;
                        $returnData["msg"] = "Succesfully registred!";
                        $returnData["statusCode"] = "000000";
                        unset($iboPlayerServer['id']);
                        $abeNotif = IBONotification::where('status', '1')->get()->toArray();
                        $returnData["data"] = [
                            "mac_address" => $iboPlayerServer->mac_address,
                            "device_key" => $iboPlayerServer->device_key,
                            "is_trial" => $iboPlayerServer->is_trial,
                            "expiry_date" => $iboPlayerServer->expire_date,
                            "app_type" => $iboPlayerServer->app_type,
                            "status" => $iboPlayerServer->status,
                            "realtime" => 1,
                            "urls" => [
                                [
                                   "_id" => "99999999",
                                    "device_id" => "9999999999",
                                    "playlist_name" => "Demo Url",
                                    "url" => "http://demo.4kplayer.me/get.php?username=4kplayer&password=4kplayer_Demo&type=m3u_plus&output=mpegts",
                                    "epg_ur"=> "",
                                    "username" => "",
                                    "password" => "",
                                    "is_protected" => "",
                                    "pin" => "",
                                ]
                            ],
                            "notifications" => $abeNotif,
                            "settings" => [
                                "app_logo"     => "https://iboplayer.com/images/apps/app_logo.png",
                                "app_email" => "info@iboplayer.com",
                                "app_phone" => "+201014045819",
                                "app_title" => "IBO PLAYER",
                                "app_caption" => "Best PLAYER FOR ALL DEVICES"
                            ],
                            "themes" => [
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
                        ],
                    "languages" => []

                        ];
                        $returnData["httpCode"] = "200";
                    } else {
                        // $dddddd = \DB::select("SELECT request_ip FROM iboiptv_registration_request WHERE request_ip IN (SELECT * FROM (SELECT request_ip FROM iboiptv_registration_request GROUP BY request_ip HAVING COUNT(request_ip) > 20) AS a)");
                        $returnData["msg"] = "Device can not be registred!";
                        $returnData["statusCode"] = "C10012";
                        //$returnData["ddddd"] = array_values($dddddd);
                        $returnData["httpCode"] = "400";
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
            ], 500);
        }
    }


    public function boxActivation(Request $request){

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

                $activationDummy = [
                    "id"    => 1,
                    "type"  => "yearly",
                    "value" => "qwerty1234",
                    "status"=> 0,
                    "activated_by"=> "4c:d0:65:d8:56:m4"
                ];

                $macDummyData = [

                        "_id" => "1ghj3ed67cgfy67y7uujj",
                        "mac_address" => "4c:d0:65:d8:56:m4",
                        "is_trail"=> "1",
                        "status" => 1,
                        "expiry_date"=> "2023-03-01",
                        "device_key" => "123456",
                        "app_type" => "android",
                        "notifications" => [
                            [
                            "_id"     => "2226c62hg6yg6789",
                            "title" => "test",
                            "description"   => "testing data",
                            "status" => 1,
                            "start_time" => "2022-03-01 00:00:01",
                            "end_time" => "2022-03-03 11:59:59"
                            ]
                        ],
                        "settings" => [
                            "app_logo"     => "https://iboplayer.com/images/apps/app_logo.png",
                            "app_email" => "info@iboplayer.com",
                            "app_phone" => "+201014045819",
                          "app_title" => "IBO PLAYER",
                          "app_caption" => "Best PLAYER FOR ALL DEVICES"
                        ],
                        "themes" => [
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
                    ],
                    "languages" => [],
                    "playlists" => [
                        [
                            "_id" => "6c2jhg675g87878890",
                            "playlist_name" => "test",
                            "url" => "https://arcos.database.com?php=drtdf&username=test&password=j98u89vdf98",
                            "username" => "test",
                            "password" => "j98u89vdf98",
                            "epg_url" => "",
                            "pin" => "",
                            "is_protected" => 0
                        ],
                        [
                            "_id" => "6cfdfddjhg687878890",
                            "playlist_name" => "test2",
                            "url" => "https://arcos2.database2.com?php=drtdf&username=test2&password=j98u89vdf98",
                            "username" => "test2",
                            "password" => "j98u89vdf98",
                            "epg_url" => "",
                            "pin" => "",
                            "is_protected" => 0
                        ],
                    ]


                ];

                $returnData["msg"] = "Successfully activated box!";
                $returnData["statusCode"] = "000000";
                $returnData["data"] = $macDummyData;
                $returnData["httpCode"] = "200";
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
            ], 500);
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
                                "app_caption" => "IBO Player Best IPTV"
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
                            $validMac["playlists"] = IBOAppPlaylist::where('device_id', $validMac->_id)->get();
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

    private function __setExpiryDate($isTrail, $oldExpDate)
    {
        switch ($isTrail) {
            case '1':
                $date = date('Y-m-d', strtotime($oldExpDate . '+1 week'));
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

    public function getIp($requestIp){
        foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key){
            if (array_key_exists($key, $_SERVER) === true){
                foreach (explode(',', $_SERVER[$key]) as $ip){
                    $ip = trim($ip); // just to be safe
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false){
                        return $ip;
                    }
                }
            }
        }
        return $requestIp;
    }



    public function forIbrahimOnly(Request $request)
    {

        try {
            $reqData = $request->all();
            // $blockedIps = \DB::raw("SELECT request_ip FROM iboiptv_registration_request WHERE request_ip IN (SELECT * FROM (SELECT request_ip FROM iboiptv_registration_request GROUP BY request_ip HAVING COUNT(request_ip) > 20) AS a)");
            // //blocllist here ips...

            // if (!in_array($this->getIp($request->ip()), [])) {
            //     return new JsonResponse([
            //         "status" => false,
            //         "statusCode" => "CER403",
            //         "msg" => "You are restricted to access the site.",
            //         "requestId" => $reqData["requestId"] ?? "",
            //         "appRefId" => $reqData["appRefId"] ?? "",
            //         "channelId" => $reqData["channelId"] ?? "",
            //         "module" => $reqData["module"] ?? "",
            //         "httpCode" => "403"
            //     ], 403);
            //     //abort(403, "");
            // }


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
                    'requestData.appType' => ['required', Rule::in(['samsung', 'lg', 'appletv', 'android'])],
                    'requestData.mac_address' => 'required',
                ];

                $validatedData = Validator::make($reqData, $validArr);

                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }

                $foundMac = IBOAppDevice::where('mac_address', strtolower($reqData['requestData']['mac_address']))->first();

                if (!empty($foundMac)) {
                    //start check here if not valid status and is_trail expiry date

                    if(isset($foundMac->status) && $foundMac->status == 0){
                        $returnData["status"] = false;
                        $returnData["msg"] = "Your mac id is deactivated!";
                        $returnData["statusCode"] = "CS10001";
                        $returnData["httpCode"] = "401";
                        return new JsonResponse($returnData, $returnData['httpCode']);
                    }

                    if(strtotime($foundMac->expire_date) < strtotime(date('Y-m-d'))){
                        $returnData["status"] = false;
                        $returnData["msg"] = "Your subscription has expired!";
                        $returnData["statusCode"] = "CE10001";
                        $returnData["httpCode"] = "401";
                        return new JsonResponse($returnData, $returnData['httpCode']);
                    }
                    //end check here if not valid status and is_trail expiry date

                    $returnData["status"] = true;
                    $returnData["msg"] = "Succesfully returned details!";
                    $returnData["statusCode"] = "000000";


                    $urls = IBOAppPlaylist::select('id','device_id',  'playlist_name', 'url', 'epg_url','username', 'password', 'is_protected', 'pin')->where('device_id', $foundMac->id)->get()->toArray();
                    $urls = array_merge($urls, [
                        [
                            "_id" => "99999999",
                            "device_id" => "9999999999",
                            "playlist_name" => "Demo Url",
                            "url" => "http://demo.4kplayer.me/get.php?username=4kplayer&password=4kplayer_Demo&type=m3u_plus&output=mpegts",
                            "epg_ur"=> "",
                            "username" => "",
                            "password" => "",
                            "is_protected" => "",
                            "pin" => "",
                    ]]);


                    $abeNotif = IBONotification::select('id', 'title', 'description', 'status', 'start_time', 'end_time')->where('status', '1')->get()->toArray();
                    $returnData["data"] = [
                        "mac_address" => $foundMac->mac_address,
                        "box_dtls" => $foundMac,
                        "device_key" => $foundMac->device_key,
                        "is_trial" => $foundMac->is_trial,
                        "expiry_date" => $foundMac->expire_date,
                        "app_type" => $foundMac->app_type,
                        "realtime" => 1,
                        "urls" => $urls,
                        "notifications" => $abeNotif,
                        "settings" => [
                            "app_logo"     => "https://iboplayer.com/images/apps/app_logo.png",
                            "app_email" => "info@iboplayer.com",
                            "app_phone" => "+201014045819",
                            "app_title" => "IBO Player",
                            "app_caption" => "IBOPlayer Best Player"
                        ],
                        "themes" => [
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
                    ],

                    ];
                    $returnData["httpCode"] = "200";
                }
                else {
                    //$addValidMac = ['requestData.mac_address' => 'required|unique:App\AbeDevice,mac_address'];
                    $iboPlayerServer = new IBOAppDevice();
                    $iboPlayerServer->mac_address = strtolower($reqData['requestData']['mac_address']);
                    $iboPlayerServer->device_key = (string)rand(100000, 999999);
                    $iboPlayerServer->is_trial = 1;
                    $iboPlayerServer->expire_date = date('Y-m-d', strtotime(date('Y-m-d') . '+1 week'));
                    $iboPlayerServer->app_type = $reqData['requestData']['appType'] ?? "";
                    $iboPlayerServer->status = 1;
                    $IBOIptvRegistrationLogs = new IBOIptvRegistrationLogs();
                    $IBOIptvRegistrationLogs->mac_id = strtolower($reqData['requestData']['mac_address']);
                    $IBOIptvRegistrationLogs->request_logs = json_encode($reqData);
                    $IBOIptvRegistrationLogs->request_ip = $this->getIp($request->ip());
                    $IBOIptvRegistrationLogs->user_agent = $request->header('User-Agent');
                    $IBOIptvRegistrationLogs->app_reg_by = "IBRAHIM";
                    $IBOIptvRegistrationLogs->save();
                    if ($iboPlayerServer->save()) {
                    //if (false) {
                        $returnData["status"] = true;
                        $returnData["msg"] = "Succesfully registred!";
                        $returnData["statusCode"] = "000000";
                        unset($iboPlayerServer['id']);
                        $abeNotif = IBONotification::where('status', '1')->get()->toArray();
                        $returnData["data"] = [
                            "mac_address" => $iboPlayerServer->mac_address,
                            "device_key" => $iboPlayerServer->device_key,
                            "is_trial" => $iboPlayerServer->is_trial,
                            "expiry_date" => $iboPlayerServer->expire_date,
                            "app_type" => $iboPlayerServer->app_type,
                            "status" => $iboPlayerServer->status,
                            "realtime" => 1,
                            "urls" => [
                                [
                                   "_id" => "99999999",
                                    "device_id" => "9999999999",
                                    "playlist_name" => "Demo Url",
                                    "url" => "http://demo.4kplayer.me/get.php?username=4kplayer&password=4kplayer_Demo&type=m3u_plus&output=mpegts",
                                    "epg_ur"=> "",
                                    "username" => "",
                                    "password" => "",
                                    "is_protected" => "",
                                    "pin" => "",
                                ]
                            ],
                            "notifications" => $abeNotif,
                            "settings" => [
                                "app_logo"     => "https://iboplayer.com/images/apps/app_logo.png",
                                "app_email" => "info@iboplayer.com",
                                "app_phone" => "+201014045819",
                                "app_title" => "IBO PLAYER",
                                "app_caption" => "Best PLAYER FOR ALL DEVICES"
                            ],
                            "themes" => [
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
                        ],
                    "languages" => []

                        ];
                        $returnData["httpCode"] = "200";
                    } else {

                        $returnData["msg"] = "Device can not be registred!";
                        $returnData["statusCode"] = "C10012";
                        $returnData["httpCode"] = "400";
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
            ], 500);
        }
    }



    public function mainIBOIPTVBoxRegistration(Request $request)
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
                "httpCode" => "502"
            ];
            if ($request->isJson()) {

                $validArr = [
                    'module' => ['required', Rule::in(['IBO'])],
                    'channelId' => ['required', Rule::in(['IBOPLAYER'])],
                    'domainId' => ['required', Rule::in(['IBOAPP'])],
                    'requestData.appType' =>['required', Rule::in(['android'])],
                    'requestData.macAddress' => 'required'
                ];

                $validatedData = Validator::make($reqData, $validArr);

                if ($validatedData->fails()) {
                    $returnData["msg"] =  $validatedData->errors();
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }

                $foundMac = IBOAppDevice::where('mac_address', strtolower($reqData['requestData']['macAddress']))->first();

                if (!empty($foundMac)) {
                    //start check here if not valid status and is_trail expiry date

                    if(isset($foundMac->status) && $foundMac->status == 0){
                        $returnData["status"] = false;
                        $returnData["msg"] = "Your mac id is deactivated!";
                        $returnData["statusCode"] = "CS10001";
                        $returnData["httpCode"] = "401";
                        return new JsonResponse($returnData, $returnData['httpCode']);
                    }

                    if(strtotime($foundMac->expire_date) < strtotime(date('Y-m-d'))){
                        $returnData["status"] = false;
                        $returnData["msg"] = "Your subscription has expired!";
                        $returnData["statusCode"] = "CE10001";
                        $returnData["httpCode"] = "401";
                        return new JsonResponse($returnData, $returnData['httpCode']);
                    }
                    //end check here if not valid status and is_trail expiry date

                    $returnData["status"] = true;
                    $returnData["msg"] = "Succesfully returned details!";
                    $returnData["statusCode"] = "000000";


                    $urls = IBOAppPlaylist::select('id','device_id',  'playlist_name', 'url', 'epg_url','username', 'password', 'is_protected', 'pin')->where('device_id', $foundMac->id)->get()->toArray();
                    $urls = array_merge($urls, [
                        [
                            "_id" => "99999999",
                            "device_id" => "9999999999",
                            "playlist_name" => "Demo Url",
                            "url" => "http://demo.4kplayer.me/get.php?username=4kplayer&password=4kplayer_Demo&type=m3u_plus&output=mpegts",
                            "epg_ur"=> "",
                            "username" => "",
                            "password" => "",
                            "is_protected" => "",
                            "pin" => "",
                    ]]);


                    $abeNotif = IBONotification::select('id', 'title', 'description', 'status', 'start_time', 'end_time')->where('status', '1')->get()->toArray();
                    $returnData["data"] = [
                        "mac_address" => $foundMac->mac_address,
                        "box_dtls" => $foundMac,
                        "device_key" => $foundMac->device_key,
                        "is_trial" => $foundMac->is_trial,
                        "expiry_date" => $foundMac->expire_date,
                        "app_type" => $foundMac->app_type,
                        "realtime" => 1,
                        "urls" => $urls,
                        "notifications" => $abeNotif,
                        "settings" => [
                            "app_logo"     => "https://iboplayer.com/images/apps/app_logo.png",
                            "app_email" => "info@iboplayer.com",
                            "app_phone" => "+201014045819",
                            "app_title" => "IBO Player",
                            "app_caption" => "IBOPlayer Best Player"
                        ],
                        "themes" => [
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
                    ],

                    ];
                    $returnData["httpCode"] = "200";
                }
                else {
                    //$addValidMac = ['requestData.mac_address' => 'required|unique:App\AbeDevice,mac_address'];
                    $iboPlayerServer = new IBOAppDevice();
                    $iboPlayerServer->mac_address = strtolower($reqData['requestData']['macAddress']);
                    $iboPlayerServer->device_key = (string)rand(100000, 999999);
                    $iboPlayerServer->is_trial = 1;
                    $iboPlayerServer->expire_date = date('Y-m-d', strtotime(date('Y-m-d') . '+1 week'));
                    $iboPlayerServer->app_type = $reqData['requestData']['appType'] ?? "";
                    $iboPlayerServer->status = 1;
                    $IBOIptvRegistrationLogs = new IBOIptvRegistrationLogs();
                    $IBOIptvRegistrationLogs->mac_id = strtolower($reqData['requestData']['macAddress']);
                    $IBOIptvRegistrationLogs->request_logs = json_encode($reqData);
                    $IBOIptvRegistrationLogs->request_ip = $this->getIp($request->ip());
                    $IBOIptvRegistrationLogs->user_agent = $request->header('User-Agent');
                    $IBOIptvRegistrationLogs->app_reg_by = "NEWREG";
                    $IBOIptvRegistrationLogs->request_id = $reqData['requestId'];
                    $IBOIptvRegistrationLogs->save();
                    if ($iboPlayerServer->save()) {
                    //if (false) {
                        $returnData["status"] = true;
                        $returnData["msg"] = "Succesfully registred!";
                        $returnData["statusCode"] = "000000";
                        unset($iboPlayerServer['id']);
                        $abeNotif = IBONotification::where('status', '1')->get()->toArray();
                        $returnData["data"] = [
                            "mac_address" => $iboPlayerServer->mac_address,
                            "device_key" => $iboPlayerServer->device_key,
                            "is_trial" => $iboPlayerServer->is_trial,
                            "expiry_date" => $iboPlayerServer->expire_date,
                            "app_type" => $iboPlayerServer->app_type,
                            "status" => $iboPlayerServer->status,
                            "realtime" => 1,
                            "urls" => [
                                [
                                   "_id" => "99999999",
                                    "device_id" => "9999999999",
                                    "playlist_name" => "Demo Url",
                                    "url" => "http://demo.4kplayer.me/get.php?username=4kplayer&password=4kplayer_Demo&type=m3u_plus&output=mpegts",
                                    "epg_ur"=> "",
                                    "username" => "",
                                    "password" => "",
                                    "is_protected" => "",
                                    "pin" => "",
                                ]
                            ],
                            "notifications" => $abeNotif,
                            "settings" => [
                                "app_logo"     => "https://iboplayer.com/images/apps/app_logo.png",
                                "app_email" => "info@iboplayer.com",
                                "app_phone" => "+201014045819",
                                "app_title" => "IBO PLAYER",
                                "app_caption" => "Best PLAYER FOR ALL DEVICES"
                            ],
                            "themes" => [
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
                        ],
                    "languages" => []

                        ];
                        $returnData["httpCode"] = "200";
                    } else {

                        $returnData["msg"] = "Device can not be registred!";
                        $returnData["statusCode"] = "C10012";
                        $returnData["httpCode"] = "400";
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
            ], 500);
        }
    }
}
