$file = 'd:\Development\htdocs\myagency\resources\views\pages\dashboard\leads\admin-dashboard.blade.php'
$content = [System.IO.File]::ReadAllText($file, [System.Text.Encoding]::UTF8)

# The broken flame SVG icon - replace with proper Heroicons 'fire' path
# Original bad path: M12 2c1.5 2 2 3.5 2 5.5 0 2-1.5 3.5-3.5 3.5S7 9.5 7 7.5c0-2 .5-3.5 2-5.5 0 0-5 3.5-5 9a8 8 0 0 0 16 0c0-5.5-5-9-5-9z
# Replace with proper Heroicons flame (filled version) that works with fill

$badFlame = '<svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#ffffff" stroke-width="2"><path d="M12 2c1.5 2 2 3.5 2 5.5 0 2-1.5 3.5-3.5 3.5S7 9.5 7 7.5c0-2 .5-3.5 2-5.5 0 0-5 3.5-5 9a8 8 0 0 0 16 0c0-5.5-5-9-5-9z"/></svg>'

# Proper filled flame (Heroicons solid fire)
$goodFlame = '<svg width="20" height="20" viewBox="0 0 24 24" fill="#ffffff"><path fill-rule="evenodd" d="M12.963 2.286a.75.75 0 00-1.071-.136 9.742 9.742 0 00-3.539 6.177A7.547 7.547 0 016.648 6.61a.75.75 0 00-1.152-.082A9 9 0 1015.68 4.534a7.46 7.46 0 01-2.717-2.248zM15.75 14.25a3.75 3.75 0 11-7.313-1.172c.628.465 1.35.81 2.133 1a5.99 5.99 0 011.925-3.545 3.75 3.75 0 013.255 3.717z" clip-rule="evenodd" /></svg>'

$count = ($content.Split($badFlame).Length - 1)
$content = $content.Replace($badFlame, $goodFlame)
Write-Host "Flame icon replaced: $count instances"

# Also fix the large flame SVG used in modal metric icons (with extra whitespace/newlines in original cards)
$badFlameMultiline = '<svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="#ffffff" stroke-width="2">

                                        <path d="M12 2c1.5 2 2 3.5 2 5.5 0 2-1.5 3.5-3.5 3.5S7 9.5 7 7.5c0-2 .5-3.5 2-5.5 0 0-5 3.5-5 9a8 8 0 0 0 16 0c0-5.5-5-9-5-9z"/>

                                    </svg>'

$goodFlameMultiline = '<svg width="20" height="20" viewBox="0 0 24 24" fill="#ffffff">

                                        <path fill-rule="evenodd" d="M12.963 2.286a.75.75 0 00-1.071-.136 9.742 9.742 0 00-3.539 6.177A7.547 7.547 0 016.648 6.61a.75.75 0 00-1.152-.082A9 9 0 1015.68 4.534a7.46 7.46 0 01-2.717-2.248zM15.75 14.25a3.75 3.75 0 11-7.313-1.172c.628.465 1.35.81 2.133 1a5.99 5.99 0 011.925-3.545 3.75 3.75 0 013.255 3.717z" clip-rule="evenodd" />

                                    </svg>'

$count2 = ($content.Split($badFlameMultiline).Length - 1)
$content = $content.Replace($badFlameMultiline, $goodFlameMultiline)
Write-Host "Multiline flame icon replaced: $count2 instances"

# Fix currency dollar icon (for Deal Value cards) - the circle-dollar path
# Old: M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z
# This path is actually good, keep it as-is

# Fix expected value icon (dollar sign line) - the vertical line + S path
# Old: <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
# This is actually fine too

# Now improve the branch dropdown UI - add a label before the select
$oldDropdownWrap = '<div style="display:inline-flex; align-items:center; gap:6px; background:linear-gradient(135deg,#ecfdf5,#f0fdf4); padding:5px 12px; border:1.5px solid #6ee7b7; border-radius:12px; box-shadow:0 2px 8px rgba(5,150,105,0.12);" title="Filter Key Metrics by Branch">

                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#059669" stroke-width="2"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>

                    <select id="daModalBranchDropdown" onchange="onKeyMetricsBranchChange(this.value)" style="appearance:none; -webkit-appearance:none; border:none; background:transparent; font-size:12px; font-weight:700; color:#065f46; outline:none; cursor:pointer; min-width:160px; max-width:240px; font-family:inherit;">
                        <option value="">&#x2014; All Branches (HO View) &#x2014;</option>
                    </select>

                    <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="#059669" stroke-width="2.5" style="pointer-events:none; flex-shrink:0;"><path d="M6 9l6 6 6-6"/></svg>

                </div>'

$newDropdownWrap = '<div style="display:inline-flex; align-items:center; gap:8px; background:linear-gradient(135deg,#ecfdf5,#f0fdf4); padding:6px 14px; border:1.5px solid #6ee7b7; border-radius:12px; box-shadow:0 2px 8px rgba(5,150,105,0.12);" title="Filter Key Metrics by Branch">

                    <svg width="14" height="14" viewBox="0 0 24 24" fill="#059669" style="flex-shrink:0;"><path fill-rule="evenodd" d="M3 5.25a.75.75 0 01.75-.75h16.5a.75.75 0 010 1.5H3.75A.75.75 0 013 5.25zm0 4.5A.75.75 0 013.75 9h16.5a.75.75 0 010 1.5H3.75A.75.75 0 013 9.75zm0 4.5a.75.75 0 01.75-.75h16.5a.75.75 0 010 1.5H3.75a.75.75 0 01-.75-.75zm0 4.5a.75.75 0 01.75-.75H12a.75.75 0 010 1.5H3.75a.75.75 0 01-.75-.75z" clip-rule="evenodd" /></svg>

                    <span style="font-size:11px; font-weight:600; color:#047857; white-space:nowrap; flex-shrink:0;">Branch:</span>

                    <select id="daModalBranchDropdown" onchange="onKeyMetricsBranchChange(this.value)" style="appearance:none; -webkit-appearance:none; border:none; background:transparent; font-size:12px; font-weight:700; color:#065f46; outline:none; cursor:pointer; min-width:150px; max-width:230px; font-family:inherit;">
                        <option value="">All Branches (HO View)</option>
                    </select>

                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="#059669" stroke-width="2.5" style="pointer-events:none; flex-shrink:0;"><path d="M6 9l6 6 6-6"/></svg>

                </div>'

if ($content.Contains($oldDropdownWrap)) {
    $content = $content.Replace($oldDropdownWrap, $newDropdownWrap)
    Write-Host "Dropdown UI improved"
} else {
    Write-Host "Dropdown wrap NOT found for UI improvement"
}

[System.IO.File]::WriteAllText($file, $content, [System.Text.Encoding]::UTF8)
Write-Host "Done."
