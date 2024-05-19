<?php

namespace App\Http\Middleware;
use Illuminate\Http\Request;

use Closure;
use Exception;
use Illuminate\Http\JsonResponse;

class WebEncryptionDecryptionMiddleware {

    protected $PASSPHRASE = "IBRAHIMIBOSOLKEYSET#$!@2023";

    public function handle(Request $request, Closure $next){

        $decryptedData = [];
        $response = [];

        $encrypted = base64_decode($request->data, true);
        $decryptedData = $this->CryptoJSAesDecrypt($encrypted);
        $input = json_decode($decryptedData,true);

        if(!empty($input)){
            
            array_walk_recursive($input, function(&$input) { $input = htmlentities($input); });
            $request->merge($input);

        }else{
            $response = new JsonResponse([
                'status' => false,
                'errorCode' =>1001,
                'errorMessage'=>'Unauthorised access!',
                'detailedMessage'=>'Invalid Request'
            ], 400);
        }
        $returnResponse = $next($request);
        return !empty($response) ? $response : $this->CryptoJSAesEncrypt($returnResponse->getContent());
       
    }

    public function CryptoJSAesDecrypt($jsonString){

        $data = !is_array($jsonString) ? json_decode($jsonString, true) : $jsonString;
        
        try{
            
            $salt = hex2bin($data["salt"]);
            $iv  = hex2bin($data["iv"]);          
            $passPhrase  = $this->PASSPHRASE;

        }catch(Exception $e) {
            return null;
        }
   
        $ciphertext = base64_decode($data["ciphertext"]);
        $iterations = 999;
   
        $key = hash_pbkdf2("sha512", $passPhrase, $salt, $iterations, 64);
        $decrypted= openssl_decrypt($ciphertext , 'AES-256-CBC', hex2bin($key), OPENSSL_RAW_DATA, $iv);
   
        return $decrypted;
    }

    public function CryptoJSAesEncrypt($plain_text){
        $passPhrase = $this->PASSPHRASE;
        $salt = openssl_random_pseudo_bytes(256);
        $iv = openssl_random_pseudo_bytes(16);
        $iterations = 999;
        $key = hash_pbkdf2("sha512", $passPhrase, $salt, $iterations, 64);
        $encryptedData = openssl_encrypt($plain_text, 'aes-256-cbc', hex2bin($key), OPENSSL_RAW_DATA, $iv);
        $data = ["ciphertext" => base64_encode($encryptedData), "iv" => bin2hex($iv), "salt" => bin2hex($salt)];
        return base64_encode(json_encode($data));
    }

}

?>