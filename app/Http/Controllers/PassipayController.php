<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\IBOReseller;
//api key- 539b7b-5ee44a-fd55b5-ad9757-45afda
//secret key - 180959-09982d-8469f9-907193-b552c4
//ip -- 141.95.168.19
//platform id-694

//====
//amount $200
//Invoice ID - M3WT8210
//payment link - https://payment.passimpay.io/m3wt8210

//amount - $0.12
//Invoice ID - 8CVSZZT7
//payment link - https://payment.passimpay.io/8cvszzt7
class PassipayController extends Controller
{
    public function passiPayGetPaymentUrl(Request $request){
        $appRefId = null;
        try{
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
            $validatedData = Validator::make($reqData, [
                'module' => ['required', Rule::in(['MASA', 'VIRGINIA', 'IBOAPP', 'BAYIPTV'])],
                'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER', 'TestChannel'])],
                'requestId' => 'required',
                'requestData.orderId' => 'required|min:3|max:50',
                'requestData.userId' =>'required',
                'requestData.creditPoint' => 'required'
            ]);

            if ($validatedData->fails()) {
                $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                $returnData["statusCode"] = "C10422";
                $returnData["appRefId"] = $appRefId;
                $returnData["httpCode"] = "422";
                return new JsonResponse($returnData, 422);
            }
            
            
            $url = config('passipay.createOrderUrl');//;
            $platform_id = config('passipay.platformId'); // Platform ID
            $apikey = config('passipay.apikey');// Secret key
            $order_id = time();//$reqData['requestData']['orderId']; // Payment ID of your platform
            $amount = $this->__getAmountByCreditPoint($reqData['requestData']['creditPoint']); // type string, USD, decimals - 2
            $currencies = ""; // List the currency ID separated by commas. Not required
            
            $payload = http_build_query(['platform_id' => $platform_id, 'order_id' => $order_id, 'amount' => $amount]);
            $hash = hash_hmac('sha256', $payload, $apikey);
            
            $data = [
                'platform_id' => $platform_id,
                'order_id' => $order_id,
                'amount' => $amount,
                'hash' => $hash
            ];
            //return new JsonResponse([$apikey, "$payload"=> $payload]);
            $post_data = http_build_query($data);
            
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            $result = curl_exec($curl);
            curl_close( $curl );
            
            $result = json_decode($result, true);
            // Response options
            // In case of success
            if (isset($result['result']) && $result['result'] == 1){
                //write here for passimpay model data
                $this->__addPassimPayTransLogs(["user_id" => $reqData['requestData']['userId'], "order_id" => $order_id, "invoice_url" => $result["url"], "amount" => $amount, "credit_points" => $reqData['requestData']['creditPoint']]);
                return new JsonResponse([
                    "status" => true,
                    "statusCode" => "000000",
                    "msg" =>  "Succesfully returned url",
                    "requestId" => $reqData["requestId"] ?? "",
                    "appRefId" => $appRefId,
                    "channelId" => $reqData["channelId"] ?? "",
                    "module" => $reqData["module"] ?? "",
                    "data" => [
                        "paymentUrl" => $result["url"],
                        "orderId" => $order_id,
                        "amount" => $amount
                    ],
                    "httpCode" => "200",
                ], 200); 
            }else{
                $error = $result['message']; 
                return new JsonResponse([
                    "status" => false,
                    "statusCode" => "PASERR501",
                    "msg" =>  "Failed returned url----".$error,
                    "requestId" => $reqData["requestId"] ?? "",
                    "appRefId" => $appRefId,
                    "channelId" => $reqData["channelId"] ?? "",
                    "module" => $reqData["module"] ?? "",
                    "httpCode" => "501",
                ], 501); 
            }
            
        }catch(\Exception $e){
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
    
    private function __getAmountByCreditPoint($creditPoint){
        switch($creditPoint){
            case '5':
                return '10.00';
            case '100':
                return '200.00';
            case '200':
                return '400.00';
            case '500':
                return '700.00';
            case '1000':
                return '1250.00';
            default:
                return '100.00';
        }
    }
    
    protected function __addPassimPayTransLogs($transData)
    {
        $passimPay = new \App\PassiPayPayload;
        $passimPay->approve = $transData['approve'] ?? "";
        $passimPay->order_id = $transData['order_id'] ?? "";
        $passimPay->payment_id = $transData['payment_id'] ?? "";
        $passimPay->address_to = $transData['address_to'] ?? "";
        $passimPay->amount = $transData['amount'] ?? "";
        $passimPay->fee = $transData['fee'] ?? "";
        $passimPay->txhash = $transData['txhash'] ?? "";
        $passimPay->credit_points = $transData['credit_points'] ?? "";
        $passimPay->user_id = $transData['user_id'] ?? "";
        $passimPay->invoice_url = $transData['invoice_url'] ?? "";
        return $passimPay->save();
    }
    
    /**
     * for updating the logs table with primary key only.
     *
     **/
    
    protected function __updatePassimPayTransLogs($transData, $pk)
    {
        $passimPay = \App\PassiPayPayload::find($pk);
        if(isset($transData['approve']) && !empty($transData['approve'])){
            $passimPay->approve = $transData['approve'] ?? "";    
        }
        
        if(isset($transData['payment_id']) && !empty($transData['payment_id'])){
            $passimPay->payment_id = $transData['payment_id'] ?? "";
        }
        
        if(isset($transData['address_to']) && !empty($transData['address_to'])){
            $passimPay->address_to = $transData['address_to'] ?? "";
        }
        
        if(isset($transData['amount']) && !empty($transData['amount'])){
            $passimPay->amount = $transData['amount'] ?? "";
        }
        
        if(isset($transData['fee']) && !empty($transData['fee'])){
            $passimPay->fee = $transData['fee'] ?? "";
        }
        
        if(isset($transData['txhash']) && !empty($transData['txhash'])){
            $passimPay->txhash = $transData['txhash'] ?? "";
        }
        
        if(isset($transData['amount_paid']) && !empty($transData['amount_paid'])){
            $passimPay->amount_paid = $transData['amount_paid'] ?? "";
        }
        
        if(isset($transData['order_status']) && !empty($transData['order_status'])){
            $passimPay->order_status = $transData['order_status'] ?? "";
        }
        
        if(isset($transData['order_status_checked_at']) && !empty($transData['order_status_checked_at'])){
            $passimPay->order_status_checked_at = $transData['order_status_checked_at'] ?? "";
        }
        return $passimPay->save();
    }
    
    public function passiPayGetPaymentDetails(Request $request){
        $appRefId = null;
        try{
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
            $validatedData = Validator::make($reqData, [
                'module' => ['required', Rule::in(['MASA', 'VIRGINIA', 'IBOAPP', 'BAYIPTV'])],
                'channelId' => ['required', Rule::in(['IBOPLAYERAPP', 'SAZPIN', 'MASAPLAYER', 'TestChannel'])],
                'requestId' => 'required',
                'requestData.orderId' => 'required|min:3|max:50',
                'requestData.userId' =>'required'
            ]);

            if ($validatedData->fails()) {
                $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                $returnData["statusCode"] = "C10422";
                $returnData["appRefId"] = $appRefId;
                $returnData["httpCode"] = "422";
                return new JsonResponse($returnData, 422);
            }
            
            //first check here if order_id present in table
            $validOrder =  \App\PassiPayPayload::where('order_id', $reqData['requestData']['orderId'])->first();
            if($validOrder == false){
                $returnData['statusCode'] = 'INVL422';
                $returnData['msg'] = "Invalid Order Id!";
                $returnData['httpCode'] = 422;
                return new JsonResponse($returnData);
            }else if($validOrder && ($validOrder->order_status == 'paid')){
                $returnData['statusCode'] = 'SUP200';
                $returnData['msg'] = "Your payment was already been completed and credit has been assigned.";
                $returnData['httpCode'] = 200;
                return new JsonResponse($returnData);
            }
            //end checking and returning reponse of not found
            
            $url = config('passipay.orderStatusUrl');//;
            $platform_id = config('passipay.platformId'); // Platform ID
            $apikey = config('passipay.apikey');// Secret key
            $order_id = $reqData['requestData']['orderId']; // Payment ID of your platform
            
            $payload = http_build_query(['platform_id' => $platform_id, 'order_id' => $order_id]);
            $hash = hash_hmac('sha256', $payload, $apikey);
            
            $data = [
                'platform_id' => $platform_id,
                'order_id' => $order_id,
                'hash' => $hash
            ];
            $post_data = http_build_query($data);
            
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            $result = curl_exec($curl);
            curl_close( $curl );
            
            $result = json_decode($result, true);
            // Response options
            // In case of success
            if (isset($result['result']) && $result['result'] == 1){
                //write here to update passimpay order status data
                $this->__updatePassimPayTransLogs([ 
                  "amount_paid" => $result['amount_paid'] ?? "",
                  "order_status" => $result['status'],
                  "order_status_checked_at" => date("Y-m-d H:i:s")
                ], $validOrder->id);
               
                //check if paid then assign creditpoints
                if(isset($result['status']) && $result['status'] == 'paid'){
                    $reseller = IBOReseller::find($validOrder->user_id);
                    $reseller->credit_point = ($reseller->credit_point + $validOrder->credit_points);
                    $reseller->save();
                }
                
                return new JsonResponse([
                    "status" => true,
                    "statusCode" => "000000",
                    "msg" =>  "Succesfully returned response from service.",
                    "requestId" => $reqData["requestId"] ?? "",
                    "appRefId" => $appRefId,
                    "channelId" => $reqData["channelId"] ?? "",
                    "module" => $reqData["module"] ?? "",
                    "data" => $result,
                    "httpCode" => "200",
                ], 200); 
            }else{
                $error = $result['message']; 
                return new JsonResponse([
                    "status" => false,
                    "statusCode" => "PASERR501",
                    "msg" =>  "Failed to returned response----".$error,
                    "requestId" => $reqData["requestId"] ?? "",
                    "appRefId" => $appRefId,
                    "channelId" => $reqData["channelId"] ?? "",
                    "module" => $reqData["module"] ?? "",
                    "httpCode" => "501",
                ], 501); 
            }
            
        }catch(\Exception $e){
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
    
    public function getMyOrders(Request $request){
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
                    'requestData.createdBy' => 'required'
                ]);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }
 
                $myOrders = \App\PassiPayPayload::where('user_id', $reqData['requestData']['createdBy'])->get();
                if ($myOrders) {
                    $returnData["status"] = true;
                    $returnData["msg"] = "Order fetched successful!";
                    $returnData["statusCode"] = "000000";
                    $returnData["data"] = ["MyOrders" => $myOrders];
                    $returnData["httpCode"] = "200";
                } else {
                    $returnData["msg"] = "myOrders not found!";
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

}