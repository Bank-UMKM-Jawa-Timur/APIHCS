<?php

namespace App\Repository;

use App\Http\Controllers\KaryawanController;
use DateTime;
use Illuminate\Support\Facades\DB;
use stdClass;

class HistoryRepository
{
    private $karyawanController;

    public function __construct()
    {
        $this->karyawanController = new KaryawanController;
    }

    public function getHistoryJabatan($nip) {
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

        $karyawan->map(function($data) {
            if(!$data->kd_entitas_baru) {
                $data->kantor_baru = "";
                return;
            }

            $entity = $this->karyawanController->addEntity($data->kd_entitas_baru);
            $type = $entity->type;

            if($type == 2) $data->kantor_baru = "Cab. " . $entity->cab->nama_cabang;

            if($type == 1) {
                $data->kantor_baru = isset($entity->subDiv) ?
                $entity->subDiv->nama_subdivisi . " (Pusat)":
                $entity->div->nama_divisi . " (Pusat)";
            }

            return $data;
        });

        $karyawan->map(function($dataLama) {
            if(!$dataLama->kd_entitas_lama) {
                $dataLama->kantor_lama = "";
                return;
            }

            $entityLama = $this->karyawanController->addEntity($dataLama->kd_entitas_lama);
            $typeLama = $entityLama->type;

            if($typeLama == 2) $dataLama->kantor_lama = "Cab. " . $entityLama->cab->nama_cabang;
            if($typeLama == 1) {
                $dataLama->kantor_lama = isset($entityLama->subDiv) ?
                $entityLama->subDiv->nama_subdivisi . " (Pusat)":
                $entityLama->div->nama_divisi." (Pusat)";
            }

            return $dataLama;
        });

        $data_migrasi = DB::table('migrasi_jabatan')
            ->where('nip', $nip)
            ->get();

        foreach($karyawan as $item){
            array_push($dataHistory, [
                'tanggal_pengesahan' => $item?->tanggal_pengesahan,
                'lama' =>  $item?->kd_panggol_lama . ' ' . (($item->status_jabatan_lama != null) ? $item->status_jabatan_lama.' - ' : '') . ' ' . $item->jabatan_lama . ' ' . $item->kantor_lama ?? '-',
                'baru' => $item?->kd_panggol_baru . ' ' . (($item->status_jabatan_baru != null) ? $item->status_jabatan_baru.' - ' : '') . ' ' . $item->jabatan_baru . ' ' . $item->kantor_baru ?? '-',
                'bukti_sk' => $item?->bukti_sk,
                'keterangan' => $item?->keterangan
            ]);
        }

        if($data_migrasi){
            foreach($data_migrasi as $item){
                if(empty($item?->keterangan)){
                    $keterangan = '-';
                }else{
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
}