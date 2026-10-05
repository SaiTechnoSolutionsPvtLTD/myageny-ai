$file = 'd:\Development\htdocs\myagency\resources\views\pages\dashboard\leads\admin-dashboard.blade.php'
$content = [System.IO.File]::ReadAllText($file, [System.Text.Encoding]::UTF8)

# Fix dropdown UI - replace just the opening div attributes to improve styling
$old1 = 'display:inline-flex; align-items:center; gap:6px; background:linear-gradient(135deg,#ecfdf5,#f0fdf4); padding:5px 12px; border:1.5px solid #6ee7b7; border-radius:12px; box-shadow:0 2px 8px rgba(5,150,105,0.12);'
$new1 = 'display:inline-flex; align-items:center; gap:8px; background:linear-gradient(135deg,#ecfdf5,#f0fdf4); padding:6px 14px; border:1.5px solid #6ee7b7; border-radius:12px; box-shadow:0 2px 8px rgba(5,150,105,0.12);'

if ($content.Contains($old1)) {
    $content = $content.Replace($old1, $new1)
    Write-Host "Dropdown gap improved"
} else {
    Write-Host "Dropdown gap not found"
}

# Replace the building SVG icon with a list/bars icon (filled)
$oldBuildingIcon = '<svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#059669" stroke-width="2"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>'
$newListIcon = '<svg width="14" height="14" viewBox="0 0 24 24" fill="#059669" style="flex-shrink:0;"><path fill-rule="evenodd" d="M3 5.25a.75.75 0 01.75-.75h16.5a.75.75 0 010 1.5H3.75A.75.75 0 013 5.25zm0 4.5A.75.75 0 013.75 9h16.5a.75.75 0 010 1.5H3.75A.75.75 0 013 9.75zm0 4.5a.75.75 0 01.75-.75h16.5a.75.75 0 010 1.5H3.75a.75.75 0 01-.75-.75zm0 4.5a.75.75 0 01.75-.75H12a.75.75 0 010 1.5H3.75a.75.75 0 01-.75-.75z" clip-rule="evenodd" /></svg><span style="font-size:11px; font-weight:700; color:#047857; white-space:nowrap; flex-shrink:0; letter-spacing:0.3px;">Branch:</span>'

if ($content.Contains($oldBuildingIcon)) {
    $content = $content.Replace($oldBuildingIcon, $newListIcon)
    Write-Host "Building icon replaced with list+label"
} else {
    Write-Host "Building icon not found"
}

# Fix the select min-width to be a bit smaller since we have a label now
$oldSelectStyle = 'min-width:160px; max-width:240px; font-family:inherit;'
$newSelectStyle = 'min-width:140px; max-width:210px; font-family:inherit;'
if ($content.Contains($oldSelectStyle)) {
    $content = $content.Replace($oldSelectStyle, $newSelectStyle)
    Write-Host "Select width adjusted"
} else {
    Write-Host "Select width style not found"
}

# Fix the default option text (remove em-dashes, make cleaner)
$oldOption = '<option value="">&#x2014; All Branches (HO View) &#x2014;</option>'
$newOption = '<option value="">All Branches (HO View)</option>'
if ($content.Contains($oldOption)) {
    $content = $content.Replace($oldOption, $newOption)
    Write-Host "Default option text cleaned"
} else {
    Write-Host "Default option not found"
}

# Also fix the '— All Branches (HO View) —' in the JS innerHTML 
$oldJsHtml = "branchDropdown.innerHTML = '<option value=""`\u2014 All Branches (HO View) `\u2014</option>';"
$newJsHtml = "branchDropdown.innerHTML = '<option value="""">All Branches (HO View)</option>';"
# Try direct replacement since encoding is tricky
$content = $content -replace '\\u2014 All Branches \(HO View\) \\u2014', 'All Branches (HO View)'
Write-Host "JS default option text cleaned"

[System.IO.File]::WriteAllText($file, $content, [System.Text.Encoding]::UTF8)
Write-Host "Done."
