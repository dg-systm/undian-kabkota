<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Winner extends Model
{
    use HasFactory;
    protected $fillable = [
        'draw_id',
        'kendaraan_id',
        'id_kendaraan',
        'prize_id'
    ];

    public function draw()
    {
        return $this->belongsTo(Draw::class);
    }

    public function prize()
    {
        return $this->belongsTo(Prize::class);
    }

    public function kendaraan()
    {
        return $this->belongsTo(Kendaraan::class)->orderBy('id');
    }
}
