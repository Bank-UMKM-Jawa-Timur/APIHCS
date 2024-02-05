<?php

namespace App\Repository;

use App\Http\Controllers\KaryawanController;
use Illuminate\Support\Facades\DB;
use stdClass;

class SlipGajiRepository
{
    public function list($nip, $tahun, int $bulan = 0) {
        $returnData = [];
        $karyawan = DB::table('mst_karyawan')
            ->where('nip', $nip)
            ->first();
        $data = DB::table('gaji_per_bulan AS gaji')
                    ->select(
                        'gaji.id',
                        'gaji.batch_id',
                        'batch.tanggal_input',
                        'batch.tanggal_cetak',
                        'batch.tanggal_upload',
                        'batch.tanggal_final',
                        'batch.file',
                        'batch.status',
                        'gaji.nip',
                        DB::raw("CAST(gaji.bulan AS SIGNED) AS bulan"),
                        'gaji.gj_pokok',
                        'gaji.gj_penyesuaian',
                        'gaji.tj_keluarga',
                        'gaji.tj_telepon',
                        'gaji.tj_jabatan',
                        'gaji.tj_teller',
                        'gaji.tj_perumahan',
                        'gaji.tj_kemahalan',
                        'gaji.tj_pelaksana',
                        'gaji.tj_kesejahteraan',
                        'gaji.tj_multilevel',
                        'gaji.tj_ti',
                        'gaji.tj_fungsional',
                        'gaji.tj_transport',
                        'gaji.tj_pulsa',
                        'gaji.tj_vitamin',
                        'gaji.uang_makan',
                        'gaji.dpp',
                        'gaji.kredit_koperasi',
                        'gaji.iuran_koperasi',
                        'gaji.kredit_pegawai',
                        'gaji.iuran_ik',
                        DB::raw("(gaji.gj_pokok + gaji.gj_penyesuaian + gaji.tj_keluarga + gaji.tj_telepon + gaji.tj_jabatan + gaji.tj_teller + gaji.tj_perumahan + gaji.tj_kemahalan + gaji.tj_pelaksana + gaji.tj_kesejahteraan + gaji.tj_multilevel + gaji.tj_ti + gaji.tj_transport + gaji.tj_pulsa + gaji.tj_vitamin + gaji.uang_makan) AS gaji"),
                        DB::raw("(gaji.gj_pokok + gaji.gj_penyesuaian + gaji.tj_keluarga + gaji.tj_jabatan + gaji.tj_teller + gaji.tj_perumahan + gaji.tj_telepon + gaji.tj_pelaksana + gaji.tj_kemahalan + gaji.tj_kesejahteraan) AS total_gaji"),
                        DB::raw("(gaji.uang_makan + gaji.tj_vitamin + gaji.tj_pulsa + gaji.tj_transport) AS total_tunjangan_lainnya"),
                    )
                    ->join('batch_gaji_per_bulan AS batch', 'batch.id', 'gaji.batch_id')
                    ->whereNull('batch.deleted_at')
                    ->where('gaji.nip', $nip)
                    ->where('gaji.tahun', $tahun)
                    ->when($bulan, function($query) use ($bulan) {
                        if ($bulan != 0) {
                            $query->where('gaji.bulan', $bulan);
                        }
                    })
                    ->orderBy('gaji.tahun')
                    ->orderBy('gaji.bulan')
                    ->get();

        if($data != null){
            foreach($data as $key => $value){
                $tj_khusus = 0;
                if ($value->tj_ti > 0) {
                    $tj_khusus += $value->tj_ti;
                }
                if ($value->tj_multilevel > 0) {
                    $tj_khusus += $value->tj_multilevel;
                }
                if ($value->tj_fungsional > 0) {
                    $tj_khusus += $value->tj_fungsional;
                }
                $value->tj_khusus = $tj_khusus;
                $value->total_gaji += $tj_khusus;
                $batch = DB::table('batch_gaji_per_bulan')->find($value->batch_id);
                if ($batch) {
                    $hitungan_penambah = DB::table('pemotong_pajak_tambahan')
                        ->where('mst_profil_kantor.kd_cabang', $batch->kd_entitas)
                        ->where('active', 1)
                        ->join('mst_profil_kantor', 'pemotong_pajak_tambahan.id_profil_kantor', 'mst_profil_kantor.id')
                        ->select('jkk', 'jht', 'jkm', 'kesehatan', 'kesehatan_batas_atas', 'kesehatan_batas_bawah', 'jp', 'total')
                        ->first();
                    $hitungan_pengurang = DB::table('pemotong_pajak_pengurangan')
                        ->where('kd_cabang', $batch->kd_entitas)
                        ->where('active', 1)
                        ->join('mst_profil_kantor', 'pemotong_pajak_pengurangan.id_profil_kantor', 'mst_profil_kantor.id')
                        ->select('dpp', 'jp', 'jp_jan_feb', 'jp_mar_des')
                        ->first();
                }

                if (!$hitungan_penambah && !$hitungan_pengurang) {
                    $persen_jkk = 0;
                    $persen_jht = 0;
                    $persen_jkm = 0;
                    $persen_kesehatan = 0;
                    $persen_jp_penambah = 0;
                    $persen_dpp = 0;
                    $persen_jp_pengurang = 0;
                    $batas_atas = 0;
                    $batas_bawah = 0;
                    $jp_jan_feb = 0;
                    $jp_mar_des = 0;
                }else{
                    $persen_jkk = $hitungan_penambah->jkk;
                    $persen_jht = $hitungan_penambah->jht;
                    $persen_jkm = $hitungan_penambah->jkm;
                    $persen_kesehatan = $hitungan_penambah->kesehatan;
                    $persen_jp_penambah = $hitungan_penambah->jp;
                    $persen_dpp = $hitungan_pengurang->dpp;
                    $persen_jp_pengurang = $hitungan_pengurang->jp;
                    $batas_atas = $hitungan_penambah->kesehatan_batas_atas;
                    $batas_bawah = $hitungan_penambah->kesehatan_batas_bawah;
                    $jp_jan_feb = $hitungan_pengurang->jp_jan_feb;
                    $jp_mar_des = $hitungan_pengurang->jp_mar_des;
                }
                
                $potongan = new \stdClass();
                $data_list = new \stdClass();
                // Get BPJS TK * Kesehatan
                $obj_gaji = $value;
                $gaji = $obj_gaji->gaji;
                $total_gaji = $obj_gaji->total_gaji;
                if($total_gaji > 0){
                    $jkk = 0;
                    $jht = 0;
                    $jkm = 0;
                    $jp_penambah = 0;
                    if(!$karyawan->tanggal_penonaktifan && $karyawan->kpj){
                        $jkk = round(($persen_jkk / 100) * $total_gaji);
                        $jht = round(($persen_jht / 100) * $total_gaji);
                        $jkm = round(($persen_jkm / 100) * $total_gaji);
                        $jp_penambah = round(($persen_jp_penambah / 100) * $total_gaji);
                    }

                    if($karyawan->jkn){
                        if($total_gaji > $batas_atas){
                            $bpjs_kesehatan = round($batas_atas * ($persen_kesehatan / 100));
                        } else if($total_gaji < $batas_bawah){
                            $bpjs_kesehatan = round($batas_bawah * ($persen_kesehatan / 100));
                        } else{
                            $bpjs_kesehatan = round($total_gaji * ($persen_kesehatan / 100));
                        }
                    }
                    $jamsostek = $jkk + $jht + $jkm + $bpjs_kesehatan + $jp_penambah;
                }

                // Get Potongan(JP1%, DPP 5%)
                $nominal_jp = ($obj_gaji->bulan > 2) ? $jp_mar_des : $jp_jan_feb;
                $gj_pokok = $obj_gaji->gj_pokok;
                $tj_keluarga = $obj_gaji->tj_keluarga;
                $tj_kesejahteraan = $obj_gaji->tj_kesejahteraan;

                // DPP (Pokok + Keluarga + Kesejahteraan 50%) * 5%
                $dpp = (($gj_pokok + $tj_keluarga) + ($tj_kesejahteraan * 0.5)) * ($persen_dpp / 100);
                // dd($gj_pokok ,$tj_keluarga,$tj_kesejahteraan,$persen_dpp, $dpp);
                if($gaji >= $nominal_jp){
                    $jp_1_persen = round($nominal_jp * ($persen_jp_pengurang / 100), 2);
                } else {
                    $jp_1_persen = round($gaji * ($persen_jp_pengurang / 100), 2);
                }
                $potongan->dpp = $dpp;
                $potongan->jp_1_persen = (int) $jp_1_persen;
                $potongan->kredit_koperasi = (int) $value->kredit_koperasi;
                $potongan->iuran_koperasi = (int) $value->iuran_koperasi;
                $potongan->kredit_pegawai = (int) $value->kredit_pegawai;
                $potongan->iuran_ik = (int) $value->iuran_ik;

                // Get BPJS TK
                if ($obj_gaji->bulan > 2) {
                    if ($total_gaji > $jp_mar_des) {
                        $bpjs_tk = $jp_mar_des * 1 / 100;
                    }
                    else {
                        $bpjs_tk = $total_gaji * 1 / 100;
                    }
                }
                else {
                    if ($total_gaji >= $jp_jan_feb) {
                        $bpjs_tk = $jp_jan_feb * 1 / 100;
                    }
                    else {
                        $bpjs_tk = $total_gaji * 1 / 100;
                    }
                }
                $bpjs_tk = round($bpjs_tk);

                // Penghasilan rutin
                $penghasilan_rutin = $gaji + $jamsostek;
                $value->jamsostek = (int) $jamsostek;
                $value->bpjs_tk = (int) $bpjs_tk;
                $value->bpjs_kesehatan = (int) $bpjs_kesehatan;
                $total_potongan = (int) $value->kredit_koperasi + (int) $value->iuran_koperasi + (int) $value->kredit_pegawai + (int) $value->iuran_ik + $dpp + $jp_1_persen;
                $potongan->total_potongan = (int) $total_potongan;
                $value->potongan = $potongan;
                // End GET POTONGAN
                
                // GET Gaji bersih
                $total_diterima = (int) $value->total_gaji - $total_potongan;
                $value->total_diterima = (int) $total_diterima;
                $data_list->total_diterima = (int) $total_diterima;
                $data_list->total_potongan = (int) $total_potongan;
                $data_list->total_gaji = (int) $value->total_gaji;
                $data_list->bulan = (int) $value->bulan;
                $data_list->tahun = (int) $tahun;
                $value->data_list = $data_list;
            }
        }
        return $data;
    }

    public function detail($id) {
        $data = DB::table('gaji_per_bulan AS gaji')
                    ->select(
                        'gaji.id',
                        'gaji.batch_id',
                        'batch.tanggal_input',
                        'batch.tanggal_cetak',
                        'batch.tanggal_upload',
                        'batch.tanggal_final',
                        'batch.file',
                        'batch.status',
                        'batch.kd_entitas as entitas_gaji',
                        'gaji.nip',
                        DB::raw("CAST(gaji.bulan AS SIGNED) AS bulan"),
                        'gaji.gj_pokok',
                        'gaji.gj_penyesuaian',
                        'gaji.tj_keluarga',
                        'gaji.tj_telepon',
                        'gaji.tj_jabatan',
                        'gaji.tj_teller',
                        'gaji.tj_perumahan',
                        'gaji.tj_kemahalan',
                        'gaji.tj_pelaksana',
                        'gaji.tj_kesejahteraan',
                        'gaji.tj_multilevel',
                        'gaji.tj_ti',
                        'gaji.tj_fungsional',
                        'gaji.tj_transport',
                        'gaji.tj_pulsa',
                        'gaji.tj_vitamin',
                        'gaji.uang_makan',
                        'gaji.dpp',
                        'gaji.kredit_koperasi',
                        'gaji.iuran_koperasi',
                        'gaji.kredit_pegawai',
                        'gaji.iuran_ik',
                        DB::raw("(gaji.gj_pokok + gaji.gj_penyesuaian + gaji.tj_keluarga + gaji.tj_telepon + gaji.tj_jabatan + gaji.tj_teller + gaji.tj_perumahan + gaji.tj_kemahalan + gaji.tj_pelaksana + gaji.tj_kesejahteraan + gaji.tj_multilevel + gaji.tj_ti + gaji.tj_transport + gaji.tj_pulsa + gaji.tj_vitamin + gaji.uang_makan) AS gaji"),
                        DB::raw("(gaji.gj_pokok + gaji.gj_penyesuaian + gaji.tj_keluarga + gaji.tj_jabatan + gaji.tj_teller + gaji.tj_perumahan + gaji.tj_telepon + gaji.tj_pelaksana + gaji.tj_kemahalan + gaji.tj_kesejahteraan) AS total_gaji"),
                        DB::raw("(gaji.uang_makan + gaji.tj_vitamin + gaji.tj_pulsa + gaji.tj_transport) AS total_tunjangan_lainnya"),
                    )
                    ->join('batch_gaji_per_bulan AS batch', 'batch.id', 'gaji.batch_id')
                    ->whereNull('batch.deleted_at')
                    ->where('gaji.id', $id)
                    ->orderBy('gaji.tahun')
                    ->orderBy('gaji.bulan')
                    ->first();

        if($data != null){
            $karyawan = DB::table('mst_karyawan')
                ->where('nip', $data->nip)
                ->first();

            $ttdKaryawan = new stdClass;
            if($data->entitas_gaji != '000'){
                $kantorCabang = DB::table('mst_cabang')
                    ->where('kd_cabang', $data->entitas_gaji)
                    ->first();
                $dataPincab = DB::table('mst_karyawan')
                    ->where('kd_jabatan', 'PC')
                    ->where('kd_entitas', $data->entitas_gaji)
                    ->first();
                $display_jabatan = 'Pemimpin Cabang';
                $kantor = $kantorCabang->nama_cabang;
            } else {
                $kantor = 'Surabaya';
                $pincab = new stdClass;
                $dataPincab = DB::table('mst_karyawan')
                    ->where('mst_karyawan.kd_jabatan','PIMDIV')
                    ->where('mst_karyawan.kd_entitas','UMUM')
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
                        'mst_bagian.nama_bagian'
                    )
                    ->orderBy('nip', 'desc')
                    ->first();

                $karyawanController = new KaryawanController;
                $pincab->entitas = $karyawanController->addEntity($dataPincab->kd_entitas);
                $prefix = match ($dataPincab->status_jabatan) {
                    'Penjabat' => 'Pj. ',
                    'Penjabat Sementara' => 'Pjs. ',
                    default => '',
                };
                
                $jabatan = '';
                if ($dataPincab->nama_jabatan) {
                    $jabatan = $dataPincab->nama_jabatan;
                } else {
                    $jabatan = 'undifined';
                }
                
                $ket = $dataPincab->ket_jabatan ? "({$dataPincab->ket_jabatan})" : '';
                
                if (isset($pincab->entitas->subDiv)) {
                    $entitas = $pincab->entitas->subDiv->nama_subdivisi;
                } elseif (isset($pincab->entitas->div)) {
                    $entitas = $pincab->entitas->div->nama_divisi;
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
                    $jabatan = $dataPincab?->nama_jabatan ? $dataPincab?->nama_jabatan : 'undifined';
                }
    
                $display_jabatan = $prefix . ' ' . $jabatan . ' ' . $entitas . ' ' . $dataPincab?->nama_bagian . ' ' . $ket;
            }

            $ttdKaryawan->kantor_cabang = $kantor;
            $ttdKaryawan->nama_karyawan = $dataPincab->nama_karyawan;
            $ttdKaryawan->jabatan = $display_jabatan;

            $tj_khusus = 0;
            if ($data->tj_ti > 0) {
                $tj_khusus += $data->tj_ti;
            }
            if ($data->tj_multilevel > 0) {
                $tj_khusus += $data->tj_multilevel;
            }
            if ($data->tj_fungsional > 0) {
                $tj_khusus += $data->tj_fungsional;
            }
            $data->tj_khusus = $tj_khusus;
            $data->total_gaji += $tj_khusus;
            $batch = DB::table('batch_gaji_per_bulan')->find($data->batch_id);
            if ($batch) {
                $hitungan_penambah = DB::table('pemotong_pajak_tambahan')
                    ->where('mst_profil_kantor.kd_cabang', $batch->kd_entitas)
                    ->where('active', 1)
                    ->join('mst_profil_kantor', 'pemotong_pajak_tambahan.id_profil_kantor', 'mst_profil_kantor.id')
                    ->select('jkk', 'jht', 'jkm', 'kesehatan', 'kesehatan_batas_atas', 'kesehatan_batas_bawah', 'jp', 'total')
                    ->first();
                $hitungan_pengurang = DB::table('pemotong_pajak_pengurangan')
                    ->where('kd_cabang', $batch->kd_entitas)
                    ->where('active', 1)
                    ->join('mst_profil_kantor', 'pemotong_pajak_pengurangan.id_profil_kantor', 'mst_profil_kantor.id')
                    ->select('dpp', 'jp', 'jp_jan_feb', 'jp_mar_des')
                    ->first();
            }

            if (!$hitungan_penambah && !$hitungan_pengurang) {
                $persen_jkk = 0;
                $persen_jht = 0;
                $persen_jkm = 0;
                $persen_kesehatan = 0;
                $persen_jp_penambah = 0;
                $persen_dpp = 0;
                $persen_jp_pengurang = 0;
                $batas_atas = 0;
                $batas_bawah = 0;
                $jp_jan_feb = 0;
                $jp_mar_des = 0;
            }else{
                $persen_jkk = $hitungan_penambah->jkk;
                $persen_jht = $hitungan_penambah->jht;
                $persen_jkm = $hitungan_penambah->jkm;
                $persen_kesehatan = $hitungan_penambah->kesehatan;
                $persen_jp_penambah = $hitungan_penambah->jp;
                $persen_dpp = $hitungan_pengurang->dpp;
                $persen_jp_pengurang = $hitungan_pengurang->jp;
                $batas_atas = $hitungan_penambah->kesehatan_batas_atas;
                $batas_bawah = $hitungan_penambah->kesehatan_batas_bawah;
                $jp_jan_feb = $hitungan_pengurang->jp_jan_feb;
                $jp_mar_des = $hitungan_pengurang->jp_mar_des;
            }
            
            $potongan = new \stdClass();
            $data_list = new \stdClass();
            // Get BPJS TK * Kesehatan
            $obj_gaji = $data;
            $gaji = $obj_gaji->gaji;
            $total_gaji = $obj_gaji->total_gaji;
            if($total_gaji > 0){
                $jkk = 0;
                $jht = 0;
                $jkm = 0;
                $jp_penambah = 0;
                if(!$karyawan->tanggal_penonaktifan && $karyawan->kpj){
                    $jkk = round(($persen_jkk / 100) * $total_gaji);
                    $jht = round(($persen_jht / 100) * $total_gaji);
                    $jkm = round(($persen_jkm / 100) * $total_gaji);
                    $jp_penambah = round(($persen_jp_penambah / 100) * $total_gaji);
                }

                if($karyawan->jkn){
                    if($total_gaji > $batas_atas){
                        $bpjs_kesehatan = round($batas_atas * ($persen_kesehatan / 100));
                    } else if($total_gaji < $batas_bawah){
                        $bpjs_kesehatan = round($batas_bawah * ($persen_kesehatan / 100));
                    } else{
                        $bpjs_kesehatan = round($total_gaji * ($persen_kesehatan / 100));
                    }
                }
                $jamsostek = $jkk + $jht + $jkm + $bpjs_kesehatan + $jp_penambah;
            }

            // Get Potongan(JP1%, DPP 5%)
            $nominal_jp = ($obj_gaji->bulan > 2) ? $jp_mar_des : $jp_jan_feb;
            $gj_pokok = $obj_gaji->gj_pokok;
            $tj_keluarga = $obj_gaji->tj_keluarga;
            $tj_kesejahteraan = $obj_gaji->tj_kesejahteraan;

            // DPP (Pokok + Keluarga + Kesejahteraan 50%) * 5%
            $dpp = (($gj_pokok + $tj_keluarga) + ($tj_kesejahteraan * 0.5)) * ($persen_dpp / 100);
            // dd($gj_pokok ,$tj_keluarga,$tj_kesejahteraan,$persen_dpp, $dpp);
            if($gaji >= $nominal_jp){
                $jp_1_persen = round($nominal_jp * ($persen_jp_pengurang / 100), 2);
            } else {
                $jp_1_persen = round($gaji * ($persen_jp_pengurang / 100), 2);
            }
            $potongan->dpp = (int) $dpp;
            $potongan->jp_1_persen = (int) $jp_1_persen;
            $potongan->kredit_koperasi = (int) $data->kredit_koperasi;
            $potongan->iuran_koperasi = (int) $data->iuran_koperasi;
            $potongan->kredit_pegawai = (int) $data->kredit_pegawai;
            $potongan->iuran_ik = (int) $data->iuran_ik;

            // Get BPJS TK
            if ($obj_gaji->bulan > 2) {
                if ($total_gaji > $jp_mar_des) {
                    $bpjs_tk = $jp_mar_des * 1 / 100;
                }
                else {
                    $bpjs_tk = $total_gaji * 1 / 100;
                }
            }
            else {
                if ($total_gaji >= $jp_jan_feb) {
                    $bpjs_tk = $jp_jan_feb * 1 / 100;
                }
                else {
                    $bpjs_tk = $total_gaji * 1 / 100;
                }
            }
            $bpjs_tk = round($bpjs_tk);

            // Penghasilan rutin
            $penghasilan_rutin = $gaji + $jamsostek;
            $data->jamsostek = (int) $jamsostek;
            $data->bpjs_tk = (int) $bpjs_tk;
            $data->bpjs_kesehatan = (int) $bpjs_kesehatan;
            $total_potongan = (int) $data->kredit_koperasi + (int) $data->iuran_koperasi + (int) $data->kredit_pegawai + (int) $data->iuran_ik + $dpp + $jp_1_persen;
            $potongan->total_potongan = (int) $total_potongan;
            $data->potongan = $potongan;
            // End GET POTONGAN
            
            // GET Gaji bersih
            $total_diterima = (int) $data->total_gaji - $total_potongan;
            $data->total_diterima = (int) $total_diterima;
            $data_list->total_diterima = (int) $total_diterima;
            $data_list->total_potongan = (int) $total_potongan;
            $data_list->total_gaji = (int) $data->total_gaji;
            $data->data_list = $data_list;
            $data->terbilang = strtoupper($this->terbilang($total_diterima));
            $data->ttd_karyawan = $ttdKaryawan;
        }
        return $data;
    }

    private function penyebut($nilai)
    {
        $nilai = abs($nilai);
        $huruf = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
        $temp = '';
        if ($nilai < 12) {
            $temp = ' ' . $huruf[$nilai];
        } elseif ($nilai < 20) {
            $temp = $this->penyebut($nilai - 10) . ' belas';
        } elseif ($nilai < 100) {
            $temp = $this->penyebut($nilai / 10) . ' puluh' . $this->penyebut($nilai % 10);
        } elseif ($nilai < 200) {
            $temp = ' seratus' . $this->penyebut($nilai - 100);
        } elseif ($nilai < 1000) {
            $temp = $this->penyebut($nilai / 100) . ' ratus' . $this->penyebut($nilai % 100);
        } elseif ($nilai < 2000) {
            $temp = ' seribu' . $this->penyebut($nilai - 1000);
        } elseif ($nilai < 1000000) {
            $temp = $this->penyebut($nilai / 1000) . ' ribu' . $this->penyebut($nilai % 1000);
        } elseif ($nilai < 1000000000) {
            $temp = $this->penyebut($nilai / 1000000) . ' juta' . $this->penyebut($nilai % 1000000);
        } elseif ($nilai < 1000000000000) {
            $temp = $this->penyebut($nilai / 1000000000) . ' milyar' . $this->penyebut(fmod($nilai, 1000000000));
        } elseif ($nilai < 1000000000000000) {
            $temp = $this->penyebut($nilai / 1000000000000) . ' trilyun' . $this->penyebut(fmod($nilai, 1000000000000));
        }
        return $temp;
    }

    private function terbilang($nilai)
    {
        if ($nilai < 0) {
            $hasil = 'minus ' . trim($this->penyebut($nilai));
        } else {
            $hasil = trim($this->penyebut($nilai));
        }
        return $hasil;
    }
}