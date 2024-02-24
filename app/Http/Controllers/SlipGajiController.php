<?php

namespace App\Http\Controllers;

use App\Repository\HistoryRepository;
use App\Repository\SlipGajiRepository;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Database\QueryException;

class SlipGajiController extends BaseController
{
    public function list(Request $request)
    {
        $status = 0;
        $message = '';
        $data = null;
        $responseCode = Response::HTTP_UNAUTHORIZED;

        DB::beginTransaction();
        try {
            $nip = $request->get('nip');
            $tahun = (int) $request->get('tahun');
            $bulan = $request->has('bulan') ? (int) $request->get('bulan') : 0;

            $slipRepo = new SlipGajiRepository;
            $data = $slipRepo->list($nip, $tahun, $bulan);
            $repo = new HistoryRepository;
            $rincian = $repo->getRincianKaryawn($nip);

            $responseCode = Response::HTTP_OK;
            $message = 'Berhasil mengambil data.';
            $status = 1;
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
                'rincian' => $rincian,
                'data' => $data,
            ];

            return response()->json($response, $responseCode);
        }
    }

    public function detail($id)
    {
        $status = 0;
        $message = '';
        $data = null;
        $responseCode = Response::HTTP_UNAUTHORIZED;

        DB::beginTransaction();
        try {
            $slipRepo = new SlipGajiRepository;
            $data = $slipRepo->detail($id);

            if ($data) {
                $responseCode = Response::HTTP_OK;
                $message = 'Berhasil mengambil data.';
            } else {
                $responseCode = Response::HTTP_NOT_FOUND;
                $message = 'Data tidak ditemukan';
            }
            $status = 1;
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

            return response()->json($response, $responseCode);
        }
    }
}
