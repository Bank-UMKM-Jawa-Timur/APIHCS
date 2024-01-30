<?php

namespace App\Http\Controllers;

use App\Models\KaryawanModel;
use Exception;
use finfo;
use Illuminate\Database\QueryException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use stdClass;

class KaryawanController extends Controller
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

    public function login(Request $request){
        $status = 0;
        $message = '';
        $responseCode = Response::HTTP_UNAUTHORIZED;
        $data = null;
        try{
            $responseCode = Response::HTTP_OK;
            $karyawan = DB::table('mst_karyawan')
                ->where('nip', $request->get('nip'))
                ->first();
            if($karyawan != null){
                if(!Hash::check($request->get('password'), $karyawan->password)){
                    $message = 'Password yang anda masukkan salah';
                } else if(DB::table('personal_access_tokens')->where('tokenable_id', $request->get('nip'))->count() > 0) {
                    $message = 'Akun sedang digunakan di perangkat lain.';
                } else {
                    DB::table('personal_access_tokens')
                        ->insert([
                            'tokenable_type' => 'api',
                            'name' => 'api',
                            'tokenable_id' => $request->get('nip'),
                            'token' => Hash::make($request->get('nip')),
                            'created_at' => date('Y-m-d H:i:s')
                        ]);
                    
                    $status = 1;
                    $message = 'Berhasil login';
                    $karyawan = DB::table('mst_karyawan')
                        ->where('nip', $request->get('nip'))
                        ->leftJoin('mst_cabang', 'mst_cabang.kd_cabang', 'mst_karyawan.kd_entitas')
                        ->leftJoin('mst_bagian', 'mst_bagian.kd_bagian', 'mst_karyawan.kd_bagian')
                        ->leftJoin('mst_pangkat_golongan', 'mst_pangkat_golongan.golongan', 'mst_karyawan.kd_panggol')
                        ->leftJoin('mst_jabatan', 'mst_jabatan.kd_jabatan', 'mst_karyawan.kd_jabatan')
                        ->select(
                            'mst_karyawan.nip',
                            'mst_karyawan.nik',
                            'mst_karyawan.nama_karyawan',
                            'mst_karyawan.kd_bagian',
                            'mst_karyawan.kd_jabatan',
                            'mst_karyawan.kd_entitas',
                            'mst_karyawan.tanggal_penonaktifan',
                            'mst_karyawan.status_jabatan',
                            'mst_karyawan.ket_jabatan',
                            'mst_karyawan.kd_entitas',
                            'mst_jabatan.nama_jabatan',
                            'mst_bagian.nama_bagian'
                        )
                        ->first();
                        
                    $dataKaryawan = new stdClass;
                    $dataKaryawan->nip = $karyawan->nip;
                    $dataKaryawan->nama_karyawan = $karyawan->nama_karyawan;
                    $dataKaryawan->entitas = $this->getEntity($karyawan->kd_entitas);
                    $prefix = match ($karyawan->status_jabatan) {
                        'Penjabat' => 'Pj. ',
                        'Penjabat Sementara' => 'Pjs. ',
                        default => '',
                    };
                    
                    $jabatan = '';
                    if ($karyawan->nama_jabatan) {
                        $jabatan = $karyawan->nama_jabatan;
                    } else {
                        $jabatan = 'undifined';
                    }
                    
                    $ket = $karyawan->ket_jabatan ? "({$karyawan->ket_jabatan})" : '';
                    
                    if (isset($dataKaryawan->entitas->subDiv)) {
                        $entitas = $dataKaryawan->entitas->subDiv->nama_subdivisi;
                    } elseif (isset($dataKaryawan->entitas->div)) {
                        $entitas = $dataKaryawan->entitas->div->nama_divisi;
                    } else {
                        $entitas = '';
                    }
                    
                    if ($jabatan == 'Pemimpin Sub Divisi') {
                        $jabatan = 'PSD';
                    } elseif ($jabatan == 'Pemimpin Bidang Operasional') {
                        $jabatan = 'PBO';
                    } elseif ($jabatan == 'Pemimpin Bidang Pemasaran') {
                        $jabatan = 'PBP';
                    } else {
                        $jabatan = $karyawan?->nama_jabatan ? $karyawan?->nama_jabatan : 'undifined';
                    }
        
                    $display_jabatan = $prefix . ' ' . $jabatan . ' ' . $entitas . ' ' . $karyawan?->nama_bagian . ' ' . $ket;
                    $dataKaryawan->display_jabatan = $display_jabatan;
                    // End get data karyawan

                    $data = $dataKaryawan;
                }
            } else {
                $message = 'NIP tidak dapat ditemukan';
            }
        } catch (Exception $e){
            $status = 0;
            $message = 'Terjadi kesalahan. ' . $e->getMessage();
            $responseCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        } catch (QueryException $e){
            $status = 0;
            $message = 'Terjadi kesalahan. ' . $e->getMessage();
            $responseCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        } 
        finally{
            $response = [
                'status' => $status,
                'message' => $message,
                'data' => $data
            ];

            return response()->json($response, $responseCode);
        }
    }

    public function logout(Request $request){
        $status = 0;
        $message = '';
        $responseCode = Response::HTTP_UNAUTHORIZED;

        DB::beginTransaction();
        try{
            $responseCode = Response::HTTP_OK;
            $data = DB::table('personal_access_tokens')
                ->where('tokenable_id', $request->get('nip'))
                ->delete();
            DB::commit();
            $message = 'Berhasil logout.';
            $status = 1;
        } catch(Exception $e) {
            DB::rollBack();
            $status = 0;
            $message = 'Terjadi kesalahan. ' . $e->getMessage();
            $responseCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        } catch(QueryException $e) {
            DB::rollBack();
            $status = 0;
            $message = 'Terjadi kesalahan. ' . $e->getMessage();
            $responseCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        } finally {
            $response = [
                'status' => $status,
                'message' => $message,
            ];

            return response()->json($response, $responseCode);
        }
    }

    public function getSlipGaji(Request $request){
        $status = 0;
        $message = '';
        $responseCode = Response::HTTP_UNAUTHORIZED;
        $data = null;

        try{
            $month = $request->get('month');
            $year = $request->get('year');
            $nip = $request->get('nip');
            
        } catch (Exception $e){
            $message = 'Terjadi kesalahan. ' . $e->getMessage();
            $responseCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        } catch (QueryException $e){
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

    private static function getEntity($entity)
    {
        if (!$entity) return (object) [
            'type' => 1,
        ];

        $subDiv = DB::table('mst_sub_divisi')
            ->select('*')
            ->where('kd_subdiv', $entity)
            ->first();

        $div = DB::table('mst_divisi')
            ->select('*')
            ->where('kd_divisi', ($subDiv) ? $subDiv->kd_divisi : $entity)
            ->first();

        $cab = DB::table('mst_cabang')
            ->select('*')
            ->where('kd_cabang', $entity)
            ->first();

        if ($subDiv) return (object) [
            'type' => 1,
            'subDiv' => $subDiv,
            'div' => $div
        ];

        if ($div) return (object) [
            'type' => 1,
            'div' => $div,
            'subDiv' => null
        ];

        return (object) [
            'type' => 2,
            'cab' => $cab
        ];
    }
}
