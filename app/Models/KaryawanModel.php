<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KaryawanModel extends Model implements Authenticatable
{
    use HasFactory;

    protected $table = 'mst_karyawan';
    protected $primaryKey = 'nip';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'nip',
        'nama_karyawan',
        'nik',
        'ket_jabatan',
        'kd_subdivisi',
        'id_cabang',
        'kd_jabatan',
        'kd_panggol',
        'id_is',
        'kd_agama',
        'tmp_lahir',
        'tgl_lahir',
        'kewarganegaraan',
        'jk',
        'status',
        'alamat_ktp',
        'alamat_sek',
        'kpj',
        'jkn',
        'gj_pokok',
        'gj_penyesuaian',
        'status_karyawan',
        'skangkat',
        'tanggal_pengangkat',
        'tanggal_penonaktifan',
        'kategori_penonaktifan',
        'sk_pemberhentian',
    ];
}
