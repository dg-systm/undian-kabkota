<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi Wilayah Undian
|--------------------------------------------------------------------------
|
| Aplikasi ini dipakai oleh seluruh Kabupaten/Kota di Provinsi Jawa Tengah.
|
| Wilayah aktif diatur langsung pada file .env:
|
|     APP_KABKOTA="Kabupaten Boyolali"
|     APP_ID_LOKASI=12
|
| Daftar lengkap Kabupaten/Kota, kecamatan, dan kelurahan bersumber dari
| file config/coordinator.php (mapping Plat -> Samsat -> Kota/Kabupaten ->
| Kecamatan -> Kelurahan) yang dibaca melalui App\Support\Wilayah.
|
| Nama pada APP_KABKOTA harus sama dengan salah satu nilai 'kota' pada
| config/coordinator.php (pencocokan tidak membedakan huruf besar/kecil).
|
| Pengaturan lain (mode pengundian, komposisi pemenang global) diatur dari
| halaman admin (Filament > Pengaturan Undian) melalui
| App\Support\UndianSettings.
|
| Catatan: jangan jalankan `php artisan config:cache`, karena nilai dari
| file pengaturan akan terkunci pada cache config.
|
*/

$undianSettings = App\Support\UndianSettings::all();

return [

    /*
    |--------------------------------------------------------------------------
    | Mode Pengundian
    |--------------------------------------------------------------------------
    |
    | ''          -> belum di-set (admin wajib mengatur dulu; mengundi ditolak)
    | 'kecamatan' -> undian per kecamatan (halaman undian biasa + grand prize)
    | 'global6'   -> global semua kecamatan dengan komposisi pemenang
    |
    */
    'draw_mode' => $undianSettings['draw_mode'],

    // Kabupaten/Kota aktif (diatur langsung di .env)
    'active' => env('APP_KABKOTA', ''),

    /*
    |--------------------------------------------------------------------------
    | ID Lokasi / Samsat (validasi silang)
    |--------------------------------------------------------------------------
    |
    | Nilai kolom kendaraans.id_lokasi untuk wilayah aktif. Dipakai sebagai
    | pengaman agar aplikasi tidak memanggil SP getWinner dengan ID Samsat
    | yang salah. Contoh: Kabupaten Boyolali = 12.
    |
    | Bila wilayah memiliki lebih dari satu Samsat, isi dengan pemisah koma,
    | mis. APP_ID_LOKASI=1,2,3. Kosongkan bila tidak ingin validasi otomatis.
    |
    */
    'id_lokasi' => array_values(array_unique(array_filter(array_map(
        'intval',
        explode(',', (string) env('APP_ID_LOKASI', ''))
    )))),

    // Jumlah pemenang per lokasi/kecamatan untuk 1 halaman (bawaan)
    'winners_per_page' => $undianSettings['winners_per_page'],

    /*
    |--------------------------------------------------------------------------
    | Mode Global (Semua Kecamatan) - Komposisi Pemenang
    |--------------------------------------------------------------------------
    |
    | Dipakai halaman Grand Prize (undian global semua kecamatan):
    | jumlah pemenang roda 2 dan roda 4. Total pemenang selalu =
    | roda 2 + roda 4 (default 5 + 1 = 6). Fleksibel, diubah dari halaman
    | admin (Filament > Pengaturan Undian).
    |
    */
    'global' => [
        'roda2' => $undianSettings['global_roda2'],
        'roda4' => $undianSettings['global_roda4'],
        'total' => $undianSettings['global_total'],
    ],

];
