<?php

namespace App\Http\Controllers;

use App\Repository\PenghasilanRepository;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PenghasilanController extends Controller
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

    public function listPenghasilan(Request $request) {
        $status = 0;
        $message = '';
        $responseCode = Response::HTTP_UNAUTHORIZED;
        $data = null;

        try {
            $status = 1;
            $message = 'Berhasil menampilkan list penghasilan.';
            $responseCode = Response::HTTP_OK;
            
            $repo = new PenghasilanRepository;
            $status = $request->get('status');
            $cabang = $request->get('cabang') ?? null;
            $limit = $request->get('limit') ?? 10;
            $page = $request->get('page') ?? 1;
            $search = $request->get('search') ?? null;

            // Filter
            $bulan = $request->get('bulan') ?? null;
            $tahun = $request->get('tahun') ?? null;
            $kantor = strtolower($request->get('kantor')) ?? null;

            // $data = $status;
            $data = $repo->listPenghasilan($cabang, $status, $limit, $page, $search, $bulan, $tahun);
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

    public function detailPenghasilan($id, Request $request) {
        $status = 0;
        $message = '';
        $responseCode = Response::HTTP_UNAUTHORIZED;
        $data = null;

        try {
            $status = 1;
            $message = 'Berhasil menampilkan list penghasilan.';
            $responseCode = Response::HTTP_OK;
            
            $repo = new PenghasilanRepository;
            $search = $request->get('search') ?? null;
            $data = $repo->detailPenghasilan($id, $search);
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

    public function rincianPenghasilan(Request $request) {
        $status = 0;
        $message = '';
        $responseCode = Response::HTTP_UNAUTHORIZED;
        $data = null;

        try {
            $status = 1;
            $message = 'Berhasil menampilkan list penghasilan.';
            $responseCode = Response::HTTP_OK;
            
            $repo = new PenghasilanRepository;
            $nip = $request->get('nip');
            $month = $request->get('bulan');
            $year = $request->get('tahun');
            $batch_id = $request->get('batch_id');
            $kategori = strtolower($request->get('kategori'));

            $data = $repo->getRincianPayroll($month, $year, $batch_id, $nip, $kategori);
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
}
