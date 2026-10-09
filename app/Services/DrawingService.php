<?php

namespace App\Services;

use App\Models\Draw;
use App\Models\Kendaraan;
use App\Models\Prize;
use App\Models\Winner;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DrawingService
{
    protected ?array $samsatIdMap = null;

    public function getWinnerMySql(int $idLokasi, int $totalWinner = 5)
    {
        return DB::select('CALL getWinner(?, ?)', [$idLokasi, $totalWinner]);
    }

    public function getWinnerSqlsrv(int $idLokasi, int $totalWinner = 5)
    {
        return DB::select('EXEC getWinner ?, ?', [$idLokasi, $totalWinner]);
    }

    public function getWinner(int $idLokasi, int $totalWinner = 5)
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'mysql', 'mariadb' => $this->getWinnerMySql($idLokasi, $totalWinner),
            'sqlsrv'           => $this->getWinnerSqlsrv($idLokasi, $totalWinner),
            default => $this->getWinnerMySql($idLokasi, $totalWinner),
        };
    }

    public function getSamsatId(string $samsatName): ?int
    {
        $normalizedSamsatName = $this->normalizeSamsatName($samsatName);

        return $this->samsatIdMap()[$normalizedSamsatName] ?? null;
    }

    public function getSamsatIds(string $samsatName): array
    {
        $names = collect($this->parseSamsatNames($samsatName))
            ->map(fn($name) => $this->getSamsatId($name))
            ->filter()
            ->unique()
            ->values();

        return $names->all();
    }

    public function pickDraw(
        array $prizeIds,
        array $samsats = [],
        int $totalWinnerPerSamsat = 5,
        bool $isDemo = true,
        array $samsatIdsByLocation = []
    ): array {
        $prizes = Prize::whereIn('id', $prizeIds)->get();
        $totalWinner = $totalWinnerPerSamsat * count($samsats);
        $totalAvailable = $prizes->sum(fn($prize) => $prize->available_quantity);

        if ($totalAvailable < $totalWinner) {
            return [
                'message' => "Total stok hadiah yang dipilih ({$totalAvailable}) kurang dari jumlah pemenang yang dibutuhkan ({$totalWinner}). Pilih lebih banyak hadiah atau kurangi jumlah lokasi.",
                'status'  => 422,
            ];
        }

        // Mode per-Kecamatan: semua lokasi biasanya berada pada Samsat yang sama,
        // sehingga pengundian dilakukan sekali (union) lalu dibagikan ke tiap lokasi
        // agar tidak ada kendaraan duplikat antar lokasi.
        if (!empty($samsatIdsByLocation)) {
            return $this->pickByLocations(
                $prizeIds,
                $prizes,
                $samsats,
                $totalWinnerPerSamsat,
                $isDemo,
                $samsatIdsByLocation
            );
        }

        $draw = null;
        if (!$isDemo) {
            $draw = Draw::create([
                'prize_id'          => $prizeIds[0],
                'prize_category_id' => $prizes->first()?->prize_category_id,
                'quantity'          => $totalWinner,
            ]);
        }

        $prizePool = $this->buildPrizePool($prizes, $totalWinner);
        $selectedVehicles = [];
        $selectedVehicleIds = [];

        foreach ($samsats as $samsat) {
            $samsatIds = $this->getSamsatIds($samsat);

            if (empty($samsatIds)) {
                $draw?->delete();

                return [
                    'message' => "Samsat {$samsat} tidak ditemukan di konfigurasi UPPD.",
                    'status'  => 422,
                ];
            }

            $spWinners = collect($samsatIds)
                ->flatMap(fn($samsatId) => $this->getWinner($samsatId, $totalWinnerPerSamsat))
                ->unique('id')
                ->shuffle()
                ->take($totalWinnerPerSamsat)
                ->values();

            if ($spWinners->count() < $totalWinnerPerSamsat) {
                $draw?->delete();

                return [
                    'message' => "Tidak cukup kendaraan tersedia di Samsat {$samsat}. Tersedia: {$spWinners->count()}, dibutuhkan: {$totalWinnerPerSamsat}.",
                    'status'  => 422,
                ];
            }

            $pickedIds = $spWinners
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();

            if ($pickedIds->count() < $totalWinnerPerSamsat) {
                $draw?->delete();

                return [
                    'message' => "Hasil SP untuk Samsat {$samsat} tidak valid atau mengandung kendaraan duplikat.",
                    'status'  => 422,
                ];
            }

            $duplicateWinnerExists = Winner::whereIn('kendaraan_id', $pickedIds)->exists();
            $duplicateInCurrentDraw = $pickedIds->intersect($selectedVehicleIds)->isNotEmpty();

            if ($duplicateWinnerExists || $duplicateInCurrentDraw) {
                $draw?->delete();

                return [
                    'message' => "Hasil SP untuk Samsat {$samsat} mengandung kendaraan yang sudah menang.",
                    'status'  => 422,
                ];
            }

            $vehicles = Kendaraan::whereIn('id', $pickedIds)->get()->keyBy('id');

            if ($vehicles->count() < $totalWinnerPerSamsat) {
                $draw?->delete();

                return [
                    'message' => "Hasil SP untuk Samsat {$samsat} tidak cocok dengan data kendaraan.",
                    'status'  => 422,
                ];
            }

            foreach ($pickedIds as $pickedId) {
                $vehicle = $vehicles[$pickedId];
                $vehicle->lokasi = $samsat;

                $selectedVehicles[] = $vehicle;
                $selectedVehicleIds[] = $pickedId;
            }
        }

        foreach ($selectedVehicles as $index => $vehicle) {
            $prizeId = $prizePool[$index];

            if (!$isDemo && $draw) {
                $this->saveWinner($draw->id, $vehicle->id, $vehicle->id_kendaraan, $prizeId);
            }

            $vehicle->prize = $prizes->firstWhere('id', $prizeId);
        }

        $response = ['data' => $selectedVehicles];

        if ($draw) {
            $response['draw_id'] = $draw->id;
        }

        return $response;
    }

    /**
     * Pengundian per lokasi (mis. per Kecamatan) dengan pemetaan lokasi -> ID Samsat.
     * Semua pemenang diambil dari gabungan Samsat terkait lalu dibagikan rata ke tiap lokasi.
     */
    protected function pickByLocations(
        array $prizeIds,
        Collection $prizes,
        array $locations,
        int $perLocation,
        bool $isDemo,
        array $samsatIdsByLocation
    ): array {
        $required = $perLocation * count($locations);

        $samsatIds = collect($locations)
            ->flatMap(fn($location) => $samsatIdsByLocation[$location] ?? $this->getSamsatIds($location))
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($samsatIds->isEmpty()) {
            return [
                'message' => 'Tidak ada Samsat yang cocok untuk lokasi/kecamatan yang dipilih.',
                'status'  => 422,
            ];
        }

        $draw = null;
        if (!$isDemo) {
            $draw = Draw::create([
                'prize_id'          => $prizeIds[0],
                'prize_category_id' => $prizes->first()?->prize_category_id,
                'quantity'          => $required,
            ]);
        }

        // Ambil kandidat pemenang dari seluruh Samsat terkait.
        $candidates = collect();
        foreach ($samsatIds as $samsatId) {
            $candidates = $candidates->merge(
                collect($this->getWinner($samsatId, max($required, $perLocation)))
            );
        }

        $candidates = $candidates->unique('id')->shuffle()->values();

        if ($candidates->count() < $required) {
            $draw?->delete();

            return [
                'message' => "Tidak cukup kendaraan tersedia. Tersedia: {$candidates->count()}, dibutuhkan: {$required}.",
                'status'  => 422,
            ];
        }

        $picked = $candidates->take($required)->values();
        $pickedIds = $picked
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($pickedIds->count() < $required) {
            $draw?->delete();

            return [
                'message' => 'Hasil SP tidak valid atau mengandung kendaraan duplikat.',
                'status'  => 422,
            ];
        }

        if (Winner::whereIn('kendaraan_id', $pickedIds)->exists()) {
            $draw?->delete();

            return [
                'message' => 'Hasil SP mengandung kendaraan yang sudah pernah menang.',
                'status'  => 422,
            ];
        }

        $vehicles = Kendaraan::whereIn('id', $pickedIds)->get()->keyBy('id');

        if ($vehicles->count() < $required) {
            $draw?->delete();

            return [
                'message' => 'Hasil SP tidak cocok dengan data kendaraan.',
                'status'  => 422,
            ];
        }

        $prizePool = $this->buildPrizePool($prizes, $required);

        $selectedVehicles = [];
        $index = 0;
        foreach ($locations as $location) {
            for ($i = 0; $i < $perLocation; $i++) {
                if (!isset($pickedIds[$index])) {
                    break 2;
                }

                $vehicle = $vehicles[$pickedIds[$index]];
                $vehicle->lokasi = $location;

                $prizeId = $prizePool[$index] ?? $prizeIds[0];

                if (!$isDemo && $draw) {
                    $this->saveWinner($draw->id, $vehicle->id, $vehicle->id_kendaraan, $prizeId);
                }

                $vehicle->prize = $prizes->firstWhere('id', $prizeId);
                $selectedVehicles[] = $vehicle;
                $index++;
            }
        }

        $response = ['data' => $selectedVehicles];

        if ($draw) {
            $response['draw_id'] = $draw->id;
        }

        return $response;
    }

    protected function buildPrizePool(Collection $prizes, int $required): array
    {
        $prizePool = [];

        foreach ($prizes as $prize) {
            $quantity = min($prize->available_quantity, $required - count($prizePool));

            for ($i = 0; $i < $quantity; $i++) {
                $prizePool[] = $prize->id;

                if (count($prizePool) >= $required) {
                    break;
                }
            }

            if (count($prizePool) >= $required) {
                break;
            }
        }

        shuffle($prizePool);

        return $prizePool;
    }

    protected function normalizeSamsatName(string $name): string
    {
        $name = trim(str_replace(['Kab.', 'Kota'], '', $name));

        return strtoupper(preg_replace('/\s+/', ' ', $name));
    }

    protected function parseSamsatNames(string $samsatName): array
    {
        $normalizedSeparator = str_replace(['â†’', '→'], '->', $samsatName);
        $normalizedSeparator = str_replace(["\xC3\xA2\xE2\x80\xA0\xE2\x80\x99", "\xE2\x86\x92"], '->', $normalizedSeparator);
        $parts = explode('->', $normalizedSeparator, 2);
        $names = [trim($parts[0])];

        if (isset($parts[1])) {
            foreach (explode(',', $parts[1]) as $subName) {
                $names[] = trim($subName);
            }
        }

        return array_values(array_filter($names));
    }

    protected function samsatIdMap(): array
    {
        if ($this->samsatIdMap !== null) {
            return $this->samsatIdMap;
        }

        $this->samsatIdMap = collect(config('uppd', []))
            ->mapWithKeys(fn($name, $id) => [
                $this->normalizeSamsatName($name) => (int) $id,
            ])
            ->all();

        return $this->samsatIdMap;
    }

    protected function saveWinner(int $drawId, int $vehicleId, int $idKendaraan, int $prizeId): void
    {
        Winner::create([
            'draw_id'      => $drawId,
            'kendaraan_id' => $vehicleId,
            'id_kendaraan' => $idKendaraan,
            'prize_id'     => $prizeId,
        ]);
    }
}
