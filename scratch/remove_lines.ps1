$file = 'd:\Development\htdocs\myagency\resources\views\pages\dashboard\leads\admin-dashboard.blade.php'
$lines = Get-Content $file
# Keep lines 0..3817 (indices) and 4185..end  (0-indexed = line numbers 1..3818 and 4186..end)
$keep = $lines[0..3817] + $lines[4185..($lines.Length-1)]
Set-Content $file $keep -Encoding UTF8
Write-Host "Done. Kept $($keep.Length) lines."
