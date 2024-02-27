<?php

namespace App\Repository;

use App\Http\Controllers\KaryawanController;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use stdClass;

class LaporanRepository
{
    public function listMutasi(Request $request)
    {
        $search = $request->get('search') ?? null;
        $limit = $request->get('limit') ?? 10;
        $tanggal_awal = $request->get('tanggal_awal');
        $tanggal_akhir = $request->get('tanggal_akhir');

        $returnData = [];
        $data = DB::table('demosi_promosi_pangkat')
            ->where('keterangan', 'Mutasi')
            ->select(
                'demosi_promosi_pangkat.*',
                'karyawan.*',
                'newPos.nama_jabatan as jabatan_baru',
                'oldPos.nama_jabatan as jabatan_lama',
                DB::raw("
                    IF((cabang_lama.nama_cabang != 'NULL' AND IFNULL(div_lama.nama_divisi, '-') = '-' AND IFNULL(sub_div_lama.nama_subdivisi, '-') = '-'),
                        CONCAT('Cab.', cabang_lama.nama_cabang),
                        IF((IFNULL(div_lama.nama_divisi, '-') != '-' AND IFNULL(sub_div_lama.nama_subdivisi, '-') = '-'), CONCAT(div_lama.nama_divisi, ' (Pusat)'),
                            IF(IFNULL(sub_div_lama.nama_subdivisi, '-') != '-', CONCAT(sub_div_lama.nama_subdivisi, ' (Pusat)'), 
                            IF(IFNULL(bagian_lama.nama_bagian, '-') != '-', CONCAT(bagian_lama.nama_bagian, ' (Pusat)'), '-'))
                        )
                    ) AS kantor_lama
                "),
                DB::raw("
                    IF((cabang_baru.nama_cabang != 'NULL' AND IFNULL(div_baru.nama_divisi, '-') = '-' AND IFNULL(sub_div_baru.nama_subdivisi, '-') = '-'),
                        CONCAT('Cab.', cabang_baru.nama_cabang),
                        IF((IFNULL(div_baru.nama_divisi, '-') != '-' AND IFNULL(sub_div_baru.nama_subdivisi, '-') = '-'), CONCAT(div_baru.nama_divisi, ' (Pusat)'),
                            IF(IFNULL(sub_div_baru.nama_subdivisi, '-') != '-', CONCAT(sub_div_baru.nama_subdivisi, ' (Pusat)'), 
                            IF(IFNULL(bagian_baru.nama_bagian, '-') != '-', CONCAT(bagian_baru.nama_bagian, ' (Pusat)'), '-'))
                        )
                    ) AS kantor_baru
                ")
            )
            ->join('mst_karyawan as karyawan', function ($join) {
                $join->on('karyawan.nip', 'demosi_promosi_pangkat.nip')
                    ->orOn('karyawan.nip', 'demosi_promosi_pangkat.nip_baru');
            })
            ->join('mst_jabatan as newPos', 'newPos.kd_jabatan', 'demosi_promosi_pangkat.kd_jabatan_baru')
            ->join('mst_jabatan as oldPos', 'oldPos.kd_jabatan', 'demosi_promosi_pangkat.kd_jabatan_lama')
            // Kantor lama
            ->leftJoin('mst_divisi as div_lama', 'div_lama.kd_divisi', 'demosi_promosi_pangkat.kd_entitas_lama')
            ->leftJoin('mst_sub_divisi as sub_div_lama', 'sub_div_lama.kd_subdiv', 'demosi_promosi_pangkat.kd_entitas_lama')
            ->leftJoin('mst_cabang as cabang_lama', 'cabang_lama.kd_cabang', 'demosi_promosi_pangkat.kd_entitas_lama')
            ->leftJoin('mst_bagian as bagian_lama', 'bagian_lama.kd_bagian', 'demosi_promosi_pangkat.kd_bagian_lama')
            // Kantor Baru
            ->leftJoin('mst_divisi as div_baru', 'div_baru.kd_divisi', 'demosi_promosi_pangkat.kd_entitas_baru')
            ->leftJoin('mst_sub_divisi as sub_div_baru', 'sub_div_baru.kd_subdiv', 'demosi_promosi_pangkat.kd_entitas_baru')
            ->leftJoin('mst_cabang as cabang_baru', 'cabang_baru.kd_cabang', 'demosi_promosi_pangkat.kd_entitas_baru')
            ->leftJoin('mst_bagian as bagian_baru', 'bagian_baru.kd_bagian', 'demosi_promosi_pangkat.kd_bagian')
            ->when($search, function ($query) use ($search) {
                $query->where('karyawan.nip', 'LIKE', "%$search%")
                    ->orWhere('karyawan.nama_karyawan', 'LIKE', "%$search%")
                    ->orWhereDate('demosi_promosi_pangkat.tanggal_pengesahan', $search)
                    ->orWhere('newPos.nama_jabatan', 'LIKE', "%$search%")
                    ->orWhere('oldPos.nama_jabatan', 'LIKE', "%$search%")
                    ->orWhere('div_lama.nama_divisi', 'LIKE', "%$search%")
                    ->orWhere('sub_div_lama.nama_subdivisi', 'LIKE', "%$search%")
                    ->orWhere('cabang_lama.nama_cabang', 'LIKE', "%$search%")
                    ->orWhere('div_baru.nama_divisi', 'LIKE', "%$search%")
                    ->orWhere('sub_div_baru.nama_subdivisi', 'LIKE', "%$search%")
                    ->orWhere('cabang_baru.nama_cabang', 'LIKE', "%$search%")
                    ->orWhere('demosi_promosi_pangkat.bukti_sk', 'LIKE', "%$search%");
            })
            ->whereBetween('tanggal_pengesahan', [$tanggal_awal, $tanggal_akhir])
            ->orderBy('tanggal_pengesahan', 'desc')
            ->paginate($limit);
        foreach ($data as $key => $value) {
            $karyawan = new stdClass;
            $karyawan->nip = $value->nip;
            $karyawan->nama_karyawan = $value->nama_karyawan;
            $karyawan->tanggal_pengesahan = date('d F Y', strtotime($value->tanggal_pengesahan));
            $karyawan->jabatan_lama = ($value->status_jabatan_lama != null ? $value->status_jabatan_lama . ' - ' : ' ') . ($value->jabatan_lama);
            $karyawan->jabatan_baru = ($value->status_jabatan_baru != null ? $value->status_jabatan_baru . ' - ' : ' ') . ($value->jabatan_baru);
            $karyawan->kantor_lama = $value->kantor_lama;
            $karyawan->kantor_baru = $value->kantor_baru;
            $karyawan->bukti_sk = $value->bukti_sk;
            array_push($returnData, $karyawan);
        }
        return $returnData;
    }

    public function listPromosi(Request $request)
    {
        $search = $request->get('search') ?? null;
        $limit = $request->get('limit') ?? 10;
        $tanggal_awal = $request->get('tanggal_awal');
        $tanggal_akhir = $request->get('tanggal_akhir');

        $returnData = [];
        $data = DB::table('demosi_promosi_pangkat')
            ->where('keterangan', 'Promosi')
            ->select(
                'demosi_promosi_pangkat.*',
                'karyawan.*',
                'newPos.nama_jabatan as jabatan_baru',
                'oldPos.nama_jabatan as jabatan_lama',
                DB::raw("
                    IF((cabang_lama.nama_cabang != 'NULL' AND IFNULL(div_lama.nama_divisi, '-') = '-' AND IFNULL(sub_div_lama.nama_subdivisi, '-') = '-'),
                        CONCAT('Cab.', cabang_lama.nama_cabang),
                        IF((IFNULL(div_lama.nama_divisi, '-') != '-' AND IFNULL(sub_div_lama.nama_subdivisi, '-') = '-'), CONCAT(div_lama.nama_divisi, ' (Pusat)'),
                            IF(IFNULL(sub_div_lama.nama_subdivisi, '-') != '-', CONCAT(sub_div_lama.nama_subdivisi, ' (Pusat)'), 
                            IF(IFNULL(bagian_lama.nama_bagian, '-') != '-', CONCAT(bagian_lama.nama_bagian, ' (Pusat)'), '-'))
                        )
                    ) AS kantor_lama
                "),
                DB::raw("
                    IF((cabang_baru.nama_cabang != 'NULL' AND IFNULL(div_baru.nama_divisi, '-') = '-' AND IFNULL(sub_div_baru.nama_subdivisi, '-') = '-'),
                        CONCAT('Cab.', cabang_baru.nama_cabang),
                        IF((IFNULL(div_baru.nama_divisi, '-') != '-' AND IFNULL(sub_div_baru.nama_subdivisi, '-') = '-'), CONCAT(div_baru.nama_divisi, ' (Pusat)'),
                            IF(IFNULL(sub_div_baru.nama_subdivisi, '-') != '-', CONCAT(sub_div_baru.nama_subdivisi, ' (Pusat)'), 
                            IF(IFNULL(bagian_baru.nama_bagian, '-') != '-', CONCAT(bagian_baru.nama_bagian, ' (Pusat)'), '-'))
                        )
                    ) AS kantor_baru
                ")
            )
            ->join('mst_karyawan as karyawan', function ($join) {
                $join->on('karyawan.nip', 'demosi_promosi_pangkat.nip')
                    ->orOn('karyawan.nip', 'demosi_promosi_pangkat.nip_baru');
            })
            ->join('mst_jabatan as newPos', 'newPos.kd_jabatan', 'demosi_promosi_pangkat.kd_jabatan_baru')
            ->join('mst_jabatan as oldPos', 'oldPos.kd_jabatan', 'demosi_promosi_pangkat.kd_jabatan_lama')
            // Kantor lama
            ->leftJoin('mst_divisi as div_lama', 'div_lama.kd_divisi', 'demosi_promosi_pangkat.kd_entitas_lama')
            ->leftJoin('mst_sub_divisi as sub_div_lama', 'sub_div_lama.kd_subdiv', 'demosi_promosi_pangkat.kd_entitas_lama')
            ->leftJoin('mst_cabang as cabang_lama', 'cabang_lama.kd_cabang', 'demosi_promosi_pangkat.kd_entitas_lama')
            ->leftJoin('mst_bagian as bagian_lama', 'bagian_lama.kd_bagian', 'demosi_promosi_pangkat.kd_bagian_lama')
            // Kantor Baru
            ->leftJoin('mst_divisi as div_baru', 'div_baru.kd_divisi', 'demosi_promosi_pangkat.kd_entitas_baru')
            ->leftJoin('mst_sub_divisi as sub_div_baru', 'sub_div_baru.kd_subdiv', 'demosi_promosi_pangkat.kd_entitas_baru')
            ->leftJoin('mst_cabang as cabang_baru', 'cabang_baru.kd_cabang', 'demosi_promosi_pangkat.kd_entitas_baru')
            ->leftJoin('mst_bagian as bagian_baru', 'bagian_baru.kd_bagian', 'demosi_promosi_pangkat.kd_bagian')
            ->when($search, function ($query) use ($search) {
                $query->where('karyawan.nip', 'LIKE', "%$search%")
                    ->orWhere('karyawan.nama_karyawan', 'LIKE', "%$search%")
                    ->orWhereDate('demosi_promosi_pangkat.tanggal_pengesahan', $search)
                    ->orWhere('newPos.nama_jabatan', 'LIKE', "%$search%")
                    ->orWhere('oldPos.nama_jabatan', 'LIKE', "%$search%")
                    ->orWhere('div_lama.nama_divisi', 'LIKE', "%$search%")
                    ->orWhere('sub_div_lama.nama_subdivisi', 'LIKE', "%$search%")
                    ->orWhere('cabang_lama.nama_cabang', 'LIKE', "%$search%")
                    ->orWhere('div_baru.nama_divisi', 'LIKE', "%$search%")
                    ->orWhere('sub_div_baru.nama_subdivisi', 'LIKE', "%$search%")
                    ->orWhere('cabang_baru.nama_cabang', 'LIKE', "%$search%")
                    ->orWhere('demosi_promosi_pangkat.bukti_sk', 'LIKE', "%$search%");
            })
            ->whereBetween('tanggal_pengesahan', [$tanggal_awal, $tanggal_akhir])
            ->orderBy('tanggal_pengesahan', 'desc')
            ->paginate($limit);

        foreach ($data as $key => $value) {
            $karyawan = new stdClass;
            $karyawan->nip = $value->nip;
            $karyawan->nama_karyawan = $value->nama_karyawan;
            $karyawan->tanggal_pengesahan = date('d F Y', strtotime($value->tanggal_pengesahan));
            $karyawan->jabatan_lama = ($value->status_jabatan_lama != null ? $value->status_jabatan_lama . ' - ' : ' ') . ($value->jabatan_lama);
            $karyawan->jabatan_baru = ($value->status_jabatan_baru != null ? $value->status_jabatan_baru . ' - ' : ' ') . ($value->jabatan_baru);
            $karyawan->kantor_lama = $value->kantor_lama;
            $karyawan->kantor_baru = $value->kantor_baru;
            $karyawan->bukti_sk = $value->bukti_sk;
            array_push($returnData, $karyawan);
        }
        return $returnData;
    }

    public function listDemosi(Request $request)
    {
        $search = $request->get('search') ?? null;
        $limit = $request->get('limit') ?? 10;
        $tanggal_awal = $request->get('tanggal_awal');
        $tanggal_akhir = $request->get('tanggal_akhir');
        
        $returnData = [];
        $data = DB::table('demosi_promosi_pangkat')
            ->where('keterangan', 'Demosi')
            ->select(
                'demosi_promosi_pangkat.*',
                'karyawan.*',
                'newPos.nama_jabatan as jabatan_baru',
                'oldPos.nama_jabatan as jabatan_lama',
                DB::raw("
                    IF((cabang_lama.nama_cabang != 'NULL' AND IFNULL(div_lama.nama_divisi, '-') = '-' AND IFNULL(sub_div_lama.nama_subdivisi, '-') = '-'),
                        CONCAT('Cab.', cabang_lama.nama_cabang),
                        IF((IFNULL(div_lama.nama_divisi, '-') != '-' AND IFNULL(sub_div_lama.nama_subdivisi, '-') = '-'), CONCAT(div_lama.nama_divisi, ' (Pusat)'),
                            IF(IFNULL(sub_div_lama.nama_subdivisi, '-') != '-', CONCAT(sub_div_lama.nama_subdivisi, ' (Pusat)'), 
                            IF(IFNULL(bagian_lama.nama_bagian, '-') != '-', CONCAT(bagian_lama.nama_bagian, ' (Pusat)'), '-'))
                        )
                    ) AS kantor_lama
                "),
                DB::raw("
                    IF((cabang_baru.nama_cabang != 'NULL' AND IFNULL(div_baru.nama_divisi, '-') = '-' AND IFNULL(sub_div_baru.nama_subdivisi, '-') = '-'),
                        CONCAT('Cab.', cabang_baru.nama_cabang),
                        IF((IFNULL(div_baru.nama_divisi, '-') != '-' AND IFNULL(sub_div_baru.nama_subdivisi, '-') = '-'), CONCAT(div_baru.nama_divisi, ' (Pusat)'),
                            IF(IFNULL(sub_div_baru.nama_subdivisi, '-') != '-', CONCAT(sub_div_baru.nama_subdivisi, ' (Pusat)'), 
                            IF(IFNULL(bagian_baru.nama_bagian, '-') != '-', CONCAT(bagian_baru.nama_bagian, ' (Pusat)'), '-'))
                        )
                    ) AS kantor_baru
                ")
            )
            ->join('mst_karyawan as karyawan', function ($join) {
                $join->on('karyawan.nip', 'demosi_promosi_pangkat.nip')
                    ->orOn('karyawan.nip', 'demosi_promosi_pangkat.nip_baru');
            })
            ->join('mst_jabatan as newPos', 'newPos.kd_jabatan', 'demosi_promosi_pangkat.kd_jabatan_baru')
            ->join('mst_jabatan as oldPos', 'oldPos.kd_jabatan', 'demosi_promosi_pangkat.kd_jabatan_lama')
            // Kantor lama
            ->leftJoin('mst_divisi as div_lama', 'div_lama.kd_divisi', 'demosi_promosi_pangkat.kd_entitas_lama')
            ->leftJoin('mst_sub_divisi as sub_div_lama', 'sub_div_lama.kd_subdiv', 'demosi_promosi_pangkat.kd_entitas_lama')
            ->leftJoin('mst_cabang as cabang_lama', 'cabang_lama.kd_cabang', 'demosi_promosi_pangkat.kd_entitas_lama')
            ->leftJoin('mst_bagian as bagian_lama', 'bagian_lama.kd_bagian', 'demosi_promosi_pangkat.kd_bagian_lama')
            // Kantor Baru
            ->leftJoin('mst_divisi as div_baru', 'div_baru.kd_divisi', 'demosi_promosi_pangkat.kd_entitas_baru')
            ->leftJoin('mst_sub_divisi as sub_div_baru', 'sub_div_baru.kd_subdiv', 'demosi_promosi_pangkat.kd_entitas_baru')
            ->leftJoin('mst_cabang as cabang_baru', 'cabang_baru.kd_cabang', 'demosi_promosi_pangkat.kd_entitas_baru')
            ->leftJoin('mst_bagian as bagian_baru', 'bagian_baru.kd_bagian', 'demosi_promosi_pangkat.kd_bagian')
            ->when($search, function ($query) use ($search) {
                $query->where('karyawan.nip', 'LIKE', "%$search%")
                    ->orWhere('karyawan.nama_karyawan', 'LIKE', "%$search%")
                    ->orWhereDate('demosi_promosi_pangkat.tanggal_pengesahan', $search)
                    ->orWhere('newPos.nama_jabatan', 'LIKE', "%$search%")
                    ->orWhere('oldPos.nama_jabatan', 'LIKE', "%$search%")
                    ->orWhere('div_lama.nama_divisi', 'LIKE', "%$search%")
                    ->orWhere('sub_div_lama.nama_subdivisi', 'LIKE', "%$search%")
                    ->orWhere('cabang_lama.nama_cabang', 'LIKE', "%$search%")
                    ->orWhere('div_baru.nama_divisi', 'LIKE', "%$search%")
                    ->orWhere('sub_div_baru.nama_subdivisi', 'LIKE', "%$search%")
                    ->orWhere('cabang_baru.nama_cabang', 'LIKE', "%$search%")
                    ->orWhere('demosi_promosi_pangkat.bukti_sk', 'LIKE', "%$search%");
            })
            ->whereBetween('tanggal_pengesahan', [$tanggal_awal, $tanggal_akhir])
            ->orderBy('tanggal_pengesahan', 'desc')
            ->paginate($limit);

        foreach ($data as $key => $value) {
            $karyawan = new stdClass;
            $karyawan->nip = $value->nip;
            $karyawan->nama_karyawan = $value->nama_karyawan;
            $karyawan->tanggal_pengesahan = date('d F Y', strtotime($value->tanggal_pengesahan));
            $karyawan->jabatan_lama = ($value->status_jabatan_lama != null ? $value->status_jabatan_lama . ' - ' : ' ') . ($value->jabatan_lama);
            $karyawan->jabatan_baru = ($value->status_jabatan_baru != null ? $value->status_jabatan_baru . ' - ' : ' ') . ($value->jabatan_baru);
            $karyawan->kantor_lama = $value->kantor_lama;
            $karyawan->kantor_baru = $value->kantor_baru;
            $karyawan->bukti_sk = $value->bukti_sk;
            array_push($returnData, $karyawan);
        }
        return $returnData;
    }

    public function listJamsostek(Request $request) {
        $orderRaw = "
            CASE 
            WHEN karyawan.kd_jabatan='DIRUT' THEN 1
            WHEN karyawan.kd_jabatan='DIRUMK' THEN 2
            WHEN karyawan.kd_jabatan='DIRPEM' THEN 3
            WHEN karyawan.kd_jabatan='DIRHAN' THEN 4
            WHEN karyawan.kd_jabatan='KOMU' THEN 5
            WHEN karyawan.kd_jabatan='KOM' THEN 7
            WHEN karyawan.kd_jabatan='STAD' THEN 8
            WHEN karyawan.kd_jabatan='PIMDIV' THEN 9
            WHEN karyawan.kd_jabatan='PSD' THEN 10
            WHEN karyawan.kd_jabatan='PC' THEN 11
            WHEN karyawan.kd_jabatan='PBP' THEN 12
            WHEN karyawan.kd_jabatan='PBO' THEN 13
            WHEN karyawan.kd_jabatan='PEN' THEN 14
            WHEN karyawan.kd_jabatan='ST' THEN 15
            WHEN karyawan.kd_jabatan='NST' THEN 16
            WHEN karyawan.kd_jabatan='IKJP' THEN 17 END ASC
        ";
        $kategori = strtolower($request->get('kategori'));
        $bulan = (int) $request->get('bulan');
        $tahun = (int) $request->get('tahun');

        if ($kategori == 'keseluruhan')  {
            $dataGaji = DB::table('gaji_per_bulan AS gaji')
                ->join('mst_karyawan as karyawan', 'gaji.nip', 'karyawan.nip')
                ->join('batch_gaji_per_bulan as batch', 'batch.id', 'gaji.batch_id')
                ->whereNull('batch.deleted_at')
                ->whereNull('tanggal_penonaktifan')
                ->select(
                    // 'karyawan.nip',
                    // 'karyawan.nama_karyawan',
                    // DB::raw("(gaji.gj_pokok + gaji.gj_penyesuaian + gaji.tj_keluarga + gaji.tj_telepon + gaji.tj_jabatan + gaji.tj_teller + gaji.tj_perumahan + gaji.tj_kemahalan + gaji.tj_pelaksana + gaji.tj_kesejahteraan + gaji.tj_multilevel + gaji.tj_ti) AS total_gaji"),
                    DB::raw("COUNT(karyawan.nip) AS total_karyawan"),
                    DB::raw("IF(karyawan.kd_entitas NOT IN(select kd_cabang from mst_cabang) OR karyawan.kd_entitas IS NULL, '000', karyawan.kd_entitas) AS kd_kantor"),
                    DB::raw("SUM(
                        IF(
                            (gaji.gj_pokok + gaji.gj_penyesuaian + gaji.tj_keluarga + gaji.tj_telepon + gaji.tj_jabatan + gaji.tj_teller + gaji.tj_perumahan + gaji.tj_kemahalan + gaji.tj_pelaksana + gaji.tj_kesejahteraan + gaji.tj_multilevel + gaji.tj_ti) > 9077600, 
                            0.001 * (gaji.gj_pokok + gaji.gj_penyesuaian + gaji.tj_keluarga + gaji.tj_telepon + gaji.tj_jabatan + gaji.tj_teller + gaji.tj_perumahan + gaji.tj_kemahalan + gaji.tj_pelaksana + gaji.tj_kesejahteraan + gaji.tj_multilevel + gaji.tj_ti), 
                            0.001 * 9077600)
                    ) AS jp_1"),
                    DB::raw("SUM(
                        IF(
                            (gaji.gj_pokok + gaji.gj_penyesuaian + gaji.tj_keluarga + gaji.tj_telepon + gaji.tj_jabatan + gaji.tj_teller + gaji.tj_perumahan + gaji.tj_kemahalan + gaji.tj_pelaksana + gaji.tj_kesejahteraan + gaji.tj_multilevel + gaji.tj_ti) > 9077600, 
                            0.002 * (gaji.gj_pokok + gaji.gj_penyesuaian + gaji.tj_keluarga + gaji.tj_telepon + gaji.tj_jabatan + gaji.tj_teller + gaji.tj_perumahan + gaji.tj_kemahalan + gaji.tj_pelaksana + gaji.tj_kesejahteraan + gaji.tj_multilevel + gaji.tj_ti), 
                            0.002 * 9077600)
                    ) AS jp_2"),
                    DB::raw("SUM(
                        (0.0024 * (gaji.gj_pokok + gaji.gj_penyesuaian + gaji.tj_keluarga + gaji.tj_telepon + gaji.tj_jabatan + gaji.tj_teller + gaji.tj_perumahan + gaji.tj_kemahalan + gaji.tj_pelaksana + gaji.tj_kesejahteraan + gaji.tj_multilevel + gaji.tj_ti))
                    ) AS jkk"),
                    DB::raw("SUM(
                        (0.003 * (gaji.gj_pokok + gaji.gj_penyesuaian + gaji.tj_keluarga + gaji.tj_telepon + gaji.tj_jabatan + gaji.tj_teller + gaji.tj_perumahan + gaji.tj_kemahalan + gaji.tj_pelaksana + gaji.tj_kesejahteraan + gaji.tj_multilevel + gaji.tj_ti))
                    ) AS jkm"),
                    DB::raw("SUM(
                        (0.0057 * (gaji.gj_pokok + gaji.gj_penyesuaian + gaji.tj_keluarga + gaji.tj_telepon + gaji.tj_jabatan + gaji.tj_teller + gaji.tj_perumahan + gaji.tj_kemahalan + gaji.tj_pelaksana + gaji.tj_kesejahteraan + gaji.tj_multilevel + gaji.tj_ti))
                    ) AS jht")
                )
                ->where('gaji.bulan', $bulan)
                ->where('gaji.tahun', $tahun)
                ->groupBy('kd_kantor')
                ->get();
            
            foreach($dataGaji as $key => $value) {
                $kantor = '';

                $value->total_jp = $value->jp_1 + $value->jp_2;
                if($value->kd_kantor == '000')
                    $kantor = 'Pusat';
                else{
                    $kantor = DB::table('mst_cabang')
                        ->where('kd_cabang', $value->kd_kantor)
                        ->first()?->nama_cabang;
                }
                $value->kantor = $kantor;
                $value->jp_1 = number_format(round($value->jp_1), 0, ',', '.');
                $value->jp_2 = number_format(round($value->jp_2), 0, ',', '.');
                $value->jkk = number_format(round($value->jkk), 0, ',', '.');
                $value->jkm = number_format(round($value->jkm), 0, ',', '.');
                $value->jht = number_format(round($value->jht), 0, ',', '.');
                $value->total_jp = number_format(round($value->total_jp), 0, ',', '.');
            }
            return $dataGaji;
        } else {
            $kantor = strtolower($request->get('kantor'));
            if($kantor == 'pusat') {
                $kd_cabang = DB::table('mst_cabang')
                    ->select('kd_cabang')
                    ->pluck('kd_cabang')
                    ->toArray();
                $dataGaji = DB::table('gaji_per_bulan AS gaji')
                    ->join('mst_karyawan as karyawan', 'gaji.nip', 'karyawan.nip')
                    ->join('batch_gaji_per_bulan as batch', 'batch.id', 'gaji.batch_id')
                    ->whereNull('batch.deleted_at')
                    ->whereNull('tanggal_penonaktifan')
                    ->whereNotIn('karyawan.kd_entitas', $kd_cabang)
                    ->orWhere('karyawan.kd_entitas', 0)
                    ->orWhereNull('karyawan.kd_entitas')
                    ->select(
                        'karyawan.nip',
                        'karyawan.nama_karyawan',
                        DB::raw("(gaji.gj_pokok + gaji.gj_penyesuaian + gaji.tj_keluarga + gaji.tj_telepon + gaji.tj_jabatan + gaji.tj_teller + gaji.tj_perumahan + gaji.tj_kemahalan + gaji.tj_pelaksana + gaji.tj_kesejahteraan + gaji.tj_multilevel + gaji.tj_ti) AS total_gaji"),
                        DB::raw("IF(karyawan.kd_entitas NOT IN(select kd_cabang from mst_cabang), '000', karyawan.kd_entitas) AS kd_kantor")
                    )
                    ->where('gaji.bulan', $bulan)
                    ->where('gaji.tahun', $tahun)
                    ->orderByRaw($orderRaw)
                    ->orderBy('karyawan.kd_entitas', 'asc')
                    ->simplePaginate(10);
            } else {
                $kd_cabang = $request->get('kd_cabang');
                $dataGaji = DB::table('gaji_per_bulan AS gaji')
                    ->join('mst_karyawan as karyawan', 'gaji.nip', 'karyawan.nip')
                    ->join('batch_gaji_per_bulan as batch', 'batch.id', 'gaji.batch_id')
                    ->whereNull('batch.deleted_at')
                    ->whereNull('tanggal_penonaktifan')
                    ->where('karyawan.kd_entitas', $kd_cabang)
                    ->select(
                        'karyawan.nip',
                        'karyawan.nama_karyawan',
                        DB::raw("(gaji.gj_pokok + gaji.gj_penyesuaian + gaji.tj_keluarga + gaji.tj_telepon + gaji.tj_jabatan + gaji.tj_teller + gaji.tj_perumahan + gaji.tj_kemahalan + gaji.tj_pelaksana + gaji.tj_kesejahteraan + gaji.tj_multilevel + gaji.tj_ti) AS total_gaji"),
                        DB::raw("IF(karyawan.kd_entitas NOT IN(select kd_cabang from mst_cabang), '000', karyawan.kd_entitas) AS kd_kantor")
                    )
                    ->where('gaji.bulan', $bulan)
                    ->where('gaji.tahun', $tahun)
                    ->orderByRaw($orderRaw)
                    ->orderBy('karyawan.kd_entitas', 'asc')
                    ->simplePaginate(10);
            }
            
            foreach ($dataGaji as $key => $value) {
                $perhitungan = new stdClass;
                $perhitungan->jp_1 = number_format((($value->total_gaji > 9077600 ? 9077600 * 0.001 : $value->total_gaji * 0.001) ?? 0), 0, ',', '.');
                $perhitungan->jp_2 = number_format((($value->total_gaji > 9077600 ? 9077600 * 0.002 : $value->total_gaji * 0.002) ?? 0), 0, ',', '.');
                $perhitungan->total_jp = number_format((($value->total_gaji > 9077600 ? 9077600 * 0.001 : $value->total_gaji * 0.001) ?? 0) + (($value->total_gaji > 9077600 ? 9077600 * 0.002 : $value->total_gaji * 0.002) ?? 0), 0, ',', '.');
                $perhitungan->jkk = number_format((($value->total_gaji * 0.0024) ?? 0), 4, ',', '.');
                $perhitungan->jkm = number_format((($value->total_gaji * 0.003) ?? 0), 2, ',', '.');
                $perhitungan->jht = number_format((($value->total_gaji * 0.057) ?? 0), 2, ',', '.');
                
                $value->perhitungan = $perhitungan;
                $value->total_gaji = number_format($value->total_gaji, 0, ',', '.');
            }
            return $dataGaji->items();
        }
    }

    public function listDpp(Request $request) {
        $orderRaw = "
            CASE 
            WHEN karyawan.kd_jabatan='DIRUT' THEN 1
            WHEN karyawan.kd_jabatan='DIRUMK' THEN 2
            WHEN karyawan.kd_jabatan='DIRPEM' THEN 3
            WHEN karyawan.kd_jabatan='DIRHAN' THEN 4
            WHEN karyawan.kd_jabatan='KOMU' THEN 5
            WHEN karyawan.kd_jabatan='KOM' THEN 7
            WHEN karyawan.kd_jabatan='STAD' THEN 8
            WHEN karyawan.kd_jabatan='PIMDIV' THEN 9
            WHEN karyawan.kd_jabatan='PSD' THEN 10
            WHEN karyawan.kd_jabatan='PC' THEN 11
            WHEN karyawan.kd_jabatan='PBP' THEN 12
            WHEN karyawan.kd_jabatan='PBO' THEN 13
            WHEN karyawan.kd_jabatan='PEN' THEN 14
            WHEN karyawan.kd_jabatan='ST' THEN 15
            WHEN karyawan.kd_jabatan='NST' THEN 16
            WHEN karyawan.kd_jabatan='IKJP' THEN 17 END ASC
        ";
        $kategori = strtolower($request->get('kategori'));
        $bulan = (int) $request->get('bulan');
        $tahun = (int) $request->get('tahun');

        if($kategori == 'keseluruhan') {
            $dataDpp = DB::table('gaji_per_bulan as gaji')
                ->join('batch_gaji_per_bulan as batch', 'batch.id', 'gaji.batch_id')
                ->whereNull('batch.deleted_at')
                ->select(
                    'batch.kd_entitas',
                    DB::raw("SUM(gaji.dpp) as total_dpp")
                )
                ->where('gaji.bulan', $bulan)
                ->where('gaji.tahun', $tahun)
                ->groupBy('batch.kd_entitas')
                ->get();

            foreach($dataDpp as $key => $value) {
                $value->total_dpp = number_format(($value->total_dpp ?? 0), 0, ',', '.');

                $kantor = '';
                if ($value->kd_entitas == '000') {
                    $kantor = 'Pusat';
                } else {
                    $kantor = DB::table('mst_cabang')
                        ->where('kd_cabang', $value->kd_entitas)
                        ->first()?->nama_cabang;
                }

                $value->kantor = $kantor;
            }

            return $dataDpp;
        } else {
            $kantor = strtolower($request->get('kantor'));

            if($kantor == 'pusat') {
                $dataDpp =  DB::table('gaji_per_bulan as gaji')
                    ->join('batch_gaji_per_bulan as batch', 'batch.id', 'gaji.batch_id')
                    ->whereNull('batch.deleted_at')
                    ->where('batch.kd_entitas', '000')
                    ->where('gaji.tahun', $tahun)
                    ->where('gaji.bulan', $bulan)
                    ->join('mst_karyawan as karyawan', 'karyawan.nip', 'gaji.nip')
                    ->select(
                        'karyawan.nip',
                        'karyawan.nama_karyawan',
                        'gaji.dpp'
                    )
                    ->orderByRaw($orderRaw)
                    ->simplePaginate(10);
            } else {
                $kd_cabang = $request->get('kd_cabang');
                $dataDpp = DB::table('gaji_per_bulan as gaji')
                    ->join('batch_gaji_per_bulan as batch', 'batch.id', 'gaji.batch_id')
                    ->whereNull('batch.deleted_at')
                    ->where('batch.kd_entitas', $kd_cabang)
                    ->where('gaji.tahun', $tahun)
                    ->where('gaji.bulan', $bulan)
                    ->join('mst_karyawan as karyawan', 'karyawan.nip', 'gaji.nip')
                    ->select(
                        'karyawan.nip',
                        'karyawan.nama_karyawan',
                        'gaji.dpp'
                    )
                    ->orderByRaw($orderRaw)
                    ->simplePaginate(10);
            }

            foreach ($dataDpp as $key => $value) {
                $value->dpp = number_format(($value->dpp ?? 0), 0, ',', '.');
            }

            return $dataDpp->items();
        }
    }

    public function listPenonaktifan (Request $request) {
        $search = $request->get('search') ?? null;
        $limit = $request->get('limit') ?? 10;
        $tanggal_awal = $request->get('tanggal_awal');
        $tanggal_akhir = $request->get('tanggal_akhir');
        $returnData = [];
        $karyawanController = new KaryawanController;
        
        $kd_cabang = DB::table('mst_cabang')
            ->select('kd_cabang')
            ->pluck('kd_cabang')
            ->toArray();
        $data = DB::table('mst_karyawan')
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
                'mst_karyawan.tanggal_penonaktifan',
                'mst_karyawan.kategori_penonaktifan',
                'mst_jabatan.nama_jabatan',
                'mst_bagian.nama_bagian',
                'mst_cabang.nama_cabang'
            )
            ->leftJoin('mst_cabang', 'kd_cabang', 'mst_karyawan.kd_entitas')
            ->leftJoin('mst_bagian', 'mst_karyawan.kd_bagian', 'mst_bagian.kd_bagian')
            ->leftJoin('mst_jabatan', 'mst_jabatan.kd_jabatan', 'mst_karyawan.kd_jabatan')
            ->whereNotNull('tanggal_penonaktifan')
            ->where(function ($query) use ($search, $kd_cabang) {
                $query->where('mst_karyawan.nama_karyawan', 'like', "%$search%")
                    ->orWhere('mst_karyawan.nip', 'like', "%$search%")
                    ->orWhere('mst_karyawan.nik', 'like', "%$search%")
                    ->orWhere('mst_karyawan.kd_bagian', 'like', "%$search%")
                    ->orWhere('mst_karyawan.kd_jabatan', 'like', "%$search%")
                    ->orWhere('mst_karyawan.kd_entitas', 'like', "%$search%")
                    ->orWhere('mst_karyawan.status_jabatan', 'like', "%$search%")
                    ->orWhere('mst_cabang.nama_cabang', 'like', "%$search%")
                    ->orWhere(function ($q2) use ($search, $kd_cabang) {
                        if (str_contains($search, 'pusat')) {
                            $q2->whereNotIn('mst_karyawan.kd_entitas', $kd_cabang)
                                ->orWhereNull('mst_karyawan.kd_entitas');
                        } else {
                            $q2->where('mst_cabang.nama_cabang', 'like', "%$search%");
                        }
                    })
                    ->orWhere('mst_karyawan.ket_jabatan', 'like', "%$search%");
                // ->orWhereHas('jabatan', function($query3) use ($search) {
                //     $query3->where("nama_jabatan", 'like', "%$search%");
                // })
                // ->orWhereHas('bagian', function($query3) use ($search) {
                //     $query3->where("nama_bagian", 'like', "%$search%");
                // })
                // ->orWhere(function($query2) use ($search) {
                //     $query2->orWhereHas('jabatan', function($query3) use ($search) {
                //         $query3->where("nama_jabatan", 'like', "%$search%")
                //             ->orWhereRaw("MATCH(nama_jabatan) AGAINST('$search')");
                //     })
                //     ->whereHas('bagian', function($query3) use ($search) {
                //         $query3->whereRaw("MATCH(nama_bagian) AGAINST('$search')")
                //             ->orWhereRaw("MATCH(nama_bagian) AGAINST('$search')");
                //     });
                // });
            })
            ->orderBy('tanggal_penonaktifan', 'desc')
            ->whereBetween('tanggal_penonaktifan', [$tanggal_awal, $tanggal_akhir])
            ->simplePaginate($limit);

        foreach ($data as $value) {
            $value->entitas = $karyawanController->addEntity($value->kd_entitas);
            $prefix = match ($value->status_jabatan) {
                'Penjabat' => 'Pj. ',
                'Penjabat Sementara' => 'Pjs. ',
                default => '',
            };

            $jabatan = '';
            if ($value->nama_jabatan) {
                $jabatan = $value->nama_jabatan;
            } else {
                $jabatan = 'undifined';
            }

            $ket = $value->ket_jabatan ? "({$value->ket_jabatan})" : '';

            if (isset($value->entitas->subDiv)) {
                $entitas = $value->entitas->subDiv->nama_subdivisi;
            } elseif (isset($value->entitas->div)) {
                $entitas = $value->entitas->div->nama_divisi;
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
                $jabatan = $value->nama_jabatan ? $value->nama_jabatan : 'undifined';
            }

            unset($value->entitas);
            $display_jabatan = $prefix . ' ' . $jabatan . ' ' . $entitas . ' ' . $value?->nama_bagian . ' ' . $ket;
            $value->display_jabatan = $display_jabatan;
        }

        return $data->items();
    }
}