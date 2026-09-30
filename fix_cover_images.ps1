# fix_cover_images.ps1
# PowerShell script to replace placeholder images with the first screenshot

$appsPath = Join-Path $PSScriptRoot "apps_cache.json"
$gamesPath = Join-Path $PSScriptRoot "games_cache.json"

function Update-Cache {
    param(
        [string]$FilePath,
        [string]$ImageKey,
        [string]$ScreenshotKey
    )
    $json = Get-Content -Raw -Path $FilePath | ConvertFrom-Json
    $updated = 0
    foreach ($item in $json.data) {
        if ($item.PSObject.Properties.Name -contains $ImageKey -and $item.PSObject.Properties.Name -contains $ScreenshotKey) {
            $placeholder = $item.$ImageKey -match "placeholder(-game)?\.png"
            $hasScreens = $item.$ScreenshotKey -and $item.$ScreenshotKey.Count -gt 0
            if ($placeholder -and $hasScreens) {
                $item.$ImageKey = $item.$ScreenshotKey[0]
                $updated++
            }
        }
    }
    $json | ConvertTo-Json -Depth 10 | Set-Content -Encoding UTF8 -Path $FilePath
    Write-Host "✓ Updated $updated entries in $(Split-Path $FilePath -Leaf)"
}

# Update apps cache
Update-Cache -FilePath $appsPath -ImageKey "image_url" -ScreenshotKey "screenshots"
# Update games cache
Update-Cache -FilePath $gamesPath -ImageKey "image_url" -ScreenshotKey "short_screenshots"
