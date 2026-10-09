<#
    enable-sqlserver-tcp.ps1
    -------------------------------------------------------------------------
    Mengaktifkan protokol TCP/IP pada SQL Server default instance (MSSQLSERVER)
    di port 1433, lalu me-restart service-nya.

    JALANKAN DENGAN HAK ADMINISTRATOR:
        Klik kanan "Windows PowerShell" -> "Run as administrator", lalu:
        cd "C:\Users\USER A\Documents\projects\undian2.0"
        powershell -ExecutionPolicy Bypass -File .\scripts\enable-sqlserver-tcp.ps1

    Catatan: skrip ini khusus SQL Server 2025 (instance registry MSSQL17).
    Jika versi SQL Server Anda berbeda, ubah $regInstance di bawah.
#>

$ErrorActionPreference = 'Stop'

if (-not ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    Write-Host 'Skrip ini HARUS dijalankan sebagai Administrator.' -ForegroundColor Red
    exit 1
}

$serviceName = 'MSSQLSERVER'
$regInstance = 'MSSQL17.MSSQLSERVER'   # SQL Server 2025. SQL 2022 = MSSQL16, 2019 = MSSQL15
$key = "HKLM:\SOFTWARE\Microsoft\Microsoft SQL Server\$regInstance\MSSQLServer\SuperSocketNetLib\Tcp"

Write-Host "Mengaktifkan TCP/IP untuk instance '$regInstance'..." -ForegroundColor Cyan
Set-ItemProperty -Path $key -Name Enabled -Value 1
Set-ItemProperty -Path "$key\IPAll" -Name TcpPort -Value '1433'
Set-ItemProperty -Path "$key\IPAll" -Name TcpDynamicPorts -Value ''

# Set semua alamat IP spesifik ke port statis 1433 agar konsisten
Get-ChildItem $key | Where-Object { $_.PSChildName -like 'IP*' -and $_.PSChildName -ne 'IPAll' } | ForEach-Object {
    Set-ItemProperty -Path $_.PSPath -Name TcpPort -Value '1433'
    Set-ItemProperty -Path $_.PSPath -Name TcpDynamicPorts -Value ''
}

Write-Host "Merestart service '$serviceName'..." -ForegroundColor Cyan
Restart-Service -Name $serviceName -Force

$svc = Get-Service $serviceName
$svc.WaitForStatus('Running', '00:01:00')
Start-Sleep -Seconds 2

Write-Host ''
Write-Host "Status service : $($svc.Status)" -ForegroundColor Green
$listen = Get-NetTCPConnection -LocalPort 1433 -State Listen -ErrorAction SilentlyContinue
if ($listen) {
    Write-Host 'OK: SQL Server mendengarkan TCP port 1433.' -ForegroundColor Green
    $listen | Select-Object LocalAddress, LocalPort | Format-Table -AutoSize
} else {
    Write-Host 'PERINGATAN: port 1433 belum mendengarkan. Cek SQL Server Configuration Manager.' -ForegroundColor Yellow
}
