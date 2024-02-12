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

    public function getDataPensiun($request = []){
        $kategori = strtolower($request['kategori']);

        if($kategori == 'divisi') {
            $subDivs = DB::table('mst_sub_divisi')->where('kd_divisi', $request['divisi'])
                ->pluck('kd_subdiv');

            $bagians = DB::table('mst_bagian')->whereIn('kd_entitas', $subDivs)
                ->orWhere('kd_entitas', $request['divisi'])
                ->pluck('kd_bagian');

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
                    'mst_cabang.nama_cabang',
                    'mst_karyawan.tgl_lahir'
                )->leftJoin('mst_cabang', 'kd_cabang', 'mst_karyawan.kd_entitas')
                ->leftJoin('mst_bagian', 'mst_karyawan.kd_bagian', 'mst_bagian.kd_bagian')
                ->leftJoin('mst_jabatan', 'mst_jabatan.kd_jabatan', 'mst_karyawan.kd_jabatan')
                ->whereNull('tanggal_penonaktifan')
                ->where('kd_entitas', $request['divisi'])
                ->orWhereIn('kd_entitas', $subDivs)
                ->orWhereIn('kd_bagian', $bagians)
                ->orderByRaw($this->orderRaw)
                ->orderBy('kd_cabang', 'asc')
                ->orderBy('kd_entitas', 'asc')
                ->paginate(25);
        } else if($kategori == 'sub divisi') {
            $entitas = $request['subDivisi'] ?? $request['divisi'];

            $bagian = DB::table('mst_bagian')->where('kd_entitas', $entitas)
                ->pluck('kd_bagian');
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
                    'mst_cabang.nama_cabang',
                    'mst_karyawan.tgl_lahir'
                )->leftJoin('mst_cabang', 'kd_cabang', 'mst_karyawan.kd_entitas')
                ->leftJoin('mst_bagian', 'mst_karyawan.kd_bagian', 'mst_bagian.kd_bagian')
                ->leftJoin('mst_jabatan', 'mst_jabatan.kd_jabatan', 'mst_karyawan.kd_jabatan')
                ->whereNull('tanggal_penonaktifan')
                ->where('kd_entitas', $entitas)
                ->orWhereIn('kd_bagian', $bagian)
                ->orderByRaw($this->orderRaw)
                ->orderBy('kd_cabang', 'asc')
                ->orderBy('kd_entitas', 'asc')
                ->paginate(25);
        } else if($kategori == 'bagian') {
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
                    'mst_cabang.nama_cabang',
                    'mst_karyawan.tgl_lahir'
                )->leftJoin('mst_cabang', 'kd_cabang', 'mst_karyawan.kd_entitas')
                ->leftJoin('mst_bagian', 'mst_karyawan.kd_bagian', 'mst_bagian.kd_bagian')
                ->leftJoin('mst_jabatan', 'mst_jabatan.kd_jabatan', 'mst_karyawan.kd_jabatan')
                ->whereNull('tanggal_penonaktifan')
                ->where('kd_bagian', $request['bagian'])
                ->whereNotNull('kd_bagian')
                ->orderByRaw($this->orderRaw)
                ->orderBy('kd_cabang', 'asc')
                ->orderBy('kd_entitas', 'asc')
                ->paginate(25);
        } else if($kategori == 'kantor') {
            if($request['kantor'] == 'Cabang'){
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
                        'mst_cabang.nama_cabang',
                        'mst_karyawan.tgl_lahir'
                    )->leftJoin('mst_cabang', 'kd_cabang', 'mst_karyawan.kd_entitas')
                    ->leftJoin('mst_bagian', 'mst_karyawan.kd_bagian', 'mst_bagian.kd_bagian')
                    ->leftJoin('mst_jabatan', 'mst_jabatan.kd_jabatan', 'mst_karyawan.kd_jabatan')
                    ->whereNull('tanggal_penonaktifan')->where('kd_entitas', $request['kd_cabang'])
                    ->orderByRaw($this->orderRaw)
                    ->orderBy('kd_cabang', 'asc')
                    ->orderBy('kd_entitas', 'asc')
                    ->paginate(25);
            }
            else {
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
                        'mst_cabang.nama_cabang',
                        'mst_karyawan.tgl_lahir'
                    )->leftJoin('mst_cabang', 'kd_cabang', 'mst_karyawan.kd_entitas')
                    ->leftJoin('mst_bagian', 'mst_karyawan.kd_bagian', 'mst_bagian.kd_bagian')
                    ->leftJoin('mst_jabatan', 'mst_jabatan.kd_jabatan', 'mst_karyawan.kd_jabatan')
                    ->whereNull('tanggal_penonaktifan')
                    ->whereNotIn('mst_karyawan.kd_entitas', $kd_cabang)
                    ->orWhere('mst_karyawan.kd_entitas', 0)
                    ->orWhereNull('mst_karyawan.kd_entitas')
                    ->orderByRaw($this->orderRaw)
                    ->orderBy('kd_cabang', 'asc')
                    ->orderBy('kd_entitas', 'asc')
                    ->paginate(25);
            }
        } else {
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
                    'mst_cabang.nama_cabang',
                    'mst_karyawan.tgl_lahir'
                )->leftJoin('mst_cabang', 'kd_cabang', 'mst_karyawan.kd_entitas')
                ->leftJoin('mst_bagian', 'mst_karyawan.kd_bagian', 'mst_bagian.kd_bagian')
                ->leftJoin('mst_jabatan', 'mst_jabatan.kd_jabatan', 'mst_karyawan.kd_jabatan')
                ->whereNull('tanggal_penonaktifan')
                ->orderByRaw($this->orderRaw)
                ->orderBy('kd_cabang', 'asc')
                ->orderBy('kd_entitas', 'asc')
                ->paginate(25);
        }
        
        foreach($data as $value) {
            $value->entitas = $this->karyawanController->addEntity($value->kd_entitas);
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
            
            $umur = Carbon::create($value->tgl_lahir);
            $waktuSekarang = Carbon::now();
            $hitung = $waktuSekarang->diff($umur);
            $waktuSekarang = Carbon::now();
            $tglLahir = $value->tgl_lahir;
            $pensiun = Carbon::create(date('Y-m-d', strtotime($tglLahir . ' + 56 years')));
            $hitungPensiun = $pensiun->diff($waktuSekarang);
            $tampilPensiun = null;

            if($waktuSekarang->diffInYears($umur) >= 54){
                $tampilPensiun = 'Persiapan pensiun dalam ' . $hitungPensiun->format('%y Tahun, %m Bulan, %d Hari');
            } else if($waktuSekarang->diffInYears($umur) >= 56) {
                $tampilPensiun = 'Telah melebihi batas pensiun.';
            }
            $value->pensiun = $tampilPensiun;

            $value->kantor = $value->nama_cabang != null ? $value->nama_cabang : 'Pusat';
        }
        return $data;
    }

    public function listPengkinianData($limit = 10, $search = null) {
        $orderRaw = "
            CASE 
            WHEN h.kd_jabatan='DIRUT' THEN 1
            WHEN h.kd_jabatan='DIRUMK' THEN 2
            WHEN h.kd_jabatan='DIRPEM' THEN 3
            WHEN h.kd_jabatan='DIRHAN' THEN 4
            WHEN h.kd_jabatan='KOMU' THEN 5
            WHEN h.kd_jabatan='KOM' THEN 7
            WHEN h.kd_jabatan='STAD' THEN 8
            WHEN h.kd_jabatan='PIMDIV' THEN 9
            WHEN h.kd_jabatan='PSD' THEN 10
            WHEN h.kd_jabatan='PC' THEN 11
            WHEN h.kd_jabatan='PBP' THEN 12
            WHEN h.kd_jabatan='PBO' THEN 13
            WHEN h.kd_jabatan='PEN' THEN 14
            WHEN h.kd_jabatan='ST' THEN 15
            WHEN h.kd_jabatan='NST' THEN 16
            WHEN h.kd_jabatan='IKJP' THEN 17 END ASC
        ";
        $data = DB::table('history_pengkinian_data_karyawan AS h')
            ->select(
                'h.id',
                'h.nip',
                'h.nik',
                'h.nama_karyawan',
                'h.kd_entitas',
                'h.kd_jabatan',
                'h.kd_bagian',
                'h.ket_jabatan',
                'h.status_karyawan',
                'j.nama_jabatan',
                'h.status_jabatan',
                'b.nama_bagian',
                DB::raw("IF((SELECT m.kd_entitas FROM mst_karyawan AS m WHERE m.nip = h.`nip` AND m.kd_entitas IN(SELECT mst_cabang.kd_cabang FROM mst_cabang)), 1, 0) AS status_kantor"),
                'c.nama_cabang',
            )
            ->join('mst_jabatan AS j', 'j.kd_jabatan', 'h.kd_jabatan')
            ->leftJoin('mst_cabang AS c', 'c.kd_cabang', 'h.kd_entitas')
            ->leftJoin('mst_bagian AS b', 'b.kd_bagian', 'h.kd_bagian')
            ->when($search, function($q) use ($search) {
                $q->where('h.nip', 'like', "%$search%")
                ->orWhere('h.nik', 'like', "%$search%")
                ->orWhere('h.nama_karyawan', 'like', "%$search%")
                ->orWhere('h.kd_entitas', 'like', "%$search%")
                ->orWhere('h.kd_jabatan', 'like', "%$search%")
                ->orWhere('h.kd_bagian', 'like', "%$search%")
                ->orWhere('h.ket_jabatan', 'like', "%$search%")
                ->orWhere('h.status_karyawan', 'like', "%$search%")
                ->orWhere('j.nama_jabatan', 'like', "%$search%")
                ->orWhere('h.status_jabatan', 'like', "%$search%");
            })
            ->orderByRaw($orderRaw)
            ->paginate($limit);
        foreach($data as $key => $value) {
            $value->entitas = $this->karyawanController->addEntity($value->kd_entitas);
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

            $value->kantor = $value->nama_cabang != null ? $value->nama_cabang : 'Pusat';
        }

        return $data;
    }

    public function detailPengkinianData($id) {
        $returnData = new stdClass;
        $biodata = new stdClass;
        $norek = new stdClass;
        $dataJabatan = new stdClass;
        $karyawan = DB::table('history_pengkinian_data_karyawan AS h')
            ->select(
                'h.*',
                'j.nama_jabatan',
                'b.nama_bagian',
                DB::raw("IF((SELECT m.kd_entitas FROM mst_karyawan AS m WHERE m.nip = h.`nip` AND m.kd_entitas IN(SELECT mst_cabang.kd_cabang FROM mst_cabang)), 1, 0) AS status_kantor"),
                'c.nama_cabang',
                'mst_pangkat_golongan.pangkat',
                'mst_pangkat_golongan.golongan',
                'mst_agama.agama',
            )
            ->where('h.id', $id)
            ->join('mst_jabatan AS j', 'j.kd_jabatan', 'h.kd_jabatan')
            ->leftJoin('mst_cabang AS c', 'c.kd_cabang', 'h.kd_entitas')
            ->leftJoin('mst_bagian AS b', 'b.kd_bagian', 'h.kd_bagian')
            ->leftJoin('mst_pangkat_golongan', 'mst_pangkat_golongan.golongan', 'h.kd_panggol')
            ->leftJoin('mst_jabatan', 'mst_jabatan.kd_jabatan', 'h.kd_jabatan')
            ->leftJoin('mst_agama', 'mst_agama.kd_agama', 'h.kd_agama')
            ->first();
        
        $karyawan->entitas = $this->karyawanController->addEntity($karyawan->kd_entitas);
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
        
        if ($karyawan->nama_bagian != null && $karyawan->entitas->type == 1){
            $entitas = '';
        } else if (isset($karyawan->entitas->subDiv)) {
            $entitas = $karyawan->entitas->subDiv->nama_subdivisi;
        } elseif (isset($karyawan->entitas->div)) {
            $entitas = $karyawan->entitas->div->nama_divisi;
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

        $karyawan->display_jabatan = $prefix . ' ' . $jabatan . ' ' . $entitas . ' ' . $karyawan?->nama_bagian . ' ' . $ket . ($karyawan->nama_cabang != null ? ' Cabang ' . $karyawan->nama_cabang : ' (Pusat)');
        unset($karyawan->entitas);
        return $karyawan;
    }

    public function listMutasi($search = '', $limit = 10) {
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
            ->join('mst_karyawan as karyawan', function($join) {
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
            ->when($search, function($query) use ($search) {
                $query->where('karyawan.nip', 'LIKE', "%$search%")
                    ->orWhere('karyawan.nama_karyawan', 'LIKE', "%$search%")
                    ->orWhereDate('demosi_promosi_pangkat.tanggal_pengesahan', $search)
                    ->orWhere('newPos.nama_jabatan',  'LIKE', "%$search%")
                    ->orWhere('oldPos.nama_jabatan',  'LIKE', "%$search%")
                    ->orWhere('div_lama.nama_divisi',  'LIKE', "%$search%")
                    ->orWhere('sub_div_lama.nama_subdivisi',  'LIKE', "%$search%")
                    ->orWhere('cabang_lama.nama_cabang',  'LIKE', "%$search%")
                    ->orWhere('div_baru.nama_divisi',  'LIKE', "%$search%")
                    ->orWhere('sub_div_baru.nama_subdivisi',  'LIKE', "%$search%")
                    ->orWhere('cabang_baru.nama_cabang',  'LIKE', "%$search%")
                    ->orWhere('demosi_promosi_pangkat.bukti_sk',  'LIKE', "%$search%");
            })
            ->orderBy('tanggal_pengesahan', 'desc')
            ->paginate($limit);
        foreach($data as $key => $value) {
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

    public function listPromosi($search = '', $limit = 10) {
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
            ->join('mst_karyawan as karyawan', function($join) {
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
            ->when($search, function($query) use ($search) {
                $query->where('karyawan.nip', 'LIKE', "%$search%")
                    ->orWhere('karyawan.nama_karyawan', 'LIKE', "%$search%")
                    ->orWhereDate('demosi_promosi_pangkat.tanggal_pengesahan', $search)
                    ->orWhere('newPos.nama_jabatan',  'LIKE', "%$search%")
                    ->orWhere('oldPos.nama_jabatan',  'LIKE', "%$search%")
                    ->orWhere('div_lama.nama_divisi',  'LIKE', "%$search%")
                    ->orWhere('sub_div_lama.nama_subdivisi',  'LIKE', "%$search%")
                    ->orWhere('cabang_lama.nama_cabang',  'LIKE', "%$search%")
                    ->orWhere('div_baru.nama_divisi',  'LIKE', "%$search%")
                    ->orWhere('sub_div_baru.nama_subdivisi',  'LIKE', "%$search%")
                    ->orWhere('cabang_baru.nama_cabang',  'LIKE', "%$search%")
                    ->orWhere('demosi_promosi_pangkat.bukti_sk',  'LIKE', "%$search%");
            })
            ->orderBy('tanggal_pengesahan', 'desc')
            ->paginate($limit);

        foreach($data as $key => $value) {
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

    public function listDemosi($search = '', $limit = 10) {
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
            ->join('mst_karyawan as karyawan', function($join) {
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
            ->when($search, function($query) use ($search) {
                $query->where('karyawan.nip', 'LIKE', "%$search%")
                    ->orWhere('karyawan.nama_karyawan', 'LIKE', "%$search%")
                    ->orWhereDate('demosi_promosi_pangkat.tanggal_pengesahan', $search)
                    ->orWhere('newPos.nama_jabatan',  'LIKE', "%$search%")
                    ->orWhere('oldPos.nama_jabatan',  'LIKE', "%$search%")
                    ->orWhere('div_lama.nama_divisi',  'LIKE', "%$search%")
                    ->orWhere('sub_div_lama.nama_subdivisi',  'LIKE', "%$search%")
                    ->orWhere('cabang_lama.nama_cabang',  'LIKE', "%$search%")
                    ->orWhere('div_baru.nama_divisi',  'LIKE', "%$search%")
                    ->orWhere('sub_div_baru.nama_subdivisi',  'LIKE', "%$search%")
                    ->orWhere('cabang_baru.nama_cabang',  'LIKE', "%$search%")
                    ->orWhere('demosi_promosi_pangkat.bukti_sk',  'LIKE', "%$search%");
            })
            ->orderBy('tanggal_pengesahan', 'desc')
            ->paginate($limit);

        foreach($data as $key => $value) {
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
}