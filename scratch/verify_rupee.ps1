$file = 'd:\Development\htdocs\myagency\resources\views\pages\dashboard\leads\admin-dashboard.blade.php'
$bytes = [System.IO.File]::ReadAllBytes($file)
$content = [System.Text.Encoding]::UTF8.GetString($bytes)
$doubleBytes = [byte[]]@(0xC3, 0xA2, 0xE2, 0x80, 0x9A, 0xC2, 0xB9)
$doubleStr = [System.Text.Encoding]::UTF8.GetString($doubleBytes)
$correctBytes = [byte[]]@(0xE2, 0x82, 0xB9)
$correctStr = [System.Text.Encoding]::UTF8.GetString($correctBytes)
Write-Host "Double-encoded rupee present: $($content.Contains($doubleStr))"
Write-Host "Correct rupee present: $($content.Contains($correctStr))"
Write-Host "HO branch fix present: $($content.Contains('branch.is_default'))"

if ($content.Contains($doubleStr)) {
    $count = ($content.Split($doubleStr).Length - 1)
    $content = $content.Replace($doubleStr, $correctStr)
    Write-Host "Re-applying rupee fix: $count instances"
    $outBytes = [System.Text.Encoding]::UTF8.GetBytes($content)
    [System.IO.File]::WriteAllBytes($file, $outBytes)
    Write-Host "Saved."
}
