<?php

namespace App\Repository;

use App\Http\Controllers\KaryawanController;
use Illuminate\Support\Facades\DB;
use stdClass;

class DashboardRepository
{
    public function getTotalGaji() {
        $returnData = new stdClass;
        $total = 0;

        // Get perkiraan gaji 
        $karyawan = DB::table('mst_karyawan AS m')
            ->select(
                'gj_pokok',
                'gj_penyesuaian',
                DB::raw("(SELECT SUM(nominal) FROM tunjangan_karyawan JOIN mst_tunjangan ON mst_tunjangan.id = tunjangan_karyawan.id_tunjangan WHERE tunjangan_karyawan.nip = m.nip AND mst_tunjangan.kategori = 'teratur') AS total_tunjangan")
            )
            ->whereNull('tanggal_penonaktifan')
            ->get();
        foreach($karyawan as $key => $value) {
            $total += $value->gj_pokok;
            $total += $value->gj_penyesuaian;
            $total += $value->total_tunjangan;
        }
        // End get perkiraan gaji

        // Get karyawan masuk
        $karyawanMasuk = DB::table('mst_karyawan')
            ->whereRaw("MONTH(tgl_mulai) = MONTH(NOW())")
            ->count();
        // End get karyawan masuk

        // Get karyawan keluar
        $karyawanKeluar = DB::table('mst_karyawan')
            ->whereRaw("MONTH(tanggal_penonaktifan) = MONTH(NOW())")
            ->count();
        // End get karyawan keluar

        // Get karyawan pensiun
        $karyawanPensiun = DB::table('mst_karyawan')
            ->whereRaw("MONTH(tanggal_penonaktifan) = MONTH(NOW())")
            ->where('kategori_penonaktifan', 'pensiun')
            ->count();
        // End get karyawan pensiun

        $returnData->total_gaji =  number_format($total, 0, ',', '.');
        $returnData->total_karyawan = count($karyawan);
        $returnData->karyawan_masuk = $karyawanMasuk;
        $returnData->karyawan_keluar = $karyawanKeluar;
        $returnData->karyawan_pensiun = $karyawanPensiun;

        return $returnData;
    }
}