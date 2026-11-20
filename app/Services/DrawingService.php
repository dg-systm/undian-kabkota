<?php

namespace App\Services;

use App\Models\Draw;
use App\Models\Kendaraan;
use App\Models\Prize;
use App\Models\Winner;
use App\Support\Wilayah;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DrawingService
{
    public const PROCEDURE_DEFAULT = 'getWinner';

    public const PROCEDURE_BBNKB = 'getWinnerBbn';

    protected ?array $samsatIdMap = null;

    public function getWinnerMySql(int $idLokasi, ?string $idKecamatan, int $totalWinner = 5)
    {
        return DB::select('CALL getWinner(?, ?, ?)', [
            $idLokasi,
            $idKecamatan ?? '',
            $totalWinner,
        ]);
    }

    public function getWinnerSqlsrv(int $idLokasi, ?string $idKecamatan, int $totalWinner = 5, string $procedure = self::PROCEDURE_DEFAULT)
    {
        $procedure = $this->resolveProcedureName($procedure, 'sqlsrv');

        return DB::select("EXEC {$procedure} ?, ?, ?", [
            $idLokasi,
            $idKecamatan ?? '',
            $totalWinner,
        ]);
    }

    /**
     * Panggil SP getWinner sesuai signature database:
     * getWinner(@id_lokasi_samsat, @id_kecamatan, @length).
     *
     * Untuk kategori hadiah BBNKB, SP getWinnerBbn dipakai agar hanya
     * kendaraan dengan id_pendaftaran = 1 yang ikut diundi.
     *
     * @param  string|null  $idKecamatan  Kode kecamatan (mis. "0801"); '' = semua kecamatan.
     * @param  bool  $isBbnkb  true = gunakan SP getWinnerBbn.
     */
    public function getWinner(int $idLokasi, ?string $idKecamatan = '', int $totalWinner = 5, bool $isBbnkb = false)
    {
        $driver = DB::connection()->getDriverName();
        $procedure = $isBbnkb ? self::PROCEDURE_BBNKB : self::PROCEDURE_DEFAULT;

        return match ($driver) {
            'mysql', 'mariadb' => $this->getWinnerMySql($idLokasi, $idKecamatan, $totalWinner),
            'sqlsrv'           => $this->getWinnerSqlsrv($idLokasi, $idKecamatan, $totalWinner, $procedure),
            default => $this->getWinnerMySql($idLokasi, $idKecamatan, $totalWinner),
        };
    }

    /**
     * Pastikan nama SP hanya berasal dari daftar yang diizinkan.
     *
     * getWinnerBbn hanya tersedia di SQL Server; driver lain memakai getWinner.
     */
    protected function resolveProcedureName(string $procedure, string $driver): string
    {
        $allowed = [self::PROCEDURE_DEFAULT, self::PROCEDURE_BBNKB];

        if (!in_array($procedure, $allowed, true)) {
            return self::PROCEDURE_DEFAULT;
        }

        if ($procedure === self::PROCEDURE_BBNKB && $driver !== 'sqlsrv') {
            return self::PROCEDURE_DEFAULT;
        }

        return $procedure;
    }

    /**
     * Ambil kandidat pemenang dari SP sampai jumlah yang diminta terpenuhi.
     *
     * SP mengambil sampel acak dari rentang ID kendaraan, sehingga untuk
     * kecamatan dengan populasi kecil satu pemanggilan bisa mengembalikan
     * lebih sedikit dari yang diminta. Pemanggilan diulang beberapa kali
     * tanpa mengubah isi SP.
     */
    public function getWinnerCandidates(int $idLokasi, ?string $idKecamatan, int $required, int $maxAttempts = 10, bool $isBbnkb = false, array $excludeIds = []): Collection
    {
        $required = max($required, 1);
        $collected = collect();
        $emptyAttempts = 0;

        for ($attempt = 0; $attempt < $maxAttempts && $collected->count() < $required; $attempt++) {
            $rows = collect($this->getWinner($idLokasi, $idKecamatan, $required, $isBbnkb));
            $before = $collected->count();

            $collected = $collected
                ->merge($rows)
                ->reject(fn($row) => in_array((int) $row->id, $excludeIds, true))
                ->unique('id')
                ->values();

            if ($collected->count() === $before) {
                if (++$emptyAttempts >= 3) {
                    break;
                }
            } else {
                $emptyAttempts = 0;
            }
        }

        return $collected;
    }

    /**
     * Kandidat pemenang mode global (semua kecamatan) dengan komposisi roda.
     *
     * SP getWinner yang tersedia tidak memiliki parameter roda, sehingga
     * kandidat diambil lebih banyak lewat SP getWinner(999, '', N) (mode
     * global, semua kecamatan) lalu disaring per roda di sini tanpa
     * menambah objek baru di database.
     *
     * @param  int  $roda2Required  jumlah pemenang roda 2
     * @param  int  $roda4Required  jumlah pemenang roda 4
     * @return array{roda2: Collection, roda4: Collection}
     */
    public function getWinnerCandidatesByRoda(int $roda2Required, int $roda4Required, int $maxAttempts = 15): array
    {
        $roda2Required = max(0, $roda2Required);
        $roda4Required = max(0, $roda4Required);

        $byRoda = [
            'roda2' => collect(),
            'roda4' => collect(),
        ];

        $needed = fn (string $key, int $required): int => max(0, $required - $byRoda[$key]->count());

        $totalRequired = $roda2Required + $roda4Required;

        if ($totalRequired === 0) {
            return $byRoda;
        }

        // Ambil lebih banyak per panggilan karena hasil dibagi per jenis roda.
        $batchSize = max($totalRequired * 5, 20);
        $emptyAttempts = 0;
        $seenIds = [];

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $remaining = $needed('roda2', $roda2Required) + $needed('roda4', $roda4Required);

            if ($remaining === 0) {
                break;
            }

            $rows = collect($this->getWinner(999, '', max($remaining, $batchSize)));

            $added = false;

            foreach ($rows as $row) {
                $id = (int) ($row->id ?? 0);

                if ($id <= 0 || isset($seenIds[$id])) {
                    continue;
                }

                $roda = (string) ($row->roda ?? '');

                if ($roda === '2' && $byRoda['roda2']->count() < $roda2Required) {
                    $seenIds[$id] = true;
                    $byRoda['roda2']->push($row);
                    $added = true;
                } elseif ($roda === '4' && $byRoda['roda4']->count() < $roda4Required) {
                    $seenIds[$id] = true;
                    $byRoda['roda4']->push($row);
                    $added = true;
                }
            }

            if (!$added) {
                if (++$emptyAttempts >= 3) {
                    break;
                }
            } else {
                $emptyAttempts = 0;
            }
        }

        return $byRoda;
    }

    /**
     * Daftar kecamatan yang sudah memiliki pemenang grand prize
     * (undian global), diambil dari pemenang yang tersimpan di database.
     *
     * Dipakai agar grand prize tidak menghasilkan pemenang dari kecamatan
     * yang sama selama masih ada kecamatan yang belum menang, meski
     * pengundian dilakukan bertahap dalam beberapa sesi.
     *
     * @return list<string> id kecamatan unik yang sudah menang grand prize
     */
    public function getGlobalDrawnKecamatan(): array
    {
        $rows = DB::table('winners')
            ->join('draws', 'draws.id', '=', 'winners.draw_id')
            ->join('kendaraans', 'kendaraans.id', '=', 'winners.kendaraan_id')
            ->where('draws.is_global', true)
            ->whereNotNull('kendaraans.id_kecamatan')
            ->where('kendaraans.id_kecamatan', '!=', '')
            ->distinct()
            ->pluck('kendaraans.id_kecamatan');

        return $rows
            ->map(fn ($id) => trim((string) $id))
            ->filter(fn ($id) => $id !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Kandidat pemenang grand prize (mode global) dengan aturan satu
     * kecamatan satu pemenang:
     *
     * - kendaraan dari kecamatan yang sudah menang grand prize sebelumnya
     *   ($excludeKecamatan) tidak diambil;
     * - dalam satu pengundian pun maksimal satu pemenang per kecamatan.
     *
     * SP getWinner(999, ...) tidak punya parameter kecamatan dan hasilnya
     * sampel acak, sehingga penyaringan dilakukan di sini dengan mengulang
     * pemanggilan SP (pola sama dengan getWinnerCandidates) tanpa mengubah
     * isi database.
     *
     * @param  list<string>  $excludeKecamatan  id kecamatan yang sudah menang
     */
    public function getGlobalWinnerCandidates(int $required, bool $isBbnkb = false, array $excludeKecamatan = [], int $maxAttempts = 30): Collection
    {
        $required = max($required, 1);
        $collected = collect();
        $seenIds = [];
        $seenKecamatan = [];
        $emptyAttempts = 0;

        // Ambil lebih banyak per panggilan karena sebagian hasil bisa gugur
        // oleh filter kecamatan atau kendaraan yang sudah pernah menang.
        $batchSize = max($required * 5, 25);

        for ($attempt = 0; $attempt < $maxAttempts && $collected->count() < $required; $attempt++) {
            $rows = collect($this->getWinner(999, '', $batchSize, $isBbnkb));
            $before = $collected->count();

            foreach ($rows as $row) {
                if ($collected->count() >= $required) {
                    break;
                }

                $id = (int) ($row->id ?? 0);

                if ($id <= 0 || isset($seenIds[$id])) {
                    continue;
                }

                $kecamatan = trim((string) ($row->id_kecamatan ?? ''));

                // Kendaraan tanpa id_kecamatan tidak bisa menjamin aturan
                // satu kecamatan satu pemenang, jadi tidak diambil.
                if ($kecamatan === '') {
                    continue;
                }

                if (isset($seenKecamatan[$kecamatan]) || in_array($kecamatan, $excludeKecamatan, true)) {
                    continue;
                }

                $seenIds[$id] = true;
                $seenKecamatan[$kecamatan] = true;
                $collected->push($row);
            }

            if ($collected->count() === $before) {
                if (++$emptyAttempts >= 5) {
                    break;
                }
            } else {
                $emptyAttempts = 0;
            }
        }

        return $collected;
    }

    /**
     * Hitung progres komposisi: berapa pemenang roda 2 dan roda 4 yang
     * sudah tersimpan untuk hadiah-hadiah yang dipilih.
     *
     * Dipakai agar pengundian bisa dilakukan bertahap (mis. 1 pemenang per
     * undian) tanpa mengubah komposisi: setiap undian mengambil sisa slot
     * komposisi berikutnya.
     *
     * @param  array<int, int|string>  $prizeIds
     * @return array{roda2: int, roda4: int}
     */
    public function countDrawnWinnersByRoda(array $prizeIds): array
    {
        if (empty($prizeIds)) {
            return ['roda2' => 0, 'roda4' => 0];
        }

        $rows = DB::table('winners')
            ->join('kendaraans', 'kendaraans.id', '=', 'winners.kendaraan_id')
            ->whereIn('winners.prize_id', $prizeIds)
            ->groupBy('kendaraans.roda')
            ->selectRaw('kendaraans.roda, COUNT(*) as total')
            ->pluck('total', 'roda');

        return [
            'roda2' => (int) ($rows[2] ?? $rows['2'] ?? 0),
            'roda4' => (int) ($rows[4] ?? $rows['4'] ?? 0),
        ];
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
        array $samsatIdsByLocation = [],
        bool $isBbnkb = false
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
                $samsatIdsByLocation,
                $isBbnkb
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

            $configError = $this->validateConfiguredSamsatIds([$samsat => $samsatIds]);

            if ($configError !== null) {
                $draw?->delete();

                return [
                    'message' => $configError,
                    'status'  => 422,
                ];
            }

            $spWinners = collect($samsatIds)
                ->flatMap(fn($samsatId) => $this->getWinnerCandidates($samsatId, '', $totalWinnerPerSamsat, 10, $isBbnkb))
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
     *
     * Setiap kecamatan diundi langsung melalui SP getWinner(id_lokasi, id_kecamatan, length)
     * sehingga pemenang benar-benar berasal dari kecamatan yang dipilih. Hasil antar
     * kecamatan digabung tanpa duplikasi lalu dipetakan ke daftar hadiah.
     */
    protected function pickByLocations(
        array $prizeIds,
        Collection $prizes,
        array $locations,
        int $perLocation,
        bool $isDemo,
        array $samsatIdsByLocation,
        bool $isBbnkb = false
    ): array {
        $required = $perLocation * count($locations);
        $totalAvailable = $prizes->sum(fn($prize) => $prize->available_quantity);

        if ($totalAvailable < $required) {
            return [
                'message' => "Total stok hadiah yang dipilih ({$totalAvailable}) kurang dari jumlah pemenang yang dibutuhkan ({$required}).",
                'status'  => 422,
            ];
        }

        $samsatIdsByLocation = $this->resolveSamsatIdsByLocation($locations, $samsatIdsByLocation);

        $missingLocation = collect($locations)
            ->first(fn($location) => empty($samsatIdsByLocation[$location] ?? []));

        if ($missingLocation !== null) {
            return [
                'message' => "Samsat untuk kecamatan {$missingLocation} tidak ditemukan pada konfigurasi wilayah.",
                'status'  => 422,
            ];
        }

        $configError = $this->validateConfiguredSamsatIds($samsatIdsByLocation);

        if ($configError !== null) {
            return [
                'message' => $configError,
                'status'  => 422,
            ];
        }

        // Ambil kandidat per kecamatan sesuai SP.
        $pickedByLocation = [];
        $pickedIds = [];
        $usedIds = [];

        foreach ($locations as $location) {
            $candidates = collect();

            foreach ($samsatIdsByLocation[$location] as $samsatId) {
                $candidates = $candidates->merge(
                    $this->getWinnerCandidates((int) $samsatId, (string) $location, $perLocation, 10, $isBbnkb)
                );
            }

            $candidates = $candidates
                ->reject(fn($row) => in_array((int) $row->id, $usedIds, true))
                ->unique(fn($row) => (int) $row->id)
                ->values();

            if ($candidates->count() < $perLocation) {
                return [
                    'message' => "Tidak cukup kendaraan tersedia di Kecamatan {$location}. Tersedia: {$candidates->count()}, dibutuhkan: {$perLocation}.",
                    'status'  => 422,
                ];
            }

            $locationPicks = $candidates->take($perLocation)->values();
            $pickedByLocation[$location] = $locationPicks;

            foreach ($locationPicks as $row) {
                $id = (int) $row->id;
                $pickedIds[] = $id;
                $usedIds[] = $id;
            }
        }

        if (count($pickedIds) < $required) {
            return [
                'message' => 'Hasil SP tidak valid atau mengandung kendaraan duplikat.',
                'status'  => 422,
            ];
        }

        if (Winner::whereIn('kendaraan_id', $pickedIds)->exists()) {
            return [
                'message' => 'Hasil SP mengandung kendaraan yang sudah pernah menang.',
                'status'  => 422,
            ];
        }

        $vehicles = Kendaraan::whereIn('id', $pickedIds)->get()->keyBy('id');

        if ($vehicles->count() < $required) {
            return [
                'message' => 'Hasil SP tidak cocok dengan data kendaraan.',
                'status'  => 422,
            ];
        }

        $prizePool = $this->buildPrizePool($prizes, $required);

        $draw = null;
        if (!$isDemo) {
            $draw = Draw::create([
                'prize_id'          => $prizeIds[0],
                'prize_category_id' => $prizes->first()?->prize_category_id,
                'quantity'          => $required,
            ]);
        }

        $selectedVehicles = [];
        $index = 0;

        foreach ($locations as $location) {
            foreach ($pickedByLocation[$location] as $row) {
                $vehicle = $vehicles[(int) $row->id] ?? null;

                if (!$vehicle) {
                    $draw?->delete();

                    return [
                        'message' => 'Hasil SP tidak cocok dengan data kendaraan.',
                        'status'  => 422,
                    ];
                }

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

    /**
     * Pastikan setiap lokasi/kecamatan punya daftar ID Samsat.
     *
     * Urutan sumber:
     * 1. Pemetaan yang dikirim front-end (kecamatan -> [id_lokasi]).
     * 2. config/coordinator.php melalui App\Support\Wilayah.
     * 3. Data kendaraan pada kecamatan tersebut.
     */
    protected function resolveSamsatIdsByLocation(array $locations, array $samsatIdsByLocation): array
    {
        $resolved = [];
        $wilayah = null;

        foreach ($locations as $location) {
            $ids = collect($samsatIdsByLocation[$location] ?? [])
                ->map(fn($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();

            if ($ids->isEmpty()) {
                $ids = collect($this->configuredSamsatIds());
            }

            if ($ids->isEmpty()) {
                $wilayah ??= Wilayah::find(config('wilayah.active'));
                $samsatId = $wilayah['kecamatan_samsat'][(string) $location] ?? null;

                if ($samsatId !== null) {
                    $ids = collect([(int) $samsatId]);
                }
            }

            if ($ids->isEmpty()) {
                $ids = Kendaraan::where('id_kecamatan', (string) $location)
                    ->distinct()
                    ->pluck('id_lokasi')
                    ->map(fn($id) => (int) $id)
                    ->filter()
                    ->unique()
                    ->values();
            }

            $resolved[$location] = $ids->all();
        }

        return $resolved;
    }

    /**
     * ID lokasi/Samsat yang dikonfigurasi pada APP_ID_LOKASI.
     *
     * @return array<int, int>
     */
    protected function configuredSamsatIds(): array
    {
        return collect(config('wilayah.id_lokasi', []))
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Validasi silang ID Samsat hasil mapping dengan APP_ID_LOKASI.
     *
     * Bila APP_ID_LOKASI diisi, seluruh ID Samsat yang dipakai harus termasuk
     * di dalamnya. Ini mencegah pengundian memakai id_lokasi yang salah.
     *
     * @param  array<string, array<int, int>>  $samsatIdsByLocation
     */
    protected function validateConfiguredSamsatIds(array $samsatIdsByLocation): ?string
    {
        $configured = $this->configuredSamsatIds();

        if (empty($configured)) {
            return null;
        }

        foreach ($samsatIdsByLocation as $location => $samsatIds) {
            $samsatIds = collect($samsatIds)
                ->map(fn($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->all();

            $unexpected = array_values(array_diff($samsatIds, $configured));

            if (!empty($unexpected)) {
                return 'APP_ID_LOKASI (' . implode(',', $configured) . ') tidak cocok dengan Samsat untuk '
                    . "kecamatan/lokasi {$location} (id_lokasi " . implode(',', $samsatIds) . '). '
                    . 'Periksa kembali APP_ID_LOKASI pada .env dan mapping wilayah.';
            }
        }

        return null;
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
