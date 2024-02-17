<?php

namespace App\Http\Controllers;

use App\Repository\HistoryRepository;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Response;

class HistoryController extends Controller
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

    public function getHistoryJabatan($id) {
        $status = 0;
        $message = '';
        $responseCode = Response::HTTP_UNAUTHORIZED;
        $data = null;

        try {
            $status = 1;
            $message = 'Berhasil menampilkan list history jabatan.';
            $responseCode = Response::HTTP_OK;

            $nip = $id;
            $repo = new HistoryRepository;
            $data = $repo->getHistoryJabatan($nip);
        } catch(Exception $e) {
            $status = 0;
            $message = 'Terjadi kesalahan. ' . $e->getMessage();
            $responseCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        } catch(QueryException $e) {
            $status = 0;
            $message = 'Terjadi kesalahan. ' . $e->getMessage();
            $responseCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        } 
        finally {
            $response = [
                'status' => $status,
                'message' => $message,
                'data' => $data
            ];

            return response()->json($response, $responseCode);
        }
    }
}
