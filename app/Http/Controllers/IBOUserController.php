<?php

namespace App\Http\Controllers;
//use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\IBOUser;
use App\IBOStreamlistActivationCode;


class IBOUserController extends Controller
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

    public function getUserById(Request $request){
        $returnData = array("status" => false, "statusCode" => "10014", "data" => false, "msg" => "Something went wrong.", "httpCode"=> 501);
        try {
            //if ($request->isJson) {
            if (true) {
                $reqData = $request->all();
                if (!empty($reqData)) {
                    $id = isset($reqData['id']) ? $reqData['id'] : "";
                    //$userData = IBOUser::select('id', 'mac_address', 'firstname', 'lastname', 'expiry_date', 'email', 'username', 'streamlist_url', 'streamlist_url2', 'streamlist_url3', 'streamlist_url4', 'streamlist_url5', 'is_activated', 'gender', 'group_id', 'status')->where('mac_address', $macAddress)->first();
                    if (!empty($id)) {
                        $strmActi = IBOUser::where('id', $id)->first();
                        if(!empty($strmActi)){
                            $returnData = array("status" => true, "statusCode" => "00000", "data" => ['IBOUser' => $strmActi], "msg" => "User Added Succesfully.", "httpCode" => 200);    
                        }else{
                            $returnData = array("status" => false, "statusCode" => "10081", "data" => false, "msg" => "Failed to fetch user detail!", "httpCode" => 501);    
                        }
                    } else {
                        $returnData = array("status" => false, "statusCode" => "10082", "data" => false, "msg" => "Invalid inputs!", "httpCode" => 422);
                    }
                }
            } else {
                $returnData = array("status" => false, "statusCode" => "10083", "data" => false, "msg" => "Invalid request type!", "httpCode" => 422);
            }
        } catch (\Exception $e) {
            return new JsonResponse(array("status" => false, "statusCode" => "10084", "data" => false, "msg" => env('APP_DEBUG') ? $e->getMessage() : "Invalid request type!"), 501);
        }
        return new JSonResponse($returnData, $returnData['httpCode']);
    }

    public function editUser(Request $request){
        $returnData = array("status" => false, "statusCode" => "10014", "data" => false, "msg" => "Something went wrong.", "httpCode"=>501);
        try {
            //if ($request->isJson) {
            if (true) {
                $reqData = $request->all();
                if (!empty($reqData)) {
                    $userId = isset($reqData['id']) ? $reqData['id'] : "";
                    //$userData = IBOUser::select('id', 'mac_address', 'firstname', 'lastname', 'expiry_date', 'email', 'username', 'streamlist_url', 'streamlist_url2', 'streamlist_url3', 'streamlist_url4', 'streamlist_url5', 'is_activated', 'gender', 'group_id', 'status')->where('mac_address', $macAddress)->first();
                    if (!empty($userId)) {
                        $strmActi = IBOUser::find($userId);
                        $strmActi->id = $userId;
                        $strmActi->mac_address = $reqData['mac_address'] ?? "";
                        $strmActi->firstname = $reqData['firstname'] ?? "";
                        $strmActi->lastname = $reqData['lastname'] ?? "";
                        $strmActi->streamlist_url = $reqData['streamlist_url'] ?? "";
                        $strmActi->streamlist_url2 = $reqData['streamlist_url2'] ?? "";
                        $strmActi->streamlist_url3 = $reqData['streamlist_url3'] ?? "";
                        $strmActi->streamlist_url4 = $reqData['streamlist_url4'] ?? "";
                        $strmActi->streamlist_url5 = $reqData['streamlist_url5'] ?? "";
                        $strmActi->group_id = '3';
                        $strmActi->created_by = $reqData['created_by'] ?? "";
                        $planExpDate = isset($reqData['selected_plan']) ? $reqData['selected_plan'] : "";
                        if(!empty($planExpDate)){
                            $strmActi->selected_plan = $planExpDate;
                            $planExpDate = $this->__getPlanExp($planExpDate);
                            $strmActi->expiry_date = $planExpDate;
                        }
                        //$strmActi->expiry_date = isset($reqData['expiry_date']) ? date('Y-m-d', strtotime($reqData['expiry_date'])):  "";
                        if($strmActi->save()){
                            $returnData = array("status" => true, "statusCode" => "00000", "data" => ['IBOUser' => $strmActi], "msg" => "User detail updated succesfully.", "httpCode" => 200);    
                        }else{
                            $returnData = array("status" => false, "statusCode" => "10091", "data" => false, "msg" => "Failed to add!", "httpCode" => 501);    
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

    public function updateStatus(Request $request){
        $returnData = array("status" => false, "statusCode" => "10014", "data" => false, "msg" => "Something went wrong.", "httpCode" => 501);
        try {
            //if ($request->isJson) {
            if (true) {
                $reqData = $request->all();
                if (!empty($reqData)) {
                    $isValid = isset($reqData['isValid']) ? $reqData['isValid'] : "";
                    $status = isset($reqData['status']) ? $reqData['status'] : "";
                    $userId = isset($reqData['id']) ? $reqData['id'] : "";
                    //$userData = IBOUser::select('id', 'mac_address', 'firstname', 'lastname', 'expiry_date', 'email', 'username', 'streamlist_url', 'streamlist_url2', 'streamlist_url3', 'streamlist_url4', 'streamlist_url5', 'is_activated', 'gender', 'group_id', 'status')->where('mac_address', $macAddress)->first();
                    if (!empty($isValid)) {
                        $strmActi = IBOUser::find($userId);
                        $strmActi->id = $userId;
                        $strmActi->status = $status;
                        if($strmActi->save() !== false){
                            $returnData = array("status" => true, "statusCode" => "00000", "data" => ['IBOUser' => $strmActi], "msg" => "User status updated Succesfully.", "httpCode" => 200);    
                        }else{
                            $returnData = array("status" => false, "statusCode" => "10061", "data" => false, "msg" => "Failed to update status!", "httpCode" => 501);    
                        }
                    } else {
                        $returnData = array("status" => false, "statusCode" => "10062", "data" => false, "msg" => "Invalid inputs!", "httpCode" => 422);
                    }
                }
            } else {
                $returnData = array("status" => false, "statusCode" => "10063", "data" => false, "msg" => "Invalid request type!", "httpCode" => 422);
            }
        } catch (\Exception $e) {
            return new JsonResponse(array("status" => false, "statusCode" => "10064", "data" => false, "msg" => env('APP_DEBUG') ? $e->getMessage() : "Invalid request type!"), 501);
        }
        return new JSonResponse($returnData, $returnData['httpCode']);
    }

    public function users(Request $request){
        $returnData = array("status" => false, "statusCode" => "10074", "data" => false, "msg" => "Something went wrong.", "httpCode" => 501);
        try {
            //if ($request->isJson) {
            if (true) {
                $reqData = $request->all();
                if (!empty($reqData)) {
                    $isValid = isset($reqData['isValid']) ? $reqData['isValid'] : "";
                    $createdBy = isset($reqData['created_by']) ? $reqData['created_by'] : "";
                    if (!empty($isValid) && !empty($createdBy)) {
                        $strmActi = IBOUser::where('status', '!=', 2)->where('created_by', $createdBy)->get();
                        if(!empty($strmActi)){
                            $returnData = array("status" => true, "statusCode" => "00000", "data" => ['IBOUser' => $strmActi], "msg" => "User list fetched succesfully.", "httpCode" => 200);    
                        }else{
                            $returnData = array("status" => false, "statusCode" => "10071", "data" => false, "msg" => "Failed to add!", "httpCode" => 501);    
                        }
                    } else {
                        $returnData = array("status" => false, "statusCode" => "10072", "data" => false, "msg" => "Invalid inputs!", "httpCode" => 422);
                    }
                }
            } else {
                $returnData = array("status" => false, "statusCode" => "10073", "data" => false, "msg" => "Invalid request type!", "httpCode" => 422);
            }
        } catch (\Exception $e) {
            return new JsonResponse(array("status" => false, "statusCode" => "10074", "data" => false, "msg" => env('APP_DEBUG') ? $e->getMessage() : "Invalid request type!"), 501);
        }
        return new JSonResponse($returnData, $returnData['httpCode']);
    }

    public function addUser(Request $request){
        $returnData = array("status" => false, "statusCode" => "10014", "data" => false, "msg" => "Something went wrong.", "httpCode" => 501);
        try {
            //if ($request->isJson) {
            if (true) {
                $reqData = $request->all();
                if (!empty($reqData)) {
                    $macAddress = isset($reqData['mac_address']) ? $reqData['mac_address'] : "";
                    $streamlistUrl = isset($reqData['streamlist_url']) ? $reqData['streamlist_url'] : "";
                    $activationCode = isset($reqData['activation_code']) ? $reqData['activation_code'] : "";
                    //$userData = IBOUser::select('id', 'mac_address', 'firstname', 'lastname', 'expiry_date', 'email', 'username', 'streamlist_url', 'streamlist_url2', 'streamlist_url3', 'streamlist_url4', 'streamlist_url5', 'is_activated', 'gender', 'group_id', 'status')->where('mac_address', $macAddress)->first();
                    if (!empty($macAddress) && !empty($streamlistUrl)) {
                        $strmActi = new IBOUser;
                        $strmActi->mac_address = strtoupper($macAddress);
                        $strmActi->password = '12345678';
                        $strmActi->firstname = $reqData['firstname'] ?? "";
                        $strmActi->streamlist_url = $reqData['streamlist_url'] ?? "";
                        $strmActi->streamlist_url2 = $reqData['streamlist_url2'] ?? "";
                        $strmActi->streamlist_url3 = $reqData['streamlist_url3'] ?? "";
                        $strmActi->streamlist_url4 = $reqData['streamlist_url4'] ?? "";
                        $strmActi->streamlist_url5 = $reqData['streamlist_url5'] ?? "";
                        $strmActi->is_activated = 0;
                        if(!empty($activationCode)){
                            $strmActi->activation_code = $activationCode;
                            $strmActi->is_activated = 1;
                        }
                        
                        $strmActi->group_id = '3';
                        $strmActi->created_by = $reqData['created_by'] ?? "";
                        $planExpDate = isset($reqData['selected_plan']) ? $reqData['selected_plan'] : "";
                        if(!empty($planExpDate)){
                            $strmActi->selected_plan = $planExpDate;
                            $planExpDate = $this->__getPlanExp($planExpDate);
                            $strmActi->expiry_date = $planExpDate;
                        }
                        $strmActi->status = 1;
                        if($strmActi->save()){
                            $returnData = array("status" => true, "statusCode" => "00000", "data" => ['IBOUser' => $strmActi], "msg" => "User Added Succesfully.", "httpCode" => 200);    
                        }else{
                            $returnData = array("status" => false, "statusCode" => "10051", "data" => false, "msg" => "Failed to add!", "httpCode" => 501);    
                        }
                    } else {
                        $returnData = array("status" => false, "statusCode" => "10052", "data" => false, "msg" => "Invalid inputs!", "httpCode" => 422);
                    }
                }
            } else {
                $returnData = array("status" => false, "statusCode" => "10053", "data" => false, "msg" => "Invalid request type!", "httpCode" => 422);
            }
        } catch (\Exception $e) {
            return new JsonResponse(array("status" => false, "statusCode" => "10054", "data" => false, "msg" => env('APP_DEBUG') ? $e->getMessage() : "Invalid request type!"), 501);
        }
        return new JSonResponse($returnData, $returnData['httpCode']);
    }

    public function addStreamlistActivationCode(Request $request){
        $returnData = array("status" => false, "statusCode" => "10014", "data" => false, "msg" => "Something went wrong.", "httpCode" => 501);
        try {
            //if ($request->isJson) {
            if (true) {
                $reqData = $request->all();
                if (!empty($reqData)) {
                    $streamlist = isset($reqData['streamlist']) ? $reqData['streamlist'] : "";
                    $activationCode = isset($reqData['activation_code']) ? $reqData['activation_code'] : "";
                    //$userData = IBOUser::select('id', 'mac_address', 'firstname', 'lastname', 'expiry_date', 'email', 'username', 'streamlist_url', 'streamlist_url2', 'streamlist_url3', 'streamlist_url4', 'streamlist_url5', 'is_activated', 'gender', 'group_id', 'status')->where('mac_address', $macAddress)->first();
                    if (!empty($streamlist) && !empty($activationCode)) {
                        $strmActi = new IBOStreamlistActivationCode;
                        $strmActi->streamlist_url = $streamlist;
                        $strmActi->activation_code = $activationCode;
                        $strmActi->selected_plan = isset($reqData['selected_plan']) ? $reqData['selected_plan'] : "";
                        $strmActi->status = 0;
                        if($strmActi->save()){
                            $returnData = array("status" => true, "statusCode" => "00000", "data" => false, "msg" => "Added Succesfully.", "httpCode" => 200);    
                        }else{
                            $returnData = array("status" => false, "statusCode" => "10041", "data" => false, "msg" => "Failed to add!", "httpCode" => 501);    
                        }
                    } else {
                        $returnData = array("status" => false, "statusCode" => "10042", "data" => false, "msg" => "Invalid inputs!", "httpCode" => 422);
                    }
                }
            } else {
                $returnData = array("status" => false, "statusCode" => "10043", "data" => false, "msg" => "Invalid request type!", "httpCode" => 501);
            }
        } catch (\Exception $e) {
            return new JsonResponse(array("status" => false, "statusCode" => "10044", "data" => false, "msg" => env('APP_DEBUG') ? $e->getMessage() : "Invalid request type!"), 501);
        }
        return new JSonResponse($returnData, $returnData['httpCode']);
    }

    public function getStreamListActi(Request $request){
        $returnData = array("status" => false, "statusCode" => "10074", "data" => false, "msg" => "Something went wrong.", "httpCode" => 501);
        try {
            //if ($request->isJson) {
            if (true) {
                $reqData = $request->all();
                if (!empty($reqData)) {
                    $isValid = isset($reqData['isValid']) ? $reqData['isValid'] : "";
                    if ($isValid) {
                        $strmActi = IBOStreamlistActivationCode::where('status', 0)->get();
                        if(!empty($strmActi)){
                            $returnData = array("status" => true, "statusCode" => "00000", "data" => ['IBOStreamlistActivationCode' => $strmActi], "msg" => "StreamList Activation list fetched succesfully.", "httpCode" => 200);    
                        }else{
                            $returnData = array("status" => false, "statusCode" => "10071", "data" => false, "msg" => "Failed to add!", "httpCode" => 501);    
                        }
                    } else {
                        $returnData = array("status" => false, "statusCode" => "10072", "data" => false, "msg" => "Invalid inputs!", "httpCode" => 422);
                    }
                }
            } else {
                $returnData = array("status" => false, "statusCode" => "10073", "data" => false, "msg" => "Invalid request type!", "httpCode" => 422);
            }
        } catch (\Exception $e) {
            return new JsonResponse(array("status" => false, "statusCode" => "10074", "data" => false, "msg" => env('APP_DEBUG') ? $e->getMessage() : "Invalid request type!"), 501);
        }
        return new JSonResponse($returnData, $returnData['httpCode']);
    }


    public function custAuth(Request $request)
    {
        $returnData = array("status" => false, "statusCode" => "10014", "data" => false, "msg" => "Something went wrong.", "httpCode" => 501);
        try {
            //if ($request->isJson) {
            if (true) {
                $reqData = $request->all();

                if (!empty($reqData)) {
                    $macAddress = isset($reqData['mac_address']) ? $reqData['mac_address'] : "";
                    $userData = IBOUser::select('id', 'mac_address', 'firstname', 'lastname', 'expiry_date', 'email', 'username', 'streamlist_url', 'streamlist_url2', 'streamlist_url3', 'streamlist_url4', 'streamlist_url5', 'is_activated', 'gender', 'group_id', 'status')->where('mac_address', $macAddress)->first();
                    if (!empty($userData)) {
                        if ($userData['status'] == 1) {
                            if (strtotime($userData['expiry_date']) >= strtotime(date('Y-m-d'))) {
                                $returnData = array("status" => true, "statusCode" => "00000", "data" => ["IBOUser" => $userData], "msg" => "Data validated successfully & returned.", "httpCode" => 200);
                            } else {
                                $returnData = array("status" => false, "statusCode" => "10012", "data" => false, "msg" => "mac_address subscription is expired.", "httpCode" => 501);
                            }
                        } else {
                            $returnData = array("status" => false, "statusCode" => "10011", "data" => false, "msg" => "mac_address is not active state.", "httpCode" => 501);
                        }
                    } else {
                        $returnData = array("status" => false, "statusCode" => "10010", "data" => false, "msg" => "No data foud for mac_address.", "httpCode" => 422);
                    }
                }
            } else {
                $returnData = array("status" => false, "statusCode" => "10017", "data" => false, "msg" => "Invalid request type!", "httpCode" => 422);
            }
        } catch (\Exception $e) {
            return new JsonResponse(array("status" => false, "statusCode" => "10017", "data" => false, "msg" => env('APP_DEBUG') ? $e->getMessage() : "Invalid request type!"), 501);
        }
        return new JSonResponse($returnData, $returnData['httpCode']);
    }

    /**
     * @Author: Himanshu mishra
     */
    public function custAuthActiCode(Request $request)
    {
        $returnData = array("status" => false, "statusCode" => "10029", "data" => false, 'msg'=> 'Something went wrong!', "httpCode" => 501);
        try {
            if (true) {
                $reqData = $request->all();
                if (!empty($reqData) && isset($reqData['activation_code']) && !empty($reqData['activation_code'])) {
                    $macAddress = isset($reqData['mac_address']) ? $reqData['mac_address'] : "";
                    $activationCode = isset($reqData['activation_code']) ? $reqData['activation_code'] : "";
                    $old = IBOUser::where('mac_address', $macAddress)->first();
                    if (!empty($old)) {
                        $validCode = IBOStreamlistActivationCode::where('activation_code', $activationCode)->where('status', 0)->first();
                        if (!empty($validCode)) {
                            //update user activationCode and
                            $updateUser = IBOUser::where('mac_address', $macAddress)->update(['activation_code' => $macAddress, 'status' => 1, 'is_activated' => 1]);
                            //update activationcode status
                            $updateStrActCode = IBOStreamlistActivationCode::where('activation_code', $activationCode)->update(['status' => 1, 'used_by' => $old['id']]);
                            if ($updateStrActCode && $updateUser) {
                                $newUserData = IBOUser::where('mac_address', $macAddress)->first();
                                $returnData = array("status" => true, "statusCode" => "00000", "data" => ["IBOUser" => $newUserData], "msg" => "Userdetail fetched succefully & returned!", "httpCode" => 200);
                            } else {
                                $returnData = array("status" => false, "statusCode" => "10028", "data" => false, "msg" => "Something went wrong!", "httpCode" => 501);
                            }
                        } else {
                            $returnData = array("status" => false, "statusCode" => "10027", "data" => false, "msg" => "Invalid ActivationCode!", "httpCode" => 422);
                        }
                    } else {
                        $validCode = IBOStreamlistActivationCode::where('activation_code', $activationCode)->where('status', 0)->first();
                        if (!empty($validCode)) {
                            $createNewUSer = new IBOUser;
                            $createNewUSer->firstname = '';
                             $createNewUSer->mac_address = $macAddress;
                            $createNewUSer->streamlist_url = $validCode['streamlist_url'];
                            $createNewUSer->lastname = '';
                            $createNewUSer->username = '';
                            $createNewUSer->expiry_date = date('Y-m-d', strtotime('+1 year'));
                            $createNewUSer->is_activated = 1;
                            $createNewUSer->status = 1;
                            $createNewUSer->password = '12345678';
                            $createNewUSer->created_by = null;
                            $createNewUSer->group_id = '3';
                            $createNewUSer->activation_code = $activationCode;
                            if($createNewUSer->save()){
                                $updateStreamActi = IBOStreamlistActivationCode::where('activation_code', $activationCode)->update(['status' => 1, 'used_by' => $createNewUSer['id']]);
                                $returnData = array("status" => true, "statusCode" => "00000", "data" => ["IBOUser" => $createNewUSer], "msg" => "Userdetail fetched succefully & returned!", "httpCode" => 200);
                            }else{
                                $returnData = array("status" => false, "statusCode" => "10028", "data" => false, "msg" => "failed to add user!", "httpCode" => 501);
                            }
                            
                        } else {
                            $returnData = array("status" => false, "statusCode" => "10027", "data" => false, "msg" => "Invalid ActivationCode!", "httpCode" => 422);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            return  array("status" => false, "statusCode" => "10030", "data" => false, "msg" => env('APP_DEBUG') ? $e->getMessage() : "Something went wrong!", "httpCode" => 501);
        }
        return new JsonResponse($returnData, $returnData['httpCode']);
    }

    protected function __getPlanExp($planName){
        $planName = strtoupper($planName);
        switch ($planName) {
            case 'QUARTERLY':
                $returnExpDate = date('Y-m-d', strtotime('+1 week'));
                break;
            case 'MONTHLY':
                $returnExpDate = date('Y-m-d', strtotime('+1 month'));
                break;
            case 'HALFYEARLY':
                $returnExpDate = date('Y-m-d', strtotime('+6 months'));
                break;
            case 'YEARLY':
                $returnExpDate = date('Y-m-d', strtotime('+1 year'));
                break;
            default:
                $returnExpDate  = date('Y-m-d', strtotime('+1 week'));
                break;
        }
        return $returnExpDate;
    }


    public function serachUser(Request $request){
        $returnData = array("status" => false, "statusCode" => "10014", "data" => false, "msg" => "Something went wrong.", "httpCode" => 501);
        try {
            //if ($request->isJson) {
            if (true) {
                $reqData = $request->all();
                $macAddress = isset($reqData['search_key']) ? strtoupper($reqData['search_key']) : "";
                $createdBy = isset($reqData['created_by']) ? $reqData['created_by'] : "";
                $groupId = isset($reqData['group_id']) ? $reqData['group_id'] : "";
                if (!empty($macAddress)) {
                    if($groupId ==1){
                        $userData = IBOUser::select('id', 'mac_address', 'firstname', 'lastname', 'expiry_date', 'email', 'username', 'streamlist_url', 'streamlist_url2', 'streamlist_url3', 'streamlist_url4', 'streamlist_url5', 'is_activated', 'gender', 'group_id', 'status', 'created_at', 'updated_at')->where('mac_address','LIKE', '%'.strtoupper($macAddress).'%')->where('status', '!=', '2')->get();
                    }else {
                        $userData = IBOUser::select('id', 'mac_address', 'firstname', 'lastname', 'expiry_date', 'email', 'username', 'streamlist_url', 'streamlist_url2', 'streamlist_url3', 'streamlist_url4', 'streamlist_url5', 'is_activated', 'gender', 'group_id', 'status', 'created_at', 'updated_at')
                        ->where('mac_address','LIKE', '%'.strtoupper($macAddress).'%')
                        ->where('created_by', $createdBy)
                        ->where('status', '!=', '2')
                        ->get();
                    }
                    
                    if (!empty($userData)) {
                        $returnData = array("status" => true, "statusCode" => "00000", "data" => ["IBOUser" => $userData], "msg" => "Data Searched successfully & returned.", "httpCode" => 200);
                    } else {
                        $returnData = array("status" => false, "statusCode" => "10010", "data" => false, "msg" => "No data foud for mac_address.", "httpCode" => 422);
                    }
                }else{
                    $returnData = array("status" => false, "statusCode" => "10018", "data" => false, "msg" => "No data foud for mac_address.", "httpCode" => 422);
                }

            } else {
                $returnData = array("status" => false, "statusCode" => "10017", "data" => false, "msg" => "Invalid request type!", "httpCode" => 422);
            }
        } catch (\Exception $e) {
            return new JsonResponse(array("status" => false, "statusCode" => "10017", "data" => false, "msg" => env('APP_DEBUG') ? $e->getMessage() : "Something went wrong!"), 501);
        }
        return new JSonResponse($returnData, $returnData['httpCode']);
    }
}
