<?php

namespace App\Http\Controllers;

use App\ClientActiTranLogs;
use App\ClientCreditShareLogs;
use App\Clients;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
class ClientsController extends Controller{

    public function addClient(Request $request)
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
                    'requestData.email' => 'required|email|unique:App\Clients,email_id',
                    'requestData.channelName' => 'required|unique:App\Clients,channel_name',
                    'requestData.clientId' => 'required|unique:App\Clients,client_id',
                    'requestData.secretKey' => 'required',
                    'requestData.creditPoint' => 'required',
                    'requestData.creatorId' => 'required'
                ]);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }
                $iboreseller = new Clients;
                $iboreseller->channel_name = $reqData['requestData']['channelName'];
                $iboreseller->client_id = $reqData['requestData']['clientId'];
                $iboreseller->email_id = $reqData['requestData']['email'];
                $iboreseller->secret_key = $reqData['requestData']['secretKey'];
                $iboreseller->created_by = $reqData['requestData']['creatorId'];
                $iboreseller->status = "1";
                $iboreseller->credit_point = $reqData['requestData']['creditPoint'] ?? 0;
                if ($iboreseller->save()) {
                    $returnData["status"] = true;
                    $returnData["msg"] = "Client added successfully!";
                    $returnData["statusCode"] = "000000";
                    unset($iboreseller['password']);
                    unset($iboreseller['id']);
                    $returnData["data"] = ["Clients" => $iboreseller];
                    $returnData["httpCode"] = "200";
                } else {
                    $returnData["msg"] = "Client can not be added this moment, Please try after sometime.!";
                    $returnData["statusCode"] = "C10012";
                    $returnData["httpCode"] = "501";
                }
                return new JsonResponse($returnData, $returnData['httpCode']);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => false,
                "statusCode" => "C10010",
                "msg" => env("APP_DEBUG") ? $e->getMessage() : "Technical error occured, Please try after sometime.",
                "requestId" => $reqData["requestId"] ?? "",
                "appRefId" => $reqData["appRefId"] ?? "",
                "channelId" => $reqData["channelId"] ?? "",
                "module" => $reqData["module"] ?? "",
            ], 501);
        }
    }

    

    public function clientList(Request $request)
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

                
                $iboreseller =  Clients::where('status', '!=', '2')
                ->withCount('activatedBox')
                ->get();
                

                if (!empty($iboreseller)) {
                    $returnData["status"] = true;
                    $returnData["msg"] = "Client list fetched successfully!";
                    $returnData["statusCode"] = "000000";
                    $returnData["data"] = ["Clients" => $iboreseller];
                    $returnData["httpCode"] = "200";
                } else {
                    $returnData["msg"] = "Client list can not be fetched this moment, Please try after sometime.!";
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
                
                $iboreseller = Clients::find($reqData['resellerId']);
                $iboreseller->credit_point = ($iboreseller->credit_point == 0) ? $reqData['creditPoint'] : ($iboreseller->credit_point + $reqData['creditPoint']);
				$creditorRes = Clients::find($reqData['createdBy']);
				
                if ($iboreseller->save()) {
                    if ($reqData['groupId'] == 2) {
                        //$creditorRes = Clients::find($reqData['createdBy']);
                        $creditorRes->credit_point = ($creditorRes->credit_point - $reqData['creditPoint']);
                        $creditorRes->save();
                    }
                    //update into resecrdittarans logs
                    $this->__addClientCreditShareLogs($reqData);
                    //update into resecrdittarans logs
                    $returnData["status"] = true;
                    $returnData["msg"] = "Client credit  successfully added!";
                    $returnData["statusCode"] = "000000";
                    unset($iboreseller['password']);
                    unset($iboreseller['id']);
                    $returnData["data"] = ["Clients" => $iboreseller];
                    $returnData["httpCode"] = "200";
                } else {
                    $returnData["msg"] = "Client credit point can not be added this moment, Please try after sometime.!";
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

    public function debitCreditPointFromClient(Request $request){
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
                $iboreseller = Clients::find($reqData['resellerId']);
                if($iboreseller->credit_point === null || $iboreseller->credit_point == 0 || $iboreseller->credit_point < $reqData['creditPoint']){
                    $returnData["msg"] = "Client do not have enough credit, to be debited!";
                    $returnData["statusCode"] = "C10202";
                    $returnData["httpCode"] = "202";
                    return new JsonResponse($returnData, $returnData['httpCode']);
                }
                $iboreseller->credit_point = ($iboreseller->credit_point == null) ? $reqData['creditPoint'] : ($iboreseller->credit_point - $reqData['creditPoint']);
                if ($iboreseller->save()) {
                    if ($reqData['groupId'] == 2) {
                        $creditorRes = Clients::find($reqData['createdBy']);
                        $creditorRes->credit_point = ($creditorRes->credit_point + $reqData['creditPoint']);
                        $creditorRes->save();
                    }
                    //update into resecrdittarans logs
                    $this->__addClientCreditShareLogs($reqData, 'DEBIT');
                    //update into resecrdittarans logs
                    $returnData["status"] = true;
                    $returnData["msg"] = "Client credit  successfully deducted!";
                    $returnData["statusCode"] = "000000";
                    unset($iboreseller['password']);
                    unset($iboreseller['id']);
                    $returnData["data"] = ["Clients" => $iboreseller];
                    $returnData["httpCode"] = "200";
                } else {
                    $returnData["msg"] = "Client credit point can not be debit this moment, Please try after sometime.!";
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

    private function __addClientCreditShareLogs($reqData, $trType = 'CREDIT')
    {
        $reseCredit = new ClientCreditShareLogs();
        $reseCredit->debitor_id = $reqData['createdBy'];
        $reseCredit->creditor_id = $reqData['resellerId'];
        $reseCredit->credit_point = $reqData['creditPoint'];
        $reseCredit->created_by = $reqData['createdBy'];
        $reseCredit->tr_type = $trType;
        return $reseCredit->save();
    }

    public function ClientTransLogs(Request $request){
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
                    $userActiLogs = ClientActiTranLogs::where('reseller_id', $reqData['userId'])->with('resellerDetail')->orderBy('created_at', 'desc')->get();
                } else {
                    $userActiLogs = ClientActiTranLogs::orderBy('created_at', 'desc')->get();
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
}