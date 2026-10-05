$bytes = [System.IO.File]::ReadAllBytes('d:\Development\htdocs\myagency\resources\views\pages\dashboard\leads\admin-dashboard.blade.php')
$bom = $bytes[0..3] | ForEach-Object { $_.ToString('X2') }
Write-Host "First 4 bytes: $($bom -join ' ')"
# Check for UTF-8 BOM (EF BB BF)
if ($bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
    Write-Host "File has UTF-8 BOM"
} else {
    Write-Host "No BOM detected"
}
