<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Kendaraan extends Model
{
    use HasFactory;
    protected $fillable = [
        'id_kendaraan',
        'no_polisi',
        'nama',
        'alamat',
        'id_kecamatan',
        'id_kelurahan',
        'id_lokasi',
        'lokasi',
        'id_lokasi_proses',
        'lokasi_proses',
        'id_billing',
        'id_warna_tnkb',
        'warna_tnkb',
        'id_fungsi_kend',
        'fungsi_kend',
        'roda',
        'id_pendaftaran',
        'pendaftaran',
        'tgl_daftar',
        'tgl_bayar',
        'tgl_jatuh_tempo',
        'tgl_akhir_stnk',
    ];

    protected $appends = [
        'masked_no_polisi'
    ];

    public function getMaskedNoPolisiAttribute()
    {
        $no_polisi = $this->attributes['no_polisi'] ?? null;
        if (empty($no_polisi)) {
            return null;
        }

        $parts = preg_split("/(,?\s+)|((?<=[a-z])(?=\d))|((?<=\d)(?=[a-z]))/i", $no_polisi);
        if (!is_array($parts) || count($parts) < 3) {
            return $no_polisi;
        }

        return ($parts[0] ?? '') . ' ' . ($parts[1] ?? '') . ' ' . ($parts[2] ?? '');
    }

    public function scopeMinId()
    {
        return Cache::remember('Kendaraan.scopeMinId', 360, function () {
            return $this->min('id');
        });
    }

    public function scopeMaxId()
    {
        return Cache::remember('Kendaraan.scopeMaxId', 360, function () {
            return $this->max('id');
        });
    }
}
