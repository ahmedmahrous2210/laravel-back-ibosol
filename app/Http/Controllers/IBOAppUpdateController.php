<?php

namespace App\Http\Controllers;
//use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\IBOUser;
use App\IBOAppUpdate;


class IBOAppUpdateController extends Controller
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

    public function addAppUpdate(Request $request)
    {
        $returnData = array("status" => false, "statusCode" => "10014", "data" => false, "msg" => "Something went wrong.");
        try {
            //if ($request->isJson) {
            if (true) {
                $reqData = $request->all();
                if (!empty($reqData)) {
                    $reqData['app_version'] = isset($reqData['app_version']) ? $reqData['app_version'] : "";
                    $reqData['update_url'] = isset($reqData['update_url']) ? $reqData['update_url']: "";
                    $reqData['description'] = isset($reqData['description']) ? $reqData['description']: "";
                    //$userData = IBOUser::select('id', 'mac_address', 'firstname', 'lastname', 'expiry_date', 'email', 'username', 'streamlist_url', 'streamlist_url2', 'streamlist_url3', 'streamlist_url4', 'streamlist_url5', 'is_activated', 'gender', 'group_id', 'status')->where('mac_address', $macAddress)->first();
                    if (!empty($reqData['app_version']) && !empty($reqData['update_url']) && !empty($reqData['description'])) {
                        $strmActi = new IBOAppUpdate;
                        $strmActi->app_version = $reqData['app_version'];
                        $strmActi->update_url = $reqData['update_url'];
                        $strmActi->description = $reqData['description'];
                        if(!$strmActi->save()){
                            $returnData = array("status" => true, "statusCode" => "00000", "data" => ['IBOAppUpdate' => $strmActi], "msg" => "User Added Succesfully.");    
                        }else{
                            $returnData = array("status" => false, "statusCode" => "12011", "data" => false, "msg" => "Failed to add app detail!");    
                        }
                    } else {
                        $returnData = array("status" => false, "statusCode" => "12012", "data" => false, "msg" => "Invalid inputs!");
                    }
                }
            } else {
                $returnData = array("status" => false, "statusCode" => "12013", "data" => false, "msg" => "Invalid request type!");
            }
        } catch (\Exception $e) {
            return new JsonResponse(array("status" => false, "statusCode" => "12014", "data" => false, "msg" => env('APP_DEBUG') ? $e->getMessage() : "Invalid request type!"), 501);
        }
        return new JSonResponse($returnData, 200);
    }
    
    public function getAppUpdateList(Request $request){
        $returnData = array("status" => false, "statusCode" => "10014", "data" => false, "msg" => "Something went wrong.");
        try {
            //if ($request->isJson) {
            if (true) {
                $reqData = $request->all();
                if (!empty($reqData)) {
                    $isValid = isset($reqData['isValid']) ? $reqData['isValid'] : "";
                    //$userData = IBOUser::select('id', 'mac_address', 'firstname', 'lastname', 'expiry_date', 'email', 'username', 'streamlist_url', 'streamlist_url2', 'streamlist_url3', 'streamlist_url4', 'streamlist_url5', 'is_activated', 'gender', 'group_id', 'status')->where('mac_address', $macAddress)->first();
                    if (!empty($isValid)) {
                        $strmActi = IBOAppUpdate::get();
                        if(!empty($strmActi)){
                            $returnData = array("status" => true, "statusCode" => "00000", "data" => ['IBOAppUpdate' => $strmActi], "msg" => "Appupdates fetched Succesfully.");    
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
}