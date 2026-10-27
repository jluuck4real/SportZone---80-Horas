param([switch]$TestDatabase, [int]$Port = 8080)
$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath (Split-Path $PSScriptRoot)
if ($TestDatabase) {
  $env:DB_DRIVER = 'sqlite'
  $env:DB_SQLITE_PATH = 'storage/sportzone.sqlite'
  $env:APP_ENV = 'development'
  Write-Host 'MODO DE TESTE EXPLÍCITO: SQLite. Não substitui a homologação SQL Server.'
  if (!(Test-Path -LiteralPath 'storage/sportzone.sqlite')) {
    php scripts/install.php
    if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
  }
  php scripts/migrate.php
  if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
}
php scripts/doctor.php
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
Write-Host "SportZone: http://localhost:$Port — Ctrl+C para encerrar"
php -S "localhost:$Port" router.php
