# Zip the project into the root folder (parks-geojson-map.zip)
# Excludes: .git, node_modules, and existing zip files

$ErrorActionPreference = 'Stop'
$root = $PSScriptRoot.TrimEnd('\')
$zipPath = Join-Path $root 'parks-geojson-map.zip'

Add-Type -AssemblyName System.IO.Compression.FileSystem
if (Test-Path $zipPath) { Remove-Item $zipPath -Force }

$zip = [System.IO.Compression.ZipFile]::Open($zipPath, 'Create')
try {
    Get-ChildItem -Path $root -Recurse -File | Where-Object {
        $rel = $_.FullName.Substring($root.Length + 1)
        $rel -notlike '.git\*' -and
        $rel -notlike 'node_modules\*' -and
        $rel -notlike '*.zip'
    } | ForEach-Object {
        $entryName = $_.FullName.Substring($root.Length + 1).Replace('\', '/')
        [void][System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $_.FullName, $entryName, 'Optimal')
    }
} finally {
    $zip.Dispose()
}

Write-Host "Created: $zipPath" -ForegroundColor Green
