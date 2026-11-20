# Panduan Wilayah Undian (Kabupaten/Kota & Kecamatan)

Aplikasi ini dipakai oleh seluruh Kabupaten/Kota di Provinsi Jawa Tengah.

- **Wilayah aktif** (Kabupaten/Kota & ID Lokasi) diatur langsung pada file `.env`.
- **Mode pengundian & komposisi pemenang global** diatur dari **halaman admin
  (Filament > Pengaturan Undian)**, tersimpan pada file
  `storage/app/undian-settings.json` — tidak menambah objek apa pun di
  database (database hanya menyediakan SP).

## 1. Mengatur Wilayah di .env

Buka file `.env`, lalu isi sesuai wilayah tempat aplikasi dipakai
(pakai tanda kutip karena ada spasi):

```env
APP_KABKOTA="Kabupaten Boyolali"
APP_ID_LOKASI=12
APP_WINNERS_PER_PAGE=1
```

- `APP_KABKOTA` → Kabupaten/Kota aktif. Sidebar **"Pilih Kecamatan"** dan
  statistik dashboard otomatis mengikuti wilayah ini.
- `APP_ID_LOKASI` → ID lokasi/Samsat pada kolom `kendaraans.id_lokasi` untuk
  wilayah aktif. Dipakai sebagai **validasi silang** agar pengundian tidak
  memanggil SP `getWinner` dengan ID Samsat yang salah.
  - Contoh: Kabupaten Boyolali = `12`, sehingga SP dipanggil sebagai
    `EXEC getWinner '12', '<kode_kecamatan>', <jumlah>`.
  - Bila wilayah memiliki lebih dari satu Samsat, isi dengan pemisah koma,
    mis. `APP_ID_LOKASI=1,2,3`.
  - Kosongkan bila tidak ingin validasi otomatis.
- `APP_WINNERS_PER_PAGE` → default jumlah pemenang per kecamatan dalam 1
  halaman (masih bisa diubah dari sidebar saat aplikasi berjalan).

Setelah mengubah `.env`, jalankan:

```bash
php artisan config:clear
```

## 2. Mengatur Mode Pengundian (Halaman Admin)

Login ke `/admin`, buka menu **Pengaturan Undian** (grup menu *Pengaturan*).

### Mode Pengundian (WAJIB dipilih terlebih dahulu)

Selama mode belum di-set, halaman undian hanya menampilkan poster dan
**tidak dapat mengundi** (request pengundian ditolak di backend).

| Mode | Halaman Undian | Pengaturan Lanjutan |
|------|----------------|---------------------|
| **Undian Per Kecamatan (biasa + Grand Prize)** | `/` (per kecamatan) dan `/grand-prize` (global fleksibel) | tidak ada — sisanya diatur di **sidebar pengaturan undian** tiap halaman (kecamatan, hadiah, jumlah pemenang per kecamatan / jumlah pemenang) |
| **Global Semua Kecamatan (Komposisi Pemenang)** | hanya **1 halaman**: `/grand-prize` (halaman `/` otomatis diarahkan ke sana, tombol "Kembali ke Undian Biasa" disembunyikan) | komposisi pemenang di halaman admin: **Pemenang Roda 2** dan **Pemenang Roda 4** — **Total Pemenang terbaca otomatis dari roda 2 + roda 4** (naik/turun mengikuti komposisi). Sidebar Grand Prize menentukan jumlah pemenang **per undian** — bisa **1 per 1 (bertahap)**: tiap undian mengambil sisa slot komposisi (roda 2 dulu, roda 4 terakhir). Bila jumlah yang di-set melebihi sisa slot, undian otomatis memakai seluruh sisa slot (tanpa error); saat komposisi penuh, undian berikutnya ditolak dengan pesan |

Animasi pengundian menampilkan **kode plat daerah aktif saja** (dari mapping
wilayah, mis. Boyolali = `AD`), bukan daftar plat se-Jawa Tengah.

Perubahan pengaturan langsung berlaku untuk halaman undian berikutnya
(tanpa perlu restart). Catatan: **jangan menjalankan `php artisan config:cache`**
karena nilai dari file pengaturan akan terkunci pada cache config; bila
sebelumnya pernah di-cache, jalankan `php artisan config:clear`.

## 3. Sumber Data Wilayah

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
- mencocokkan nama Kabupaten/Kota (tidak membedakan huruf besar/kecil),
- menggabungkan semua Samsat dalam satu Kabupaten/Kota,
- menghilangkan duplikasi dari Samsat Pembantu (data `induk`).

Daftar Kabupaten/Kota yang valid ada 35 (29 kabupaten + 6 kota) dan semuanya
sudah termuat di `config/coordinator.php`.

## 4. Alur Tampilan

1. Halaman pertama menampilkan **poster utama** (gambar full 1 halaman).
2. Terdapat **2 tombol di pojok kanan atas**: tombol Grand Prize dan tombol sidebar.
3. Buka sidebar untuk:
   - melihat Kabupaten/Kota aktif,
   - memilih **Kecamatan** (Select All / per-kecamatan),
   - memilih **Kategori Hadiah** dan **Hadiah**,
   - mengatur **jumlah pemenang per kecamatan**,
   - menekan **Simpan**.
4. Setelah disimpan, halaman undian muncul dan tombol Start dapat ditekan.

## 5. Statistik Dashboard Admin

Widget di dashboard `/admin` menampilkan:
- **Total Kendaraan** → data kendaraan pada Samsat wilayah aktif.
- **Kecamatan** → jumlah kecamatan wilayah aktif (dari `config/coordinator.php`).
- **Kelurahan** → jumlah kelurahan wilayah aktif (dari `config/coordinator.php`).
