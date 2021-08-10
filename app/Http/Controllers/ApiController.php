<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use GuzzleHttp\Client;
use Exception;

class ApiController extends BaseController
{

    protected $client;

    public function __construct()
    {
        $guzzleClient = new Client();
        $this->client = $guzzleClient;
    }

    public function ApiCall($method='GET', $url='') {

        try {
            $apirequest = $this->client->request($method, $url);
            if($apirequest->getStatusCode() == 200){
                return json_decode($apirequest->getBody());
            }
            return (object)['error'=> $apirequest->getBody()];
        } catch (Exception $e) {
            return (object)['error'=> $e->getMessage()];
        }

    }




}
