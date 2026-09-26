<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Lead;
use App\Models\LeadCallUpdate;
use App\Models\LeadProduct;
use App\Models\LeadProductPayment;
use App\Models\LeadReminder;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\User;
use App\Services\DataVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(private readonly DataVisibilityService $visibility) {}


    /**
     * Data for Admin dashboard.
     */

    protected function success($data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        $response = ['success' => true, 'message' => $message];
        if (!is_null($data)) {
            $response['data'] = $data;
        }
        return response()->json($response, $code);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $users = $this->visibility->visibleAssignableUsers($user);
        $branches = $this->visibility->visibleBranches($user);
        $sources = LeadSource::query()
            ->when($user->company_id, fn ($query) => $query->where('company_id', $user->company_id))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        if (empty($sources)) {
            $sources = $this->visibility->visibleLeadSources($user)
                ->mapWithKeys(fn ($source) => [$source => ucfirst($source)])
                ->toArray();
        }
        $statuses = LeadStatus::query()
            ->when($user->company_id, fn ($query) => $query->where('company_id', $user->company_id))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        if (empty($statuses)) {
            $statusQuery = Lead::query()->whereNotNull('lead_status');
            $this->visibility->applyLeadVisibility($statusQuery, $user);
            $statuses = $statusQuery
                ->distinct()
                ->orderBy('lead_status')
                ->pluck('lead_status', 'lead_status')
                ->toArray();
        }

        // Create a fresh dashboard token (expires in 2 hours)
        // Revoke old dashboard tokens first to keep it clean
        $user->tokens()->where('name', 'dashboard-session')->delete();
        $token = $user->createToken('dashboard-session')->plainTextToken;

        // Determine user role type for modal scoping and UI visibility
        $isCompanyAdmin = $user->isSuperAdmin() || $user->isCompanyAdminRole();
        $isCbo          = $user->isCbo();
        $isBranchManager = $user->isBranchManager() && !$isCompanyAdmin && !$isCbo;
        $isBranchAdmin   = $user->isBranchAdmin() && !$isCompanyAdmin && !$isCbo && !$isBranchManager;
        $isTl            = $user->hasTlLikeRole() && !$isCompanyAdmin && !$isCbo && !$isBranchManager && !$isBranchAdmin;
        $isNstUser       = !$isCompanyAdmin && !$isCbo && !$isBranchManager && !$isBranchAdmin && !$isTl; // NST/Executive level

        $userBranchIds = $user->getMyBranchIds();
        $defaultBranchId = Branch::where('is_default', true)->value('id') ?? 1;

        if ($isCompanyAdmin || $isCbo) {
            $hasDefaultBranch = true;
            $hasCocoBranch    = true;
            $hasNonCocoBranch = true;
        } elseif ($isBranchManager) {
            // Branch Manager:
            // 1. Additional branches (non-default) check for COCO / NON COCO
            $additionalBranchIds = array_values(array_filter($userBranchIds, fn($id) => (int)$id !== (int)$defaultBranchId));
            $additionalBranches = !empty($additionalBranchIds) ? Branch::whereIn('id', $additionalBranchIds)->get() : collect();

            $hasCocoBranch    = $additionalBranches->contains(fn($b) => strtoupper(trim((string) $b->branch_type)) === 'COCO');
            $hasNonCocoBranch = $additionalBranches->contains(fn($b) => in_array(strtoupper(trim((string) $b->branch_type)), ['NON COCO', 'NON_COCO', 'NON-COCO']));

            // 2. HO: show if BM has users mapped under them in HO OR has leads assigned to themselves in HO
            $descendants = $this->visibility->descendantUserIds($user);
            $hasHoMappedUsers = false;
            if ($descendants->isNotEmpty()) {
                $hasHoMappedUsers = User::withoutGlobalScope('branch')
                    ->whereIn('id', $descendants)
                    ->where('branch_id', $defaultBranchId)
                    ->exists();
            }

            $hasOwnHoLeads = Lead::where('branch_id', $defaultBranchId)
                ->where('assigned_to', $user->id)
                ->exists();

            $hasDefaultBranch = $hasHoMappedUsers || $hasOwnHoLeads;
        } elseif ($isBranchAdmin) {
            // Branch Admin: check their branch(es)
            $branchIds = $userBranchIds;
            if (empty($branchIds) && $user->branch_id) {
                $branchIds = [(int) $user->branch_id];
            }
            $adminBranches = !empty($branchIds) ? Branch::whereIn('id', $branchIds)->get() : collect();

            $hasDefaultBranch = $adminBranches->contains(fn($b) => (bool) $b->is_default);
            $hasCocoBranch    = $adminBranches->contains(fn($b) => strtoupper(trim((string) $b->branch_type)) === 'COCO');
            $hasNonCocoBranch = $adminBranches->contains(fn($b) => in_array(strtoupper(trim((string) $b->branch_type)), ['NON COCO', 'NON_COCO', 'NON-COCO']));
        } elseif ($isTl) {
            // Sales TL: check TL and team mapped members' branches
            $teamUserIds = $this->visibility->descendantUserIds($user)->push($user->id)->unique();
            $teamBranchIds = User::withoutGlobalScope('branch')
                ->whereIn('id', $teamUserIds)
                ->pluck('branch_id')
                ->filter()
                ->unique()
                ->all();
            if (empty($teamBranchIds) && $user->branch_id) {
                $teamBranchIds = [(int) $user->branch_id];
            }
            $tlBranches = !empty($teamBranchIds) ? Branch::whereIn('id', $teamBranchIds)->get() : collect();

            $hasDefaultBranch = $tlBranches->contains(fn($b) => (bool) $b->is_default);
            $hasCocoBranch    = $tlBranches->contains(fn($b) => strtoupper(trim((string) $b->branch_type)) === 'COCO');
            $hasNonCocoBranch = $tlBranches->contains(fn($b) => in_array(strtoupper(trim((string) $b->branch_type)), ['NON COCO', 'NON_COCO', 'NON-COCO']));
        } else {
            // Sales Executive (NST): check executive's branch
            $execBranch = $user->branch;
            $hasDefaultBranch = (bool) ($execBranch?->is_default || (int)$user->branch_id === (int)$defaultBranchId);
            $hasCocoBranch    = strtoupper(trim((string) ($execBranch?->branch_type ?? ''))) === 'COCO';
            $hasNonCocoBranch = in_array(strtoupper(trim((string) ($execBranch?->branch_type ?? ''))), ['NON COCO', 'NON_COCO', 'NON-COCO']);
        }

        $userBranchType = $user->branch?->branch_type ?? ($hasCocoBranch ? 'COCO' : ($hasNonCocoBranch ? 'NON COCO' : null));

        // Can see Active Branches table in modal — only Company Admin and CBO
        $canViewActiveBranches = $isCompanyAdmin || $isCbo;

        // Determine user role key for JS-side scoping
        if ($isCompanyAdmin) {
            $userRoleType = 'company_admin';
        } elseif ($isCbo) {
            $userRoleType = 'cbo';
        } elseif ($isBranchManager) {
            $userRoleType = 'branch_manager';
        } elseif ($isBranchAdmin) {
            $userRoleType = 'branch_admin';
        } elseif ($isTl) {
            $userRoleType = 'tl';
        } else {
            $userRoleType = 'nst'; // NST/Executive
        }

        return view('pages.dashboard.leads.admin-dashboard', [
            'apiToken'  => $token,
            'apiBase'   => url('/api'),
            'leadBase'  => url('/leads'),
            'userName'  => $user->name,
            'userRole'  => $user->resolvedRoles()->first()?->display_name ?? 'Admin',
            'today'     => now()->format('D, d M Y'),
            'branches'  => $branches,
            'users'     => $users,
            'sources'   => $sources,
            'statuses'  => $statuses,
            'companyId' => $user->company_id,
            'canViewForecasting'   => (bool) ($user->isSuperAdmin() || $user->isCompanyAdminRole() || $user->isCbo()),
            'canViewActiveBranches' => (bool) $canViewActiveBranches,
            'userRoleType'          => $userRoleType,
            'userBranchId'          => $user->branch_id,
            'userBranchIds'         => $userBranchIds,
            'userBranchType'        => $userBranchType,
            'hasDefaultBranch'      => $hasDefaultBranch,
            'hasCocoBranch'         => $hasCocoBranch,
            'hasNonCocoBranch'      => $hasNonCocoBranch,
        ]);

    }

    /**
     * Data for Team Leader dashboard.
     */
    private function teamLeaderData($user): array
    {
        // Example: scope leads to branch
        // $branchLeads = Lead::where('branch_id', $user->branch_id)->get();
        return [
            'branchName' => $user->branch?->name ?? 'Branch',
        ];
    }

    /**
     * Data for Executive dashboard.
     */
    private function executiveData($user): array
    {
        // Example: scope leads to this executive
        // $myLeads      = Lead::where('owner_id', $user->id)->get();
        // $pendingCount = Lead::where('owner_id', $user->id)
        //                     ->where('follow_up_at', '<=', now())
        //                     ->whereNotIn('stage', ['won','lost'])
        //                     ->count();
        return [
            'userName' => $user->name,
        ];
    }
}