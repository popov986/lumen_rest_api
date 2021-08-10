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
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ApiController;
use Exception;
use Illuminate\Support\Facades\Auth;

class UserController extends BaseController
{

    protected $auth;

    public function __construct(AuthController $authcontroller)
    {
        $this->auth = $authcontroller;
    }

    public function countries(Request $request) {

        $user_id = $this->auth::me($request->bearerToken())->sub;

        $client = new ApiController();
        $response = $client->ApiCall('GET', 'https://restcountries.eu/rest/v2/all');
        if(isset($response->error)){
            return response()->json(['error'=> $response->error ], 404);
        }

        $data = array();
        $fav = $this->getUserFavoriteCountriesAlfa2Codes($user_id);

        foreach($response as $country){
            $country->favourite = false;
            if (in_array($country->alpha2Code, $fav)){
                $country->favourite = true;
            }
            $data[] = $country;
        }

        return response()->json(['data'=>$data], 200);
    }

    public  function search (Request $request, $search=''){

        if(empty($search)){
            return response()->json(['error'=> 'Empty search params'], 400);
        }

        $user_id = $this->auth::me($request->bearerToken())->sub;

        $client = new ApiController();
        $response = $client->ApiCall('GET', 'https://restcountries.eu/rest/v2/name/'. $search);
        if(isset($response->error)){
            return response()->json(['error'=> $response->error ], 404);
        }

        $data = array();
        $fav = $this->getUserFavoriteCountriesAlfa2Codes($user_id);

        foreach($response as $country){
            $country->favourite = false;
            if (in_array($country->alpha2Code, $fav)){
                $country->favourite = true;
            }
            $data[] = $country;
        }

        return response()->json(['data'=> $data], 200);
    }

    public function details(Request $request, $alpha2Code=''){

        if(empty($alpha2Code)){
            return response()->json(['error'=> 'Empty alpha2Code !'], 400);
        }

        $user_id = $this->auth::me($request->bearerToken())->sub;

        $client = new ApiController();
        $response = $client->ApiCall('GET', 'https://restcountries.eu/rest/v2/alpha/'.$alpha2Code);
        if(isset($response->error)){
            return response()->json(['error'=> $response->error ], 404);
        }
        return response()->json(['data'=> $response], 200);

    }

    public  function favourites(Request $request){
        $user_id = $this->auth::me($request->bearerToken())->sub;

        $client = new ApiController();
        $response = $client->ApiCall('GET', 'https://restcountries.eu/rest/v2/all');
        if(isset($response->error)){
            return response()->json(['error'=> $response->error ], 404);
        }

        $data = array();
        $fav = $this->getUserFavoriteCountriesAlfa2Codes($user_id);

        foreach($response as $country){
            $country->favourite = false;
            if (in_array($country->alpha2Code, $fav)){
                $country->favourite = true;
                $country->comments = $this->getUserCountryComments($user_id, $country->alpha2Code);
                $data[] = $country;
            }
        }

        return response()->json(['data'=> $data], 200);
    }

    public  function user(Request $request){
        $user = $this->auth::me($request->bearerToken());
        return response()->json(['data'=> $user], 200);
    }


    public function addFavorite(Request $request){
        $user_id = $this->auth::me($request->bearerToken())->sub;
        $res = DB::insert("REPLACE INTO `user_country` (`user_id`,`alpha2Code`) VALUES (?,?);  ", [$user_id, $request->alpha2Code]);
        if($res){
            return response()->json(['created'=> true], 200);
        }
        return response()->json(['created'=> false], 400);
    }

    public function removeFavourite(Request $request, $alpha2Code){
        $user_id = $this->auth::me($request->bearerToken())->sub;
        $res = DB::delete("DELETE FROM `user_country`  WHERE `user_id` = ? AND `alpha2Code` = ?  ", [$user_id, $alpha2Code]);
        if($res){
            return response()->json(['deleted'=> true], 200);
        }
        return response()->json(['deleted'=> false], 400);
    }


    public function addComment(Request $request){
        $user_id = $this->auth::me($request->bearerToken())->sub;

        if(!isset($request->comment) || empty($request->comment)){
            return response()->json(['error'=> 'Missing comment !'], 400);
        }
        $user_country_id = $this->getUserCountryId($user_id, $request->alpha2Code);
        if(empty($user_country_id) || !is_numeric($user_country_id)){
            return response()->json(['error'=> 'Invalid data !'], 400);
        }

        $res = DB::insert("INSERT INTO `user_country_comments` (`user_country_id`,`comment`) VALUES (?,?); ", [$user_country_id, $request->comment]);
        if($res){
            return response()->json(['created'=> true], 200);
        }
        return response()->json(['created'=> false], 400);
    }



    protected function getUserCountryComments($id, $alpha2Code){
        $user_country_id = $this->getUserCountryId($id, $alpha2Code);
        return  DB::select("SELECT * FROM `user_country_comments` WHERE user_country_id  = ? ", [$user_country_id]);
    }

    protected function getUserCountryId($id, $alpha2Code){
        $data = DB::select("SELECT id FROM `user_country` WHERE `user_id` = ? AND `alpha2Code` = ? LIMIT 1; ", [$id, $alpha2Code]);
        return isset($data[0]->id) ? $data[0]->id : false;
    }

    protected function getUserFavoriteCountriesAlfa2Codes($id){
        $favourites = DB::select("SELECT `alpha2Code` FROM `user_country` WHERE `user_id` = ? ",[$id]);
        $res = array();
        foreach($favourites as $row){
            $res[] = $row->alpha2Code;
        }
        return $res;
    }



}
