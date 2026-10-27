param([ValidatePattern('^[A-Za-z0-9_-]+\.zip$')][string]$OutputName = 'SportZone-entrega.zip')
$ErrorActionPreference = 'Stop'
$projectRoot = [IO.Path]::GetFullPath((Split-Path $PSScriptRoot))
$output = Join-Path $projectRoot $OutputName
Add-Type -AssemblyName System.IO.Compression.FileSystem
Add-Type -AssemblyName System.IO.Compression
# Create mode deliberately refuses to overwrite an existing delivery package.
$archive = [IO.Compression.ZipFile]::Open($output, [IO.Compression.ZipArchiveMode]::Create)
try {
  $files = Get-ChildItem -LiteralPath $projectRoot -File | Where-Object {
    $_.Name -match '\.(html|css|js|php|md)$' -or $_.Name -in @('.env.example','.gitignore','.htaccess','package.json','package-lock.json')
  }
  foreach ($folder in @('app','assets','config','data','docs','scripts','sql','tests')) {
    $files += Get-ChildItem -LiteralPath (Join-Path $projectRoot $folder) -File -Recurse
  }
  $files += Get-Item -LiteralPath (Join-Path $projectRoot 'storage\.gitkeep')
  foreach ($file in $files) {
    $relative = $file.FullName.Substring($projectRoot.Length + 1).Replace('\','/')
    [IO.Compression.ZipFileExtensions]::CreateEntryFromFile($archive, $file.FullName, ('SportZone/' + $relative), [IO.Compression.CompressionLevel]::Optimal) | Out-Null
  }
} finally { $archive.Dispose() }
Write-Host "Entrega criada: $output"
Write-Host 'Excluídos .env, node_modules e bancos locais/testes de storage. O banco deve ser instalado a partir dos scripts.'
