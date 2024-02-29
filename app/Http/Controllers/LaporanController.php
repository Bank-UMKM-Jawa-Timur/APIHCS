<?php

namespace App\Http\Controllers;

use App\Repository\LaporanRepository;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LaporanController extends Controller
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

    public function listMutasi(Request $request) {
        $status = 0;
        $message = '';
        $responseCode = Response::HTTP_UNAUTHORIZED;
        $data = null;

        try {
            $status = 1;
            $message = 'Berhasil menampilkan list mutasi.';
            $responseCode = Response::HTTP_OK;

            $repo = new LaporanRepository;
            $data = $repo->listMutasi($request);
        } catch(Exception $e) {
            $status = 0;
            $message = 'Terjadi kesalahan. ' . $e->getMessage();
            $responseCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        } catch(QueryException $e) {
            $status = 0;
            $message = 'Terjadi kesalahan. ' . $e->getMessage();
            $responseCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        } finally {
            $response = [
                'status' => $status,
                'message' => $message,
                'data' => $data
            ];

            return response()->json($response, $responseCode);
        }
    }

    public function listPromosi(Request $request) {
        $status = 0;
        $message = '';
        $responseCode = Response::HTTP_UNAUTHORIZED;
        $data = null;

        try {
            $status = 1;
            $message = 'Berhasil menampilkan list promosi.';
            $responseCode = Response::HTTP_OK;

            $repo = new LaporanRepository;
            $data = $repo->listPromosi($request);
        } catch(Exception $e) {
            $status = 0;
            $message = 'Terjadi kesalahan. ' . $e->getMessage();
            $responseCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        } catch(QueryException $e) {
            $status = 0;
            $message = 'Terjadi kesalahan. ' . $e->getMessage();
            $responseCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        } finally {
            $response = [
                'status' => $status,
                'message' => $message,
                'data' => $data
            ];

            return response()->json($response, $responseCode);
        }
    }

    public function listDemosi(Request $request) {
        $status = 0;
        $message = '';
        $responseCode = Response::HTTP_UNAUTHORIZED;
        $data = null;

        try {
            $status = 1;
            $message = 'Berhasil menampilkan list demosi.';
            $responseCode = Response::HTTP_OK;

            $repo = new LaporanRepository;
            $data = $repo->listPromosi($request);
        } catch(Exception $e) {
            $status = 0;
            $message = 'Terjadi kesalahan. ' . $e->getMessage();
            $responseCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        } catch(QueryException $e) {
            $status = 0;
            $message = 'Terjadi kesalahan. ' . $e->getMessage();
            $responseCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        } finally {
            $response = [
                'status' => $status,
                'message' => $message,
                'data' => $data
            ];

            return response()->json($response, $responseCode);
        }
    }

    public function listJamsostek(Request $request) {
        $status = 0;
        $message = '';
        $responseCode = Response::HTTP_UNAUTHORIZED;
        $data = null;

        try {
            $status = 1;
            $message = 'Berhasil menampilkan list jamsostek.';
            $responseCode = Response::HTTP_OK;

            $repo = new LaporanRepository;
            $data = $repo->listJamsostek($request);
        } catch(Exception $e) {
            $status = 0;
            $message = 'Terjadi kesalahan. ' . $e->getMessage();
            $responseCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        } catch(QueryException $e) {
            $status = 0;
            $message = 'Terjadi kesalahan. ' . $e->getMessage();
            $responseCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        } finally {
            $response = [
                'status' => $status,
                'message' => $message,
                'data' => $data
            ];

            return response()->json($response, $responseCode);
        }
    }

    public function listDpp(Request $request) {
        $status = 0;
        $message = '';
        $responseCode = Response::HTTP_UNAUTHORIZED;
        $data = null;

        try {
            $status = 1;
            $message = 'Berhasil menampilkan list DPP.';
            $responseCode = Response::HTTP_OK;

            $repo = new LaporanRepository;
            $data = $repo->listDpp($request);
        } catch(Exception $e) {
            $status = 0;
            $message = 'Terjadi kesalahan. ' . $e->getMessage();
            $responseCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        } catch(QueryException $e) {
            $status = 0;
            $message = 'Terjadi kesalahan. ' . $e->getMessage();
            $responseCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        } finally {
            $response = [
                'status' => $status,
                'message' => $message,
                'data' => $data
            ];

            return response()->json($response, $responseCode);
        }
    }

    public function listPenonaktifan(Request $request) {
        $status = 0;
        $message = '';
        $responseCode = Response::HTTP_UNAUTHORIZED;
        $data = null;

        try {
            $status = 1;
            $message = 'Berhasil menampilkan list penonaktifan.';
            $responseCode = Response::HTTP_OK;

            $repo = new LaporanRepository;
            $data = $repo->listPenonaktifan($request);
        } catch(Exception $e) {
            $status = 0;
            $message = 'Terjadi kesalahan. ' . $e->getMessage();
            $responseCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        } catch(QueryException $e) {
            $status = 0;
            $message = 'Terjadi kesalahan. ' . $e->getMessage();
            $responseCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        } finally {
            $response = [
                'status' => $status,
                'message' => $message,
                'data' => $data
            ];

            return response()->json($response, $responseCode);
        }
    }
}
