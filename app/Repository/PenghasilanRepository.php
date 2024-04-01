<?php

namespace App\Repository;

use App\Helpers\HitungPPH;
use App\Http\Controllers\KaryawanController;
use App\Models\KaryawanModel;
use App\Models\PtkpModel;
use Illuminate\Support\Facades\DB;
use stdClass;

class PenghasilanRepository
{
    private $orderRaw;
    public function __construct() {
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
    
    public function listPenghasilan($cabang, $status, $limit = 10, $page = 1, $search, $bulanFilter, $tahunFilter) {
        $months = array(
            1 => "Januari",
            2 => "Februari",
            3 => "Maret",
            4 => "April",
            5 => "Mei",
            6 => "Juni",
            7 => "Juli",
            8 => "Agustus",
            9 => "September",
            10 => "Oktober",
            11 => "November",
            12 => "Desember"
        );
        // return [$cabang, $status, $limit, $search];
        $return = [];
        $data = DB::table('batch_gaji_per_bulan AS batch')
                ->join('gaji_per_bulan AS gaji', 'gaji.batch_id', 'batch.id')
                ->join('pph_yang_dilunasi AS pph', 'pph.gaji_per_bulan_id', 'gaji.id')
                ->join('mst_karyawan AS m', 'm.nip', 'gaji.nip')
                ->leftJoin('mst_divisi AS md', 'md.kd_divisi', 'm.kd_entitas')
                ->join('mst_cabang AS cab', 'cab.kd_cabang', 'batch.kd_entitas')
                ->select(
                    'batch.id',
                    'batch.kd_entitas',
                    'batch.is_pegawai',
                    'cab.nama_cabang AS kantor',
                    'batch.tanggal_input',
                    'batch.tanggal_final',
                    'batch.tanggal_cetak',
                    'batch.tanggal_upload',
                    'batch.file',
                    'batch.status',
                    'gaji.bulan',
                    'gaji.tahun',
                    'gaji.dpp',
                    'gaji.jp',
                    'gaji.bpjs_tk',
                    'gaji.penambah_bruto_jamsostek',
                    DB::raw('CAST(SUM(pph.total_pph) AS SIGNED) AS total_pph'),
                    DB::raw('CAST(SUM(pph.insentif_kredit + pph.insentif_penagihan) AS SIGNED) AS total_pajak_insentif'),
                    DB::raw('CAST(SUM(pph.total_pph) - SUM(pph.insentif_kredit + pph.insentif_penagihan) AS SIGNED) AS hasil_pph'),
                    DB::raw('CAST(SUM(gaji.gj_pokok + gaji.gj_penyesuaian + gaji.tj_keluarga + gaji.tj_telepon + gaji.tj_jabatan + gaji.tj_teller + gaji.tj_perumahan + gaji.tj_kemahalan + gaji.tj_pelaksana + gaji.tj_kesejahteraan + gaji.tj_multilevel + gaji.tj_ti + gaji.tj_fungsional) AS SIGNED) AS bruto'),
                    DB::raw('CAST(SUM(gaji.kredit_koperasi + gaji.iuran_koperasi + gaji.kredit_pegawai + gaji.iuran_ik + gaji.dpp + gaji.bpjs_tk) AS SIGNED) AS total_potongan'),
                    DB::raw('CAST(SUM(gaji.gj_pokok + gaji.gj_penyesuaian + gaji.tj_keluarga + gaji.tj_telepon + gaji.tj_jabatan + gaji.tj_teller + gaji.tj_perumahan + gaji.tj_kemahalan + gaji.tj_pelaksana + gaji.tj_kesejahteraan + gaji.tj_multilevel + gaji.tj_ti + gaji.tj_fungsional) - SUM(gaji.kredit_koperasi + gaji.iuran_koperasi + gaji.kredit_pegawai + gaji.iuran_ik + gaji.dpp + gaji.bpjs_tk) AS SIGNED) AS netto'),
                    'm.kd_entitas AS entitas_karyawan',
                    'md.nama_divisi',
                )
                ->where(function ($query) use ($search) {
                    if ($search != null) {
                        $query->where('gaji.tahun', 'like', "%$search%")
                            ->orWhere('cab.nama_cabang', 'like', "%$search%");
                    }
                })
                ->where(function ($query) use ($cabang) {
                    if ($cabang != null) {
                        $query->where('batch.kd_entitas', $cabang);
                    }
                })
                ->where(function ($query) use ($bulanFilter, $tahunFilter) {
                    if($bulanFilter != null) {
                        $query->whereMonth('batch.tanggal_input', $bulanFilter);
                    } else if($tahunFilter != null) {
                        $query->whereYear('batch.tanggal_input', $tahunFilter);
                    } else if($bulanFilter != null && $bulanFilter != null) {
                        $query->whereMonth('batch.tanggal_input', $bulanFilter)
                            ->whereYear('batch.tanggal_input', $tahunFilter);
                    }
                })
                ->where('batch.status', $status)
                ->whereNull('batch.deleted_at')
                ->orderBy('batch.kd_entitas')
                ->groupBy('batch.kd_entitas')
                ->groupBy('batch.id')
                ->groupBy('gaji.bulan')
                ->groupBy('gaji.tahun')
                ->simplePaginate($limit);

        foreach ($data as $item) {
            $kd_entitas = $item->kd_entitas;
            $tanggal = $item->tanggal_input;
            $day = date('d', strtotime($tanggal));
            // Cek apakah ada perubahan data penghasilan
            $total_penyesuaian = 0;
            if ($item->status == 'proses') {
                $data_gaji = DB::table('gaji_per_bulan AS gaji')
                                ->select(
                                    'gaji.*',
                                    'm.nama_karyawan',
                                    DB::raw('CAST((gaji.gj_pokok + gaji.gj_penyesuaian + gaji.tj_keluarga + gaji.tj_telepon + gaji.tj_jabatan + gaji.tj_teller + gaji.tj_perumahan + gaji.tj_kemahalan + gaji.tj_pelaksana + gaji.tj_kesejahteraan + gaji.tj_multilevel + gaji.tj_ti + gaji.tj_fungsional) AS SIGNED) AS total_penghasilan'),
                                    DB::raw('CAST((gaji.kredit_koperasi + gaji.iuran_koperasi + gaji.kredit_pegawai + gaji.iuran_ik) AS SIGNED) AS total_potongan')
                                )
                                ->join('mst_karyawan AS m', 'm.nip', 'gaji.nip')
                                ->where('gaji.batch_id', $item->id)
                                ->get();

                foreach ($data_gaji as $gaji) {
                    $tahun = $gaji->tahun;
                    $bulan = $gaji->bulan;
                    $karyawan = DB::table('mst_karyawan')
                                ->where('nip', $gaji->nip)
                                ->first();
                    if ($gaji->gj_pokok != $karyawan->gj_pokok) {
                        $total_penyesuaian++;
                    }
                    if ($gaji->gj_penyesuaian != $karyawan->gj_penyesuaian) {
                        $total_penyesuaian++;
                    }

                    $tunjangan = DB::table('tunjangan_karyawan')
                                    ->where('nip', $gaji->nip)
                                    ->get();
                    foreach ($tunjangan as $tunj) {
                        // Keluarga
                        if ($tunj->id_tunjangan == 1) {
                            if ($gaji->tj_keluarga != $tunj->nominal) {
                                $total_penyesuaian++;
                            }
                        }
                        // Telepon
                        if ($tunj->id_tunjangan == 2) {
                            if ($gaji->tj_telepon != $tunj->nominal) {
                                $total_penyesuaian++;
                            }
                        }
                        // Jabatan
                        if ($tunj->id_tunjangan == 3) {
                            if ($gaji->tj_jabatan != $tunj->nominal) {
                                $total_penyesuaian++;
                            }
                        }
                        // Teller
                        if ($tunj->id_tunjangan == 4) {
                            if ($gaji->tj_teller != $tunj->nominal) {
                                $total_penyesuaian++;
                            }
                        }
                        // Perumahan
                        if ($tunj->id_tunjangan == 5) {
                            if ($gaji->tj_perumahan != $tunj->nominal) {
                                $total_penyesuaian++;
                            }
                        }
                        // Kemahalan
                        if ($tunj->id_tunjangan == 6) {
                            if ($gaji->tj_kemahalan != $tunj->nominal) {
                                $total_penyesuaian++;
                            }
                        }
                        // Pelaksana
                        if ($tunj->id_tunjangan == 7) {
                            if ($gaji->tj_pelaksana != $tunj->nominal) {
                                $total_penyesuaian++;
                            }
                        }
                        // Kesejahteraan
                        if ($tunj->id_tunjangan == 8) {
                            if ($gaji->tj_kesejahteraan != $tunj->nominal) {
                                $total_penyesuaian++;
                            }
                        }
                        // Multilevel
                        if ($tunj->id_tunjangan == 9) {
                            if ($gaji->tj_multilevel != $tunj->nominal) {
                                $total_penyesuaian++;
                            }
                        }
                        // TI
                        if ($tunj->id_tunjangan == 10) {
                            if ($gaji->tj_ti != $tunj->nominal) {
                                $total_penyesuaian++;
                            }
                        }
                    }

                    $transaksi_tunjangan = DB::table('transaksi_tunjangan')
                                            ->where('nip', $gaji->nip)
                                            ->whereYear('tanggal', $gaji->tahun)
                                            ->where(function($query) use ($tahun, $bulan, $tanggal, $day, $kd_entitas) {
                                                if ($bulan > 1) {
                                                    // Tanggal penggajian bulan sebelumnya
                                                    $start_date = $this->getDatePenggajianSebelumnya($tanggal, $kd_entitas);
                                                    $query->whereBetween('tanggal', [$start_date, $tanggal]);
                                                }
                                                else if ($bulan == 12) {
                                                    $start_date = $this->getDatePenggajianSebelumnya($tanggal, $kd_entitas);
                                                    $last_day = $this->getLastDateOfMonth($tahun, $bulan);
                                                    $end_date = $tahun.'-'.$bulan.'-'.$last_day;
                                                    $query->whereBetween('tanggal', [$start_date, $end_date]);
                                                }
                                                else {
                                                    $query->whereDay('tanggal', '<=', $day);
                                                }
                                            })
                                            ->get();
                    foreach ($transaksi_tunjangan as $tunj) {
                        // Transport
                        if ($tunj->id_tunjangan == 11) {
                            if ($gaji->tj_transport != $tunj->nominal) {
                                $total_penyesuaian++;
                            }
                        }
                        // Pulsa
                        if ($tunj->id_tunjangan == 12) {
                            if ($gaji->tj_pulsa != $tunj->nominal) {
                                $total_penyesuaian++;
                            }
                        }
                        // Vitamin
                        if ($tunj->id_tunjangan == 13) {
                            if ($gaji->tj_vitamin != $tunj->nominal) {
                                $total_penyesuaian++;
                            }
                        }
                        // Uang Makan
                        if ($tunj->id_tunjangan == 14) {
                            if ($gaji->uang_makan != $tunj->nominal) {
                                $total_penyesuaian++;
                            }
                        }
                    }

                    // Get Penghasilan Tidak Rutin
                    $penghasilanTidakRutin = DB::table('penghasilan_tidak_teratur')
                                                ->select('id', 'id_tunjangan', 'nominal')
                                                ->where('nip', $gaji->nip)
                                                ->where('tahun', (int) $gaji->tahun)
                                                ->where(function($query) use ($tahun, $bulan, $tanggal, $day, $kd_entitas) {
                                                    if ($bulan > 1) {
                                                        // Tanggal penggajian bulan sebelumnya
                                                        $start_date = $this->getDatePenggajianSebelumnya($tanggal, $kd_entitas);
                                                        $query->whereBetween('created_at', [$start_date, $tanggal]);
                                                    }
                                                    else if ($bulan == 12) {
                                                        $start_date = $this->getDatePenggajianSebelumnya($tanggal, $kd_entitas);
                                                        $last_day = $this->getLastDateOfMonth($tahun, $bulan);
                                                        $end_date = $tahun.'-'.$bulan.'-'.$last_day;
                                                        $query->whereBetween('created_at', [$start_date, $end_date]);
                                                    }
                                                    else {
                                                        $query->whereDay('created_at', '<=', $day);
                                                    }
                                                })
                                                ->get();
                    foreach ($penghasilanTidakRutin as $tidakRutin) {
                        $current = DB::table('batch_penghasilan_tidak_teratur')
                                    ->where('gaji_per_bulan_id', $gaji->id)
                                    ->where('penghasilan_tidak_teratur_id', $tidakRutin->id)
                                    ->first();
                        if ($current) {
                            $nominalLama = $current->nominal;
                            $nominalBaru = $tidakRutin->nominal;
                            if ($nominalLama != $nominalBaru) {
                                $total_penyesuaian++;
                            }
                        }
                        else {
                            $total_penyesuaian++;
                        }
                    }

                    // Get Batch Penghasilan Tidak Rutin
                    $batchPenghasilanTidakRutin = DB::table('batch_penghasilan_tidak_teratur AS p')
                                                    ->select(
                                                        'p.id',
                                                        'p.penghasilan_tidak_teratur_id',
                                                        'p.id_tunjangan',
                                                        'm.nama_tunjangan',
                                                        'p.nominal',
                                                    )
                                                    ->join('mst_tunjangan AS m', 'm.id', 'p.id_tunjangan')
                                                    ->where('p.gaji_per_bulan_id', $gaji->id)
                                                    ->get();
                    foreach ($batchPenghasilanTidakRutin as $batchTidakRutin) {
                        $current = DB::table('penghasilan_tidak_teratur')
                                        ->where('id', $batchTidakRutin->penghasilan_tidak_teratur_id)
                                        ->first();
                        if (!$current) {
                            $total_penyesuaian++;
                        }
                    }

                    // Get Potongan
                    $potongan = DB::table('potongan_gaji')
                                    ->where('nip', $gaji->nip)
                                    ->first();

                    if ($potongan) {
                        if ($potongan->kredit_koperasi != $gaji->kredit_koperasi) {
                            $total_penyesuaian++;
                        }
                        if ($potongan->iuran_koperasi != $gaji->iuran_koperasi) {
                            $total_penyesuaian++;
                        }
                        if ($potongan->kredit_pegawai != $gaji->kredit_pegawai) {
                            $total_penyesuaian++;
                        }
                        if ($potongan->iuran_ik != $gaji->iuran_ik) {
                            $total_penyesuaian++;
                        }
                    }
                }
            }
            $item->total_penyesuaian = $total_penyesuaian;

            $returnData = new stdClass;
            $returnData->id = $item->id;
            $returnData->kategori = $item->nama_divisi ?? 'Pegawai';
            $returnData->kantor = $item->kantor;
            $returnData->tahun = $item->tahun;
            $returnData->bulan = $months[(int) $item->bulan];
            $returnData->tanggal = $item->tanggal_input;
            $returnData->bruto = $item->bruto ?? 0;
            $returnData->total_potongan = $item->total_potongan ?? 0;
            $returnData->netto = $item->netto ?? 0;
            $returnData->total_pph = $item->total_pph ?? 0;
            $returnData->total_pajak_insentif = $item->total_pajak_insentif ?? 0;
            $returnData->hasil_pph = $item->hasil_pph ?? 0;
            array_push($return, $returnData);
        }

        return $return;
    }

    public function detailPenghasilan($id, $search) {
        $data = DB::table('batch_gaji_per_bulan AS batch')
                ->join('gaji_per_bulan AS gaji', 'gaji.batch_id', 'batch.id')
                ->join('pph_yang_dilunasi AS pph', 'pph.gaji_per_bulan_id', 'gaji.id')
                ->join('mst_karyawan AS m', 'm.nip', 'gaji.nip')
                ->leftJoin('mst_divisi AS md', 'md.kd_divisi', 'm.kd_entitas')
                ->join('mst_cabang AS cab', 'cab.kd_cabang', 'batch.kd_entitas')
                ->select(
                    'gaji.nip',
                    'm.nama_karyawan',
                    'gaji.bulan',
                    'gaji.tahun',
                    'gaji.dpp',
                    'gaji.jp',
                    'gaji.bpjs_tk',
                    'gaji.penambah_bruto_jamsostek',
                    DB::raw('CAST(pph.insentif_kredit + pph.insentif_penagihan AS SIGNED) AS total_pajak_insentif'),
                    DB::raw('CAST(pph.total_pph - pph.insentif_kredit + pph.insentif_penagihan AS SIGNED) AS hasil_pph'),
                    DB::raw('CAST(gaji.gj_pokok + gaji.gj_penyesuaian + gaji.tj_keluarga + gaji.tj_telepon + gaji.tj_jabatan + gaji.tj_teller + gaji.tj_perumahan + gaji.tj_kemahalan + gaji.tj_pelaksana + gaji.tj_kesejahteraan + gaji.tj_multilevel + gaji.tj_ti + gaji.tj_fungsional AS SIGNED) AS bruto'),
                    DB::raw('CAST(gaji.kredit_koperasi + gaji.iuran_koperasi + gaji.kredit_pegawai + gaji.iuran_ik + gaji.dpp + gaji.bpjs_tk AS SIGNED) AS total_potongan'),
                    DB::raw('CAST(gaji.gj_pokok + gaji.gj_penyesuaian + gaji.tj_keluarga + gaji.tj_telepon + gaji.tj_jabatan + gaji.tj_teller + gaji.tj_perumahan + gaji.tj_kemahalan + gaji.tj_pelaksana + gaji.tj_kesejahteraan + gaji.tj_multilevel + gaji.tj_ti + gaji.tj_fungsional - (gaji.kredit_koperasi + gaji.iuran_koperasi + gaji.kredit_pegawai + gaji.iuran_ik + gaji.dpp + gaji.bpjs_tk) AS SIGNED) AS netto'),
                )
                ->where('batch.id', $id)
                ->where(function ($query) use ($search) {
                    if($search != null){
                        $query->where('m.nama_karyawan', 'like', "%$search%");
                    }
                })
                ->simplePaginate(10);

        return $data->items();
    }

    public function getRincianPayroll($month, $year, $batch_id, $nip, $kategori) {
        $returnData = new stdClass;
        $batch = DB::table('batch_gaji_per_bulan')->find($batch_id);
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

        $data = KaryawanModel::with([
                                'keluarga' => function($query) {
                                    $query->select(
                                        'nip',
                                        'enum',
                                        'nama AS nama_keluarga',
                                        'jml_anak',
                                        DB::raw("
                                            IF(jml_anak > 3,
                                                'K/3',
                                                IF(jml_anak IS NOT NULL, CONCAT('K/', jml_anak), 'K/0')) AS status_kawin
                                        ")
                                    )
                                    ->whereIn('enum', ['Suami', 'Istri']);
                                },
                                'gaji' => function($query) use ($batch_id) {
                                    $query->select(
                                        'gaji_per_bulan.id',
                                        'nip',
                                        DB::raw("CAST(bulan AS SIGNED) AS bulan"),
                                        DB::raw("CAST(tahun AS SIGNED) AS tahun"),
                                        'gj_pokok',
                                        'gj_penyesuaian',
                                        'tj_keluarga',
                                        'tj_telepon',
                                        'tj_jabatan',
                                        'tj_teller',
                                        'tj_perumahan',
                                        'tj_kemahalan',
                                        'tj_pelaksana',
                                        'tj_kesejahteraan',
                                        'tj_multilevel',
                                        'tj_ti',
                                        'tj_fungsional',
                                        DB::raw('(tj_ti + tj_multilevel + tj_fungsional) AS tj_khusus'),
                                        'tj_transport',
                                        'tj_pulsa',
                                        'tj_vitamin',
                                        'uang_makan',
                                        'dpp',
                                        'jp',
                                        'bpjs_tk',
                                        'penambah_bruto_jamsostek',
                                        DB::raw("(gj_pokok + gj_penyesuaian + tj_keluarga + tj_telepon + tj_jabatan + tj_teller + tj_perumahan  + tj_kemahalan + tj_pelaksana + tj_kesejahteraan + tj_multilevel + tj_ti + tj_fungsional + tj_transport + tj_pulsa + tj_vitamin + uang_makan) AS gaji"),
                                        DB::raw("(gj_pokok + gj_penyesuaian + tj_keluarga + tj_jabatan + tj_teller + tj_perumahan + tj_telepon + tj_pelaksana + tj_kemahalan + tj_kesejahteraan + tj_multilevel + tj_ti + tj_fungsional) AS total_gaji"),
                                        DB::raw("(uang_makan + tj_transport + tj_pulsa + tj_vitamin) AS tunjangan_rutin"),
                                        'kredit_koperasi',
                                        'iuran_koperasi',
                                        'kredit_pegawai',
                                        'iuran_ik',
                                        DB::raw('(kredit_koperasi + iuran_koperasi + kredit_pegawai + iuran_ik) AS total_potongan'),
                                    )
                                    ->join('batch_gaji_per_bulan AS batch', 'batch.id', 'gaji_per_bulan.batch_id')
                                    ->whereNull('batch.deleted_at')
                                    ->where('batch.id', $batch_id);
                                },
                                'tunjangan' => function($query) use ($month, $year) {
                                    $query->whereMonth('tunjangan_karyawan.created_at', $month)
                                        ->whereYear('tunjangan_karyawan.created_at', $year);
                                },
                                'tunjanganTidakTetap' => function($query) use ($year, $month) {
                                    $query->select(
                                            'penghasilan_tidak_teratur.id',
                                            'penghasilan_tidak_teratur.nip',
                                            'penghasilan_tidak_teratur.id_tunjangan',
                                            'mst_tunjangan.nama_tunjangan',
                                            DB::raw('CAST(SUM(penghasilan_tidak_teratur.nominal) AS SIGNED) AS nominal'),
                                            'penghasilan_tidak_teratur.kd_entitas',
                                            'penghasilan_tidak_teratur.tahun',
                                            'penghasilan_tidak_teratur.bulan',
                                        )
                                        ->where('mst_tunjangan.kategori', 'tidak teratur')
                                        ->where('penghasilan_tidak_teratur.tahun', $year)
                                        ->where('penghasilan_tidak_teratur.bulan', $month)
                                        ->groupBy('penghasilan_tidak_teratur.id_tunjangan');
                                },
                                'bonus' => function($query) use ($year, $month) {
                                    $query->select(
                                            'penghasilan_tidak_teratur.id',
                                            'penghasilan_tidak_teratur.nip',
                                            'penghasilan_tidak_teratur.id_tunjangan',
                                            'mst_tunjangan.nama_tunjangan',
                                            DB::raw('CAST(SUM(penghasilan_tidak_teratur.nominal) AS SIGNED) AS nominal'),
                                            'penghasilan_tidak_teratur.kd_entitas',
                                            'penghasilan_tidak_teratur.tahun',
                                            'penghasilan_tidak_teratur.bulan',
                                        )
                                        ->where('mst_tunjangan.kategori', 'bonus')
                                        ->where('penghasilan_tidak_teratur.tahun', $year)
                                        ->where('penghasilan_tidak_teratur.bulan', $month)
                                        ->groupBy('penghasilan_tidak_teratur.id_tunjangan');
                                },
                                'potonganGaji' => function($query) use ($month, $year) {
                                    $query->select(
                                        'potongan_gaji.nip',
                                        DB::raw('potongan_gaji.kredit_koperasi AS kredit_koperasi'),
                                        DB::raw('potongan_gaji.iuran_koperasi AS iuran_koperasi'),
                                        DB::raw('potongan_gaji.kredit_pegawai AS kredit_pegawai'),
                                        DB::raw('potongan_gaji.iuran_ik AS iuran_ik'),
                                        DB::raw('(potongan_gaji.kredit_koperasi + potongan_gaji.iuran_koperasi + potongan_gaji.kredit_pegawai + potongan_gaji.iuran_ik) AS total_potongan'),
                                    );
                                }
                            ])
                            ->select(
                                'mst_karyawan.nip',
                                'mst_karyawan.kd_entitas',
                                'mst_karyawan.gj_pokok',
                                'mst_karyawan.gj_penyesuaian',
                                'mst_karyawan.status_ptkp',
                                'nama_karyawan',
                                'npwp',
                                'no_rekening',
                                'tanggal_penonaktifan',
                                'kpj',
                                'jkn',
                                DB::raw("
                                    IF(
                                        mst_karyawan.status = 'Kawin',
                                        'K',
                                        IF(
                                            mst_karyawan.status = 'Belum Kawin',
                                            'TK/0',
                                            mst_karyawan.status
                                        )
                                    ) AS status
                                "),
                                'mst_karyawan.status_karyawan',
                                DB::raw("IF((SELECT m.kd_entitas FROM mst_karyawan AS m WHERE m.nip = `mst_karyawan`.`nip` AND m.kd_entitas IN(SELECT mst_cabang.kd_cabang FROM mst_cabang) LIMIT 1), 1, 0) AS status_kantor"),
                                'batch.tanggal_input'
                            )
                            ->join('gaji_per_bulan', 'gaji_per_bulan.nip', 'mst_karyawan.nip')
                            ->join('batch_gaji_per_bulan AS batch', 'batch.id', 'gaji_per_bulan.batch_id')
                            ->join('mst_cabang AS c', 'c.kd_cabang', 'batch.kd_entitas')
                            ->orderByRaw("
                                CASE WHEN mst_karyawan.kd_jabatan='PIMDIV' THEN 1
                                WHEN mst_karyawan.kd_jabatan='PSD' THEN 2
                                WHEN mst_karyawan.kd_jabatan='PC' THEN 3
                                WHEN mst_karyawan.kd_jabatan='PBP' THEN 4
                                WHEN mst_karyawan.kd_jabatan='PBO' THEN 5
                                WHEN mst_karyawan.kd_jabatan='PEN' THEN 6
                                WHEN mst_karyawan.kd_jabatan='ST' THEN 7
                                WHEN mst_karyawan.kd_jabatan='NST' THEN 8
                                WHEN mst_karyawan.kd_jabatan='IKJP' THEN 9 END ASC
                            ")
                            ->whereNull('batch.deleted_at')
                            ->whereRaw("(tanggal_penonaktifan IS NULL OR ($month = MONTH(tanggal_penonaktifan) AND is_proses_gaji = 1))")
                            ->where('gaji_per_bulan.nip', $nip)
                            ->orderByRaw($this->orderRaw)
                            ->orderBy('status_kantor', 'asc')
                            ->orderBy('kd_cabang', 'asc')
                            ->orderBy('nip', 'asc')
                            ->orderBy('batch.kd_entitas')
                            ->get();

        foreach ($data as $key => $karyawan) {
            $insentif = DB::table('pph_yang_dilunasi')
                        ->select(
                            'insentif_kredit',
                            'insentif_penagihan',
                            DB::raw('CAST((insentif_kredit + insentif_penagihan) AS SIGNED) AS total_pajak_insentif')
                        )
                        ->where('nip', $karyawan->nip)
                        ->where('bulan', (int) $month)
                        ->where('tahun', (int) $year)
                        ->first();
            $karyawan->insentif = $insentif;
            $ptkp = null;
            if ($karyawan->keluarga) {
                $ptkp = PtkpModel::select('id', 'kode', 'ptkp_bulan', 'ptkp_tahun', 'keterangan')
                                ->where('kode', $karyawan->keluarga->status_kawin)
                                ->first();
            }
            else {
                $ptkp = PtkpModel::select('id', 'kode', 'ptkp_bulan', 'ptkp_tahun', 'keterangan')
                                ->where('kode', 'TK/0')
                                ->first();
            }
            $karyawan->ptkp = $ptkp;

            $nominal_jp = 0;
            $jamsostek = 0;
            $bpjs_tk = 0;
            $bpjs_kesehatan = 0;
            $potongan = new \stdClass();
            $total_gaji = 0;
            $tunjangan_rutin = 0;
            $total_potongan = 0;
            $penghasilan_rutin = 0;
            $penghasilan_tidak_rutin = 0;
            $penghasilan_tidak_teratur = 0;
            $bonus = 0;
            $tunjangan_teratur_import = 0; // Uang makan, vitamin, transport & pulsa

            if ($karyawan->gaji) {
                // Get BPJS TK * Kesehatan
                $obj_gaji = $karyawan->gaji;
                $gaji = floor($obj_gaji->gaji);
                $total_gaji = floor($obj_gaji->total_gaji);
                $tunjangan_rutin = floor($obj_gaji->tunjangan_rutin);

                $jamsostek = $obj_gaji->penambah_bruto_jamsostek;

                // Get Potongan(JP1%, DPP 5%)
                $potongan->dpp = $obj_gaji->dpp;
                $potongan->jp_1_persen = $obj_gaji->jp;

                // Get BPJS TK
                $bpjs_tk = $obj_gaji->bpjs_tk;

                // Penghasilan rutin
                $penghasilan_rutin = $gaji + $jamsostek;
            }

            $karyawan->jamsostek = $jamsostek;
            $karyawan->bpjs_tk = $bpjs_tk;
            $karyawan->bpjs_kesehatan = $bpjs_kesehatan;
            $karyawan->potongan = $potongan;

            // Get total penghasilan tidak teratur
            if ($karyawan->tunjanganTidakTetap) {
                $tunjangan_tidak_tetap = $karyawan->tunjanganTidakTetap;
                foreach ($tunjangan_tidak_tetap as $key => $value) {
                    $penghasilan_tidak_teratur += $value->pivot->nominal;
                }
            }

            // Get total bonus
            if ($karyawan->bonus) {
                $bonus_item = $karyawan->bonus;
                foreach ($bonus_item as $key => $value) {
                    $bonus += $value->pivot->nominal;
                }
            }

            // Penghasilan tidak rutin
            $penghasilan_tidak_rutin = $penghasilan_tidak_teratur + $bonus;

            // Get total potongan
            if ($karyawan->gaji) {
                $total_potongan += $karyawan->gaji->total_potongan;
            }

            if ($karyawan->potongan) {
                $total_potongan += $karyawan->potongan->dpp;
            }

            $total_potongan += $bpjs_tk;
            $total_potongan = floor($total_potongan);
            $karyawan->total_potongan = $total_potongan;

            // Get total yg diterima
            $total_yg_diterima = $total_gaji - $total_potongan;
            $karyawan->total_yg_diterima = $total_yg_diterima;

            $karyawan->penghasilan_rutin = $penghasilan_rutin;
            $karyawan->penghasilan_tidak_rutin = $penghasilan_tidak_rutin;

            // Get Penghasilan bruto
            $month_on_year = 12;
            $month_paided_arr = [];
            $month_on_year_paid = 0;
            $penghasilanBruto = new \stdClass();
            $penguranganPenghasilan = new \stdClass();
            $pph_dilunasi = 0;

            $karyawan_bruto = KaryawanModel::with([
                                                'allGajiByKaryawan' => function($query) use ($karyawan, $year) {
                                                    $query->select(
                                                        'nip',
                                                        DB::raw("CAST(bulan AS SIGNED) AS bulan"),
                                                        'gj_pokok',
                                                        'tj_keluarga',
                                                        'tj_kesejahteraan',
                                                        DB::raw("(gj_pokok + gj_penyesuaian + tj_keluarga + tj_telepon + tj_jabatan + tj_teller + tj_perumahan  + tj_kemahalan + tj_pelaksana + tj_kesejahteraan + tj_multilevel + tj_ti + tj_transport + tj_pulsa + tj_vitamin + uang_makan) AS gaji"),
                                                        DB::raw("(gj_pokok + gj_penyesuaian + tj_keluarga + tj_jabatan + tj_teller + tj_perumahan + tj_telepon + tj_pelaksana + tj_kemahalan + tj_kesejahteraan + tj_multilevel + tj_ti + tj_fungsional) AS total_gaji"),
                                                        DB::raw("(uang_makan + tj_vitamin + tj_pulsa + tj_transport) AS total_tunjangan_lainnya"),
                                                    )
                                                    ->where('nip', $karyawan->nip)
                                                    ->where('tahun', $year)
                                                    ->orderBy('bulan')
                                                    ->groupBy('bulan');
                                                },
                                                'sumBonusKaryawan' => function($query) use ($karyawan, $year) {
                                                    $query->select(
                                                        'penghasilan_tidak_teratur.nip',
                                                        'mst_tunjangan.kategori',
                                                        DB::raw("SUM(penghasilan_tidak_teratur.nominal) AS total"),
                                                    )
                                                        ->where('penghasilan_tidak_teratur.nip', $karyawan->nip)
                                                        ->where('mst_tunjangan.kategori', 'bonus')
                                                        ->where('penghasilan_tidak_teratur.tahun', $year)
                                                        ->groupBy('penghasilan_tidak_teratur.nip');
                                                },
                                                'sumTunjanganTidakTetapKaryawan' => function($query) use ($karyawan, $year) {
                                                    $query->select(
                                                            'penghasilan_tidak_teratur.nip',
                                                            'mst_tunjangan.kategori',
                                                            DB::raw("SUM(penghasilan_tidak_teratur.nominal) AS total"),
                                                        )
                                                        ->where('penghasilan_tidak_teratur.nip', $karyawan->nip)
                                                        ->where('mst_tunjangan.kategori', 'tidak teratur')
                                                        ->where('penghasilan_tidak_teratur.tahun', $year)
                                                        ->groupBy('penghasilan_tidak_teratur.nip');
                                                },
                                                'pphDilunasi' => function($query) use ($karyawan, $year) {
                                                    $query->select(
                                                        'nip',
                                                        DB::raw("CAST(bulan AS SIGNED) AS bulan"),
                                                        DB::raw("CAST(tahun AS SIGNED) AS tahun"),
                                                        DB::raw('SUM(total_pph) AS nominal'),
                                                        DB::raw('CAST(SUM(insentif_kredit) AS SIGNED) AS insentif_kredit'),
                                                        DB::raw('CAST(SUM(insentif_penagihan) AS SIGNED) AS insentif_penagihan'),
                                                        DB::raw('CAST((SUM(insentif_kredit) + SUM(insentif_penagihan)) AS SIGNED) AS total_pajak_insentif')
                                                    )
                                                    ->where('tahun', $year)
                                                    ->where('nip', $karyawan->nip)
                                                    ->where('gaji_per_bulan_id', $karyawan->gaji->id)
                                                    ->groupBy('bulan');
                                                }
                                            ])
                                            ->select(
                                                'nip',
                                                'nama_karyawan',
                                                'no_rekening',
                                                'tanggal_penonaktifan',
                                                'kpj',
                                                'jkn',
                                                'status_karyawan',
                                            )
                                            ->leftJoin('mst_cabang AS c', 'c.kd_cabang', 'kd_entitas')
                                            ->where(function($query) use ($karyawan) {
                                                $query->whereRelation('allGajiByKaryawan', 'nip', $karyawan->nip);
                                            })
                                            ->orderByRaw("
                                                CASE WHEN mst_karyawan.kd_jabatan='PIMDIV' THEN 1
                                                WHEN mst_karyawan.kd_jabatan='PSD' THEN 2
                                                WHEN mst_karyawan.kd_jabatan='PC' THEN 3
                                                WHEN mst_karyawan.kd_jabatan='PBP' THEN 4
                                                WHEN mst_karyawan.kd_jabatan='PBO' THEN 5
                                                WHEN mst_karyawan.kd_jabatan='PEN' THEN 6
                                                WHEN mst_karyawan.kd_jabatan='ST' THEN 7
                                                WHEN mst_karyawan.kd_jabatan='NST' THEN 8
                                                WHEN mst_karyawan.kd_jabatan='IKJP' THEN 9 END ASC
                                            ")
                                            ->first();

            if ($karyawan_bruto) {
                $gaji_bruto = 0;
                $tunjangan_lainnya_bruto = 0;
                // Get jamsostek
                if ($karyawan_bruto->allGajiByKaryawan) {
                    $allGajiByKaryawan = $karyawan_bruto->allGajiByKaryawan;
                    $total_gaji_bruto = 0;
                    $total_jamsostek = 0;
                    $total_pengurang_bruto = 0;
                    foreach ($allGajiByKaryawan as $key => $value) {
                        array_push($month_paided_arr, intval($value->bulan));
                        $gaji_bruto += $value->total_gaji ? intval($value->total_gaji) : 0;
                        $tunjangan_lainnya_bruto += $value->total_tunjangan_lainnya ? intval($value->total_tunjangan_lainnya) : 0;
                        $total_gaji = $value->total_gaji ? intval($value->total_gaji) : 0;
                        $total_gaji_bruto += $total_gaji;
                        $pengurang_bruto = 0;

                        // Get jamsostek
                        if($total_gaji > 0){
                            $jkk = 0;
                            $jht = 0;
                            $jkm = 0;
                            $jp_penambah = 0;
                            $bpjs_kesehatan = 0;
                            if(!$karyawan_bruto->tanggal_penonaktifan && $karyawan_bruto->kpj){
                                $jkk = floor(($persen_jkk / 100) * $total_gaji);
                                $jht = floor(($persen_jht / 100) * $total_gaji);
                                $jkm = floor(($persen_jkm / 100) * $total_gaji);
                                $jp_penambah = floor(($persen_jp_penambah / 100) * $total_gaji);
                            }

                            if($karyawan_bruto->jkn){
                                if($total_gaji > $batas_atas){
                                    $bpjs_kesehatan = floor($batas_atas * ($persen_kesehatan / 100));
                                } else if($total_gaji < $batas_bawah){
                                    $bpjs_kesehatan = floor($batas_bawah * ($persen_kesehatan / 100));
                                } else{
                                    $bpjs_kesehatan = floor($total_gaji * ($persen_kesehatan / 100));
                                }
                            }
                            $jamsostek = $jkk + $jht + $jkm + $bpjs_kesehatan + $jp_penambah;

                            $total_jamsostek += $jamsostek;

                            // Get Potongan(JP1%, DPP 5%)
                            $nominal_jp = ($value->bulan > 2) ? $jp_mar_des : $jp_jan_feb;
                            $dppBruto = 0;
                            $dppBrutoExtra = 0;
                            if($karyawan->status_karyawan == 'IKJP' || $karyawan->status_karyawan == 'Kontrak Perpanjangan') {
                                $dppBrutoExtra = floor(($persen_jp_pengurang / 100) * $total_gaji);
                            } else{
                                $gj_pokok = $value->gj_pokok;
                                $tj_keluarga = $value->tj_keluarga;
                                $tj_kesejahteraan = $value->tj_kesejahteraan;

                                // DPP (Pokok + Keluarga + Kesejahteraan 50%) * 5%
                                $dppBruto = (($gj_pokok + $tj_keluarga) + ($tj_kesejahteraan * 0.5)) * ($persen_dpp / 100);
                                if($total_gaji >= $nominal_jp){
                                    $dppBrutoExtra = floor($nominal_jp * ($persen_jp_pengurang / 100));
                                } else {
                                    $dppBrutoExtra = floor($total_gaji * ($persen_jp_pengurang / 100));
                                }
                            }

                            $pengurang_bruto = intval($dppBruto + $dppBrutoExtra);
                            $total_pengurang_bruto += $pengurang_bruto;
                        }
                        $value->pengurangan_bruto = $pengurang_bruto;
                    }
                    $penguranganPenghasilan->total_pengurangan_bruto = $total_pengurang_bruto;
                    $penghasilanBruto->gaji_pensiun = intval($total_gaji_bruto);
                    $penghasilanBruto->total_jamsostek = intval($total_jamsostek);
                }

                // Get keterangan tunjangan tidak tetap & bonus
                $ketTunjanganTidakTetap = DB::table('penghasilan_tidak_teratur')
                                            ->select('bulan', 'tahun')
                                            ->where('nip', $karyawan->nip)
                                            ->where('tahun', $year)
                                            ->groupBy(['bulan', 'tahun'])
                                            ->pluck('bulan');
                for ($i=0; $i < count($ketTunjanganTidakTetap); $i++) {
                    $i_bulan = $ketTunjanganTidakTetap[$i];
                    if (!in_array($i_bulan, $month_paided_arr)) {
                        array_push($month_paided_arr, intval($i_bulan));
                    }
                }
                $month_on_year_paid = count($month_paided_arr);

                // Get penghasilan tidak teratur
                $total_penghasilan_tidak_teratur_bruto = 0;
                if ($karyawan_bruto->sumTunjanganTidakTetapKaryawan) {
                    $sumTunjanganTidakTetapKaryawan = $karyawan_bruto->sumTunjanganTidakTetapKaryawan;
                    $total_penghasilan_tidak_teratur_bruto = isset($sumTunjanganTidakTetapKaryawan[0]) ? intval($sumTunjanganTidakTetapKaryawan[0]->total) : 0;
                    $penghasilanBruto->total_penghasilan_tidak_teratur = $total_penghasilan_tidak_teratur_bruto;
                }

                // Get total bonus
                $total_bonus_bruto = 0;
                if ($karyawan_bruto->sumBonusKaryawan) {
                    $sumBonusKaryawan = $karyawan_bruto->sumBonusKaryawan;
                    $total_bonus_bruto = isset($sumBonusKaryawan[0]) ? intval($sumBonusKaryawan[0]->total) : 0;
                    $penghasilanBruto->total_bonus = $total_bonus_bruto;
                }

                $penghasilan_rutin_bruto = 0;
                $penghasilan_tidak_rutin_bruto = 0;
                $total_penghasilan = 0; // ( Teratur + Tidak Teratur )

                $penghasilan_rutin_bruto = $gaji_bruto + $tunjangan_lainnya_bruto + $penghasilanBruto->total_jamsostek;
                $penghasilan_tidak_rutin_bruto = $total_penghasilan_tidak_teratur_bruto + $total_bonus_bruto;
                $total_penghasilan = $penghasilan_rutin_bruto + $penghasilan_tidak_rutin_bruto;
                $penghasilanBruto->penghasilan_rutin = $penghasilan_rutin_bruto;
                $penghasilanBruto->penghasilan_tidak_rutin = $penghasilan_tidak_rutin_bruto;
                $penghasilanBruto->total_penghasilan = $total_penghasilan;
                // Get pph dilunasi
                if ($karyawan_bruto->pphDilunasi) {
                    if (count($karyawan_bruto->pphDilunasi) > 0) {
                        $last_index = count($karyawan_bruto->pphDilunasi) - 1;
                        $pph_dilunasi = $karyawan_bruto->pphDilunasi[$last_index]->nominal;
                        $terutang = DB::table('pph_yang_dilunasi')
                                    ->select('terutang')
                                    ->where('nip', $karyawan->nip)
                                    ->where('tahun', $karyawan_bruto->pphDilunasi[$last_index]->tahun)
                                    ->where('bulan', intval($karyawan_bruto->pphDilunasi[$last_index]->bulan - 1))
                                    ->first();
                        if ($terutang) {
                            $pph_dilunasi += $terutang->terutang;
                        }
                    }
                }
                // Get insentif
                $total_pajak_insentif = 0;
                if ($karyawan->insentif) {
                    $total_pajak_insentif = $karyawan->insentif->total_pajak_insentif;
                }
                $pph_db = 0;
                $pphKaryawan = DB::table('pph_yang_dilunasi AS pph')
                                ->select('pph.total_pph AS nominal')
                                ->join('gaji_per_bulan AS gaji', 'gaji.id', 'pph.gaji_per_bulan_id')
                                ->join('batch_gaji_per_bulan AS batch', 'batch.id', 'gaji.batch_id')
                                ->where('pph.tahun', $year)
                                ->where('pph.bulan', $month)
                                ->where('pph.nip', $karyawan->nip)
                                ->whereNull('batch.deleted_at')
                                ->first();
                if ($pphKaryawan) {
                    $pph_db = $pphKaryawan->nominal;
                }
                $karyawan->pph_dilunasi_bulan_ini = (int) $pph_db;
            }

            // Get Tunjangan lainnya (T.Makan, T.Pulsa, T.Transport, T.Vitamin, T.Tidak teratur, Bonus)
            if ($karyawan->tunjangan) {
                $tunjangan = $karyawan->tunjangan;
                foreach ($tunjangan as $key => $value) {
                    $tunjangan_teratur_import += $value->pivot->nominal;
                }
            }
            // $tunjangan_lainnya = $tunjangan_teratur_import + $penghasilan_tidak_rutin;
            $tunjangan_lainnya = $tunjangan_lainnya_bruto;
            $penghasilanBruto->tunjangan_lainnya = $tunjangan_lainnya;

            // Get total penghasilan bruto
            $total_penghasilan_bruto = 0;
            if (property_exists($penghasilanBruto, 'gaji_pensiun')) {
                $total_penghasilan_bruto += $penghasilanBruto->gaji_pensiun;
            }
            if (property_exists($penghasilanBruto, 'total_jamsostek')) {
                $total_penghasilan_bruto += $penghasilanBruto->total_jamsostek;
            }
            if (property_exists($penghasilanBruto, 'total_bonus')) {
                $total_penghasilan_bruto += $penghasilanBruto->total_bonus;
            }
            if (property_exists($penghasilanBruto, 'tunjangan_lainnya')) {
                $total_penghasilan_bruto += $penghasilanBruto->tunjangan_lainnya;
            }
            $penghasilanBruto->total_keseluruhan = $total_penghasilan_bruto;

            /**
             * Pengurang Penghasilan
             * IF(5%*K46>500000*SUM(L18:L29);500000*SUM(L18:L29);5%*K46)
             * K46 = total penghasilan
             * SUM(L18:19) = jumlah bulan telah digaji dalam setahun ($month_on_year_paid)
             */

            $biaya_jabatan = 0;
            $pembanding = 0;
            $biaya_jabatan = 0;
            if (property_exists($penghasilanBruto, 'total_penghasilan')) {
                $pembanding = 500000 * $month_on_year_paid;
                $biaya_jabatan = (0.05 * $penghasilanBruto->total_penghasilan) > $pembanding ? $pembanding : (0.05 * $penghasilanBruto->total_penghasilan);
            }
            $penguranganPenghasilan->biaya_jabatan = $biaya_jabatan;

            $jumlah_pengurangan = $total_pengurang_bruto + $biaya_jabatan;
            $penguranganPenghasilan->jumlah_pengurangan = $jumlah_pengurangan;

            $karyawan->karyawan_bruto = $karyawan_bruto;
            $karyawan->penghasilan_bruto = $penghasilanBruto;
            $karyawan->pengurangan_penghasilan = $penguranganPenghasilan;

            // Perhitungan Pph 21
            $perhitunganPph21 = new \stdClass();

            $total_rutin = $penghasilanBruto->penghasilan_rutin;
            $total_tidak_rutin = $penghasilanBruto->penghasilan_tidak_rutin;
            $bonus_sum = $penghasilanBruto->total_bonus;
            $pengurang = $penguranganPenghasilan->total_pengurangan_bruto;
            $total_ket = $month_on_year_paid;

            // Get PPh21 PP58
            $pph21_pp58 = HitungPPH::getPPh58($month, $year, $karyawan, $ptkp, $karyawan->tanggal_input, $total_gaji, $tunjangan_rutin);
            $perhitunganPph21->pph21_pp58 = $pph21_pp58;

            // Get jumlah penghasilan neto
            $jumlah_penghasilan = property_exists($penghasilanBruto, 'total_keseluruhan') ? $penghasilanBruto->total_keseluruhan : 0;
            $jumlah_pengurang = property_exists($penguranganPenghasilan, 'jumlah_pengurangan') ? $penguranganPenghasilan->jumlah_pengurangan : 0;
            // $jumlah_penghasilan_neto = $jumlah_penghasilan - $jumlah_pengurang;
            $jumlah_penghasilan_neto = ($total_rutin + $total_tidak_rutin) - ($biaya_jabatan + $pengurang);
            $perhitunganPph21->jumlah_penghasilan_neto = $jumlah_penghasilan_neto;

            // Get jumlah penghasilan neto masa sebelumnya
            $jumlah_penghasilan_neto_sebelumnya = 0;
            $perhitunganPph21->jumlah_penghasilan_neto_sebelumnya = $jumlah_penghasilan_neto_sebelumnya;

            // Get total penghasilan neto
            $total_penghasilan_neto = ($total_rutin + $total_tidak_rutin) - ($biaya_jabatan + $pengurang);
            $perhitunganPph21->total_penghasilan_neto = $total_penghasilan_neto;

            // Get jumlah penghasilan neto untuk Pph 21 (Setahun/Disetaunkan)
            if ($month_on_year_paid == 0) {
                $jumlah_penghasilan_neto_pph21 = 0;
            } else {
                $rumus_14 = 0;
                if (0.05 * $penghasilanBruto->total_penghasilan > $pembanding) {
                    $rumus_14 = ceil($pembanding);
                } else{
                    $rumus_14 = ceil(0.05 * $penghasilanBruto->total_penghasilan);
                }

                $jumlah_penghasilan_neto_pph21 = (($total_rutin + $total_tidak_rutin) - $bonus_sum - $pengurang - $biaya_jabatan) / $total_ket * 12 + $bonus_sum + ($biaya_jabatan - $rumus_14);

                $perhitunganPph21->jumlah_penghasilan_neto_pph21 = $jumlah_penghasilan_neto_pph21;
            }

            // Get PTKP
            $nominal_ptkp = 0;
            if ($ptkp) {
                $nominal_ptkp = $ptkp->ptkp_tahun;
            }
            $perhitunganPph21->ptkp = $nominal_ptkp;

            // Get Penghasilan Kena Pajak Setahun/Disetahunkan
            $keluarga = $karyawan->keluarga;
            $status_kawin = 'TK/0';
            if ($keluarga) {
                $status_kawin = $keluarga->status_kawin;
            }
            else {
                $status_kawin = $karyawan->status;
            }

            $penghasilan_kena_pajak_setahun = 0;
            if ($status_kawin == 'Mutasi Keluar') {
                $penghasilan_kena_pajak_setahun = floor(($jumlah_penghasilan_neto_pph21 - $ptkp?->ptkp_tahun) / 1000) * 1000;
            } else {
                if (($jumlah_penghasilan_neto_pph21 - $ptkp?->ptkp_tahun) <= 0) {
                    $penghasilan_kena_pajak_setahun = 0;
                } else {
                    $penghasilan_kena_pajak_setahun = $jumlah_penghasilan_neto_pph21 - $ptkp?->ptkp_tahun;
                }
            }
            $perhitunganPph21->penghasilan_kena_pajak_setahun = $penghasilan_kena_pajak_setahun;

            /**
             * Get PPh Pasal 21 atas Penghasilan Kena Pajak Setahun/Disetahunkan
             * 1. Create std class object pphPasal21
             * 2. Get persentase perhitungan pph21
             * 3. PPh Pasal 21 atas Penghasilan Kena Pajak Setahun/Disetahunkan
             * 4. PPh Pasal 21 yang telah dipotong Masa Sebelumnya (default 0)
             * 5. PPh Pasal 21 Terutang
             * 6. PPh Pasal 21 dan PPh Pasal 26 yang telah dipotong/dilunasi
             * 7. PPh Pasal 21 yang masih harus dibayar
             */

            // 1. Create std class object pphPasal21
            $pphPasal21 = new \stdClass;

            // 2. Get persentase perhitungan pph21
            $persen5 = 0;
            if (($jumlah_penghasilan_neto_pph21 - $nominal_ptkp) > 0) {
                if (($jumlah_penghasilan_neto_pph21 - $nominal_ptkp) <= 60000000) {
                    $persen5 = ($karyawan->npwp != null) ? (floor(($jumlah_penghasilan_neto_pph21 - $nominal_ptkp) / 1000) * 1000) * 0.05 :  (floor(($jumlah_penghasilan_neto_pph21 - $nominal_ptkp) / 1000) * 1000) * 0.06;
                } else {
                    $persen5 = ($karyawan->npwp != null) ? 60000000 * 0.05 : 60000000 * 0.06;
                }
            } else {
                $persen5 = 0;
            }
            $persen15 = 0;
            if (($jumlah_penghasilan_neto_pph21 - $nominal_ptkp) > 60000000) {
                if (($jumlah_penghasilan_neto_pph21 - $nominal_ptkp) <= 250000000) {
                    $persen15 = ($karyawan->npwp != null) ? (floor(($jumlah_penghasilan_neto_pph21 - $nominal_ptkp) / 1000) * 1000 - 60000000) * 0.15 :  (floor(($jumlah_penghasilan_neto_pph21 - $nominal_ptkp) / 1000) * 1000- 60000000) * 0.18;
                } else {
                    $persen15 = 190000000 * 0.15;
                }
            } else {
                $persen15 = 0;
            }
            $persen25 = 0;
            if (($jumlah_penghasilan_neto_pph21 - $nominal_ptkp) > 250000000) {
                if (($jumlah_penghasilan_neto_pph21 - $nominal_ptkp) <= 500000000) {
                    $persen25 = ($karyawan->npwp != null) ? (floor(($jumlah_penghasilan_neto_pph21 - $nominal_ptkp) / 1000) * 1000 - 250000000) * 0.25 :  (floor(($jumlah_penghasilan_neto_pph21 - $nominal_ptkp) / 1000) * 1000 - 250000000) * 0.3;
                } else {
                    $persen25 = 250000000 * 0.25;
                }
            } else {
                $persen25 = 0;
            }
            $persen30 = 0;
            if (($jumlah_penghasilan_neto_pph21 - $nominal_ptkp) > 500000000) {
                if (($jumlah_penghasilan_neto_pph21 - $nominal_ptkp) <= 5000000000) {
                    $persen30 = ($karyawan->npwp != null) ? (floor(($jumlah_penghasilan_neto_pph21 - $nominal_ptkp) / 1000) * 1000 - 500000000) * 0.3 :  (floor(($jumlah_penghasilan_neto_pph21 - $nominal_ptkp) / 1000) * 1000 - 500000000) * 0.36;
                } else {
                    $persen30 = 4500000000 * 0.30;
                }
            } else {
                $persen30 = 0;
            }
            $persen35 = 0;
            if (($jumlah_penghasilan_neto_pph21 - $nominal_ptkp) > 5000000000) {
                    $persen35 = ($karyawan->npwp != null) ? (floor(($jumlah_penghasilan_neto_pph21 - $nominal_ptkp) / 1000) * 1000 - 5000000000) * 0.35 :  (floor(($jumlah_penghasilan_neto_pph21 - $nominal_ptkp) / 1000) * 1000 - 5000000000) * 0.42;
            } else {
                $persen35 = 0;
            }
            $pphPasal21->persen5 = $persen5;
            $pphPasal21->persen15 = $persen15;
            $pphPasal21->persen25 = $persen25;
            $pphPasal21->persen30 = $persen30;
            $pphPasal21->persen35 = $persen35;

            // 3. PPh Pasal 21 atas Penghasilan Kena Pajak Setahun/Disetahunkan
            $penghasilan_kena_pajak_setahun = (($persen5 + $persen15 + $persen25 + $persen30 + $persen35) / 1000) * 1000;
            $pphPasal21->penghasilan_kena_pajak_setahun = $penghasilan_kena_pajak_setahun;

            // 4. PPh Pasal 21 yang telah dipotong Masa Sebelumnya
            $pph_21_dipotong_masa_sebelumnya = 0;
            $pphPasal21->pph_21_dipotong_masa_sebelumnya = $pph_21_dipotong_masa_sebelumnya;

            // 5. PPh Pasal 21 Terutang
            $pph_21_terutang = floor(($penghasilan_kena_pajak_setahun / 12) * $total_ket);
            $pphPasal21->pph_21_terutang = $pph_21_terutang;

            // 6. PPh Pasal 21 dan PPh Pasal 26 yang telah dipotong/dilunasi
            $total_pph_dilunasi = 0;
            if ($karyawan_bruto->pphDilunasi) {
                $pphDilunasi = $karyawan_bruto->pphDilunasi;
                foreach ($pphDilunasi as $key => $value) {
                    $total_pph_dilunasi += intval($value->nominal);
                }
            }
            $pphPasal21->pph_telah_dilunasi = $total_pph_dilunasi;

            // 7. PPh Pasal 21 yang masih harus dibayar
            $pph_harus_dibayar = $pph_21_terutang - $total_pph_dilunasi;
            $pphPasal21->pph_harus_dibayar = $pph_harus_dibayar;

            $perhitunganPph21->pph_pasal_21 = $pphPasal21;
            $karyawan->perhitungan_pph21 = $perhitunganPph21;

            $returnData->data = $karyawan;
        }
        $return = new stdClass;
        $return->nama_karyawan = $returnData->data->nama_karyawan;
        if($kategori == 'rincian') {
            $return->gaji_pokok = $returnData->data->gj_pokok ?? 0;
            $return->tj_keluarga = $returnData->data->gaji->tj_keluarga ?? 0;
            $return->tj_listrik = $returnData->data->gaji->tj_listrik ?? 0;
            $return->tj_jabatan = $returnData->data->gaji->tj_jabatan ?? 0;
            $return->tj_khusus = $returnData->data->gaji->tj_khusus ?? 0;
            $return->tj_perumahan = $returnData->data->gaji->tj_perumahan ?? 0;
            $return->tj_pelaksana = $returnData->data->gaji->tj_pelaksana ?? 0;
            $return->tj_kemahalan = $returnData->data->gaji->tj_kemahalan ?? 0;
            $return->tj_kesejahteraan = $returnData->data->gaji->tj_kesejahteraan ?? 0;
            $return->tj_teller = $returnData->data->gaji->tj_teller ?? 0;
            $return->penyesuaian = $returnData->data->gaji->gj_penyesuaian ?? 0;
            $return->total_gaji = $returnData->data->gaji->total_gaji ?? 0;
            $return->pph21 = $returnData->data->pph_dilunasi_bulan_ini ?? 0;
        } else {
            $return->gaji = $returnData->data->gaji->total_gaji ?? 0;
            $return->no_rekening = $returnData->data->no_rekening ?? 0;
            $return->bpjs_tk = $returnData->data->bpjs_tk ?? 0;
            $return->dpp = $returnData->data->potongan->dpp ?? 0;
            $return->kredit_koperasi = $returnData->data->gaji->kredit_koperasi ?? 0;
            $return->iuran_koperasi = $returnData->data->gaji->iuran_koperasi ?? 0;
            $return->kredit_pegawai = $returnData->data->gaji->kredit_pegawai ?? 0;
            $return->iuran_ik = $returnData->data->gaji->iuran_ik ?? 0;
            $return->total_potongan = $returnData->data->total_potongan ?? 0;
            $return->total_yg_diterima = $returnData->data->total_yg_diterima ?? 0;
        }

        return $return;
    }

    private static function getDatePenggajianSebelumnya($tanggal_penggajian, $kd_entitas) {
        $currentMonth = intval(date('m', strtotime($tanggal_penggajian)));
        $beforeMonth = $currentMonth - 1;
        $currentYear = date('Y', strtotime($tanggal_penggajian));

        // Gaji bulan sebelumnya
        $batch = DB::table('batch_gaji_per_bulan AS batch')
                    ->where('kd_entitas', $kd_entitas)
                    ->whereMonth('tanggal_input', $beforeMonth)
                    ->orderByDesc('id')
                    ->first();
        $start_date = $currentYear.'-'.$beforeMonth.'-26';
        if ($batch) {
            $start_date = date('Y-m-d', strtotime($batch->tanggal_input. ' + 1 days'));
        }

        return $start_date;
    }

    private function getLastDateOfMonth($year, $month) {
        // Get the last day of the month
        $lastDay = date('t', strtotime("$year-$month-01"));
        
        // Return the last day of the month
        return intval($lastDay);
    }
}