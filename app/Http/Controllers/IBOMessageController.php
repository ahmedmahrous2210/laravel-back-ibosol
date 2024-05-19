<?php

namespace App\Http\Controllers;
//use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\IBOUser;
use App\IBOMessage;


class IBOMessageController extends Controller
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

    public function addMessage(Request $request)
    {
        $returnData = array("status" => false, "statusCode" => "10014", "data" => false, "msg" => "Something went wrong.");
        try {
            //if ($request->isJson) {
            if (true) {
                $reqData = $request->all();
                if (!empty($reqData)) {
                    $reqData['title'] = isset($reqData['title']) ? $reqData['title'] : "";
                    $reqData['description'] = isset($reqData['description']) ? $reqData['description']: "";
                    $reqData['start_time'] = isset($reqData['start_time']) ? date('Y-m-d', strtotime($reqData['start_time'])): "";
                    $reqData['end_time'] = isset($reqData['end_time']) ? date('Y-m-d', strtotime($reqData['end_time'])): "";
                    
                    //$userData = IBOUser::select('id', 'mac_address', 'firstname', 'lastname', 'expiry_date', 'email', 'username', 'streamlist_url', 'streamlist_url2', 'streamlist_url3', 'streamlist_url4', 'streamlist_url5', 'is_activated', 'gender', 'group_id', 'status')->where('mac_address', $macAddress)->first();
                    if (!empty($reqData['title']) && !empty($reqData['description']) && !empty($reqData['start_time'])) {
                        $strmActi = new IBOMessage;
                        $strmActi->title = $reqData['title'];
                        $strmActi->description = $reqData['description'];
                        $strmActi->start_time = $reqData['start_time'];
                        $strmActi->end_time = $reqData['end_time'];
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
    
    public function messageList(Request $request){
        $returnData = array("status" => false, "statusCode" => "10014", "data" => false, "msg" => "Something went wrong.");
        try {
            //if ($request->isJson) {
            if (true) {
                $reqData = $request->all();
                if (!empty($reqData)) {
                    $reqData['isValid'] = isset($reqData['isValid']) ? $reqData['isValid'] : "";
                    //$userData = IBOUser::select('id', 'mac_address', 'firstname', 'lastname', 'expiry_date', 'email', 'username', 'streamlist_url', 'streamlist_url2', 'streamlist_url3', 'streamlist_url4', 'streamlist_url5', 'is_activated', 'gender', 'group_id', 'status')->where('mac_address', $macAddress)->first();
                    if (!empty($reqData['isValid'])) {
                        $strmActi = IBOMessage::all();
                        if(!empty($strmActi)){
                            $returnData = array("status" => true, "statusCode" => "00000", "data" => ['IBOMessage' => $strmActi], "msg" => "Message fetched Succesfully.");    
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