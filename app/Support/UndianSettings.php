<?php

namespace App\Support;

/**
 * Pengaturan undian aplikasi.
 *
 * Nilai disimpan pada file JSON (storage/app/undian-settings.json) sehingga
 * bisa diubah dari halaman admin (Filament > Pengaturan Undian) tanpa
 * menambah objek baru di database (database hanya menyediakan SP).
 *
 * Bila file belum ada / tidak memuat suatu kunci, nilai bawaan diambil
 * dari .env.
 *
 * Kunci yang dikelola:
 * - draw_mode        : mode pengundian ('' belum di-set, 'kecamatan', 'global6')
 * - winners_per_page : jumlah pemenang per kecamatan untuk 1 halaman
 *                      (bawaan dari .env; bisa diubah dari sidebar undian)
 * - global_roda2     : jumlah pemenang roda 2 (komposisi mode global)
 * - global_roda4     : jumlah pemenang roda 4 (komposisi mode global)
 * - global_total     : jumlah pemenang total per pengundian mode global
 *                      (selalu = roda 2 + roda 4, turunan komposisi)
 *
 * Wilayah (APP_KABKOTA & APP_ID_LOKASI) TIDAK dikelola di sini — langsung
 * diatur pada file .env.
 *
 * Catatan: kelas ini dipakai saat config/wilayah.php dimuat (sebelum service
 * provider berjalan), sehingga hanya boleh memakai fungsi PHP native + env().
 */
class UndianSettings
{
    public const FILE_NAME = 'undian-settings.json';

    /** Daftar mode pengundian yang valid. */
    public const MODE_KECAMATAN = 'kecamatan';

    public const MODE_GLOBAL6 = 'global6';

    public const DRAW_MODES = [self::MODE_KECAMATAN, self::MODE_GLOBAL6];

    /** Cache in-memory agar file hanya dibaca sekali per request. */
    protected static ?array $cache = null;

    public static function path(): string
    {
        return storage_path('app/' . self::FILE_NAME);
    }

    /**
     * Seluruh pengaturan (file + bawaan .env).
     */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $stored = [];

        try {
            if (is_file(self::path())) {
                $raw = (string) file_get_contents(self::path());

                // Hilangkan BOM (mis. file diubah lewat PowerShell/Notepad
                // encoding) agar json_decode tetap berhasil.
                if (str_starts_with($raw, "\xEF\xBB\xBF")) {
                    $raw = substr($raw, 3);
                }

                $decoded = json_decode($raw, true);
                $stored = is_array($decoded) ? $decoded : [];
            }
        } catch (\Throwable $e) {
            $stored = [];
        }

        $stored = array_filter(
            $stored,
            fn ($value) => $value !== null && $value !== ''
        );

        return self::$cache = self::mergeDefaults($stored);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::all()[$key] ?? $default;
    }

    /**
     * Simpan pengaturan ke file JSON.
     */
    public static function save(array $data): array
    {
        $current = self::all();

        $roda2 = max(0, (int) ($data['global_roda2'] ?? $current['global_roda2']));
        $roda4 = max(0, (int) ($data['global_roda4'] ?? $current['global_roda4']));

        $next = [
            'draw_mode'        => in_array(($data['draw_mode'] ?? $current['draw_mode']), self::DRAW_MODES, true)
                ? ($data['draw_mode'] ?? $current['draw_mode'])
                : '',
            'winners_per_page' => max(1, (int) ($data['winners_per_page'] ?? $current['winners_per_page'])),
            'global_roda2'     => $roda2,
            'global_roda4'     => $roda4,
            // Total pemenang selalu membaca komposisi (roda 2 + roda 4).
            'global_total'     => $roda2 + $roda4,
        ];

        $directory = dirname(self::path());

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents(
            self::path(),
            json_encode($next, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        self::$cache = null;

        return $next;
    }

    /**
     * Gabungkan nilai tersimpan dengan bawaan .env.
     */
    protected static function mergeDefaults(array $stored): array
    {
        $roda2 = max(0, (int) ($stored['global_roda2'] ?? 5));
        $roda4 = max(0, (int) ($stored['global_roda4'] ?? 1));

        return [
            'draw_mode'        => in_array(($stored['draw_mode'] ?? ''), self::DRAW_MODES, true) ? $stored['draw_mode'] : '',
            'winners_per_page' => (int) ($stored['winners_per_page'] ?? env('APP_WINNERS_PER_PAGE', 5)),
            'global_roda2'     => $roda2,
            'global_roda4'     => $roda4,
            // Total pemenang selalu = komposisi (roda 2 + roda 4).
            'global_total'     => $roda2 + $roda4,
        ];
    }

    public static function forgetCache(): void
    {
        self::$cache = null;
    }
}
