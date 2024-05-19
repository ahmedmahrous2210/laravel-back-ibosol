<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\IBOReseller;
use App\Tickets;
class TicketController extends Controller{

    public function addTicket(Request $request){
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
                    'requestData.title' => 'required',
                    'requestData.description'=> 'required'
                ]);
                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }
    
                $addTicket = new Tickets();
                $addTicket->title = $reqData['requestData']['title'];
                $addTicket->description = $reqData['requestData']['description'];
                $addTicket->created_by = $reqData['requestData']['createdBy'];
                if ($addTicket->save()) {
                    $returnData["status"] = true;
                    $returnData["msg"] = "Ticket added successful!";
                    $returnData["statusCode"] = "000000";
                    $returnData["data"] = ["Ticket" => $addTicket];
                    $returnData["httpCode"] = "200";
                } else {
                    $returnData["msg"] = "Ticket adding failed!";
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

    public function myTickets(Request $request){
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
 
                    $myTickets = Tickets::where('created_by', $reqData['requestData']['createdBy'])->with(['admin' => function ($query) {
            $query->select('id', 'name', 'email');
        }])->with(['resellers' => function ($query) {
            $query->select('id', 'name', 'email');
        }])->orderBy('created_at', 'desc')
                ->get();
                if ($myTickets) {
                    $returnData["status"] = true;
                    $returnData["msg"] = "Ticket fetched successful!";
                    $returnData["statusCode"] = "000000";
                    $returnData["data"] = ["MyTickets" => $myTickets];
                    $returnData["httpCode"] = "200";
                } else {
                    $returnData["msg"] = "myTickets not found!";
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
    
    public function changeStatus(Request $request){
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
 
                $myTickets = Tickets::where('id', $reqData['requestData']['ticketId'])->update([
                    'status' => $reqData['requestData']['status']
                ]);
                if ($myTickets) {
                    $returnData["status"] = true;
                    $returnData["msg"] = "Ticket updated successful!";
                    $returnData["statusCode"] = "000000";
                    $returnData["data"] = ["MyTickets" => $myTickets];
                    $returnData["httpCode"] = "200";
                } else {
                    $returnData["msg"] = "myTickets not found!";
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
    
    
    public function getAllOpenTicket(Request $request){
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
 
                $allTickets = Tickets::whereIn('status', [ 1, 2,3, 4])->with(['admin' => function ($query) {
            $query->select('id', 'name', 'email');
        }])->with(['resellers' => function ($query) {
            $query->select('id', 'name', 'email');
        }])->orderBy('created_at', 'desc')->get();
                if ($allTickets) {
                    $returnData["status"] = true;
                    $returnData["msg"] = "Ticket fetched successful!";
                    $returnData["statusCode"] = "000000";
                    $returnData["data"] = ["AllTickets" => $allTickets];
                    $returnData["httpCode"] = "200";
                } else {
                    $returnData["msg"] = "Tickets not found!";
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
    
    public function updateAdminRemark(Request $request){
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
 
                $myTickets = Tickets::where('id', $reqData['requestData']['ticketId'])->update([
                    'admin_comment' => $reqData['requestData']['adminRemarks'],
                    'attended_by'=> $reqData['requestData']['createdBy'],
                    'attended_at' =>  Carbon::today(),
                    'status' => 2
                ]);
                if ($myTickets) {
                    $returnData["status"] = true;
                    $returnData["msg"] = "Ticket updated successful!";
                    $returnData["statusCode"] = "000000";
                    $returnData["data"] = ["MyTickets" => $myTickets];
                    $returnData["httpCode"] = "200";
                } else {
                    $returnData["msg"] = "myTickets not found!";
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
