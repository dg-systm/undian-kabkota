<?php

namespace App\Filament\Widgets;

use App\Models\Kendaraan;
use App\Support\Wilayah;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatistikKendaraanWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        // Sumber data wilayah: config/coordinator.php (melalui helper Wilayah).
        $info = Wilayah::find(config('wilayah.active'));
        $wilayahName = $info['found'] ? $info['name'] : (config('wilayah.active') ?: 'Wilayah');

        // Kecamatan & kelurahan mengikuti daftar mapping wilayah aktif
        // (sama dengan yang tampil di sidebar).
        $totalKecamatan = $info['kecamatan_count'];
        $totalKelurahan = $info['kelurahan_count'];

        // Total kendaraan dibatasi ke Samsat wilayah aktif.
        $totalKendaraan = $this->kendaraanWilayahQuery($info['samsat_ids'])->count();

        return [
            Stat::make('Total Kendaraan', number_format($totalKendaraan, 0, ',', '.'))
                ->description("Kendaraan di {$wilayahName}")
                ->descriptionIcon('heroicon-m-truck')
                ->color('primary'),

            Stat::make('Kecamatan', number_format($totalKecamatan, 0, ',', '.'))
                ->description("Kecamatan di {$wilayahName}")
                ->descriptionIcon('heroicon-m-map-pin')
                ->color('success'),

            Stat::make('Kelurahan', number_format($totalKelurahan, 0, ',', '.'))
                ->description("Kelurahan/desa di {$wilayahName}")
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('warning'),
        ];
    }

    /**
     * Query kendaraan yang dibatasi ke Samsat wilayah aktif.
     * Bila wilayah belum terdaftar, tidak mengembalikan data apa pun
     * (bukan total seluruh Jawa Tengah).
     */
    protected function kendaraanWilayahQuery(array $samsatIds)
    {
        $query = Kendaraan::query();

        if (!empty($samsatIds)) {
            $query->whereIn('id_lokasi', $samsatIds);
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }
}
