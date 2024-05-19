<?php

namespace App\Http\Controllers;
//use Validator;

use App\IBOReseller;
use App\ResellerCreditPointTranLogs;
use App\UserActiTranLogs;
use App\ArchUserActiTransLogs;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;



class DashboardController extends Controller
{

    public function getCounts(Request $request)
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
                    'requestData.groupId' => 'required'
                ]);

                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }

                if($reqData['requestData']['groupId'] == '1'){                
                    $iboresellerCounts = IBOReseller::where('status', '!=', '2')->count();
                    $todaysActivatedBox = UserActiTranLogs::whereDate('created_at', Carbon::today())->count();
                    $totalActivatedBox = UserActiTranLogs::count();
                    $archTotalActivatedBox = ArchUserActiTransLogs::count();
                    $resellerCreditShare = ResellerCreditPointTranLogs::whereDate('created_at', Carbon::today())->count();
                }else{
                    $iboresellerCounts = IBOReseller::where('status', '!=', '2')->where('created_by',$reqData['requestData']['userId'])->count();
                    $todaysActivatedBox = UserActiTranLogs::whereDate('created_at', Carbon::today())->where('reseller_id',$reqData['requestData']['userId'])->count();
                    $totalActivatedBox = UserActiTranLogs::where('reseller_id',$reqData['requestData']['userId'])->count();
                    $archTotalActivatedBox = ArchUserActiTransLogs::where('reseller_id',$reqData['requestData']['userId'])->count();
                    $resellerCreditShare = ResellerCreditPointTranLogs::whereDate('created_at', Carbon::today())->where('created_by',$reqData['requestData']['userId'])->count();
                }

                $returnData["data"] = [
                    "totalReseller" => $iboresellerCounts,
                    "totalTodaysBox" => $todaysActivatedBox,
                    "totalAllBox" => ($totalActivatedBox+$archTotalActivatedBox),
                    "totalCreditShare" => $resellerCreditShare,
                ];
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
    
    public function getGraphData(Request $request){
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
                    'requestData.groupId' => 'required'
                ]);

                if ($validatedData->fails()) {
                    $returnData["msg"] = env('APP_DEBUG') ? $validatedData->errors() : "Invalid inputs!";
                    $returnData["statusCode"] = "C10011";
                    $returnData["httpCode"] = "422";
                    return new JsonResponse($returnData, 422);
                }

                if($reqData['requestData']['groupId'] == '1'){                
                    
                    $totalActivatedBox = UserActiTranLogs::select(\DB::raw('YEAR(created_at) as year'), 
                    \DB::raw('month(created_at) AS month'), \DB::raw('COUNT(id) as total'))
                    ->groupBy(['year', 'month'])
                    ->get();
                   $appWiseRadarData = UserActiTranLogs::select('module','activated_from as platform' 
                    , \DB::raw('COUNT(id) as total'))
                    ->groupBy(['module', 'platform'])
                    ->get();
                    $appWiseCounts = UserActiTranLogs::select('module',\DB::raw('COUNT(id) as total'))
                    ->groupBy(['module'])
                    ->get();
                }else{
                    
                    $totalActivatedBox = UserActiTranLogs::select(\DB::raw('YEAR(created_at) as year'), 
                    \DB::raw('MONTHNAME(created_at) AS month'), \DB::raw('COUNT(id) as total'))
                    ->where('reseller_id', $reqData['requestData']['userId'])
                    ->groupBy(['year', 'month'])
                    ->get();
                   $appWiseRadarData = UserActiTranLogs::select('module','activated_from as platform' 
                    , \DB::raw('COUNT(id) as total'))
                    ->where('reseller_id', $reqData['requestData']['userId'])
                    ->groupBy(['module', 'platform'])
                    ->get();
                    $appWiseCounts = UserActiTranLogs::select('module',\DB::raw('COUNT(id) as total'))
                    ->where('reseller_id', $reqData['requestData']['userId'])
                    ->groupBy(['module'])
                    ->get();
                }

                $returnData["data"] = [
                    "lineChartData" => $totalActivatedBox,
                    "appWisePlatform" => $appWiseRadarData,
                    "appWiseData" => $appWiseCounts
                ];
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

}