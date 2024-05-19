<?php

namespace App\Http\Middleware;

use App\MasaClient;
use Closure;
use Illuminate\Http\JsonResponse;

class ClientValidationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $reqData = $request->all();
        if ($request->hasHeader('clientId') && $request->hasHeader('secretKey')) {
            //validate client and secret
            $validClient = MasaClient::where('client_id', $request->header('clientId'))->where('secret_key', $request->header('secretKey'))->count();
            if ($validClient === 0) {
                $returnData["msg"] = 'Invalid client details!';
                $returnData["requestId"] = $reqData['requestId'] ?? "";
                $returnData["appRefId"] = $reqData['appRefId'] ?? "";
                $returnData["channelId"] = $reqData['channelId'] ?? "";
                $returnData["module"] = $reqData['module'] ?? "";
                $returnData["status"] = false;
                $returnData["statusCode"] = "C10422";
                $returnData["httpCode"] = "422";
                return new JsonResponse($returnData, 422);
            }
            return $next($request);
        }else{
            $returnData["msg"] = 'Invalid client details!';
            $returnData["requestId"] = $reqData['requestId'] ?? "";
            $returnData["appRefId"] = $reqData['appRefId'] ?? "";
            $returnData["channelId"] = $reqData['channelId'] ?? "";
            $returnData["module"] = $reqData['module'] ?? "";
            $returnData["status"] = false;
            $returnData["statusCode"] = "C10422";
            $returnData["httpCode"] = "422";
            return new JsonResponse($returnData, 422);
        }
        
    }
}
