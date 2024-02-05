<?php

namespace App\Http\Controllers;

use App\Models\KaryawanModel;
use Carbon\Carbon;
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
                    DB::beginTransaction();
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
                            'mst_karyawan.jk',
                            'mst_karyawan.tanggal_pengangkat',
                            'mst_karyawan.tgl_mulai',
                            'mst_karyawan.no_rekening',
                            'mst_jabatan.nama_jabatan',
                            'mst_bagian.nama_bagian',
                            'mst_cabang.nama_cabang'
                        )
                        ->first();
                        
                    $dataKaryawan = new stdClass;
                    $returnData = new stdClass;
                    $mulaKerja = Carbon::create($karyawan->tanggal_pengangkat);
                    $waktuSekarang = Carbon::now();
                    $hitung = $waktuSekarang->diff($mulaKerja);
                    $tahunKerja = (int) $hitung->format('%y'); 
                    $bulanKerja = (int) $hitung->format('%m'); 
                    $masaKerja = $hitung->format('%y Tahun, %m Bulan');
                    
                    $returnData->nip = $karyawan->nip;
                    $returnData->nama_karyawan = $karyawan->nama_karyawan;
                    $returnData->jenis_kelamin = $karyawan->jk;
                    $returnData->tanggal_bergabung = Carbon::parse($karyawan->tanggal_pengangkat)->translatedFormat('d F Y');
                    $returnData->lama_kerja = $masaKerja;
                    $returnData->no_rekening = $karyawan->no_rekening;
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
                    
                    if ($karyawan->nama_bagian != null && $dataKaryawan->entitas->type == 1){
                        $entitas = '';
                    } else if (isset($dataKaryawan->entitas->subDiv)) {
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
        
                    $display_jabatan = $prefix . ' ' . $jabatan . ' ' . $entitas . ' ' . $karyawan?->nama_bagian . ' ' . $ket . ($karyawan->nama_cabang != null ? ' Cabang ' . $karyawan->nama_cabang : '');
                    $returnData->display_jabatan = $display_jabatan;
                    // End get data karyawan

                    DB::commit();
                    $data = $returnData;
                }
            } else {
                $message = 'NIP tidak dapat ditemukan';
            }
        } catch (Exception $e){
            DB::rollBack();
            $status = 0;
            $message = 'Terjadi kesalahan. ' . $e->getMessage();
            $responseCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        } catch (QueryException $e){
            DB::rollBack();
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

    public function changePassword(Request $request){
        $status = 0;
        $message = '';
        $responseCode = Response::HTTP_UNAUTHORIZED;
        $data = null;

        DB::beginTransaction();
        try{
            DB::table('mst_karyawan')
                ->where('nip', $request->get('nip'))
                ->update([
                    'password' => Hash::make($request->get('password'))
                ]);
            DB::commit();

            $status = 1;
            $message = 'Berhasil merubah password.';
            $responseCode = Response::HTTP_OK;
        } catch(Exception $e) {
            DB::rollBack();
            $message = 'Terjadi kesalahan. ' . $e->getMessage();
            $responseCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        } catch(QueryException $e) {
            DB::rollBack();
            $message = 'Terjadi kesalahan. ' . $e->getMessage();
            $responseCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        } finally {
            $response = [
                'status' => $status,
                'message' => $message
            ];

            return response()->json($response, $responseCode);
        }
    }

    public function biodata($id) {
        $status = 0;
        $message = '';
        $responseCode = Response::HTTP_UNAUTHORIZED;
        $data = null;
        
        try {
            $responseCode = Response::HTTP_OK;
            $status = 1;
            $message = 'Berhasil menampilkan biodata.';
            $karyawan = DB::table('mst_karyawan')
                ->where('nip', $id)
                ->first();
            if($karyawan == null){
                $status = 0;
                $message = 'Karyawan tidak ditemukan.';
            } else {
                $karyawan = DB::table('mst_karyawan')
                        ->where('nip', $id)
                        ->leftJoin('mst_cabang', 'mst_cabang.kd_cabang', 'mst_karyawan.kd_entitas')
                        ->leftJoin('mst_bagian', 'mst_bagian.kd_bagian', 'mst_karyawan.kd_bagian')
                        ->leftJoin('mst_pangkat_golongan', 'mst_pangkat_golongan.golongan', 'mst_karyawan.kd_panggol')
                        ->leftJoin('mst_jabatan', 'mst_jabatan.kd_jabatan', 'mst_karyawan.kd_jabatan')
                        ->leftJoin('mst_agama', 'mst_agama.kd_agama', 'mst_karyawan.kd_agama')
                        ->select(
                            'mst_karyawan.*',
                            'mst_jabatan.nama_jabatan',
                            'mst_bagian.nama_bagian',
                            'mst_cabang.nama_cabang',
                            'mst_pangkat_golongan.pangkat',
                            'mst_pangkat_golongan.golongan',
                            'mst_agama.agama',
                        )
                        ->first();
                        
                    $dataKaryawan = new stdClass;
                    $returnData = new stdClass;
                    $mulaKerja = Carbon::create($karyawan->tanggal_pengangkat);
                    $waktuSekarang = Carbon::now();
                    $hitung = $waktuSekarang->diff($mulaKerja);
                    $tahunKerja = (int) $hitung->format('%y'); 
                    $bulanKerja = (int) $hitung->format('%m'); 
                    $masaKerja = $hitung->format('%y Tahun, %m Bulan');
                    $tanggalLahir = Carbon::create($karyawan->tgl_lahir);
                    $hitung = $waktuSekarang->diff($tanggalLahir);
                    $umur = $hitung->format('%y Tahun | %m Bulan | %d Hari');

                    // Biodata diri
                    $returnData->nip = $karyawan->nip;
                    $returnData->nik = $karyawan->nik;
                    $returnData->nama_karyawan = $karyawan->nama_karyawan;
                    $returnData->ttl = $karyawan->tmp_lahir . ', ' . date('d F Y', strtotime($karyawan->tgl_lahir));
                    $returnData->umur = $umur;
                    $returnData->agama = $karyawan->agama;
                    $returnData->status_pernikahan = $karyawan->status_ptkp;
                    $returnData->kewarganegaraan = $karyawan->kewarganegaraan;
                    $returnData->alamat_ktp = $karyawan->alamat_ktp;
                    $returnData->alamat_sek = $karyawan->alamat_sek;
                    $returnData->jenis_kelamin = $karyawan->jk;
                    // End biodata diri

                    // Norek & NPWP
                    $returnData->no_rek = $karyawan->no_rekening;
                    $returnData->npwp = $karyawan->npwp;
                    // End Norek & NPWP

                    // Data Jabatan
                    $returnData->tanggal_bergabung = Carbon::parse($karyawan->tanggal_pengangkat)->translatedFormat('d F Y');
                    $returnData->lama_kerja = $masaKerja;
                    $returnData->pangkat = $karyawan->pangkat;
                    $returnData->golongan = $karyawan->golongan;
                    $returnData->status_karyawan = $karyawan->status_karyawan;
                    $returnData->status_jabatan = $karyawan->status_jabatan;
                    $returnData->keterangan_jabatan = $karyawan->ket_jabatan;
                    $returnData->tanggal_mulai = date('d F Y', strtotime($karyawan->tgl_mulai));
                    $returnData->pendidikan_terakhir = $karyawan->pendidikan;
                    $returnData->pendidikan_major = $karyawan->pendidikan_major;
                    $returnData->sk_pengangkatan = $karyawan->skangkat;
                    $returnData->tanggal_pengangkatan = $karyawan->tanggal_pengangkat;
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
                    
                    if ($karyawan->nama_bagian != null && $dataKaryawan->entitas->type == 1){
                        $entitas = '';
                    } else if (isset($dataKaryawan->entitas->subDiv)) {
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
        
                    $display_jabatan = $prefix . ' ' . $jabatan . ' ' . $entitas . ' ' . $karyawan?->nama_bagian . ' ' . $ket . ($karyawan->nama_cabang != null ? ' Cabang ' . $karyawan->nama_cabang : ' (Pusat)');
                    $returnData->display_jabatan = $display_jabatan;

                    $data = $returnData;
            }
        } catch(Exception $e) {
            $message = 'Terjadi kesalahan. ' . $e->getMessage();
            $responseCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        } catch(QueryException $e) {
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

    public function addEntity($karyawan)
    {
        return $this->getEntity($karyawan);
    }
}
