<?php

namespace App\Http\Controllers;

use App\Repository\DashboardRepository;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function getDataDashboard(Request $request) {
        $status = 0;
        $responseCode = Response::HTTP_UNAUTHORIZED;
        $message = '';
        $data = null;

        try {
            $message = 'Berhasil menampilkan perkiraan gaji bulan ini';
            $responseCode = Response::HTTP_OK;
            $status = 1;

            $repo = new DashboardRepository;
            $data = $repo->getTotalGaji();
        } catch (Exception $e) {
            $status = 0;
            $message = 'Terjadi kesalahan. ' . $e->getMessage();
            $responseCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        } catch (QueryException $e) {
            $status = 0;
            $message = 'Terjadi kesalahan. ' . $e->getMessage();
            $responseCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        } finally {
            $response = [
                'status' => $status,
                'message' => $message,
                'data' => $data,
            ];

            return response($response, $responseCode);
        }
    }

    public function rincianGaji(Request $request) {
        $status = 0;
        $responseCode = Response::HTTP_UNAUTHORIZED;
        $message = '';
        $data = null;

        try {
            $message = 'Berhasil menampilkan rincian perkiraan gaji bulan ini';
            $responseCode = Response::HTTP_OK;
            $status = 1;

            $repo = new DashboardRepository;
            $data = $repo->getRincianGaji();
        } catch (Exception $e) {
            $status = 0;
            $message = 'Terjadi kesalahan. ' . $e->getMessage();
            $responseCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        } catch (QueryException $e) {
            $status = 0;
            $message = 'Terjadi kesalahan. ' . $e->getMessage();
            $responseCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        } finally {
            $response = [
                'status' => $status,
                'message' => $message,
                'data' => $data,
            ];

            return response($response, $responseCode);
        }
    }
}
