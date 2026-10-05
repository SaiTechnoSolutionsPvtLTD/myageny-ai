$file = 'd:\Development\htdocs\myagency\resources\views\pages\dashboard\leads\admin-dashboard.blade.php'
$content = [System.IO.File]::ReadAllText($file, [System.Text.Encoding]::UTF8)

# Find the block to replace (from "// ── Company Admin..." to closing "};")
# We'll use a simpler string match on the activeBranchesList init + tab badge code

$oldBlock = @'
    activeBranchesList = m.activeBranches || [];

    var hoCount      = activeBranchesList.filter(function(b) { return b.is_default; }).length;

    var cocoCount    = activeBranchesList.filter(function(b) { return !b.is_default && (b.branch_type || '').toUpperCase() === 'COCO'; }).length;

    var nonCocoCount = activeBranchesList.filter(function(b) { return !b.is_default && (b.branch_type || '').toUpperCase() !== 'COCO'; }).length;

    var allBadge     = document.getElementById('daBranchTabAllBadge');

    var hoBadge      = document.getElementById('daBranchTabHoBadge');

    var cocoBadge    = document.getElementById('daBranchTabCocoBadge');

    var nonCocoBadge = document.getElementById('daBranchTabNonCocoBadge');

    if (allBadge)     allBadge.textContent    = activeBranchesList.length;

    if (hoBadge)      hoBadge.textContent     = hoCount;

    if (cocoBadge)    cocoBadge.textContent   = cocoCount;

    if (nonCocoBadge) nonCocoBadge.textContent = nonCocoCount;

    switchBranchTab(currentBranchTab || 'all');

    modal.classList.add('open');

    document.body.style.overflow = 'hidden';

};

window.closeTotalProspectsModal = function() {

    var modal = document.getElementById('daTotalProspectsModal');

    if (!modal) return;

    modal.classList.remove('open');

    document.body.style.overflow = '';

};
'@

$newBlock = @'
    activeBranchesList = m.activeBranches || [];

    // Populate the branch dropdown
    var branchDropdown = document.getElementById('daModalBranchDropdown');
    if (branchDropdown) {
        branchDropdown.innerHTML = '<option value="">\u2014 All Branches (HO View) \u2014</option>';
        activeBranchesList.forEach(function(b) {
            var label = (b.name || 'Branch') + (b.code ? ' (' + b.code + ')' : '') + (b.is_default ? ' \u2014 HO' : (b.branch_type ? ' \u2014 ' + b.branch_type : ''));
            var opt = document.createElement('option');
            opt.value = b.id;
            opt.textContent = label;
            branchDropdown.appendChild(opt);
        });
        branchDropdown.value = '';
    }

    // Reset branch-specific NST/CST section
    var branchSection = document.getElementById('daModalBranchNstCstSection');
    if (branchSection) branchSection.style.display = 'none';

    window.daSelectedBranchId = null;
    window.daSelectedBranchName = '';
    document.querySelectorAll('.da-modal-col-left .da-modal-section-card').forEach(function(el) {
        el.style.display = '';
    });

    modal.classList.add('open');

    document.body.style.overflow = 'hidden';

};

window.closeTotalProspectsModal = function() {

    var modal = document.getElementById('daTotalProspectsModal');

    if (!modal) return;

    modal.classList.remove('open');

    document.body.style.overflow = '';

};

/* Branch Dropdown Change Handler (Key Metrics Modal) */

window.onKeyMetricsBranchChange = function(branchId) {

    var branchSection = document.getElementById('daModalBranchNstCstSection');
    var colLeft = document.querySelector('.da-modal-col-left');

    if (!branchId) {
        window.daSelectedBranchId = null;
        window.daSelectedBranchName = '';
        if (colLeft) {
            colLeft.querySelectorAll('.da-modal-section-card').forEach(function(el) {
                el.style.display = '';
            });
        }
        if (branchSection) branchSection.style.display = 'none';
        return;
    }

    var bid = String(branchId);
    var branch = (activeBranchesList || []).find(function(b) { return String(b.id) === bid; });
    if (!branch) return;

    window.daSelectedBranchId = branch.id;
    window.daSelectedBranchName = branch.name || 'Branch';

    if (colLeft) {
        colLeft.querySelectorAll('.da-modal-section-card').forEach(function(el) {
            el.style.display = 'none';
        });
    }

    if (branchSection) branchSection.style.display = 'block';

    var nstHeading = document.getElementById('daModalBranchNstHeading');
    var cstHeading = document.getElementById('daModalBranchCstHeading');
    if (nstHeading) nstHeading.textContent = 'NST \u2014 ' + (branch.name || 'Branch');
    if (cstHeading) cstHeading.textContent = 'CST \u2014 ' + (branch.name || 'Branch');

    var nstCount = document.getElementById('daModalBranchNstCount');
    var nstDeal  = document.getElementById('daModalBranchNstDeal');
    var nstExp   = document.getElementById('daModalBranchNstExp');
    if (nstCount) nstCount.textContent = branch.nst_count !== undefined ? branch.nst_count : 0;
    if (nstDeal)  nstDeal.textContent  = fmt(branch.nst_deal || 0);
    if (nstExp)   nstExp.textContent   = fmt(branch.nst_exp || 0);

    var cstCount = document.getElementById('daModalBranchCstCount');
    var cstDeal  = document.getElementById('daModalBranchCstDeal');
    var cstExp   = document.getElementById('daModalBranchCstExp');
    if (cstCount) cstCount.textContent = branch.cst_count !== undefined ? branch.cst_count : 0;
    if (cstDeal)  cstDeal.textContent  = fmt(branch.cst_deal || 0);
    if (cstExp)   cstExp.textContent   = fmt(branch.cst_exp || 0);

};
'@

# Normalize line endings in old block to match file
$oldBlock = $oldBlock -replace "`r`n", "`r`n"
$oldBlock2 = $oldBlock -replace "`n", "`r`n"

if ($content.Contains($oldBlock)) {
    $content = $content.Replace($oldBlock, $newBlock)
    Write-Host "Replaced using LF version"
} elseif ($content.Contains($oldBlock2)) {
    $content = $content.Replace($oldBlock2, $newBlock)
    Write-Host "Replaced using CRLF version"
} else {
    Write-Host "Could not find target block. Trying partial match..."
    # Try a shorter, unique substring
    $shortOld = "    switchBranchTab(currentBranchTab || 'all');"
    if ($content.Contains($shortOld)) {
        Write-Host "Short match found!"
    } else {
        Write-Host "Short match NOT found"
    }
}

[System.IO.File]::WriteAllText($file, $content, [System.Text.Encoding]::UTF8)
Write-Host "Done."
