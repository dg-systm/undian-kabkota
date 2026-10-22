@echo off
REM ============================================================
REM  UNDIAN SAMSAT - AUTO START SAAT LAPTOP DINYALAKAN / LOGIN
REM ------------------------------------------------------------
REM  File ini dijalankan otomatis lewat shortcut di folder Startup.
REM  Lokasi shortcut:
REM    %APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup
REM
REM  Untuk menonaktifkan autorun:
REM    - Buka Win+R, ketik: shell:startup
REM    - Hapus shortcut "Undian Samsat.lnk"
REM ============================================================

setlocal enableextensions
cd /d "%~dp0"

REM ---------- Konfigurasi ----------
set "APP_HOST=127.0.0.1"
set "APP_PORT=8000"
REM 1 = buka browser otomatis setelah server siap, 0 = jangan buka
set "OPEN_BROWSER=1"

set "LOG_DIR=%~dp0storage\logs"
set "LOG_FILE=%LOG_DIR%\autorun.log"
set "SERVER_LOG=%LOG_DIR%\autorun-server.log"
if not exist "%LOG_DIR%" mkdir "%LOG_DIR%"

REM ---------- Cari PHP ----------
set "PHP_BIN=php"
where php >nul 2>&1
if errorlevel 1 (
    if exist "%LOCALAPPDATA%\Programs\php\php.exe" (
        set "PHP_BIN=%LOCALAPPDATA%\Programs\php\php.exe"
    ) else (
        echo [%date% %time%] GAGAL: PHP tidak ditemukan.>>"%LOG_FILE%"
        exit /b 1
    )
)

REM ---------- Cegah server ganda ----------
netstat -ano | findstr ":%APP_PORT%" | findstr "LISTENING" >nul
if not errorlevel 1 (
    echo [%date% %time%] Server sudah berjalan di port %APP_PORT%, autorun dilewati.>>"%LOG_FILE%"
    if "%OPEN_BROWSER%"=="1" (
        echo [%date% %time%] Membuka browser ke http://%APP_HOST%:%APP_PORT%>>"%LOG_FILE%"
        start "" "http://%APP_HOST%:%APP_PORT%"
    )
    exit /b 0
)

echo.>>"%LOG_FILE%"
echo [%date% %time%] Menjalankan aplikasi Undian Samsat...>>"%LOG_FILE%"

REM ---------- Siapkan cache aplikasi ----------
"%PHP_BIN%" artisan optimize >>"%LOG_FILE%" 2>&1

REM ---------- Jalankan server di jendela terpisah (minimized) ----------
start "Undian Samsat Server" /min cmd /c ""%PHP_BIN%" artisan serve --host=%APP_HOST% --port=%APP_PORT% >>"%SERVER_LOG%" 2>&1"

REM ---------- Buka browser ----------
if "%OPEN_BROWSER%"=="1" (
    echo [%date% %time%] Membuka browser ke http://%APP_HOST%:%APP_PORT%>>"%LOG_FILE%"
    start "" /min cmd /c "ping -n 6 127.0.0.1 >nul & start http://%APP_HOST%:%APP_PORT%"
)

exit /b 0
