<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Draw extends Model
{
    use HasFactory;
    protected $fillable = [
        'prize_id',  // nullable for multi-prize draws
        'quantity',
        'prize_category_id',
    ];

    public function prize()
    {
        return $this->belongsTo(Prize::class);
    }

    public function prize_category()
    {
        return $this->belongsTo(PrizeCategory::class);
    }

    public function winners()
    {
        return $this->hasMany(Winner::class)->orderBy('kendaraan_id');
    }
}
