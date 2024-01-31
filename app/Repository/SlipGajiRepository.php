<?php

namespace App\Repository;

use Illuminate\Support\Facades\DB;

class SlipGajiRepository
{
    public function list($nip, $tahun, int $bulan = 0) {
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