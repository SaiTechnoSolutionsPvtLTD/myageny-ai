<?php

namespace App\Http\Controllers\App\HRMS;

use App\Http\Controllers\Controller;
use App\Jobs\SendAnnouncementEmailJob;
use App\Models\Branch;
use App\Models\EmployeeOnboarding;
use App\Models\HrmsAnnouncement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AnnouncementApiController extends Controller
{
    // ── GET /api/mobile/hrms/announcements ─────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $canManage = $this->canManageAnnouncements($user);

        // Fetch active branches for company (used for formatting & filter options)
        $companyId = $user?->company_id;
        $branches = Branch::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $branchesById = $branches->keyBy('id');

        // Scoped base query
        $scopedQuery = HrmsAnnouncement::query()
            ->with(['creator:id,name', 'updater:id,name'])
            ->visibleForCompany($companyId);

        if (! $canManage) {
            $scopedQuery->visibleForUser($user)->active();
        }

        // Summary Counts (calculated before search/priority filters)
        $countsRaw = (clone $scopedQuery)
            ->selectRaw("
                COUNT(*) as total,
                COUNT(CASE WHEN priority = 'high' THEN 1 END) as high_priority,
                COUNT(CASE WHEN is_active = 1 THEN 1 END) as active,
                COUNT(CASE WHEN branch_ids IS NULL OR branch_ids = '[]' THEN 1 END) as all_branches
            ")
            ->first();

        $query = clone $scopedQuery;

        // Search Filter (title, message)
        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        // Priority Filter
        if ($request->filled('priority') && $request->priority !== 'all') {
            $query->where('priority', strtolower((string) $request->priority));
        }

        // Status Filter (Only managers can filter by inactive)
        if ($canManage && $request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Branch Filter (for managers)
        if ($canManage && $request->filled('branch_id') && $request->branch_id !== 'all') {
            $bId = (int) $request->branch_id;
            $query->where(function ($bQuery) use ($bId) {
                $bQuery->whereNull('branch_ids')
                       ->orWhere('branch_ids', '[]')
                       ->orWhereJsonLength('branch_ids', 0)
                       ->orWhereJsonContains('branch_ids', $bId)
                       ->orWhereJsonContains('branch_ids', (string) $bId);
            });
        }

        // Date Range Filters
        if ($request->filled('date_from')) {
            $query->whereDate('announcement_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('announcement_date', '<=', $request->date_to);
        }

        $perPage = min(max((int) ($request->per_page ?? 15), 5), 100);
        $announcements = $query
            ->orderByDesc('announcement_date')
            ->latest('id')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => [
                'announcements' => $announcements->map(fn ($a) => $this->formatAnnouncement($a, $branchesById)),
                'counts'        => [
                    'total'         => (int) ($countsRaw->total ?? 0),
                    'high_priority' => (int) ($countsRaw->high_priority ?? 0),
                    'active'        => (int) ($countsRaw->active ?? 0),
                    'all_branches'  => (int) ($countsRaw->all_branches ?? 0),
                ],
                'can_manage'    => $canManage,
                'branches'      => $branches->map(fn ($b) => [
                    'id'   => $b->id,
                    'name' => $b->name,
                    'code' => $b->code,
                ]),
                'pagination'    => [
                    'current_page' => $announcements->currentPage(),
                    'last_page'    => $announcements->lastPage(),
                    'per_page'     => $announcements->perPage(),
                    'total'        => $announcements->total(),
                    'has_more'     => $announcements->hasMorePages(),
                ],
            ],
        ]);
    }

    // ── GET /api/mobile/hrms/announcements/{announcement} ──────────────────────
    public function show(HrmsAnnouncement $announcement): JsonResponse
    {
        $user = auth()->user();
        $this->authorizeCompanyOwnership($announcement, $user);

        $canManage = $this->canManageAnnouncements($user);
        if (! $canManage) {
            if (! $announcement->is_active || ! $this->isAnnouncementVisibleToUser($announcement, $user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Announcement not found or access restricted.',
                ], 403);
            }
        }

        $announcement->load(['creator:id,name', 'updater:id,name']);

        $companyId = $user?->company_id;
        $branches = Branch::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('is_active', true)
            ->get(['id', 'name', 'code']);

        return response()->json([
            'success' => true,
            'data'    => $this->formatAnnouncement($announcement, $branches->keyBy('id')),
        ]);
    }

    // ── POST /api/mobile/hrms/announcements ────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();
        $this->authorizeAnnouncementManagement($user);

        $validated = $request->validate([
            'title'             => ['required', 'string', 'max:150'],
            'message'           => ['required', 'string', 'max:2000'],
            'priority'          => ['required', 'in:high,medium,low'],
            'announcement_date' => ['required', 'date'],
            'is_active'         => ['nullable', 'boolean'],
            'target_type'       => ['nullable', 'in:all,specific'],
            'branch_ids'        => ['nullable', 'array'],
            'branch_ids.*'      => ['integer', 'exists:branches,id'],
        ]);

        $branchIds = null;
        if (($validated['target_type'] ?? 'all') === 'specific' && !empty($validated['branch_ids'])) {
            $branchIds = array_values(array_unique(array_map('intval', $validated['branch_ids'])));
        }

        $isActive = isset($validated['is_active']) ? (bool) $validated['is_active'] : true;

        $announcement = HrmsAnnouncement::create([
            'company_id'        => $user?->company_id,
            'branch_ids'        => $branchIds,
            'title'             => $validated['title'],
            'message'           => $validated['message'],
            'priority'          => strtolower($validated['priority']),
            'announcement_date' => $validated['announcement_date'],
            'is_active'         => $isActive,
            'created_by'        => $user->id,
            'updated_by'        => $user->id,
        ]);

        $queuedCount = 0;
        if ($announcement->is_active) {
            $queuedCount = $this->dispatchAnnouncementEmails($announcement);
        }

        $announcement->load(['creator:id,name', 'updater:id,name']);

        $msg = 'Announcement created successfully.';
        if ($queuedCount > 0) {
            $msg .= " Email notifications queued for {$queuedCount} active employee(s).";
        }

        return response()->json([
            'success' => true,
            'message' => $msg,
            'data'    => $this->formatAnnouncement($announcement),
        ], 201);
    }

    // ── PUT /api/mobile/hrms/announcements/{announcement} ──────────────────────
    public function update(Request $request, HrmsAnnouncement $announcement): JsonResponse
    {
        $user = auth()->user();
        $this->authorizeAnnouncementManagement($user);
        $this->authorizeCompanyOwnership($announcement, $user);

        $validated = $request->validate([
            'title'             => ['required', 'string', 'max:150'],
            'message'           => ['required', 'string', 'max:2000'],
            'priority'          => ['required', 'in:high,medium,low'],
            'announcement_date' => ['required', 'date'],
            'is_active'         => ['nullable', 'boolean'],
            'target_type'       => ['nullable', 'in:all,specific'],
            'branch_ids'        => ['nullable', 'array'],
            'branch_ids.*'      => ['integer', 'exists:branches,id'],
        ]);

        $branchIds = null;
        if (($validated['target_type'] ?? 'all') === 'specific' && !empty($validated['branch_ids'])) {
            $branchIds = array_values(array_unique(array_map('intval', $validated['branch_ids'])));
        }

        $wasInactive = ! $announcement->is_active;
        $isActive = isset($validated['is_active']) ? (bool) $validated['is_active'] : $announcement->is_active;

        $announcement->update([
            'branch_ids'        => $branchIds,
            'title'             => $validated['title'],
            'message'           => $validated['message'],
            'priority'          => strtolower($validated['priority']),
            'announcement_date' => $validated['announcement_date'],
            'is_active'         => $isActive,
            'updated_by'        => $user->id,
        ]);

        $queuedCount = 0;
        if ($wasInactive && $announcement->is_active) {
            $queuedCount = $this->dispatchAnnouncementEmails($announcement);
        }

        $announcement->load(['creator:id,name', 'updater:id,name']);

        $msg = 'Announcement updated successfully.';
        if ($queuedCount > 0) {
            $msg .= " Email notifications queued for {$queuedCount} active employee(s).";
        }

        return response()->json([
            'success' => true,
            'message' => $msg,
            'data'    => $this->formatAnnouncement($announcement),
        ]);
    }

    // ── DELETE /api/mobile/hrms/announcements/{announcement} ───────────────────
    public function destroy(HrmsAnnouncement $announcement): JsonResponse
    {
        $user = auth()->user();
        $this->authorizeAnnouncementManagement($user);
        $this->authorizeCompanyOwnership($announcement, $user);

        $title = $announcement->title;
        $announcement->delete();

        return response()->json([
            'success' => true,
            'message' => "Announcement \"{$title}\" deleted successfully.",
        ]);
    }

    // ── PATCH /api/mobile/hrms/announcements/{announcement}/toggle-status ──────
    public function toggleStatus(HrmsAnnouncement $announcement): JsonResponse
    {
        $user = auth()->user();
        $this->authorizeAnnouncementManagement($user);
        $this->authorizeCompanyOwnership($announcement, $user);

        $newStatus = ! $announcement->is_active;
        $announcement->update([
            'is_active'  => $newStatus,
            'updated_by' => $user->id,
        ]);

        $queuedCount = 0;
        if ($newStatus) {
            $queuedCount = $this->dispatchAnnouncementEmails($announcement);
        }

        $announcement->load(['creator:id,name', 'updater:id,name']);

        $stateText = $newStatus ? 'activated' : 'deactivated';
        $msg = "Announcement {$stateText} successfully.";
        if ($queuedCount > 0) {
            $msg .= " Email notifications queued for {$queuedCount} active employee(s).";
        }

        return response()->json([
            'success' => true,
            'message' => $msg,
            'data'    => $this->formatAnnouncement($announcement),
        ]);
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    private function formatAnnouncement(HrmsAnnouncement $a, $branchesById = null): array
    {
        $date = $a->announcement_date instanceof Carbon
            ? $a->announcement_date
            : ($a->announcement_date ? Carbon::parse($a->announcement_date) : null);

        // Compute branch labels in memory without per-row DB queries
        $branchIds = (array) ($a->branch_ids ?? []);
        $targetBranchesLabel = 'All Branches';
        $targetBranchNames = [];

        if (!empty($branchIds)) {
            if ($branchesById !== null) {
                foreach ($branchIds as $bId) {
                    if (isset($branchesById[$bId])) {
                        $targetBranchNames[] = $branchesById[$bId]->name;
                    }
                }
            } else {
                $targetBranchNames = Branch::whereIn('id', array_map('intval', $branchIds))->pluck('name')->all();
            }
            if (!empty($targetBranchNames)) {
                $targetBranchesLabel = implode(', ', $targetBranchNames);
            }
        }

        return [
            'id'                     => $a->id,
            'company_id'             => $a->company_id,
            'title'                  => $a->title,
            'message'                => $a->message,
            'priority'               => strtolower((string) ($a->priority ?: 'medium')),
            'announcement_date'      => $date?->toDateString() ?: now()->toDateString(),
            'announcement_date_formatted' => $date?->format('d M Y') ?: now()->format('d M Y'),
            'is_active'              => (bool) $a->is_active,
            'target_type'            => empty($branchIds) ? 'all' : 'specific',
            'branch_ids'             => array_values(array_map('intval', $branchIds)),
            'target_branches_label'  => $targetBranchesLabel,
            'target_branch_names'    => $targetBranchNames,
            'created_by'             => $a->created_by,
            'created_by_name'        => $a->creator?->name,
            'updated_by'             => $a->updated_by,
            'updated_by_name'        => $a->updater?->name,
            'created_at'             => $a->created_at?->toIso8601String(),
            'updated_at'             => $a->updated_at?->toIso8601String(),
        ];
    }

    private function dispatchAnnouncementEmails(HrmsAnnouncement $announcement): int
    {
        $employeesQuery = EmployeeOnboarding::withoutGlobalScopes()
            ->with(['portalUser.branch'])
            ->where('status', EmployeeOnboarding::STATUS_ACTIVE)
            ->whereNotNull('email')
            ->where('email', '!=', '');

        if ($announcement->company_id) {
            $employeesQuery->where('company_id', $announcement->company_id);
        }

        $employees = $employeesQuery->get();

        if (!empty($announcement->branch_ids)) {
            $targetBranchIds = array_map('intval', (array) $announcement->branch_ids);
            $employees = $employees->filter(function (EmployeeOnboarding $employee) use ($targetBranchIds) {
                $branch = $employee->branch;
                return $branch && in_array((int) $branch->id, $targetBranchIds, true);
            });
        }

        $queuedCount = 0;
        $seenEmails = [];

        foreach ($employees as $employee) {
            $email = trim(strtolower((string) $employee->email));
            if (empty($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL) || isset($seenEmails[$email])) {
                continue;
            }

            $seenEmails[$email] = true;
            $delaySeconds = $queuedCount * 3; // 3-second stagger interval between emails

            SendAnnouncementEmailJob::dispatch($announcement, $email, $employee->name)
                ->delay(now()->addSeconds($delaySeconds));

            $queuedCount++;
        }

        Log::info("[HrmsAnnouncementApi] Queued {$queuedCount} announcement emails for #{$announcement->id} with 3s staggered delays.");

        return $queuedCount;
    }

    private function canManageAnnouncements(?User $user): bool
    {
        return (bool) ($user && ($user->isSystemAdmin() || $user->isCompanyAdmin() || $user->belongsToHrDepartment() || $user->hasHrLikeRole()));
    }

    private function authorizeAnnouncementManagement(?User $user): void
    {
        abort_unless($this->canManageAnnouncements($user), 403, 'Unauthorized to manage announcements.');
    }

    private function authorizeCompanyOwnership(HrmsAnnouncement $announcement, ?User $user): void
    {
        if ($user && ! $user->isSystemAdmin() && $announcement->company_id && (int) $announcement->company_id !== (int) $user->company_id) {
            abort(403, 'Unauthorized access to this announcement.');
        }
    }

    private function isAnnouncementVisibleToUser(HrmsAnnouncement $announcement, ?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($announcement->company_id && (int) $announcement->company_id !== (int) $user->company_id) {
            return false;
        }

        if ($announcement->isForAllBranches()) {
            return true;
        }

        $userBranchIds = $user->getMyBranchIds();
        $targetBranchIds = array_map('intval', (array) $announcement->branch_ids);

        foreach ($userBranchIds as $ubId) {
            if (in_array((int) $ubId, $targetBranchIds, true)) {
                return true;
            }
        }

        return false;
    }
}
