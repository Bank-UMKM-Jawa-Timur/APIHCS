<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class BagianController extends Controller
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

    public function showBagian($id) {
        $status = 0;
        $message = '';
        $responseCode = Response::HTTP_UNAUTHORIZED;
        $data = null;

        try {
            $status = 1;
            $message = 'Berhasil menampilkan data bagian.';
            $responseCode = Response::HTTP_OK;
            $data = DB::table('mst_bagian')
                ->select(
                    'kd_bagian',
                    'nama_bagian',
                )
                ->where('kd_entitas', $id)
                ->get();
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
