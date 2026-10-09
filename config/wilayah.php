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

    // Jumlah pemenang per lokasi/kecamatan untuk 1 halaman (default)
    'winners_per_page' => (int) env('APP_WINNERS_PER_PAGE', 5),

];
