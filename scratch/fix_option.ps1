$file = 'd:\Development\htdocs\myagency\resources\views\pages\dashboard\leads\admin-dashboard.blade.php'
$bytes = [System.IO.File]::ReadAllBytes($file)
$content = [System.Text.Encoding]::UTF8.GetString($bytes)

# Fix the default option text in the HTML dropdown
# The em-dash characters are U+2014 which in UTF-8 is E2 80 94
$emDash = [System.Text.Encoding]::UTF8.GetString([byte[]]@(0xE2, 0x80, 0x94))
Write-Host "Em dash char found: $($content.Contains($emDash))"

# Replace — All Branches (HO View) — with just All Branches (HO View)
$oldOption = $emDash + " All Branches (HO View) " + $emDash
$newOption = "All Branches (HO View)"
$count = ($content.Split($oldOption).Length - 1)
if ($count -gt 0) {
    $content = $content.Replace($oldOption, $newOption)
    Write-Host "Default option text fixed: $count instances"
} else {
    Write-Host "Em-dash option not found, trying alternative..."
    # Check what's in the option
    $optStart = $content.IndexOf('<option value="">')
    if ($optStart -gt 0) {
        $optEnd = $content.IndexOf('</option>', $optStart)
        $optContent = $content.Substring($optStart + 16, $optEnd - $optStart - 16)
        Write-Host "Current option content bytes: $(([byte[]][System.Text.Encoding]::UTF8.GetBytes($optContent)) -join ' ')"
    }
}

[System.IO.File]::WriteAllBytes($file, [System.Text.Encoding]::UTF8.GetBytes($content))
Write-Host "Done."
