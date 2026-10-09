<?php

namespace Database\Seeders;

use App\Models\Kendaraan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class KendaraanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Kendaraan::count() == 0) {
            $data = json_decode(file_get_contents(database_path('seeders/jsons/kendaraans.json')), true);
            foreach ($data as $value) {
                Kendaraan::firstOrCreate([
                    'id_kendaraan' => $value['id_kendaraan']
                ], $value);
            }
        }
    }
}
