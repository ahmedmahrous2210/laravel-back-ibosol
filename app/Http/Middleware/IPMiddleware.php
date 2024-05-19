<?php

namespace App\Http\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Closure;

class IPMiddleware
{

    public function handle($request, Closure $next)
    {
        // if ($request->ip() != "45.252.79.14") {
        
        //     $response = new JsonResponse([
        //         'status' => false,
        //         "errorCode"=> 1001,
        //         "errorMessage"=> 'Invalid Request',
        //         "detailedMessage"=>'Invalid Request',
        //         "ip" => $request->ip()
        //     ], 400);
        //     return $response;
        // }

        return $next($request);
    }

}