<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\Prize;
use App\Support\Wilayah;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function prizeAll()
    {
        return response()->json(Prize::with('prize_category')->withCount('winners')->get(), 200);
    }

    public function locationsAll()
    {
        $locations = Kendaraan::select('id_lokasi', 'lokasi')
            ->distinct()
            ->orderBy('lokasi')
            ->get();
        return response()->json($locations, 200);
    }

    public function coordinatorMapping()
    {
        $mapping = include config_path('coordinator.php');
        return response()->json($mapping, 200);
    }

    /**
     * Daftar Kecamatan berdasarkan Kabupaten/Kota aktif yang diatur di .env.
     * Sumber data: config/coordinator.php.
     */
    public function wilayah()
    {
        $active = config('wilayah.active');
        $winnersPerPage = (int) config('wilayah.winners_per_page', 5);
        $idLokasi = collect(config('wilayah.id_lokasi', []))
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $info = Wilayah::find($active);

        if (!$info['found']) {
            return response()->json([
                'kabkota'          => $active,
                'kecamatan'        => [],
                'kecamatan_samsat' => (object) [],
                'samsat_ids'       => [],
                'id_lokasi'        => $idLokasi,
                'id_lokasi_mismatch' => [],
                'id_lokasi_tidak_terpakai' => [],
                'winners_per_page' => $winnersPerPage,
                'error'            => "Kabupaten/Kota \"{$active}\" belum terdaftar pada mapping wilayah. "
                    . 'Silakan sesuaikan nilai APP_KABKOTA pada file .env.',
            ], 200);
        }

        // Validasi silang APP_ID_LOKASI dengan mapping Samsat wilayah aktif.
        $idLokasiMismatch = empty($idLokasi)
            ? []
            : array_values(array_diff($info['samsat_ids'], $idLokasi));
        $idLokasiTidakTerpakai = empty($idLokasi)
            ? []
            : array_values(array_diff($idLokasi, $info['samsat_ids']));

        return response()->json([
            'kabkota'          => $info['name'],
            'label'            => $info['label'],
            'plat'             => $info['plat'],
            'kecamatan'        => $info['kecamatan'],
            'kecamatan_samsat' => $info['kecamatan_samsat'],
            'samsat_ids'       => $info['samsat_ids'],
            'id_lokasi'        => $idLokasi,
            'id_lokasi_mismatch' => $idLokasiMismatch,
            'id_lokasi_tidak_terpakai' => $idLokasiTidakTerpakai,
            'total_kecamatan'  => $info['kecamatan_count'],
            'total_kelurahan'  => $info['kelurahan_count'],
            'winners_per_page' => $winnersPerPage,
        ], 200);
    }
}
