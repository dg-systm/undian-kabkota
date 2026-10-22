<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrizeCategory extends Model
{
    use HasFactory;

    /**
     * Nama kategori hadiah BBNKB. Pengundian kategori ini memakai
     * stored procedure getWinnerBbn (hanya kendaraan dengan id_pendaftaran = 1).
     */
    public const BBNKB = 'BBNKB';

    protected $fillable = [
        'name',
        'level',
        'description'
    ];

    public function prizes()
    {
        return $this->hasMany(Prize::class);
    }

    public function isBbnkb(): bool
    {
        return static::isBbnkbName($this->name);
    }

    public static function isBbnkbName(?string $name): bool
    {
        if ($name === null) {
            return false;
        }

        return str_contains(strtoupper(trim($name)), self::BBNKB);
    }
}
