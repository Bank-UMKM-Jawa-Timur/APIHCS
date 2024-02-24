<?php

namespace App\Repository;

use App\Http\Controllers\KaryawanController;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use stdClass;

class HistoryRepository
{
    private $karyawanController;

    public function __construct()
    {
        $this->karyawanController = new KaryawanController;
    }

    public function getHistoryJabatan($nip)
    {
        $dataHistory = [];
        $returnData = [];
        $karyawan = DB::table('demosi_promosi_pangkat')
            ->where('demosi_promosi_pangkat.nip', $nip)
            ->select(
                'demosi_promosi_pangkat.*',
                'karyawan.*',
                'newPos.nama_jabatan as jabatan_baru',
                'oldPos.nama_jabatan as jabatan_lama'
            )
            ->join('mst_karyawan as karyawan', 'karyawan.nip', '=', 'demosi_promosi_pangkat.nip')
            ->join('mst_jabatan as newPos', 'newPos.kd_jabatan', '=', 'demosi_promosi_pangkat.kd_jabatan_baru')
            ->join('mst_jabatan as oldPos', 'oldPos.kd_jabatan', '=', 'demosi_promosi_pangkat.kd_jabatan_lama')
            ->orderBy('demosi_promosi_pangkat.id', 'desc')
            ->get();

        $karyawan->map(function ($data) {
            if (!$data->kd_entitas_baru) {
                $data->kantor_baru = "";
                return;
            }

            $entity = $this->karyawanController->addEntity($data->kd_entitas_baru);
            $type = $entity->type;

            if ($type == 2)
                $data->kantor_baru = "Cab. " . $entity->cab->nama_cabang;

            if ($type == 1) {
                $data->kantor_baru = isset($entity->subDiv) ?
                    $entity->subDiv->nama_subdivisi . " (Pusat)" :
                    $entity->div->nama_divisi . " (Pusat)";
            }

            return $data;
        });

        $karyawan->map(function ($dataLama) {
            if (!$dataLama->kd_entitas_lama) {
                $dataLama->kantor_lama = "";
                return;
            }

            $entityLama = $this->karyawanController->addEntity($dataLama->kd_entitas_lama);
            $typeLama = $entityLama->type;

            if ($typeLama == 2)
                $dataLama->kantor_lama = "Cab. " . $entityLama->cab->nama_cabang;
            if ($typeLama == 1) {
                $dataLama->kantor_lama = isset($entityLama->subDiv) ?
                    $entityLama->subDiv->nama_subdivisi . " (Pusat)" :
                    $entityLama->div->nama_divisi . " (Pusat)";
            }

            return $dataLama;
        });

        $data_migrasi = DB::table('migrasi_jabatan')
            ->where('nip', $nip)
            ->get();

        foreach ($karyawan as $item) {
            array_push($dataHistory, [
                'tanggal_pengesahan' => $item?->tanggal_pengesahan,
                'lama' => $item?->kd_panggol_lama . ' ' . (($item->status_jabatan_lama != null) ? $item->status_jabatan_lama . ' - ' : '') . ' ' . $item->jabatan_lama . ' ' . $item->kantor_lama ?? '-',
                'baru' => $item?->kd_panggol_baru . ' ' . (($item->status_jabatan_baru != null) ? $item->status_jabatan_baru . ' - ' : '') . ' ' . $item->jabatan_baru . ' ' . $item->kantor_baru ?? '-',
                'bukti_sk' => $item?->bukti_sk,
                'keterangan' => $item?->keterangan
            ]);
        }

        if ($data_migrasi) {
            foreach ($data_migrasi as $item) {
                if (empty($item?->keterangan)) {
                    $keterangan = '-';
                } else {
                    $keterangan = $item?->keterangan;
                }
                array_push($dataHistory, [
                    'tanggal_pengesahan' => $item?->tgl,
                    'lama' => $item?->lama,
                    'baru' => $item?->baru,
                    'bukti_sk' => $item?->no_sk,
                    'keterangan' => $keterangan
                ]);
            }
        }
        usort($dataHistory, fn($a, $b) => strtotime($a["tanggal_pengesahan"]) - strtotime($b["tanggal_pengesahan"]));

        foreach ($dataHistory as $key => $item) {
            $masaKerja = '-';
            if ($key != 0) {
                $mulaKerja = new DateTime(date('d-M-Y', strtotime($item['tanggal_pengesahan'])));
                $waktuSekarang = new DateTime(date('d-M-Y', strtotime($dataHistory[$key - 1]['tanggal_pengesahan'])));
                $hitung = $waktuSekarang->diff($mulaKerja);
                $masaKerja = $hitung->format('%y Tahun | %m Bulan | %d Hari');

            }
            array_push($returnData, [
                'tanggal_pengesahan' => $item['tanggal_pengesahan'],
                'lama' => $item['lama'],
                'baru' => $item['baru'],
                'bukti_sk' => $item['bukti_sk'],
                'keterangan' => $item['keterangan'],
                'masa_kerja' => $masaKerja
            ]);
        }
        return $returnData;
    }

    public function getRincianKaryawn($nip)
    {
        $returnData = new stdClass;
        $data = DB::table('mst_karyawan as m')
            ->select(
                'm.nip',
                'm.nama_karyawan',
                'm.jk',
                'm.status_jabatan',
                'j.nama_jabatan',
                DB::raw("CONCAT(p.golongan, ' - ', p.pangkat) AS pangkat_golongan"),
                DB::raw("IF(m.kd_entitas NOT IN(SELECT kd_cabang FROM mst_cabang), 'Pusat', CONCAT('Cab.', c.nama_cabang)) AS kantor"),
            )
            ->where('nip', $nip)
            ->leftJoin('mst_pangkat_golongan as p', 'p.golongan', 'm.kd_panggol')
            ->leftJoin('mst_cabang as c', 'c.kd_cabang', 'm.kd_entitas')
            ->leftJoin('mst_jabatan as j', 'j.kd_jabatan', 'm.kd_jabatan')
            ->first();

        $returnData->nip = $data->nip ?? null;
        $returnData->nama_karyawan = $data->nama_karyawan ?? null;
        $returnData->jk = $data->jk ?? null;
        $returnData->status_jabatan = $data->status_jabatan ?? null;
        $returnData->nama_jabatan = $data->nama_jabatan ?? null;
        $returnData->pangkat_golongan = $data->pangkat_golongan ?? null;
        $returnData->kantor = $data->kantor ?? null;

        return $returnData;
    }

    public function getHistoryPJS(Request $request)
    {
        $kategori = strtolower($request->get('kategori'));
        if ($kategori == 'aktif') {
            $data = DB::table('pejabat_sementara as pjs')
                ->join('mst_karyawan as m', 'm.nip', 'pjs.nip')
                ->select(
                    'm.nama_karyawan',
                    'm.nip',
                    'pjs.kd_entitas',
                    'pjs.tanggal_mulai',
                    'pjs.tanggal_berakhir',
                    'pjs.no_sk',
                    'mst_jabatan.nama_jabatan',
                    'mst_bagian.nama_bagian',
                    'mst_cabang.nama_cabang',
                )
                ->leftJoin('mst_cabang', 'kd_cabang', 'pjs.kd_entitas')
                ->leftJoin('mst_bagian', 'pjs.kd_bagian', 'mst_bagian.kd_bagian')
                ->leftJoin('mst_jabatan', 'mst_jabatan.kd_jabatan', 'pjs.kd_jabatan')
                ->whereNull('pjs.tanggal_berakhir')
                ->orderBy('pjs.tanggal_mulai', 'desc')
                ->simplePaginate(25);

            foreach ($data as $key => $value) {
                $value->entitas = $this->karyawanController->addEntity($value->kd_entitas);

                $jabatan = '';
                if ($value->nama_jabatan) {
                    $jabatan = $value->nama_jabatan;
                } else {
                    $jabatan = 'undifined';
                }

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
                $display_jabatan = 'Pjs. ' . $jabatan . ' ' . $entitas . ' ' . $value?->nama_bagian;
                $value->display_jabatan = $display_jabatan;
                $value->status = 'Aktif';
            }

            return $data->items();
        } else {
            $nip = $request->get('nip');
            $data = DB::table('pejabat_sementara as pjs')
                ->join('mst_karyawan as m', 'm.nip', 'pjs.nip')
                ->where('pjs.nip', $nip)
                ->select(
                    'm.nama_karyawan',
                    'm.nip',
                    'pjs.kd_entitas',
                    'pjs.tanggal_mulai',
                    'pjs.tanggal_berakhir',
                    'pjs.no_sk',
                    'mst_jabatan.nama_jabatan',
                    'mst_bagian.nama_bagian',
                    'mst_cabang.nama_cabang',
                )
                ->leftJoin('mst_cabang', 'kd_cabang', 'pjs.kd_entitas')
                ->leftJoin('mst_bagian', 'pjs.kd_bagian', 'mst_bagian.kd_bagian')
                ->leftJoin('mst_jabatan', 'mst_jabatan.kd_jabatan', 'pjs.kd_jabatan')
                ->orderBy('pjs.tanggal_mulai', 'desc')
                ->simplePaginate(25);

            foreach ($data as $key => $value) {
                $value->entitas = $this->karyawanController->addEntity($value->kd_entitas);

                $jabatan = '';
                if ($value->nama_jabatan) {
                    $jabatan = $value->nama_jabatan;
                } else {
                    $jabatan = 'undifined';
                }

                if (isset($value->entitas->subDiv)) {
                    $entitas = $value->entitas->subDiv->nama_subdivisi;
                } elseif (isset($value->entitas->div)) {
                    $entitas = $value->entitas->div->nama_divisi;
                } elseif (isset($value->entitas->cab)) {
                    $entitas = $value->entitas->cab->nama_cabang;
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
                $display_jabatan = 'Pjs. ' . $jabatan . ' ' . $entitas . ' ' . $value?->nama_bagian;
                $value->display_jabatan = $display_jabatan;
                if ($value->tanggal_berakhir != null)
                    $value->status = 'Aktif';
                else
                    $value->status = 'Nonaktif';
            }

            return $data->items();
        }
    }

    public function getHistorySP(Request $request)
    {
        $kategori = strtolower($request->get('kategori'));
        $limit = $request->get('limit') ?? 10;
        $search = $request->get('search') ?? null;

        if ($kategori == 'keseluruhan') {
            $data = DB::table('surat_peringatan')
                ->select(
                    'surat_peringatan.id',
                    'surat_peringatan.nip',
                    'surat_peringatan.tanggal_sp',
                    'surat_peringatan.no_sp',
                    'surat_peringatan.pelanggaran',
                    'surat_peringatan.sanksi',
                    'mst_karyawan.nama_karyawan',
                    'mst_karyawan.kd_entitas'
                )
                ->join('mst_karyawan', 'surat_peringatan.nip', '=', 'mst_karyawan.nip')
                ->when($search, function ($query) use ($search) {
                    $query->where('surat_peringatan.nip', 'like', "%$search%")
                        ->orWhere('surat_peringatan.tanggal_sp', 'like', "%$search%")
                        ->orWhere('surat_peringatan.no_sp', 'like', "%$search%")
                        ->orWhere('surat_peringatan.pelanggaran', 'like', "%$search%")
                        ->orWhere('mst_karyawan.nama_karyawan', 'like', "%$search%");
                })
                ->orderBy('tanggal_sp', 'DESC')
                ->simplePaginate($limit);
        } else if ($kategori == 'karyawan') {
            $nip = $request->get('nip');
            $data = DB::table('surat_peringatan')
                ->select(
                    'surat_peringatan.id',
                    'surat_peringatan.nip',
                    'surat_peringatan.tanggal_sp',
                    'surat_peringatan.no_sp',
                    'surat_peringatan.pelanggaran',
                    'surat_peringatan.sanksi',
                    'mst_karyawan.nama_karyawan',
                    'mst_karyawan.kd_entitas'
                )
                ->join('mst_karyawan', 'surat_peringatan.nip', '=', 'mst_karyawan.nip')
                ->when($search, function ($query) use ($search) {
                    $query->where('surat_peringatan.nip', 'like', "%$search%")
                        ->orWhere('surat_peringatan.tanggal_sp', 'like', "%$search%")
                        ->orWhere('surat_peringatan.no_sp', 'like', "%$search%")
                        ->orWhere('surat_peringatan.pelanggaran', 'like', "%$search%")
                        ->orWhere('mst_karyawan.nama_karyawan', 'like', "%$search%");
                })
                ->where('surat_peringatan.nip', $nip)
                ->orderBy('tanggal_sp', 'DESC')
                ->simplePaginate($limit);
        } else if ($kategori == 'tanggal') {
            $data = DB::table('surat_peringatan')
                ->select(
                    'surat_peringatan.id',
                    'surat_peringatan.nip',
                    'surat_peringatan.tanggal_sp',
                    'surat_peringatan.no_sp',
                    'surat_peringatan.pelanggaran',
                    'surat_peringatan.sanksi',
                    'mst_karyawan.nama_karyawan',
                    'mst_karyawan.kd_entitas'
                )
                ->join('mst_karyawan', 'surat_peringatan.nip', '=', 'mst_karyawan.nip')
                ->when($search, function ($query) use ($search) {
                    $query->where('surat_peringatan.nip', 'like', "%$search%")
                        ->orWhere('surat_peringatan.tanggal_sp', 'like', "%$search%")
                        ->orWhere('surat_peringatan.no_sp', 'like', "%$search%")
                        ->orWhere('surat_peringatan.pelanggaran', 'like', "%$search%")
                        ->orWhere('mst_karyawan.nama_karyawan', 'like', "%$search%");
                })
                ->whereBetween('tanggal_sp', [$request->get('tanggal_awal'), $request->get('tanggal_akhir')])
                ->orderBy('tanggal_sp', 'DESC')
                ->simplePaginate($limit);
        } else if ($kategori == 'tahun') {
            $tahun = (int) $request->get('tahun');
            $data = DB::table('surat_peringatan')
                ->select(
                    'surat_peringatan.id',
                    'surat_peringatan.nip',
                    'surat_peringatan.tanggal_sp',
                    'surat_peringatan.no_sp',
                    'surat_peringatan.pelanggaran',
                    'surat_peringatan.sanksi',
                    'mst_karyawan.nama_karyawan',
                    'mst_karyawan.kd_entitas',
                )
                ->join('mst_karyawan', 'surat_peringatan.nip', '=', 'mst_karyawan.nip')
                ->when($search, function ($query) use ($search) {
                    $query->where('surat_peringatan.nip', 'like', "%$search%")
                        ->orWhere('surat_peringatan.tanggal_sp', 'like', "%$search%")
                        ->orWhere('surat_peringatan.no_sp', 'like', "%$search%")
                        ->orWhere('surat_peringatan.pelanggaran', 'like', "%$search%")
                        ->orWhere('mst_karyawan.nama_karyawan', 'like', "%$search%");
                })
                ->whereYear('tanggal_sp', $tahun)
                ->orderBy('tanggal_sp', 'DESC')
                ->simplePaginate($limit);
        }

        foreach ($data as $key => $value) {
            $value->entitas = $this->karyawanController->addEntity($value->kd_entitas);
            $kantor = '-';

            if ($value->entitas) {
                $is_cabang = isset($value->entitas->cab);

                $kantor = $is_cabang ? $value->entitas->cab->nama_cabang : 'Pusat';
            }
            $value->kantor = $kantor;
            unset($value->entitas);
        }

        return $data->items();
    }
}