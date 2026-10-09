# Menjalankan Undian 2.0 di SQL Server

Panduan ini untuk PC yang **sudah** memiliki SQL Server, driver ODBC, dan ekstensi PHP `pdo_sqlsrv`.
Hasil pemeriksaan mesin Anda:

| Komponen | Status |
|---|---|
| SQL Server | 2025 Developer Edition, service `MSSQLSERVER` **Running** |
| Driver ODBC | `ODBC Driver 17` & `18 for SQL Server` |
| PHP | 8.2.12 (XAMPP, ZTS x64) |
| Ekstensi PHP | `pdo_sqlsrv` **aktif**, `sqlsrv` **aktif** |
| Autentikasi server | Windows Authentication (`IsIntegratedSecurityOnly = 1`) |
| Database `undian_samsat` | **sudah dibuat**, sudah **migrate + seed** |
| TCP/IP | Aktif sementara di port **1434** saja → perlu dibuat permanen di **1433** |

Artinya tinggal **satu langkah wajib** (mengaktifkan TCP/IP port 1433 secara permanen) sebelum aplikasi dijalankan.

---

## 1. Perubahan yang sudah dilakukan di project

- `.env` → memakai SQL Server:
  ```env
  DB_CONNECTION=sqlsrv
  DB_HOST=127.0.0.1
  DB_PORT=1433
  DB_DATABASE=undian_samsat
  DB_USERNAME=
  DB_PASSWORD=
  DB_ENCRYPT=yes
  DB_TRUST_SERVER_CERTIFICATE=yes
  ```
  `DB_USERNAME`/`DB_PASSWORD` dikosongkan = **Windows Authentication** (login memakai akun Windows Anda).
- `.env.example` → didokumentasikan (SQL Server aktif, MySQL sebagai komentar).
- `config/database.php` → blok `sqlsrv` mengaktifkan `encrypt` + `trust_server_certificate` dari env.
- `database/migrations/2026_06_04_000001_add_prize_category_id_to_draws_table.php` → `->after('prize_id')` dihapus karena tidak didukung SQL Server.
- Stored procedure `getWinner` sudah otomatis dibuat versi SQL Server oleh migrasi `2026_06_18_095935_create_function_get_winner.php`.

---

## 2. Langkah wajib: aktifkan TCP/IP di port 1433

Pilih salah satu cara.

### Cara A — SQL Server Configuration Manager (disarankan)

1. Tekan `Win + R`, ketik `SQLServerManager17.msc`, lalu Enter
   (kalau tidak ada, cari **SQL Server Configuration Manager** di Start Menu).
2. Buka **SQL Server Network Configuration** → **Protocols for MSSQLSERVER**.
3. Klik kanan **TCP/IP** → **Enable** (statusnya jadi `Enabled`).
4. Klik kanan **TCP/IP** → **Properties** → tab **IP Addresses**.
5. Scroll paling bawah ke bagian **IPAll**:
   - **TCP Port** = `1433`
   - **TCP Dynamic Ports** = **kosongkan**
6. Klik **OK**.
7. Buka **SQL Server Services** → klik kanan **SQL Server (MSSQLSERVER)** → **Restart**.
8. Pastikan statusnya kembali **Running**.

### Cara B — skrip otomatis (PowerShell sebagai Administrator)

- Klik kanan **Windows PowerShell** → **Run as administrator**, lalu jalankan:
  ```powershell
  cd "C:\Users\USER A\Documents\projects\undian2.0"
  powershell -ExecutionPolicy Bypass -File .\scripts\enable-sqlserver-tcp.ps1
  ```
- Skrip akan: mengaktifkan TCP/IP, menyetel port `1433`, me-restart `MSSQLSERVER`, dan memverifikasi port.

> Catatan: skrip memakai instance `MSSQL17.MSSQLSERVER` (SQL Server 2025). Ubah variabel `$regInstance` bila versi SQL Server berbeda (2022 = `MSSQL16`, 2019 = `MSSQL15`).

---

## 3. Verifikasi koneksi

Di PowerShell:

```powershell
# Service berjalan?
Get-Service MSSQLSERVER | Select-Object Name, Status

# Port 1433 sudah mendengarkan?
Get-NetTCPConnection -LocalPort 1433 -State Listen

# Tes query (Windows auth)
sqlcmd -S "tcp:127.0.0.1,1433" -E -C -d undian_samsat -Q "SELECT DB_NAME() AS db, SUSER_SNAME() AS [login]"
```

Di aplikasi:

```powershell
cd "C:\Users\USER A\Documents\projects\undian2.0"
php artisan config:clear
php artisan migrate:status
```

`migrate:status` akan menampilkan semua migrasi `Ran`.

---

## 4. Menjalankan aplikasi

```powershell
cd "C:\Users\USER A\Documents\projects\undian2.0"
php artisan config:clear
php artisan serve
```

Buka `http://127.0.0.1:8000`.

Akun admin dari seeder:

| Field | Nilai |
|---|---|
| Username | `superadmin` |
| Email | `superadmin@example.com` |
| Password | `12345678` |

> Gunakan `php artisan serve` (bukan Apache) agar Windows Authentication otomatis memakai akun Windows Anda.

---

## 5. Sementara tanpa restart (opsional)

Jika Anda ingin mencoba lebih dulu tanpa me-restart SQL Server, instans yang sedang berjalan sudah
mendengarkan port **1434**. Ubah satu baris di `.env`:

```env
DB_PORT=1434
```

lalu `php artisan config:clear`. Ini **hanya sementara**: begitu SQL Server / PC di-restart, port 1434
hilang dan aplikasi error. Karena itu tetap lakukan **Langkah 2**.

---

## 6. Jika database perlu di-reset

Perintah `php artisan migrate:fresh` **sengaja dinonaktifkan** oleh project ini di
`app/Providers/AppServiceProvider.php`. Untuk membangun ulang:

```powershell
php artisan db:wipe       # menghapus SEMUA tabel (semua data pemenang/draw hilang)
php artisan migrate --seed
```

Untuk menambah data awal saja tanpa menghapus:

```powershell
php artisan db:seed
```

---

## 7. Alternatif: SQL Server Authentication (opsional)

Cocok bila aplikasi diakses lewat web server lain (mis. Apache sebagai service) atau multi-user.
SQL Server Anda saat ini **Windows-only**, jadi perlu mengaktifkan mode mixed terlebih dahulu.

1. Aktifkan **SQL Server and Windows Authentication mode**:
   - Di SSMS: klik kanan server → **Properties** → **Security** → pilih **SQL Server and Windows Authentication mode** → OK → restart service, **atau**
   - Di skrip TCP di atas, tambahkan nilai registry `LoginMode = 2` pada
     `HKLM:\SOFTWARE\Microsoft\Microsoft SQL Server\MSSQL17.MSSQLSERVER\MSSQLServer\` lalu restart.
2. Buat login + user (jalankan via `sqlcmd -S localhost -E -C`):
   ```sql
   CREATE LOGIN undian_user WITH PASSWORD = 'GantiPasswordIni#2026';
   USE undian_samsat;
   CREATE USER undian_user FOR LOGIN undian_user;
   ALTER ROLE db_owner ADD MEMBER undian_user;
   ```
3. Ubah `.env`:
   ```env
   DB_USERNAME=undian_user
   DB_PASSWORD=GantiPasswordIni#2026
   ```
4. `php artisan config:clear`

---

## 8. Kembali ke MySQL / XAMPP

Di `.env`, komentari blok SQL Server dan aktifkan blok MySQL, lalu:

```powershell
php artisan config:clear
```

---

## 9. Troubleshooting

| Pesan error | Penyebab & solusi |
|---|---|
| `certificate chain was issued by an authority that is not trusted` | `DB_TRUST_SERVER_CERTIFICATE=yes` belum terbaca. Pastikan ada di `.env`, lalu `php artisan config:clear`. |
| `TCP Provider: No connection could be made because the target machine actively refused it` | TCP/IP belum aktif atau port salah. Ulangi **Langkah 2**, pastikan `DB_PORT=1433`. |
| `Login failed for user '...'` | Akun Windows yang menjalankan aplikasi belum punya login SQL Server, atau mode auth salah. Lihat bagian 7. |
| `could not find driver` | Ekstensi `pdo_sqlsrv` tidak aktif. Di mesin ini sudah aktif; cek `php -m`. |
| Aplikasi jalan di CLI tapi gagal di Apache | Apache berjalan sebagai service account berbeda. Pakai `php artisan serve`, atau gunakan SQL Authentication (bagian 7). |
| Perubahan `.env` tidak terasa | Jalankan `php artisan config:clear` (dan `php artisan cache:clear` bila perlu). |

---

**Ringkas:** jalankan **Langkah 2** sekali (aktifkan TCP/IP 1433 + restart), lalu `php artisan config:clear`
dan `php artisan serve`. Aplikasi siap dipakai dengan SQL Server.
