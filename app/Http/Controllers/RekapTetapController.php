<?php

namespace App\Http\Controllers;

use App\Repository\RekapTetapRepository;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RekapTetapController extends Controller
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

    public function listRekapTetap(Request $request) {
        $status = 0;
        $message = '';
        $responseCode = Response::HTTP_UNAUTHORIZED;
        $data = null;

        try {
            $status = 1;
            $message = 'Berhasil menampilkan list penghasilan.';
            $responseCode = Response::HTTP_OK;

            $repo = new RekapTetapRepository;
            $kantor = $request->get('kantor');
            $month = $request->get('bulan');
            $year = $request->get('tahun');
            $kategori = $request->get('kategori');
            $search = $request->get('search') ?? null;
            $limit = $request->get('limit');
            $data = $repo->listRekapTetap($kantor, $kategori, $search, $limit, $year, $month);
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
                'data' => $data
            ];

            return response()->json($response, $responseCode);
        }
    }
    public function detailRekapTetap($nip, Request $request) {
        $status = 0;
        $message = '';
        $responseCode = Response::HTTP_UNAUTHORIZED;
        $data = null;

        try {
            $status = 1;
            $message = 'Berhasil menampilkan detail rekap tetap.';
            $responseCode = Response::HTTP_OK;

            $repo = new RekapTetapRepository;
            $kantor = $request->get('kantor');
            $month = $request->get('bulan');
            $year = $request->get('tahun');
            $kategori = $request->get('kategori');
            $search = $request->get('search') ?? null;
            $limit = $request->get('limit');
            $data = $repo->detailRekapTetap($kantor, $kategori, $search, $limit, $year, $month, $nip);
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
                'nip' => $nip,
                'status' => $status,
                'message' => $message,
                'data' => $data
            ];

            return response()->json($response, $responseCode);
        }
    }
}
