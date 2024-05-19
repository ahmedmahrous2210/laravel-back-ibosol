<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;

class CORSMiddleware{
    
    public $whiteIps = ['185.132.132.166'];
    
    
    public function handle($request, Closure $next){
        // if (!in_array($request->server('REMOTE_ADDR'), $this->whiteIps)) {
        //     return new JsonResponse([
        //             "status" => false,
        //             "statusCode" => "INV0403",
        //             "msg"=> "Invalid request - restricted ip",
        //             "dt" => $request->ip(),
        //             "serverip" => $request->server('SERVER_ADDR'),
        //             "rip" => $request->server('REMOTE_ADDR')
        //         ], 501);
        // }else{
            if($this->isPreflightRequest($request)){
                $response = $this->createEmptyResponse();
            }else{
                $response = $next($request);
            }
            $response = $this->addCorsHeaders($request, $response);
            return $response;
        //}
    }

    protected function isPreflightRequest($request){
        return $request->isMethod('OPTIONS');
    }

    protected function createEmptyResponse(){
        return new Response(null, 204);
    }
    
    protected function addCorsHeaders($request, $response){
        
        
        foreach([
            'Access-Control-Allow-Origin' =>  '*',
            'Access-Control-Max-Age'=> (60*60*24),
            'Access-Control-Allow-Headers' => $request->header('Access-Control-Request-Headers'),
            'Access-Control-Allow-Method' => $request->header('Access-Control-Request-Methods'),
            'Access-Control-Allow-Credentials' => 'true',
        ] as $header => $value){
            $response->header($header, $value);
        }
        return $response;
    }
}