param([switch]$TestDatabase)
$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath (Split-Path $PSScriptRoot)
if ($TestDatabase) {
  $env:DB_DRIVER='sqlite'
  $env:DB_SQLITE_PATH='storage/sportzone.sqlite'
  $env:APP_ENV='development'
}
php scripts/doctor.php
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
php scripts/migrate.php
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
$adminPassword = Read-Host 'Crie uma senha de 10 a 72 bytes para o administrador' -AsSecureString
$adminCredential = New-Object System.Management.Automation.PSCredential('sportzone',$adminPassword)
try {
  $env:SPORTZONE_ADMIN_PASSWORD=$adminCredential.GetNetworkCredential().Password
  php scripts/create-admin.php
  if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
} finally {
  Remove-Item Env:SPORTZONE_ADMIN_PASSWORD -ErrorAction SilentlyContinue
  Remove-Variable adminPassword,adminCredential -ErrorAction SilentlyContinue
}
