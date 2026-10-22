<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prize extends Model
{
    use HasFactory;
    protected $fillable = [
        'prize_category_id',
        'name',
        'description',
        'quantity'
    ];

    protected $appends = [
        'available_quantity'
    ];

    public function prize_category()
    {
        return $this->belongsTo(PrizeCategory::class);
    }

    public function draws()
    {
        return $this->hasMany(Draw::class);
    }

    public function winners()
    {
        return $this->hasMany(Winner::class);
    }

    public function getAvailableQuantityAttribute()
    {
        // Gunakan hasil withCount('winners') bila sudah tersedia agar tidak
        // menimbulkan query N+1 saat daftar hadiah dimuat.
        $used = array_key_exists('winners_count', $this->attributes)
            ? (int) $this->attributes['winners_count']
            : $this->winners()->count();

        return $this->quantity - $used;
    }
}
