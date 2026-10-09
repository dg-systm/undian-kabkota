<?php

namespace App\Http\Controllers;

use App\Models\Draw;
use App\Models\Kendaraan;
use App\Models\Prize;
use App\Models\Winner;
use App\Services\DrawingService;
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

        $prizeIds = $request->prize_ids;

        // 1. Validasi prize dan ketersediaan
        $prizes      = Prize::whereIn('id', $prizeIds)->get();
        $categoryIds = $prizes->pluck('prize_category_id')->unique();

        if ($categoryIds->count() > 1) {
            return response()->json([
                'message' => 'Semua hadiah harus berasal dari satu kategori yang sama.'
            ], 422);
        }

        $drawCategoryId = $categoryIds->first();
        $totalAvailable = $prizes->sum(fn($p) => $p->available_quantity);

        if ($isGlobal) {
            return $this->pickGlobal($request, $prizeIds, $prizes, $drawCategoryId, $totalAvailable, $isDemo);
        }

        $perSamsat = (int) $request->input('per_samsat', 5);
        $samsatIdsByLocation = (array) $request->input('samsat_ids', []);

        return $this->pickBySamsat(
            $prizeIds,
            $request->samsats,
            $totalAvailable,
            $isDemo,
            $perSamsat,
            $samsatIdsByLocation
        );
    }

    protected function pickGlobal(
        Request $request,
        array $prizeIds,
        Collection $prizes,
        $drawCategoryId,
        $totalAvailable,
        bool $isDemo
    ) {
        $required = (int) $request->quantity;

        if ($totalAvailable < $required) {
            return response()->json([
                'message' => "Total stok hadiah yang dipilih ({$totalAvailable}) kurang dari jumlah pemenang yang dibutuhkan ({$required})."
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

        $spWinners = collect($this->drawingService->getWinner(999, $required));

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

        if ($selectedIds->count() < $required) {
            return response()->json([
                'message' => "Gagal memilih kendaraan yang cukup. Coba lagi."
            ], 422);
        }

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

    protected function pickBySamsat(
        array $prizeIds,
        array $samsats,
        $totalAvailable,
        bool $isDemo,
        int $perSamsat = 5,
        array $samsatIdsByLocation = []
    ) {
        $required  = count($samsats) * $perSamsat;

        if ($totalAvailable < $required) {
            return response()->json([
                'message' => "Total stok hadiah yang dipilih ({$totalAvailable}) kurang dari jumlah pemenang yang dibutuhkan ({$required}). Pilih lebih banyak hadiah atau kurangi jumlah lokasi."
            ], 422);
        }

        $response = DB::transaction(function () use ($prizeIds, $samsats, $perSamsat, $isDemo, $samsatIdsByLocation) {
            return $this->drawingService->pickDraw($prizeIds, $samsats, $perSamsat, $isDemo, $samsatIdsByLocation);
        });

        if (isset($response['status']) && $response['status'] === 422) {
            return response()->json([
                'message' => $response['message'],
            ], 422);
        }

        return response()->json($response);
    }
}
