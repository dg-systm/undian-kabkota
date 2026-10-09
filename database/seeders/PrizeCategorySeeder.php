<?php

namespace Database\Seeders;

use App\Models\PrizeCategory;
use App\Traits\QuerySqlsrvTrait;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PrizeCategorySeeder extends Seeder
{
    use QuerySqlsrvTrait;

    public function run(): void
    {
        $data = [
            [
                'name' => 'Hadiah Lainnya',
                'level' => 1,
                'description' => null,
            ],
            [
                'name' => 'Emas',
                'level' => 2,
                'description' => null,
            ],
            [
                'name' => 'Grand Prize',
                'level' => 3,
                'description' => null,
            ],
        ];

        foreach ($data as $value) {
            PrizeCategory::create($value);
        }
        // $this->withIdentityInsert('dbo.prize_categories', function () use ($data) {
        // });
    }
}
