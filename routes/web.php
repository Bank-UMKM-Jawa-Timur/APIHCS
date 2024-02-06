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

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->post('/login', 'KaryawanController@login');
$router->post('/logout', 'KaryawanController@logout');

$router->group(['prefix' => 'slip-gaji'], function () use ($router) {
    $router->get('/list', 'SlipGajiController@list');
    $router->get('/detail/{id}', 'SlipGajiController@detail');
});

$router->post('/change-password', 'KaryawanController@changePassword');
$router->get('/biodata/{id}', 'KaryawanController@biodata');

$router->group(['prefix' => 'karyawan'], function () use ($router){
    $router->get('/', 'KaryawanController@listKaryawan');
    $router->get('/{id}', 'KaryawanController@detailKaryawan');
});