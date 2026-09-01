param(
    [string]$WrapperPath = (Join-Path $PSScriptRoot '..\platforms\web')
)

$ErrorActionPreference = 'Stop'

function Ensure-Directory {
    param([string]$Path)

    if (-not (Test-Path $Path)) {
        New-Item -ItemType Directory -Path $Path | Out-Null
    }
}

function Remove-IfExists {
    param([string]$Path)

    if (Test-Path $Path) {
        Remove-Item -LiteralPath $Path -Force -Recurse
    }
}

function New-HardLinkSafe {
    param(
        [string]$Path,
        [string]$Target
    )

    if (Test-Path $Path) {
        return
    }

    New-Item -ItemType HardLink -Path $Path -Target $Target | Out-Null
}

function New-JunctionSafe {
    param(
        [string]$Path,
        [string]$Target
    )

    if (Test-Path $Path) {
        return
    }

    New-Item -ItemType Junction -Path $Path -Target $Target | Out-Null
}

$repoRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$sharedRoot = Join-Path $repoRoot 'shared'
$wrapperRoot = if (Test-Path $WrapperPath) { (Resolve-Path $WrapperPath).Path } else { $WrapperPath }

Ensure-Directory $wrapperRoot
Ensure-Directory (Join-Path $wrapperRoot 'bootstrap')
Ensure-Directory (Join-Path $wrapperRoot 'bootstrap\cache')
Ensure-Directory (Join-Path $wrapperRoot 'config')
Ensure-Directory (Join-Path $wrapperRoot 'database')
Ensure-Directory (Join-Path $wrapperRoot 'storage')
Ensure-Directory (Join-Path $wrapperRoot 'storage\app')
Ensure-Directory (Join-Path $wrapperRoot 'storage\framework')
Ensure-Directory (Join-Path $wrapperRoot 'storage\framework\cache')
Ensure-Directory (Join-Path $wrapperRoot 'storage\framework\sessions')
Ensure-Directory (Join-Path $wrapperRoot 'storage\framework\views')
Ensure-Directory (Join-Path $wrapperRoot 'storage\logs')
Ensure-Directory (Join-Path $wrapperRoot 'public')

$sharedDirectories = @(
    'app',
    'lang',
    'resources',
    'routes',
    'tests',
    'database\factories',
    'database\migrations',
    'database\seeders',
    'public\build',
    'public\images\icons'
)

foreach ($relativePath in $sharedDirectories) {
    $linkPath = Join-Path $wrapperRoot $relativePath
    $targetPath = Join-Path $sharedRoot $relativePath
    $parentPath = Split-Path -Parent $linkPath

    Ensure-Directory $parentPath
    Remove-IfExists $linkPath
    New-JunctionSafe -Path $linkPath -Target $targetPath
}

$sharedFiles = @(
    '.editorconfig',
    '.gitattributes',
    'artisan',
    'config.json',
    'package.json',
    'package-lock.json',
    'vite.config.js',
    'phpunit.xml',
    'bootstrap\app.php',
    'bootstrap\providers.php',
    'public\.htaccess',
    'public\favicon.ico',
    'public\index.php',
    'public\robots.txt'
)

foreach ($relativePath in $sharedFiles) {
    $linkPath = Join-Path $wrapperRoot $relativePath
    $targetPath = Join-Path $sharedRoot $relativePath
    $parentPath = Split-Path -Parent $linkPath

    Ensure-Directory $parentPath
    Remove-IfExists $linkPath
    New-HardLinkSafe -Path $linkPath -Target $targetPath
}

$sharedConfigFiles = @(
    'app.php',
    'auth.php',
    'cache.php',
    'database.php',
    'filesystems.php',
    'logging.php',
    'mail.php',
    'queue.php',
    'services.php',
    'session.php'
)

foreach ($fileName in $sharedConfigFiles) {
    $linkPath = Join-Path $wrapperRoot ("config\" + $fileName)
    $targetPath = Join-Path $sharedRoot ("config\" + $fileName)

    Remove-IfExists $linkPath
    New-HardLinkSafe -Path $linkPath -Target $targetPath
}

$databaseFile = Join-Path $wrapperRoot 'database\database.sqlite'
if (-not (Test-Path $databaseFile)) {
    New-Item -ItemType File -Path $databaseFile | Out-Null
}

Write-Host "Web wrapper preparado en $wrapperRoot"
