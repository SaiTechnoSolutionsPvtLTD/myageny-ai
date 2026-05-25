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
            ->pluck('name', 'name')
            ->toArray();

        if (empty($sources)) {
            $sources = $this->visibility->visibleLeadSources($user)
                ->mapWithKeys(fn ($source) => [$source => ucfirst($source)])
                ->toArray();
        }
        $statuses = LeadStatus::query()
            ->when($user->company_id, fn ($query) => $query->where('company_id', $user->company_id))
            ->orderBy('name')
            ->pluck('name', 'name')
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
