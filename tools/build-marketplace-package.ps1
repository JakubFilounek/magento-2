param(
    [string]$Version = "2.3.0"
)

$ErrorActionPreference = "Stop"

$repoRoot = Resolve-Path (Join-Path $PSScriptRoot "..")
$moduleRoot = Join-Path $repoRoot "app/code/Ecomail/Ecomail"
$distRoot = Join-Path $repoRoot "dist"
$packageName = "ecomail-magento2-ecomail-$Version.zip"
$packagePath = Join-Path $distRoot $packageName
$tempRoot = Join-Path ([IO.Path]::GetTempPath()) ("ecomail-marketplace-" + [Guid]::NewGuid().ToString("N"))
$stagingRoot = Join-Path $tempRoot "package"
$excludedRoots = @(".git", ".github", "var", "generated", "pub", "dist")

if (-not (Test-Path $moduleRoot)) {
    throw "Module root not found: $moduleRoot"
}

New-Item -ItemType Directory -Force -Path $distRoot | Out-Null
New-Item -ItemType Directory -Force -Path $stagingRoot | Out-Null

if (Test-Path $packagePath) {
    Remove-Item -LiteralPath $packagePath -Force
}

try {
    Get-ChildItem -LiteralPath $moduleRoot -Recurse -File -Force | ForEach-Object {
        $relative = $_.FullName.Substring($moduleRoot.Length).TrimStart(
            [IO.Path]::DirectorySeparatorChar,
            [IO.Path]::AltDirectorySeparatorChar
        )
        $firstSegment = ($relative -split "[\\/]")[0]

        if ($firstSegment -in $excludedRoots) {
            return
        }

        $destination = Join-Path $stagingRoot $relative
        $destinationDirectory = Split-Path -Parent $destination
        New-Item -ItemType Directory -Force -Path $destinationDirectory | Out-Null
        Copy-Item -LiteralPath $_.FullName -Destination $destination
    }

    Push-Location $stagingRoot
    try {
        Compress-Archive -Path * -DestinationPath $packagePath -CompressionLevel Optimal
    } finally {
        Pop-Location
    }
} finally {
    if (Test-Path $tempRoot) {
        Remove-Item -LiteralPath $tempRoot -Recurse -Force
    }
}

Write-Host "Created $packagePath"
