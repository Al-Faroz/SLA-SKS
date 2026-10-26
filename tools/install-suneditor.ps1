# Memasang aset SunEditor v3 tanpa CDN saat aplikasi berjalan
# Jalankan sekali: powershell -ExecutionPolicy Bypass -File tools\install-suneditor.ps1
# Unduhan berasal dari npm registry dan dipin ke versi 3.3.3 (MIT).
$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$target = Join-Path $root 'public\assets\vendor\suneditor'
New-Item -ItemType Directory -Path $target -Force | Out-Null
$temp = Join-Path ([System.IO.Path]::GetTempPath()) ('sla-suneditor-' + [guid]::NewGuid().ToString('N'))
New-Item -ItemType Directory -Path $temp -Force | Out-Null
try {
    $tarball = Join-Path $temp 'suneditor-3.3.3.tgz'
    Invoke-WebRequest -Uri 'https://registry.npmjs.org/suneditor/-/suneditor-3.3.3.tgz' -OutFile $tarball
    tar -xzf $tarball -C $temp
    $dist = Join-Path $temp 'package\dist'
    $js = Join-Path $dist 'suneditor.min.js'
    $css = Join-Path $dist 'suneditor.min.css'
    if (!(Test-Path $js) -or !(Test-Path $css)) {
        throw 'Paket npm tidak mempunyai dist SunEditor v3 yang diharapkan. Aset tidak dipasang.'
    }
    Copy-Item $js (Join-Path $target 'suneditor.min.js') -Force
    Copy-Item $css (Join-Path $target 'suneditor.min.css') -Force
    $license = Join-Path $temp 'package\LICENSE'
    if (Test-Path $license) {
        Copy-Item $license (Join-Path $target 'LICENSE') -Force
    }
    Write-Output 'SunEditor 3.3.3 terpasang di public/assets/vendor/suneditor/'
} finally {
    Remove-Item $temp -Force -Recurse -ErrorAction SilentlyContinue
}
