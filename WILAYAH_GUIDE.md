# Panduan Wilayah Undian (Kabupaten/Kota & Kecamatan)

Aplikasi ini dipakai oleh seluruh Kabupaten/Kota di Provinsi Jawa Tengah.
Sebelum digunakan, **wajib mengatur Kabupaten/Kota aktif di file `.env`**.

## 1. Mengatur Kabupaten/Kota

Buka file `.env`, lalu isi `APP_KABKOTA` sesuai wilayah tempat aplikasi dipakai
(pakai tanda kutip karena ada spasi):

```env
APP_KABKOTA="Kabupaten Demak"
APP_WINNERS_PER_PAGE=5
```

- `APP_KABKOTA` → Kabupaten/Kota aktif. Sidebar **"Pilih Kecamatan"** dan
  statistik dashboard otomatis mengikuti wilayah ini.
- `APP_WINNERS_PER_PAGE` → default jumlah pemenang per kecamatan dalam 1 halaman
  (masih bisa diubah dari sidebar saat aplikasi berjalan).

Setelah mengubah `.env`, jalankan:

```bash
php artisan config:clear
```

## 2. Sumber Data Wilayah

Daftar lengkap **Kabupaten/Kota, Kecamatan, dan Kelurahan se-Jawa Tengah**
berasal dari file:

```
config/coordinator.php
```

Struktur file tersebut:

```
Plat -> Samsat -> Kota/Kabupaten -> Kecamatan -> Kelurahan
```

Data ini dibaca melalui helper `App\Support\Wilayah`, yang otomatis:
- mencocokkan nama Kabupaten/Kota di `.env` (tidak membedakan huruf besar/kecil),
- menggabungkan semua Samsat dalam satu Kabupaten/Kota,
- menghilangkan duplikasi dari Samsat Pembantu (data `induk`).

Daftar Kabupaten/Kota yang valid ada 35 (29 kabupaten + 6 kota) dan semuanya
sudah termuat di `config/coordinator.php`.

## 3. Alur Tampilan

1. Halaman pertama menampilkan **poster utama** (gambar full 1 halaman).
2. Terdapat **2 tombol di pojok kanan atas**: tombol Grand Prize dan tombol sidebar.
3. Buka sidebar untuk:
   - melihat Kabupaten/Kota aktif (dari `.env`),
   - memilih **Kecamatan** (Select All / per-kecamatan),
   - memilih **Kategori Hadiah** dan **Hadiah**,
   - mengatur **jumlah pemenang per kecamatan**,
   - menekan **Simpan**.
4. Setelah disimpan, halaman undian muncul dan tombol Start dapat ditekan.

## 4. Statistik Dashboard Admin

Widget di dashboard `/admin` menampilkan:
- **Total Kendaraan** → data kendaraan pada Samsat wilayah aktif.
- **Kecamatan** → jumlah kecamatan wilayah aktif (dari `config/coordinator.php`).
- **Kelurahan** → jumlah kelurahan wilayah aktif (dari `config/coordinator.php`).
