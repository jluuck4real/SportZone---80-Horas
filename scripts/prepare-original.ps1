$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.IO.Compression.FileSystem
$root = Split-Path $PSScriptRoot
$zip = Join-Path (Split-Path $root) 'SportZone.zip'
$dest = Join-Path $root 'docs\original'
New-Item -ItemType Directory -Force -Path $dest | Out-Null
$archive = [IO.Compression.ZipFile]::OpenRead($zip)
$report = @()
foreach ($entry in $archive.Entries) {
  if (!$entry.Name) { continue }
  $file = Join-Path $root $entry.Name
  $stream = $entry.Open()
  $sha = [Security.Cryptography.SHA256]::Create()
  $hash = [BitConverter]::ToString($sha.ComputeHash($stream)).Replace('-','').ToLower()
  $stream.Dispose()
  $local = (Get-FileHash -LiteralPath $file -Algorithm SHA256).Hash.ToLower()
  $report += "$($entry.Name): ZIP=$hash arquivo=$local iguais=$($hash -eq $local)"
  [IO.Compression.ZipFileExtensions]::ExtractToFile($entry, (Join-Path $dest $entry.Name), $true)
}
$archive.Dispose()
Copy-Item -LiteralPath $zip -Destination (Join-Path $dest 'SportZone.zip')
$report | Set-Content -Encoding utf8 (Join-Path $dest 'integridade.txt')
$report
