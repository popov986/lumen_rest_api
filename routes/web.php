<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->post('auth/login', ['uses' => 'AuthController@login']);

$router->group(['middleware' => 'jwt.auth'], function() use ($router) {
    $router->get('user/countries', ['uses' => 'UserController@countries']);
    $router->get('user/countries/{search}', ['uses' => 'UserController@search']);
    $router->get('user/countries/details/{alpha2Code}', ['uses' => 'UserController@details']);

    $router->get('user/favourites', ['uses' => 'UserController@favourites']);
    $router->post('user/favourites', ['uses' => 'UserController@addFavorite']);
    $router->delete('user/favourites/{alpha2Code}', ['uses' => 'UserController@removeFavourite']);

    $router->post('user/countries/comments', ['uses' => 'UserController@addComment']);
    //$router->delete('user/countries/comments', ['uses' => 'UserController@removeComment']);

    $router->get('user/info', ['uses' => 'UserController@user']);
});








