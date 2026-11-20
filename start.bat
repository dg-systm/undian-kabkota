@echo off
cd /d "%~dp0"
echo ============================================
echo   UNDIAN KAB/KOTA - Jalankan Aplikasi
echo ============================================
echo.
echo   URL   : http://127.0.0.1:8000
echo   Admin : http://127.0.0.1:8000/admin
echo   Login : superadmin / 12345678
echo.
echo   (Tekan Ctrl+C untuk berhenti)
echo -------------------------------------------
echo.
echo Menyalakan server, browser akan terbuka otomatis...
echo.

REM Pengaturan undian kini bisa diubah dari halaman admin (Filament > Pengaturan
REM Undian) dan disimpan di storage/app/undian-settings.json. Cache config akan
REM membekukan nilai tersebut, jadi cache config DIHAPUS saat start, bukan dibuat.
echo Menyiapkan cache aplikasi...
php artisan config:clear
php artisan view:clear
echo.

REM Buka browser otomatis setelah server siap (delay sekitar 5 detik)
start "" /min cmd /c "ping -n 6 127.0.0.1 >nul & start http://127.0.0.1:8000"

php artisan serve --host=127.0.0.1 --port=8000
pause
