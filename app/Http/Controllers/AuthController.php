<?php

namespace App\Http\Controllers;

use Validator;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Firebase\JWT\ExpiredException;
use Illuminate\Support\Facades\Hash;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Exception;


class AuthController extends BaseController
{

    // private $request;


    // public function __construct(Request $request) {
    //     $this->request = $request;
    // }


    protected function jwt($user_id) {
        $expire_time = time() + 60 * 60;
        $payload = [
            'iss' => "vancho", // Issuer of the token
            'sub' => $user_id, // Subject of the token
            'iat' => time(), // Time when JWT was issued.
            'exp' =>  $expire_time // Expiration time
        ];

        $token = JWT::encode($payload, env('JWT_SECRET'));

        return (object)[
            'token'=> $token,
            'expire'=>$expire_time
        ];
    }


    public function login(Request $request) {

        $this->validate($request, [
            'email'     => 'required|email',
            'password'  => 'required'
        ]);

        $user = DB::select("SELECT * FROM users where email = ? LIMIT 1",[$request->email]);

        if(!empty($user) && isset($user[0]->id) && password_verify($request->password, $user[0]->password)){
            $jwt = $this->jwt($user[0]->id);
            return response()->json([
                'access_token' => $jwt->token,
                'expire_at' => $jwt->expire
            ], 200);
        }

        // Bad Request response
        return response()->json(['error' => 'Invalid Credentials !'], 400);
    }


    public static function me($token)
    {
        try {
            return  JWT::decode($token, env('JWT_SECRET'), array('HS256'));
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }


}
