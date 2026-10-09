<?php

namespace Database\Seeders;

use App\Models\Prize;
use App\Traits\QuerySqlsrvTrait;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PrizeSeeder extends Seeder
{
    use QuerySqlsrvTrait;

    public function run(): void
    {
        $data = [
            [
                'prize_category_id' => 1,
                'name' => 'Tabungan Rp 2 jt',
                'description' => 'Tabungan uang di Bank Jateng senilai Rp 2.000.000',
                'quantity' => 185
            ],
            [
                'prize_category_id' => 2,
                'name' => 'Emas 1 gr',
                'description' => '1 Keping Emas 1 gr',
                'quantity' => 5
            ],
            [
                'prize_category_id' => 2,
                'name' => 'Emas 2,5 gr',
                'description' => '1 Keping Emas 2,5 gr',
                'quantity' => 2
            ],
            [
                'prize_category_id' => 3,
                'name' => 'Tab. Rp 7,5 jt + Emas 5 gr',
                'description' => 'Tabungan Rp 7.500.000 + Emas 5 gr',
                'quantity' => 1
            ],
            [
                'prize_category_id' => 3,
                'name' => 'Tab Rp 5 jt + Emas 5 gr',
                'description' => 'Tabungan Rp 5.000.000 + Emas 5 gr',
                'quantity' => 1
            ],
            [
                'prize_category_id' => 3,
                'name' => 'Tab. Rp 3 jt + Emas 2,5 gr',
                'description' => 'Tabungan Rp 3.000.000 + Emas 2,5 gr',
                'quantity' => 1
            ],
        ];

        foreach ($data as $value) {
            Prize::create($value);
        }
        // $this->withIdentityInsert('dbo.prizes', function () use ($data) {
        // });
    }
}
