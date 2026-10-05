$file = 'd:\Development\htdocs\myagency\resources\views\pages\dashboard\leads\admin-dashboard.blade.php'
$bytes = [System.IO.File]::ReadAllBytes($file)
$content = [System.Text.Encoding]::UTF8.GetString($bytes)

# ═══ FIX 1: Replace all double-encoded ₹ (C3 A2 E2 80 9A C2 B9) with correct ₹ (E2 82 B9) ═══
$rupeeDoubleBytes = [byte[]]@(0xC3, 0xA2, 0xE2, 0x80, 0x9A, 0xC2, 0xB9)
$rupeeDoubleStr   = [System.Text.Encoding]::UTF8.GetString($rupeeDoubleBytes)
$rupeeCorrectBytes = [byte[]]@(0xE2, 0x82, 0xB9)
$rupeeCorrect = [System.Text.Encoding]::UTF8.GetString($rupeeCorrectBytes)

$count1 = ($content.Split($rupeeDoubleStr).Length - 1)
$content = $content.Replace($rupeeDoubleStr, $rupeeCorrect)
Write-Host "Fixed double-encoded rupee: $count1 instances"

# ═══ FIX 2: Update onKeyMetricsBranchChange - add HO branch check ═══
# Find and replace the specific no-branch check section
$oldNoIdSection = @'
    // No branch selected → All Branches (HO View)
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
'@

$newNoIdSection = @'
    // Helper: show all original 4 HO-view cards, hide branch NST/CST section
    function showHoView() {
        window.daSelectedBranchId = null;
        window.daSelectedBranchName = '';
        if (colLeft) {
            colLeft.querySelectorAll('.da-modal-section-card').forEach(function(el) {
                el.style.display = '';
            });
        }
        if (branchSection) branchSection.style.display = 'none';
    }

    // No branch selected → All Branches (HO View)
    if (!branchId) {
        showHoView();
        return;
    }

    var bid = String(branchId);
    var branch = (activeBranchesList || []).find(function(b) { return String(b.id) === bid; });
    if (!branch) return;

    // If HO/default branch selected → same view as "All Branches"
    if (branch.is_default) {
        showHoView();
        return;
    }

    window.daSelectedBranchId = branch.id;
    window.daSelectedBranchName = branch.name || 'Branch';
'@

if ($content.Contains($oldNoIdSection)) {
    $content = $content.Replace($oldNoIdSection, $newNoIdSection)
    Write-Host "Fix 2 applied: HO branch now shows HO 4-card view"
} else {
    Write-Host "Fix 2: searching for alternate pattern..."
    # Check if already applied
    if ($content.Contains("If HO/default branch selected")) {
        Write-Host "Fix 2 already applied!"
    } else {
        Write-Host "Fix 2 NOT found"
    }
}

# ═══ SAVE: Write back UTF-8 without BOM ═══
$enc = [System.Text.Encoding]::UTF8
$outBytes = $enc.GetBytes($content)
# Remove BOM if present at start
if ($outBytes[0] -eq 0xEF -and $outBytes[1] -eq 0xBB -and $outBytes[2] -eq 0xBF) {
    $outBytes = $outBytes[3..($outBytes.Length-1)]
    Write-Host "BOM removed"
}
[System.IO.File]::WriteAllBytes($file, $outBytes)
Write-Host "File saved successfully. Size: $($outBytes.Length) bytes"

# Verify fix
$verifyBytes = [System.IO.File]::ReadAllBytes($file)
$verifyContent = [System.Text.Encoding]::UTF8.GetString($verifyBytes)
$rupeeDoubleStr2 = [System.Text.Encoding]::UTF8.GetString([byte[]]@(0xC3, 0xA2, 0xE2, 0x80, 0x9A, 0xC2, 0xB9))
$stillDouble = $verifyContent.Contains($rupeeDoubleStr2)
Write-Host "Double-encoded rupee still present: $stillDouble"
$rupeeCorrect2 = [System.Text.Encoding]::UTF8.GetString([byte[]]@(0xE2, 0x82, 0xB9))
$hasCorrect = $verifyContent.Contains($rupeeCorrect2)
Write-Host "Correct rupee present: $hasCorrect"
