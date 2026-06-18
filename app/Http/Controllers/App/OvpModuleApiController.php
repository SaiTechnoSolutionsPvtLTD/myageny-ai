<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\ProductionInitiation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class OvpModuleApiController extends Controller
{
    private const OVP_TL_ROLE_KEYS = ['customer_support_team_tl'];
    private const OVP_EXECUTIVE_ROLE_KEYS = ['customer_support_team_executive'];

    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $isExecutiveScopedView = $this->isExecutiveScopedUser($user);

        $initiations = ProductionInitiation::query()
            ->with([
                'lead:id,company_name,contact_name,branch_id,assigned_to,created_by',
                'lead.branch:id,name',
                'lead.assignedTo:id,name',
                'lead.createdBy:id,name',
                'leadProduct:id,lead_id,amount_paid,total_price,created_at',
                'department:id,name',
                'product.ovpFormFields',
                'ovpAllocatedTo:id,name',
                'ovpAllocatedBy:id,name',
                'reviewedBy:id,name',
            ])
            ->when($isExecutiveScopedView, fn($q) => $q->where('ovp_allocated_to', $user->id))
            ->latest()
            ->get();

        $buckets = [
            'new'      => ['title' => 'New',      'count' => 0, 'items' => []],
            'pending'  => ['title' => 'Pending',   'count' => 0, 'items' => []],
            'overdue'  => ['title' => 'Overdue',   'count' => 0, 'items' => []],
            'approved' => ['title' => 'Approved',  'count' => 0, 'items' => []],
            'reject'   => ['title' => 'Rejected',  'count' => 0, 'items' => []],
        ];

        foreach ($initiations as $initiation) {
            $bucket = $this->resolveBucket($initiation);
            if (!$bucket) continue;

            $buckets[$bucket]['items'][] = $this->formatInitiation($initiation, $user);
            $buckets[$bucket]['count']++;
        }

        $counts = collect($buckets)->map(fn($b) => $b['count']);

        return response()->json([
            'success' => true,
            'data' => [
                'buckets' => $buckets,
                'counts'  => $counts,
                'is_tl'   => $this->isTlScopedUser($user),
                'is_executive' => $isExecutiveScopedView,
            ],
        ]);
    }

    public function allocate(Request $request, ProductionInitiation $productionInitiation): JsonResponse
    {
        $user = auth()->user();

        if (!$this->canAllocate($productionInitiation, $user)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'executive_user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $selectedExecutiveId = $this->availableExecutiveUsers($user)
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->first(fn($id) => $id === (int) $validated['executive_user_id']);

        if (!$selectedExecutiveId) {
            return response()->json(['success' => false, 'message' => 'Please select a valid OVP executive.'], 422);
        }

        $productionInitiation->update([
            'ovp_allocation_status' => 'allocated',
            'ovp_allocated_to'      => $selectedExecutiveId,
            'ovp_allocated_by'      => $user->id,
            'ovp_allocated_at'      => Carbon::now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'OVP item allocated to executive successfully.',
            'data'    => $this->formatInitiation($productionInitiation->fresh([
                'lead', 'lead.branch', 'lead.assignedTo', 'leadProduct',
                'department', 'ovpAllocatedTo', 'ovpAllocatedBy', 'reviewedBy',
            ]), $user),
        ]);
    }

    public function review(Request $request, ProductionInitiation $productionInitiation): JsonResponse
    {
        $user = auth()->user();

        if (!$this->canReview($productionInitiation, $user)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'decision' => ['required', 'in:approval,rejected'],
        ]);

        $productionInitiation->update([
            'status'                          => $validated['decision'] === 'approval' ? 'approved' : 'rejected',
            'ovp_allocation_status'           => 'submitted',
            'reviewed_at'                     => Carbon::now(),
            'reviewed_by'                     => $user->id,
            'production_approval_status'      => $validated['decision'] === 'approval' ? 'pending' : null,
            'production_approval_reviewed_at' => null,
            'production_approval_reviewed_by' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => $validated['decision'] === 'approval'
                ? 'OVP item approved.'
                : 'OVP item rejected.',
            'data'    => $this->formatInitiation($productionInitiation->fresh([
                'lead', 'lead.branch', 'lead.assignedTo', 'leadProduct',
                'department', 'ovpAllocatedTo', 'ovpAllocatedBy', 'reviewedBy',
            ]), $user),
        ]);
    }

    public function executives(): JsonResponse
    {
        $user = auth()->user();

        $executives = $this->availableExecutiveUsers($user)->map(fn($e) => [
            'id'               => $e->id,
            'name'             => $e->name,
            'role_label'       => $e->role_label,
            'department_label' => $e->department_label,
        ])->values();

        return response()->json(['success' => true, 'data' => $executives]);
    }

    // ── Private helpers (mirrors web controller) ──────────────────────────────

    private function formatInitiation(ProductionInitiation $i, ?User $user): array
    {
        $leadProduct = $i->leadProduct;
        $totalAmount    = $leadProduct ? (float) $leadProduct->total_price : 0.0;
        $receivedAmount = $leadProduct ? (float) $leadProduct->amount_paid  : 0.0;

        return [
            'id'                  => $i->id,
            'product_name'        => $i->product_name ?? $i->lead?->productName ?? '',
            'total_working_days'  => $i->total_working_days ?? 0,
            'department'          => $i->department?->name ?? '',
            'company'             => $i->lead?->company_name ?? '',
            'contact_name'        => $i->lead?->contact_name ?? '',
            'status'              => $i->status,
            'bucket'              => $this->resolveBucket($i),
            'ovp_allocation_status' => $i->ovp_allocation_status,
            'allocated_to'        => $i->ovpAllocatedTo ? ['id' => $i->ovpAllocatedTo->id, 'name' => $i->ovpAllocatedTo->name] : null,
            'allocated_by'        => $i->ovpAllocatedBy ? ['id' => $i->ovpAllocatedBy->id, 'name' => $i->ovpAllocatedBy->name] : null,
            'allocated_at'        => $i->ovp_allocated_at?->toIso8601String(),
            'reviewed_by'         => $i->reviewedBy ? ['id' => $i->reviewedBy->id, 'name' => $i->reviewedBy->name] : null,
            'reviewed_at'         => $i->reviewed_at?->toIso8601String(),
            'total_amount'        => $totalAmount,
            'received_amount'     => $receivedAmount,
            'pending_amount'      => max(0, $totalAmount - $receivedAmount),
            'sales_person'        => $i->lead?->assignedTo?->name ?? '',
            'branch'              => $i->lead?->branch?->name ?? '',
            'can_review'          => $this->canReview($i, $user),
            'can_allocate'        => $this->canAllocate($i, $user),
            'created_at'          => $i->created_at?->toIso8601String(),
            'is_overdue'          => $this->isOverdue($i),
        ];
    }

    private function resolveBucket(ProductionInitiation $i): ?string
    {
        return match (strtolower(trim((string) $i->status))) {
            'ovp_pending', 'initiated' => $this->isOverdue($i) ? 'overdue' : 'new',
            'pending'                  => 'pending',
            'approval', 'approved'     => 'approved',
            'rejected', 'reject'       => 'reject',
            default                    => null,
        };
    }

    private function isOverdue(ProductionInitiation $i): bool
    {
        return $i->created_at && $i->created_at->lt(Carbon::now()->subDays(3));
    }

    private function canReview(ProductionInitiation $i, ?User $user): bool
    {
        if (!$user || !$this->isPendingOvpItem($i)) return false;
        if ($this->isTlScopedUser($user) || $user->hasAdminLikeRole()) return true;
        return (int) $i->ovp_allocated_to === (int) $user->id;
    }

    private function canAllocate(ProductionInitiation $i, ?User $user): bool
    {
        if (!$user || !$this->isPendingOvpItem($i)) return false;
        return $this->isTlScopedUser($user) || $user->hasAdminLikeRole();
    }

    private function isPendingOvpItem(ProductionInitiation $i): bool
    {
        $status     = strtolower(trim((string) $i->status));
        $department = strtolower(trim((string) $i->department?->name));
        return in_array($status, ['ovp_pending', 'initiated', 'pending'], true)
            && $department === 'development';
    }

    private function isTlScopedUser(?User $user): bool
    {
        return $user && !$user->hasAdminLikeRole()
            && ($this->hasAnyRoleKey($user, self::OVP_TL_ROLE_KEYS) || $user->hasTlLikeRole());
    }

    private function isExecutiveScopedUser(?User $user): bool
    {
        return $user && !$user->hasAdminLikeRole() && !$this->isTlScopedUser($user)
            && ($this->hasAnyRoleKey($user, self::OVP_EXECUTIVE_ROLE_KEYS) || $user->hasExecutiveLikeRole());
    }

    private function availableExecutiveUsers(?User $user): Collection
    {
        if (!$user) return collect();

        $format = function (Collection $candidates): Collection {
            return $candidates->filter(function (User $c) {
                $isSelfTl = (int) $c->id === (int) auth()->id() && $this->isTlScopedUser($c);
                return !$c->hasAdminLikeRole()
                    && (!$this->isTlScopedUser($c) || $isSelfTl)
                    && ($isSelfTl || $this->hasAnyRoleKey($c, self::OVP_EXECUTIVE_ROLE_KEYS) || $c->hasExecutiveLikeRole());
            })->map(function (User $c) {
                $roles = $c->resolvedRoles(withDepartment: true);
                return (object) [
                    'id'               => $c->id,
                    'name'             => $c->name,
                    'role_label'       => $roles->map(fn($r) => $r->display_name ?: $r->name)->filter()->unique()->implode(', ') ?: 'Mapped User',
                    'department_label' => $roles->map(fn($r) => $r->department?->name)->filter()->unique()->implode(', ') ?: 'All Departments',
                ];
            })->sortBy('name')->values();
        };

        $managed = $user->hasAdminLikeRole() ? collect() : $user->managedUsers()->where('users.is_active', true)->with(['roles.department'])->get();
        if ($this->isTlScopedUser($user) && $user->is_active) {
            $user->loadMissing(['roles.department']);
            $managed = $managed->prepend($user)->unique('id')->values();
        }

        $result = $format($managed);
        if ($result->isNotEmpty()) return $result;

        $company = User::where('is_active', true)
            ->when($user->company_id, fn($q) => $q->where('company_id', $user->company_id))
            ->with(['roles.department'])->get();
        if ($this->isTlScopedUser($user) && $user->is_active) {
            $company = $company->prepend($user)->unique('id')->values();
        }

        return $format($company);
    }

    private function hasAnyRoleKey(User $user, array $keys): bool
    {
        $normalized = collect($keys)->map(fn($k) => $this->normalizeRoleKey($k))->filter()->unique();
        return $user->resolvedRoles(withDepartment: true)->contains(function ($role) use ($normalized) {
            return $normalized->contains($this->normalizeRoleKey((string) $role->name))
                || $normalized->contains($this->normalizeRoleKey((string) ($role->display_name ?? '')));
        });
    }

    private function normalizeRoleKey(string $value): string
    {
        $value = Str::contains($value, '__') ? Str::afterLast($value, '__') : $value;
        return Str::of($value)->lower()->replace('&', 'and')->replace(['-', ' '], '_')
            ->replaceMatches('/[^a-z0-9_]+/', '')->replaceMatches('/_+/', '_')->trim('_')->value();
    }
}