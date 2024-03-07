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

    public function getRincianGaji() {
        $returnData = [];

        $dataPusat = DB::table('mst_karyawan AS m')
            ->select(
                'gj_pokok',
                'gj_penyesuaian',
                DB::raw("(SELECT SUM(nominal) FROM tunjangan_karyawan JOIN mst_tunjangan ON mst_tunjangan.id = tunjangan_karyawan.id_tunjangan WHERE tunjangan_karyawan.nip = m.nip AND mst_tunjangan.kategori = 'teratur') AS total_tunjangan")
            )
            ->whereRaw("m.tanggal_penonaktifan IS NULL and (m.kd_entitas NOT IN (SELECT kd_cabang FROM mst_cabang) OR m.kd_entitas IS NULL)")
            ->get();
        $totalGajiPusat = 0;
        foreach($dataPusat as $value) {
            $totalGajiPusat += intval($value->gj_pokok) + intval($value->gj_penyesuaian) + intval($value->total_tunjangan);
        } 
        array_push($returnData, [
            'kd_cabang' => null,
            'kantor' => 'Pusat',
            'total_gaji' => $totalGajiPusat,
        ]);

        $kd_cabang = DB::table('mst_cabang')
            ->select('kd_cabang')
            ->where('kd_cabang', '!=', '000')
            ->pluck('kd_cabang')
            ->toArray();
        foreach($kd_cabang as $value) {
            $dataCabang = DB::table('mst_karyawan AS m')
                ->select(
                    'c.kd_cabang',
                    'c.nama_cabang',
                    'm.gj_pokok',
                    'm.gj_penyesuaian',
                    DB::raw("(SELECT SUM(nominal) FROM tunjangan_karyawan JOIN mst_tunjangan ON mst_tunjangan.id = tunjangan_karyawan.id_tunjangan WHERE tunjangan_karyawan.nip = m.nip AND mst_tunjangan.kategori = 'teratur') AS total_tunjangan")
                )
                ->join('mst_cabang as c', 'c.kd_cabang', 'm.kd_entitas')
                ->whereNull('tanggal_penonaktifan')
                ->where('kd_entitas', $value)
                ->get();
            $totalGaji = 0;
            $kantor = '';
            foreach($dataCabang as $itemGaji) {
                $kantor = $itemGaji->nama_cabang;
                $totalGaji += intval($itemGaji->gj_pokok) + intval($itemGaji->gj_penyesuaian) + intval($itemGaji->total_tunjangan);
            }
            array_push($returnData, [
                'kd_cabang' => $value,
                'kantor' => $kantor,
                'total_gaji' => $totalGaji
            ]);
        }

        return $returnData;
    }
}