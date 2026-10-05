$file = 'd:\Development\htdocs\myagency\resources\views\pages\dashboard\leads\admin-dashboard.blade.php'
$content = [System.IO.File]::ReadAllText($file, [System.Text.Encoding]::UTF8)

# Fix 1: Update openBranchHotLeadsModal to accept subtype parameter
$old1 = "window.openBranchHotLeadsModal = function(target, targetName) {

    window.currentBranchHotLeadsTarget = target;
    window.currentBranchHotLeadsTargetName = targetName;"

$new1 = "window.openBranchHotLeadsModal = function(target, targetName, subtype) {

    window.currentBranchHotLeadsTarget = target;
    window.currentBranchHotLeadsTargetName = targetName;
    window.currentBranchHotLeadsSubtype = subtype || null;"

if ($content.Contains($old1)) {
    $content = $content.Replace($old1, $new1)
    Write-Host "Fix 1 applied: Added subtype parameter to openBranchHotLeadsModal"
} else {
    Write-Host "Fix 1 NOT found"
}

# Fix 2: Add subtype to URL building (after the queryParam line)
$old2 = "    var queryParam = isType ? ('type=' + encodeURIComponent(target)) : ('branch_id=' + encodeURIComponent(target));

    var url = '{{ url(""/dashboard/branch-hot-leads"") }}?' + queryParam;"

$new2 = "    var queryParam = isType ? ('type=' + encodeURIComponent(target)) : ('branch_id=' + encodeURIComponent(target));

    var url = '{{ url(""/dashboard/branch-hot-leads"") }}?' + queryParam;

    var currentSubtype = subtype || window.currentBranchHotLeadsSubtype;
    if (currentSubtype) {
        url += '&subtype=' + encodeURIComponent(currentSubtype);
    }"

if ($content.Contains($old2)) {
    $content = $content.Replace($old2, $new2)
    Write-Host "Fix 2 applied: Added subtype to URL"
} else {
    Write-Host "Fix 2 NOT found - trying alternative..."
    # Try without the escaped quotes issue
    $old2b = "    var queryParam = isType ? ('type=' + encodeURIComponent(target)) : ('branch_id=' + encodeURIComponent(target));"
    if ($content.Contains($old2b)) {
        $insert = "`r`n`r`n    var currentSubtype = subtype || window.currentBranchHotLeadsSubtype;`r`n    if (currentSubtype) {`r`n        url += '&subtype=' + encodeURIComponent(currentSubtype);`r`n    }"
        # Find after the url = line
        $idx = $content.IndexOf($old2b)
        # Find the next line after queryParam
        $afterIdx = $content.IndexOf("`r`n", $idx + $old2b.Length)
        $afterIdx2 = $content.IndexOf("`r`n", $afterIdx + 2) # skip blank line
        $urlLineEnd = $content.IndexOf("`r`n", $afterIdx2 + 2)
        if ($urlLineEnd -gt 0) {
            $content = $content.Substring(0, $urlLineEnd) + $insert + $content.Substring($urlLineEnd)
            Write-Host "Fix 2 applied via alternative method"
        }
    }
}

# Fix 3: Update NST card onclick to pass 'nst' subtype
$old3 = "onclick=""openBranchHotLeadsModal(window.daSelectedBranchId, window.daSelectedBranchName + ' - NST')"""
$new3 = "onclick=""openBranchHotLeadsModal(window.daSelectedBranchId, window.daSelectedBranchName + ' - NST', 'nst')"""
$count3 = ($content.Split($old3).Length - 1)
$content = $content.Replace($old3, $new3)
Write-Host "Fix 3 applied: $count3 NST onclick(s) updated with subtype=nst"

# Fix 4: Update CST card onclick to pass 'cst' subtype
$old4 = "onclick=""openBranchHotLeadsModal(window.daSelectedBranchId, window.daSelectedBranchName + ' - CST')"""
$new4 = "onclick=""openBranchHotLeadsModal(window.daSelectedBranchId, window.daSelectedBranchName + ' - CST', 'cst')"""
$count4 = ($content.Split($old4).Length - 1)
$content = $content.Replace($old4, $new4)
Write-Host "Fix 4 applied: $count4 CST onclick(s) updated with subtype=cst"

# Fix 5: Improve the branch dropdown container styling
$old5 = '<div style="display:inline-flex; align-items:center; gap:6px; background:#fff; padding:4px 10px; border:1px solid #d1fae5; border-radius:10px; box-shadow:0 1px 3px rgba(0,0,0,0.05);" title="Filter Key Metrics by Branch">'
$new5 = '<div style="display:inline-flex; align-items:center; gap:6px; background:linear-gradient(135deg,#ecfdf5,#f0fdf4); padding:5px 12px; border:1.5px solid #6ee7b7; border-radius:12px; box-shadow:0 2px 8px rgba(5,150,105,0.12);" title="Filter Key Metrics by Branch">'
if ($content.Contains($old5)) {
    $content = $content.Replace($old5, $new5)
    Write-Host "Fix 5 applied: Dropdown container style improved"
} else {
    Write-Host "Fix 5 NOT found"
}

# Fix 6: Improve select element styling
$old6 = 'style="appearance:none; -webkit-appearance:none; border:none; background:transparent; font-size:12px; font-weight:700; color:#065f46; outline:none; cursor:pointer; min-width:180px; max-width:260px;"'
$new6 = 'style="appearance:none; -webkit-appearance:none; border:none; background:transparent; font-size:12px; font-weight:700; color:#065f46; outline:none; cursor:pointer; min-width:160px; max-width:240px; font-family:inherit;"'
if ($content.Contains($old6)) {
    $content = $content.Replace($old6, $new6)
    Write-Host "Fix 6 applied: Select element style improved"
} else {
    Write-Host "Fix 6 NOT found"
}

[System.IO.File]::WriteAllText($file, $content, [System.Text.Encoding]::UTF8)
Write-Host "Done. All fixes applied."
