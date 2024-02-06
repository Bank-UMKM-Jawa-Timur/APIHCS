<?php
namespace App\Repository;

use App\Http\Controllers\KaryawanController;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use stdClass;

class KaryawanRepository
{
    private $orderRaw;
    private $karyawanController;

    public function __construct()
    {
        $this->karyawanController = new KaryawanController;
        $this->orderRaw = "
            CASE 
            WHEN mst_karyawan.kd_jabatan='DIRUT' THEN 1
            WHEN mst_karyawan.kd_jabatan='DIRUMK' THEN 2
            WHEN mst_karyawan.kd_jabatan='DIRPEM' THEN 3
            WHEN mst_karyawan.kd_jabatan='DIRHAN' THEN 4
            WHEN mst_karyawan.kd_jabatan='KOMU' THEN 5
            WHEN mst_karyawan.kd_jabatan='KOM' THEN 7
            WHEN mst_karyawan.kd_jabatan='STAD' THEN 8
            WHEN mst_karyawan.kd_jabatan='PIMDIV' THEN 9
            WHEN mst_karyawan.kd_jabatan='PSD' THEN 10
            WHEN mst_karyawan.kd_jabatan='PC' THEN 11
            WHEN mst_karyawan.kd_jabatan='PBP' THEN 12
            WHEN mst_karyawan.kd_jabatan='PBO' THEN 13
            WHEN mst_karyawan.kd_jabatan='PEN' THEN 14
            WHEN mst_karyawan.kd_jabatan='ST' THEN 15
            WHEN mst_karyawan.kd_jabatan='NST' THEN 16
            WHEN mst_karyawan.kd_jabatan='IKJP' THEN 17 END ASC
        ";
    }

    public function getAllKaryawan($search, $limit = 10, $page = 1) {
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
                'mst_jabatan.nama_jabatan',
                'mst_bagian.nama_bagian',
                'mst_cabang.nama_cabang'
            )->leftJoin('mst_cabang', 'kd_cabang', 'mst_karyawan.kd_entitas')
            ->leftJoin('mst_bagian', 'mst_karyawan.kd_bagian', 'mst_bagian.kd_bagian')
            ->leftJoin('mst_jabatan', 'mst_jabatan.kd_jabatan', 'mst_karyawan.kd_jabatan')
            ->whereNull('tanggal_penonaktifan')
            ->where(function ($query) use ($search, $kd_cabang) {
                $query->where('mst_karyawan.nama_karyawan', 'like', "%$search%")
                    ->orWhere('mst_karyawan.nip', 'like', "%$search%")
                    ->orWhere('mst_karyawan.nik', 'like', "%$search%")
                    ->orWhere('mst_karyawan.kd_bagian', 'like', "%$search%")
                    ->orWhere('mst_karyawan.kd_jabatan', 'like', "%$search%")
                    ->orWhere('mst_karyawan.kd_entitas', 'like', "%$search%")
                    ->orWhere('mst_karyawan.status_jabatan', 'like', "%$search%")
                    ->orWhere('mst_cabang.nama_cabang', 'like', "%$search%")
                    ->orWhere(function($q2) use ($search, $kd_cabang) {
                        if (str_contains($search, 'pusat')) {
                            $q2->whereNotIn('mst_karyawan.kd_entitas', $kd_cabang)
                            ->orWhereNull('mst_karyawan.kd_entitas');
                        }
                        else {
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
            ->orderByRaw($this->orderRaw)
            ->orderBy('kd_cabang', 'asc')
            ->orderBy('kd_entitas', 'asc')
            ->paginate($limit);

        $this->karyawanController->addEntity($data);
        foreach($data as $value) {
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

        return $data;
    }

    public function getDetailKaryawan($id) {
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
                $biodata = new stdClass;
                $norek = new stdClass;
                $dataJabatan = new stdClass;
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
                $biodata->nip = $karyawan->nip;
                $biodata->nik = $karyawan->nik;
                $biodata->nama_karyawan = $karyawan->nama_karyawan;
                $biodata->ttl = $karyawan->tmp_lahir . ', ' . date('d F Y', strtotime($karyawan->tgl_lahir));
                $biodata->umur = $umur;
                $biodata->agama = $karyawan->agama;
                $biodata->status_pernikahan = $karyawan->status_ptkp;
                $biodata->kewarganegaraan = $karyawan->kewarganegaraan;
                $biodata->alamat_ktp = $karyawan->alamat_ktp;
                $biodata->alamat_sek = $karyawan->alamat_sek;
                $biodata->jenis_kelamin = $karyawan->jk;
                // End biodata diri

                // Norek & NPWP
                $norek->no_rek = $karyawan->no_rekening;
                $norek->npwp = $karyawan->npwp;
                // End Norek & NPWP

                // Data Jabatan
                $dataJabatan->tanggal_bergabung = Carbon::parse($karyawan->tanggal_pengangkat)->translatedFormat('d F Y');
                $dataJabatan->lama_kerja = $masaKerja;
                $dataJabatan->pangkat = $karyawan->pangkat;
                $dataJabatan->golongan = $karyawan->golongan;
                $dataJabatan->status_karyawan = $karyawan->status_karyawan;
                $dataJabatan->status_jabatan = $karyawan->status_jabatan;
                $dataJabatan->keterangan_jabatan = $karyawan->ket_jabatan;
                $dataJabatan->tanggal_mulai = date('d F Y', strtotime($karyawan->tgl_mulai));
                $dataJabatan->pendidikan_terakhir = $karyawan->pendidikan;
                $dataJabatan->pendidikan_major = $karyawan->pendidikan_major;
                $dataJabatan->sk_pengangkatan = $karyawan->skangkat;
                $dataJabatan->tanggal_pengangkatan = $karyawan->tanggal_pengangkat;
                $dataKaryawan->entitas = $this->karyawanController->addEntity($karyawan->kd_entitas);
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
                $dataJabatan->display_jabatan = $display_jabatan;

                $returnData->biodata = $biodata;
                $returnData->norek_npwp = $norek;
                $returnData->data_jabatan = $dataJabatan;
                $data = $returnData;
        }

        return $data;
    }
}