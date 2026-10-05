$file = 'd:\Development\htdocs\myagency\resources\views\pages\dashboard\leads\admin-dashboard.blade.php'
$bytes = [System.IO.File]::ReadAllBytes($file)

# Find the fmt function and check bytes around the rupee symbol
$content = [System.Text.Encoding]::UTF8.GetString($bytes)
$fmtIdx = $content.IndexOf("function fmt(n)")
$fmtSnippet = $content.Substring($fmtIdx, 100)
$fmtBytes = [System.Text.Encoding]::UTF8.GetBytes($fmtSnippet)

# Find the rupee area (after "return '")
$rupeeStart = $fmtSnippet.IndexOf("return '") + 8
Write-Host "Chars around rupee in fmt: '$($fmtSnippet.Substring($rupeeStart-1, 8))'"
Write-Host "Bytes: $($fmtBytes[$rupeeStart..($rupeeStart+5)] | ForEach-Object { $_.ToString('X2') })"

# Check if file has double-encoded rupee (C3 A2 = â, E2 80 9A = low comma, C2 B9 = superscript 1)
# Correct UTF-8 for rupee: E2 82 B9
# Double-encoded: C3 A2 E2 80 9A C2 B9
$rupeeUtf8 = [byte[]]@(0xE2, 0x82, 0xB9)
$rupeeDoubleEnc = [byte[]]@(0xC3, 0xA2, 0xE2, 0x80, 0x9A, 0xC2, 0xB9)

$rupeeUtf8Str = [System.Text.Encoding]::UTF8.GetString($rupeeUtf8)
$rupeeDoubleStr = [System.Text.Encoding]::UTF8.GetString($rupeeDoubleEnc)

Write-Host "File contains correct rupee UTF8: $($content.Contains($rupeeUtf8Str))"
Write-Host "File contains double-encoded rupee: $($content.Contains($rupeeDoubleStr))"
Write-Host "Rupee char (U+20B9): $rupeeUtf8Str"
Write-Host "Double encoded looks like: $rupeeDoubleStr"
