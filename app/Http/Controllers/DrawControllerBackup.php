<?php

namespace App\Http\Controllers;

use App\Models\Draw;
use App\Models\Kendaraan;
use App\Models\Prize;
use App\Models\Winner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DrawController extends Controller
{
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

        // 2. Ambil min & max ID kendaraan (dipakai di kedua mode)
        $minId = Kendaraan::minId();
        $maxId = Kendaraan::maxId();

        if ($isGlobal) {
            // =============================================
            // --- GLOBAL DRAW (Grand Prize) ---
            // =============================================
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

            // Ambil semua ID pemenang sebelumnya
            $usedIds = Winner::pluck('kendaraan_id')->toArray();

            // Hitung total kendaraan yang eligible (belum menang)
            $eligibleCount = Kendaraan::whereNotIn('id', $usedIds)->count();
            if ($eligibleCount < $required) {
                return response()->json([
                    'message' => "Tidak cukup kendaraan tersedia. Tersedia: {$eligibleCount}, dibutuhkan: {$required}."
                ], 422);
            }

            // Pilih kendaraan secara acak menggunakan rand()
            $selectedIds = [];
            $maxAttempts = ($maxId - $minId + 1) * 3; // batas iterasi agar tidak infinite loop
            $attempts    = 0;

            while (count($selectedIds) < $required && $attempts < $maxAttempts) {
                $randId = rand($minId, $maxId);
                $attempts++;

                if (!in_array($randId, $selectedIds) && !in_array($randId, $usedIds)) {
                    if (Kendaraan::where('id', $randId)->exists()) {
                        $selectedIds[] = $randId;
                    }
                }
            }

            if (count($selectedIds) < $required) {
                return response()->json([
                    'message' => "Gagal memilih kendaraan yang cukup. Coba lagi."
                ], 422);
            }

            $vehicles = Kendaraan::whereIn('id', $selectedIds)->get()->keyBy('id');

            $draw = null;
            if (!$isDemo) {
                $draw = Draw::create([
                    'prize_id'          => count($prizeIds) === 1 ? $prizeIds[0] : null,
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
        } else {
            // =============================================
            // --- SAMSAT-BASED DRAW ---
            // =============================================
            $samsats   = $request->samsats;
            $perSamsat = 5;
            $required  = count($samsats) * $perSamsat;

            if ($totalAvailable < $required) {
                return response()->json([
                    'message' => "Total stok hadiah yang dipilih ({$totalAvailable}) kurang dari jumlah pemenang yang dibutuhkan ({$required}). Pilih lebih banyak hadiah atau kurangi jumlah Samsat."
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

            // Ambil semua ID pemenang sebelumnya
            $usedIds = Winner::pluck('kendaraan_id')->toArray();

            $draw = null;
            if (!$isDemo) {
                $draw = Draw::create([
                    'prize_id'          => count($prizeIds) === 1 ? $prizeIds[0] : null,
                    'prize_category_id' => $drawCategoryId,
                    'quantity'          => $required,
                ]);
            }

            $selectedVehicles = [];

            foreach ($samsats as $lokasi) {
                // Parse lokasi (mendukung format "MAIN → SUB1, SUB2")
                $searchLocations = [];
                if (str_contains($lokasi, '→')) {
                    $parts = explode('→', $lokasi);
                    $main  = trim($parts[0]);
                    $subs  = explode(',', $parts[1]);
                    $searchLocations[] = strtoupper($main);
                    foreach ($subs as $sub) {
                        $searchLocations[] = strtoupper(trim($sub));
                    }
                } else {
                    $searchLocations[] = strtoupper($lokasi);
                }

                // Ambil semua ID kendaraan yang eligible di samsat ini
                $eligibleIds = Kendaraan::whereIn(DB::raw('UPPER(lokasi)'), $searchLocations)
                    ->whereNotIn('id', $usedIds)
                    ->pluck('id')
                    ->toArray();

                if (count($eligibleIds) < $perSamsat) {
                    if ($draw) {
                        $draw->delete();
                    }
                    return response()->json([
                        'message' => "Tidak cukup kendaraan tersedia di Samsat {$lokasi}. Tersedia: " . count($eligibleIds) . ", dibutuhkan: {$perSamsat}."
                    ], 422);
                }

                // Pilih secara acak dari eligible IDs menggunakan rand()
                $pickedIds   = [];
                $poolSize    = count($eligibleIds);
                $maxAttempts = $poolSize * 3;
                $attempts    = 0;

                while (count($pickedIds) < $perSamsat && $attempts < $maxAttempts) {
                    $randIndex = rand(0, $poolSize - 1);
                    $randId    = $eligibleIds[$randIndex];
                    $attempts++;

                    if (!in_array($randId, $pickedIds)) {
                        $pickedIds[] = $randId;
                    }
                }

                if (count($pickedIds) < $perSamsat) {
                    if ($draw) {
                        $draw->delete();
                    }
                    return response()->json([
                        'message' => "Gagal memilih kendaraan di Samsat {$lokasi}. Coba lagi."
                    ], 422);
                }

                $vehicles = Kendaraan::whereIn('id', $pickedIds)->get()->keyBy('id');

                foreach ($pickedIds as $id) {
                    $usedIds[]  = $id; // tandai sudah dipakai di iterasi berikutnya
                    $vehicle    = $vehicles[$id];
                    $vehicle->lokasi = $lokasi;
                    $selectedVehicles[] = $vehicle;
                }
            }

            $results = [];
            foreach ($selectedVehicles as $index => $vehicle) {
                $prizeId = $prizePool[$index];

                if (!$isDemo) {
                    Winner::create([
                        'draw_id'      => $draw->id,
                        'kendaraan_id' => $vehicle->id,
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
    }
}
