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

$router->group(['prefix' => 'karyawan'], function () use ($router) {
    $router->get('/search', 'KaryawanController@searchKaryawan');
    $router->get('/', 'KaryawanController@listKaryawan');
    $router->get('/{id}', 'KaryawanController@detailKaryawan');
});

$router->group(['prefix' => 'reminder-pensiun'], function () use ($router) {
    $router->get('/', 'KaryawanController@listDataPensiun');
    $router->get('/{id}', 'KaryawanController@detailDataPensiun');
});

$router->group(['prefix' => 'pengkinian-data'], function () use ($router) {
    $router->get('/', 'KaryawanController@listPengkinianData');
    $router->get('/{id}', 'KaryawanController@detailPengkinianData');
});

$router->group(['prefix' => 'pergerakan-karir'], function () use ($router) {
    $router->get('/mutasi', 'KaryawanController@listMutasi');
    $router->get('/promosi', 'KaryawanController@listPromosi');
    $router->get('/demosi', 'KaryawanController@listDemosi');
    $router->get('/penonaktifan', 'KaryawanController@listPenonaktifan');
});

$router->get('/cabang', 'CabangController@showCabang');
$router->get('/divisi', 'DivisiController@showDivisi');
$router->get('/sub-divisi/{id}', 'SubdivisiController@showSubdivisi');
$router->get('/bagian/{id}', 'BagianController@showBagian');
$router->get('/dashboard', 'DashboardController@getDataDashboard');