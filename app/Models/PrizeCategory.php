<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrizeCategory extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'level',
        'description'
    ];

    public function prizes()
    {
        return $this->hasMany(Prize::class);
    }
}
