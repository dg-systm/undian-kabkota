<?php

namespace App\Http\Controllers;

use App\Models\Draw;
use App\Models\Kendaraan;
use App\Models\Prize;
use App\Models\Winner;
use App\Services\DrawingService;
use App\Support\UndianSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DrawController extends Controller
{
    public function __construct(
        protected DrawingService $drawingService
    ) {}

    public function pick(Request $request)
    {
        $isGlobal = $request->boolean('is_global', false);
        $isDemo   = $request->boolean('is_demo', false);

        if ($isGlobal) {
            $request->validate([
                'prize_ids'   => 'required|array|min:1',
                'prize_ids.*' => 'required|numeric|exists:prizes,id',
                'quantity'    => 'required|integer|min:1',
            ]);
        } else {
            $request->validate([
                'prize_ids'   => 'required|array|min:1',
                'prize_ids.*' => 'required|numeric|exists:prizes,id',
                'samsats'     => 'required|array|min:1',
                'samsats.*'   => 'required|string',
                'per_samsat'  => 'nullable|integer|min:1',
                'samsat_ids'  => 'nullable|array',
            ]);
        }

        if ($isGlobal) {
            $request->validate([
                'global_mode' => 'nullable|string|in:free,roda',
                'roda2'       => 'nullable|integer|min:0|max:99',
                'roda4'       => 'nullable|integer|min:0|max:99',
            ]);
        }

        $prizeIds = $request->prize_ids;

        /*
        | Mode pengundian harus di-set admin terlebih dahulu, dan request
        | harus sesuai mode yang aktif:
        | - kecamatan -> undian biasa (per kecamatan) + grand prize fleksibel.
        | - global6   -> hanya undian global dengan komposisi roda.
        */
        $drawMode = (string) config('wilayah.draw_mode', '');

        if ($drawMode === '') {
            return response()->json([
                'message' => 'Mode pengundian belum di-set. Atur terlebih dahulu di menu admin: Pengaturan Undian.'
            ], 422);
        }

        $globalMode = (string) $request->input('global_mode', 'free');

        if ($drawMode === UndianSettings::MODE_GLOBAL6 && !($isGlobal && $globalMode === 'roda')) {
            return response()->json([
                'message' => 'Mode pengundian aktif adalah Global Semua Kecamatan (6 pemenang). Undian lain tidak diizinkan.'
            ], 422);
        }

        if ($drawMode === UndianSettings::MODE_KECAMATAN && $isGlobal && $globalMode === 'roda') {
            return response()->json([
                'message' => 'Mode pengundian aktif adalah Per Kecamatan. Undian global 6 pemenang tidak diizinkan.'
            ], 422);
        }

        // 1. Validasi prize dan ketersediaan
        $prizes      = Prize::whereIn('id', $prizeIds)->get();
        $categoryIds = $prizes->pluck('prize_category_id')->unique();

        if ($categoryIds->count() > 1) {
            return response()->json([
                'message' => 'Semua hadiah harus berasal dari satu kategori yang sama.'
            ], 422);
        }

        $drawCategoryId = $categoryIds->first();
        $isBbnkb = $prizes->first()?->prize_category?->isBbnkb() ?? false;
        $totalAvailable = $prizes->sum(fn($p) => $p->available_quantity);

        if ($isGlobal) {
            return $this->pickGlobal(
                $request,
                $prizeIds,
                $prizes,
                $drawCategoryId,
                $totalAvailable,
                $isDemo,
                $isBbnkb
            );
        }

        $perSamsat = (int) $request->input('per_samsat', 5);
        $samsatIdsByLocation = (array) $request->input('samsat_ids', []);

        return $this->pickBySamsat(
            $prizeIds,
            $request->samsats,
            $totalAvailable,
            $isDemo,
            $perSamsat,
            $samsatIdsByLocation,
            $isBbnkb
        );
    }

    protected function pickGlobal(
        Request $request,
        array $prizeIds,
        Collection $prizes,
        $drawCategoryId,
        $totalAvailable,
        bool $isDemo,
        bool $isBbnkb = false
    ) {
        $globalMode = (string) $request->input('global_mode', 'free');

        $selectedIds = collect();

        if ($globalMode === 'roda') {
            /*
            | Mode global semua kecamatan dengan komposisi tetap:
            | N pemenang roda 2 + M pemenang roda 4 (diatur dari admin).
            |
            | Pengundian bisa dilakukan bertahap (mis. 1 pemenang per undian):
            | progres komposisi dihitung dari pemenang yang sudah tersimpan
            | untuk hadiah yang dipilih, lalu setiap undian mengambil sisa
            | slot komposisi berikutnya (roda 2 dulu, roda 4 terakhir).
            */
            $roda2 = (int) $request->input('roda2', (int) config('wilayah.global.roda2', 5));
            $roda4 = (int) $request->input('roda4', (int) config('wilayah.global.roda4', 1));
            $required = (int) $request->quantity;
            $komposisiTotal = $roda2 + $roda4;

            if ($komposisiTotal < 1) {
                return response()->json([
                    'message' => 'Komposisi pemenang mode global belum diatur (roda 2 dan roda 4). Atur melalui menu Pengaturan Undian.'
                ], 422);
            }

            if ($required < 1) {
                return response()->json([
                    'message' => 'Jumlah pemenang minimal 1.'
                ], 422);
            }

            // Progres komposisi: slot yang sudah terpenuhi undian sebelumnya.
            $drawn = $this->drawingService->countDrawnWinnersByRoda($prizeIds);
            $remaining2 = max(0, $roda2 - $drawn['roda2']);
            $remaining4 = max(0, $roda4 - $drawn['roda4']);
            $remainingTotal = $remaining2 + $remaining4;

            if ($remainingTotal === 0) {
                return response()->json([
                    'message' => "Komposisi {$roda2} roda 2 + {$roda4} roda 4 (total {$komposisiTotal} pemenang) sudah terpenuhi. Pilih hadiah/kategori lain untuk pengundian berikutnya."
                ], 422);
            }

            // Jumlah pemenang melebihi sisa slot? Undian ini langsung memakai
            // seluruh sisa slot komposisi (tanpa error).
            $required = min($required, $remainingTotal);

            if ($totalAvailable < $required) {
                return response()->json([
                    'message' => "Total stok hadiah yang dipilih ({$totalAvailable}) kurang dari jumlah pemenang yang dibutuhkan ({$required})."
                ], 422);
            }

            // Alokasi slot undian ini dari sisa komposisi.
            $w2 = min($remaining2, $required);
            $w4 = min($remaining4, $required - $w2);

            // Kandidat via SP getWinner (id_lokasi 999 = semua kecamatan),
            // disaring per roda tanpa mengubah isi database.
            $candidates = $this->drawingService->getWinnerCandidatesByRoda($w2, $w4);

            if ($candidates['roda2']->count() < $w2 || $candidates['roda4']->count() < $w4) {
                return response()->json([
                    'message' => "Tidak cukup kendaraan untuk komposisi {$w2} roda 2 + {$w4} roda 4. "
                        . "Tersedia: roda 2 = {$candidates['roda2']->count()}, roda 4 = {$candidates['roda4']->count()}."
                ], 422);
            }

            $selectedIds = $candidates['roda2']
                ->merge($candidates['roda4'])
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();
        } else {
            // Mode global fleksibel: jumlah pemenang bebas (sesuai permintaan).
            $required = (int) $request->quantity;

            if ($totalAvailable < $required) {
                return response()->json([
                    'message' => "Total stok hadiah yang dipilih ({$totalAvailable}) kurang dari jumlah pemenang yang dibutuhkan ({$required})."
                ], 422);
            }

            /*
            | Grand prize tidak boleh menghasilkan pemenang dari kecamatan
            | yang sama selama masih ada kecamatan yang belum menang, sehingga
            | pengundian bertahap (mis. 12 pemenang dibagi 4 sesi @ 3 pemenang)
            | tetap menghasilkan pemenang dari kecamatan berbeda. Bila semua
            | kecamatan sudah memiliki pemenang grand prize, aturan ini
            | longgar: kecamatan boleh menang lagi.
            */
            $drawnKecamatan = $this->drawingService->getGlobalDrawnKecamatan();
            $spWinners = $this->drawingService->getGlobalWinnerCandidates($required, $isBbnkb, $drawnKecamatan);

            // Fallback: bila kandidat tidak cukup dengan larangan kecamatan
            // (mis. sisa kendaraan hanya ada di kecamatan yang sudah menang),
            // ulangi tanpa larangan agar pengundian tetap bisa berjalan.
            if ($spWinners->count() < $required) {
                $spWinners = $this->drawingService->getGlobalWinnerCandidates($required, $isBbnkb, []);
            }

            if ($spWinners->count() < $required) {
                return response()->json([
                    'message' => "Tidak cukup kendaraan tersedia. Tersedia: {$spWinners->count()}, dibutuhkan: {$required}."
                ], 422);
            }

            $selectedIds = $spWinners
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();
        }

        $required = $selectedIds->count();

        if ($required < 1) {
            return response()->json([
                'message' => "Gagal memilih kendaraan yang cukup. Coba lagi."
            ], 422);
        }

        // Build shuffled prize pool
        $prizePool = [];
        foreach ($prizes as $prize) {
            $qty = min($prize->available_quantity, $required - count($prizePool));
            for ($i = 0; $i < $qty; $i++) {
                $prizePool[] = $prize->id;
                if (count($prizePool) >= $required) break;
            }
            if (count($prizePool) >= $required) break;
        }
        shuffle($prizePool);

        $vehicles = Kendaraan::whereIn('id', $selectedIds)->get()->keyBy('id');

        if ($vehicles->count() < $required) {
            return response()->json([
                'message' => "Gagal memilih kendaraan yang cukup. Coba lagi."
            ], 422);
        }

        $draw = null;
        if (!$isDemo) {
            $draw = Draw::create([
                'prize_id'          => $prizeIds[0],
                'prize_category_id' => $drawCategoryId,
                'quantity'          => $required,
                // Penanda grand prize (global): dipakai untuk aturan satu
                // kecamatan satu pemenang pada undian grand prize berikutnya.
                'is_global'         => true,
            ]);
        }

        $results = [];
        foreach ($selectedIds as $index => $id) {
            $prizeId = $prizePool[$index];
            $vehicle = $vehicles[$id];

            if (!$isDemo) {
                Winner::create([
                    'draw_id'      => $draw->id,
                    'kendaraan_id' => $id,
                    'id_kendaraan' => $vehicle->id_kendaraan,
                    'prize_id'     => $prizeId,
                ]);
            }

            $vehicle->prize = $prizes->firstWhere('id', $prizeId);
            $results[]      = $vehicle;
        }

        $response = ['data' => $results];
        if ($draw) {
            $response['draw_id'] = $draw->id;
        }

        return response()->json($response);
    }

    /**
     * Progres komposisi untuk hadiah yang dipilih.
     *
     * Dipakai front-end untuk menampilkan sisa slot komposisi dan men-guard
     * tombol Simpan bila komposisi sudah terpenuhi (undian bertahap).
     */
    public function progress(Request $request)
    {
        $prizeIds = array_filter(array_map('intval', (array) $request->input('prize_ids', [])));

        $roda2 = (int) $request->input('roda2', (int) config('wilayah.global.roda2', 5));
        $roda4 = (int) $request->input('roda4', (int) config('wilayah.global.roda4', 1));
        $total = $roda2 + $roda4;

        $drawn = $this->drawingService->countDrawnWinnersByRoda($prizeIds);

        $remaining2 = max(0, $roda2 - $drawn['roda2']);
        $remaining4 = max(0, $roda4 - $drawn['roda4']);

        return response()->json([
            'komposisi' => ['roda2' => $roda2, 'roda4' => $roda4, 'total' => $total],
            'drawn'     => ['roda2' => $drawn['roda2'], 'roda4' => $drawn['roda4'], 'total' => $drawn['roda2'] + $drawn['roda4']],
            'remaining' => ['roda2' => $remaining2, 'roda4' => $remaining4, 'total' => $remaining2 + $remaining4],
            'completed' => $total > 0 && ($remaining2 + $remaining4) === 0,
        ]);
    }

    protected function pickBySamsat(
        array $prizeIds,
        array $samsats,
        $totalAvailable,
        bool $isDemo,
        int $perSamsat = 5,
        array $samsatIdsByLocation = [],
        bool $isBbnkb = false
    ) {
        $required  = count($samsats) * $perSamsat;

        if ($totalAvailable < $required) {
            return response()->json([
                'message' => "Total stok hadiah yang dipilih ({$totalAvailable}) kurang dari jumlah pemenang yang dibutuhkan ({$required}). Pilih lebih banyak hadiah atau kurangi jumlah lokasi."
            ], 422);
        }

        $response = DB::transaction(function () use ($prizeIds, $samsats, $perSamsat, $isDemo, $samsatIdsByLocation, $isBbnkb) {
            return $this->drawingService->pickDraw($prizeIds, $samsats, $perSamsat, $isDemo, $samsatIdsByLocation, $isBbnkb);
        });

        if (isset($response['status']) && $response['status'] === 422) {
            return response()->json([
                'message' => $response['message'],
            ], 422);
        }

        return response()->json($response);
    }

    /**
     * Hasil undian (non-demo) yang baru saja tersimpan.
     *
     * Dipakai untuk memulihkan tampilan di front-end bila koneksi terputus
     * (mis. perangkat standby lama): request pengundian mungkin sudah
     * selesai dan tersimpan di server, tetapi respons tidak sempat
     * diterima front-end. Pemenang dikembalikan dalam bentuk yang sama
     * dengan respons endpoint pick.
     */
    public function recent()
    {
        $draw = Draw::query()
            ->where('created_at', '>=', now()->subMinutes(3))
            ->latest('id')
            ->first();

        if (!$draw) {
            return response()->json([
                'data'      => [],
                'recovered' => false,
            ]);
        }

        $winners = $draw->winners()->with(['kendaraan', 'prize.prize_category'])->get();

        $data = $winners
            ->map(function (Winner $winner) {
                $vehicle = $winner->kendaraan;

                if (!$vehicle) {
                    return null;
                }

                $vehicle->prize = $winner->prize;

                // Gunakan id kecamatan sebagai lokasi agar pengelompokan
                // pemenang per kecamatan di front-end tetap benar.
                $vehicle->lokasi = $vehicle->id_kecamatan ?: ($vehicle->lokasi ?? '');

                return $vehicle;
            })
            ->filter()
            ->values();

        return response()->json([
            'data'      => $data,
            'draw_id'   => $draw->id,
            'recovered' => $data->isNotEmpty(),
        ]);
    }
}
