<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi Wilayah Undian
|--------------------------------------------------------------------------
|
| Aplikasi ini dipakai oleh seluruh Kabupaten/Kota di Provinsi Jawa Tengah.
| Sebelum digunakan, WAJIB mengatur Kabupaten/Kota aktif pada file .env:
|
|     APP_KABKOTA="Kota Semarang"
|
| Daftar lengkap Kabupaten/Kota, kecamatan, dan kelurahan bersumber dari
| file config/coordinator.php (mapping Plat -> Samsat -> Kota/Kabupaten ->
| Kecamatan -> Kelurahan) yang dibaca melalui App\Support\Wilayah.
|
| Nama pada APP_KABKOTA harus sama dengan salah satu nilai 'kota' pada
| config/coordinator.php (pencocokan tidak membedakan huruf besar/kecil).
|
*/

return [

    // Kabupaten/Kota aktif (diatur di .env)
    'active' => env('APP_KABKOTA', 'Kota Semarang'),

    /*
    |--------------------------------------------------------------------------
    | ID Lokasi / Samsat (validasi silang)
    |--------------------------------------------------------------------------
    |
    | Nilai kolom kendaraans.id_lokasi untuk wilayah aktif. Dipakai sebagai
    | pengaman agar aplikasi tidak memanggil SP getWinner dengan ID Samsat
    | yang salah. Contoh: Kabupaten Grobogan = 8.
    |
    | Bila wilayah memiliki lebih dari satu Samsat, isi semuanya dengan
    | pemisah koma, mis. APP_ID_LOKASI=1,2,3. Kosongkan bila tidak ingin
    | validasi otomatis.
    |
    */
    'id_lokasi' => array_values(array_unique(array_filter(array_map(
        'intval',
        explode(',', (string) env('APP_ID_LOKASI', ''))
    )))),

    // Jumlah pemenang per lokasi/kecamatan untuk 1 halaman (default)
    'winners_per_page' => (int) env('APP_WINNERS_PER_PAGE', 5),

];
