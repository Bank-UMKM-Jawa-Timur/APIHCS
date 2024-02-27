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

// Auth
$router->post('/login', 'KaryawanController@login');
$router->post('/logout', 'KaryawanController@logout');

// Slip Gaji
$router->group(['prefix' => 'slip-gaji'], function () use ($router) {
    $router->get('/list', 'SlipGajiController@list');
    $router->get('/detail/{id}', 'SlipGajiController@detail');
});

// Change password
$router->post('/change-password', 'KaryawanController@changePassword');
// get biodata
$router->get('/biodata/{id}', 'KaryawanController@biodata');

// Karyawan
$router->group(['prefix' => 'karyawan'], function () use ($router) {
    $router->get('/search', 'KaryawanController@searchKaryawan');
    $router->get('/', 'KaryawanController@listKaryawan');
    $router->get('/{id}', 'KaryawanController@detailKaryawan');
});

// Reminder Pensiun
$router->group(['prefix' => 'reminder-pensiun'], function () use ($router) {
    $router->get('/', 'KaryawanController@listDataPensiun');
    $router->get('/{id}', 'KaryawanController@detailDataPensiun');
});

// Pengkinian data
$router->group(['prefix' => 'pengkinian-data'], function () use ($router) {
    $router->get('/', 'KaryawanController@listPengkinianData');
    $router->get('/{id}', 'KaryawanController@detailPengkinianData');
});

// Pergerakan Karir
$router->group(['prefix' => 'pergerakan-karir'], function () use ($router) {
    $router->get('/mutasi', 'KaryawanController@listMutasi');
    $router->get('/promosi', 'KaryawanController@listPromosi');
    $router->get('/demosi', 'KaryawanController@listDemosi');
    $router->get('/penonaktifan', 'KaryawanController@listPenonaktifan');
});

// History
$router->group(['prefix' => 'history'], function () use ($router) {
    $router->get('jabatan/{id}', 'HistoryController@getHistoryJabatan');
    $router->get('pjs', 'HistoryController@getHistoryPJS');
    $router->get('surat-peringatan', 'HistoryController@getHistorySP');
});

// Surat peringatan
$router->group(['prefix' => 'surat-peringatan'], function () use ($router) {
    $router->get('/', 'KaryawanController@listSP');
    $router->get('/{id}', 'KaryawanController@detailSP');
});

// Laporan
$router->group(['prefix' => 'laporan'], function () use ($router) {
    $router->get('/mutasi', 'LaporanController@listMutasi');
    $router->get('/promosi', 'LaporanController@listPromosi');
    $router->get('/demosi', 'LaporanController@listDemosi');
    $router->get('/jamsostek', 'LaporanController@listJamsostek');
});

$router->get('/cabang', 'CabangController@showCabang');
$router->get('/divisi', 'DivisiController@showDivisi');
$router->get('/sub-divisi/{id}', 'SubdivisiController@showSubdivisi');
$router->get('/bagian/{id}', 'BagianController@showBagian');
$router->get('/dashboard', 'DashboardController@getDataDashboard');
$router->get('/pjs', 'KaryawanController@listPJS');