<?php

namespace App\Repository;

use Illuminate\Support\Facades\DB;

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
                $potongan->jp_1_persen = $jp_1_persen;
                $potongan->kredit_koperasi = $value->kredit_koperasi;
                $potongan->iuran_koperasi = $value->iuran_koperasi;
                $potongan->kredit_pegawai = $value->kredit_pegawai;
                $potongan->iuran_ik = $value->iuran_ik;

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
                $value->jamsostek = $jamsostek;
                $value->bpjs_tk = $bpjs_tk;
                $value->bpjs_kesehatan = $bpjs_kesehatan;
                $total_potongan = (int) $value->kredit_koperasi + (int) $value->iuran_koperasi + (int) $value->kredit_pegawai + (int) $value->iuran_ik + $dpp + $jp_1_persen;
                $potongan->total_potongan = $total_potongan;
                $value->potongan = $potongan;
                // End GET POTONGAN
                
                // GET Gaji bersih
                $total_diterima = $value->total_gaji - $total_potongan;
                $value->total_diterima = $total_diterima;
                $data_list->total_diterima = $total_diterima;
                $data_list->total_potongan = $total_potongan;
                $data_list->total_gaji = $value->total_gaji;
                $data_list->bulan = $value->bulan;
                $data_list->tahun = $tahun;
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

        return $data;
    }
}