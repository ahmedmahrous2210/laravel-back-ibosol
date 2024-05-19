<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EncryptDecryptMiddleware {


    const ALGO = 'aes-256-cbc';
    const TOKEN_KEY = 'SASASASASASASASASASASASASASADADA';
    const PADDING = 'aes-256-cbc';//Assuming 128-bit key size
    const IV = '1212ASASASASASAS';
    const IV_SIZE = 0;
    
    
    public function handle(Request $request, Closure $next){
        $decryptedData = [];
        $response = [];
        $encrypted = $request->all();
        if(!isset($encrypted['requestEnc'])){
            return new JsonResponse([
                'status' => false,
                "errorCode"=>1001,
                "errorMessage"=> 'Invalid Request',
                "detailedMessage"=> 'requestEnc required!'
            ], 422);  
        }
        //return new JsonResponse($encrypted);
        $decryptedData = $this->decrypt ($encrypted);
        unset($request['requestEnc']);
        //return new JsonResponse(json_decode($decryptedData['requestData'], true));
        // if(isset($decryptedData['requestData']) && ($decryptedData['requestData'] == false)){
        //     return new JsonResponse([
        //         'status' => false,
        //         "errorCode"=>1001,
        //         "errorMessage"=> 'Invalid Request',
        //         "detailedMessage"=>'Invalid encyption/decryption'
        //     ], 400);
        // }
        
        if(!empty($decryptedData)){
            array_walk_recursive($decryptedData, function(&$decryptedData) { $decryptedData = ($decryptedData); });
            $request->merge($decryptedData);
            
            
        }else{
            $response = new JsonResponse([
                'status' => false,
                "errorCode"=>1001,
                "errorMessage"=> 'Invalid Request',
                "detailedMessage"=>'Invalid Request'
            ], 400);
        }
        $returnResponse = $next ($request);
        return !empty ($response) ? $response : $this->encrypt($returnResponse->getContent());
    }


    private function encrypt($request){
        
        
        
        $request = json_decode($request, true);
        if(empty($request['data'])){
            return $request;
        }
        $reqData = $request;
        $jecn = json_encode($reqData['data']);
        $request['responseData'] = base64_encode($jecn);
        unset($request['data']);
        return $request;
        //iv - CLIENTID
        // $iv = "1212ASASASASASAS";
        // $encKey =  "SASASASASASASASASASASASASASADADA";
        // $options = 0;
        // $ciphering = "AES-256-CBC";

        // $jecn = json_encode($reqData['data']);
        // $encryption = openssl_encrypt($jecn, $ciphering, $encKey, $options, $iv);
        // $request['responseData'] = bin2hex($encryption);
        // unset($request['data']);
        // return $request;
    }

    //$request
    private function decrypt($request){
        //$newReq = trim($request['requestEnc'], $request['requestId']);
        $request['requestData'] = json_decode(base64_decode($request['requestEnc'], true), true);
        unset($request['requestEnc']);
        //$request['requestData'] = $this->decryptAES($request['requestEnc']);
        return $request;
    }
    
    
    public function decryptAES($encryptedData)
    {
        
        $data = base64_decode($encryptedData);
        $iv = self::IV;
        $cipherText = substr($data, 16);
        return rtrim(openssl_decrypt($cipherText, 'aes-256-cbc', self::TOKEN_KEY, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv), "\0");

    }
}