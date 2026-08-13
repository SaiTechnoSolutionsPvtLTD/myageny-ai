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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Services\NotificationService;

class OvpModuleApiController extends Controller
{
    private const OVP_TL_ROLE_KEYS = [
        'customer_support_team_tl',
        'senior_customer_success_team_executive',
        'senior_customer_success_executive',
        'senior_success_executive',
        'senior_customer_support_executive',
        'senior_support_executive',
        'senior_cst_executive',
    ];
    private const OVP_EXECUTIVE_ROLE_KEYS = [
        'customer_support_team_executive',
        'customer_support_executive',
        'customer_success_executive',
        'senior_customer_success_team_executive',
        'cst_executive',
        'support_executive',
        'executive',
    ];

    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $isExecutiveScopedView = $this->isExecutiveScopedUser($user);

        $query = ProductionInitiation::query()
            ->with([
                'lead:id,company_name,contact_name,branch_id,assigned_to,created_by',
                'lead.branch:id,name',
                'lead.assignedTo:id,name',
                'lead.createdBy:id,name',
                'leadProduct:id,lead_id,amount_paid,total_price,created_at',
                'leadProduct.payments',
                'department:id,name',
                'product.ovpFormFields',
                'ovpAllocatedTo:id,name',
                'ovpAllocatedBy:id,name',
                'reviewedBy:id,name',
            ]);

        // Filters — mirrors web OvpModuleController::index() exactly, so mobile
        // and web return the same result set for the same query params.
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->query('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->query('end_date'));
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->query('product_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        if ($request->filled('user_id')) {
            $query->where('ovp_allocated_to', $request->query('user_id'));
        }
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->query('company_id'));
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->query('department_id'));
        }

        $initiations = $query
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
                // Filter dropdown data — same source as web's index() (Product/
                // Department/User/Company, active-only where applicable).
                'filters' => $this->filterOptions(),
            ],
        ]);
    }

    /**
     * Dropdown data for the mobile filter sheet — mirrors the four `$products`/
     * `$departments`/`$users`/`$companies` variables web's OvpModuleController
     * passes into the Blade view.
     */
    private function filterOptions(): array
    {
        return [
            'products' => \App\Models\Product::orderBy('product_name')
                ->get(['id', 'product_name'])
                ->map(fn($p) => ['id' => $p->id, 'name' => $p->product_name])
                ->all(),
            'departments' => \App\Models\Department::orderBy('name')
                ->get(['id', 'name'])
                ->map(fn($d) => ['id' => $d->id, 'name' => $d->name])
                ->all(),
            'users' => \App\Models\User::where('user_status', 'active')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn($u) => ['id' => $u->id, 'name' => $u->name])
                ->all(),
            'companies' => \App\Models\Company::orderBy('company_name')
                ->get(['id', 'company_name'])
                ->map(fn($c) => ['id' => $c->id, 'name' => $c->company_name])
                ->all(),
        ];
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
                'lead',
                'lead.branch',
                'lead.assignedTo',
                'leadProduct',
                'department',
                'ovpAllocatedTo',
                'ovpAllocatedBy',
                'reviewedBy',
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

        // Was missing entirely — mirrors web's prepareOvpCustomFormData() call.
        // Validates required OVP fields and stores custom_form_data, exactly
        // like the web controller. On reject, the existing value is kept
        // untouched (same as web).
        try {
            $customFormData = $validated['decision'] === 'approval'
                ? $this->prepareOvpCustomFormData($request, $productionInitiation)
                : ($productionInitiation->custom_form_data ?? []);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Please fill all required fields.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $productionInitiation->update([
            'status'                          => $validated['decision'] === 'approval' ? 'approved' : 'rejected',
            'ovp_allocation_status'           => 'submitted',
            'custom_form_data'                => $customFormData,
            'reviewed_at'                     => Carbon::now(),
            'reviewed_by'                     => $user->id,
            'production_approval_status'      => $validated['decision'] === 'approval' ? 'pending' : null,
            'production_approval_reviewed_at' => null,
            'production_approval_reviewed_by' => null,
        ]);

        if (($validated['decision'] ?? null) === 'approval') {
            $companyId = $productionInitiation->company_id;
            $branchId = $productionInitiation->loadMissing('lead')->lead?->branch_id;

            // withoutGlobalScopes() — otherwise this candidate pool would silently
            // shrink to the ACTING user's own branch whenever they happen to be a
            // branch_admin, which is the wrong axis of scoping here (see core doc).
            $reviewers = User::withoutGlobalScopes()
                ->where('is_active', true)
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->get()
                ->filter(fn(User $u) => $u->hasCrmPermission('production_approval_module.menuview'));

            $reviewers = $this->notifications->filterByBranchVisibility($reviewers, $branchId);

            $this->notifications->notifyMany($reviewers, 'crm', 'production_approval_pending', [
                'title' => 'Production Approval Pending',
                'message' => ($productionInitiation->product_name ?? 'A production item') . ' is awaiting your production approval.',
                'detail' => $productionInitiation->company_name ?? $productionInitiation->lead?->company_name,
                'action_url' => route('production-approvals.index', ['bucket' => 'pending']),
                'priority' => 'high',
                'request_type' => 'production_approval',
                'request_id' => $productionInitiation->id,
                'actor_name' => auth()->user()?->name,
                'status' => 'pending',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $validated['decision'] === 'approval'
                ? 'OVP item approved.'
                : 'OVP item rejected.',
            'data'    => $this->formatInitiation($productionInitiation->fresh([
                'lead',
                'lead.branch',
                'lead.assignedTo',
                'leadProduct',
                'department',
                'ovpAllocatedTo',
                'ovpAllocatedBy',
                'reviewedBy',
                'product.ovpFormFields',
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

        if ($receivedAmount <= 0 && $i->lead_id) {
            $leadPaymentsSum = (float) \App\Models\LeadProductPayment::where('lead_id', $i->lead_id)->sum('amount');
            if ($leadPaymentsSum > 0) {
                $receivedAmount = $leadPaymentsSum;
            }
        }

        if ($totalAmount <= 0 && $i->lead_id) {
            $leadTotalSum = (float) \App\Models\LeadProduct::where('lead_id', $i->lead_id)->sum('total_price');
            if ($leadTotalSum > 0) {
                $totalAmount = $leadTotalSum;
            }
        }

        return [
            'id'                    => $i->id,
            // Lets the mobile app deep-link straight to this record's Lead
            // Details screen without a separate lookup — the `lead`
            // relation above is already eager-loaded, so this is free.
            'lead_id'               => $i->lead_id,
            'product_name'          => $i->product_name ?? $i->lead?->productName ?? '',
            'total_working_days'    => $i->total_working_days ?? 0,
            'department'            => $i->department?->name ?? '',
            'company'               => $i->lead?->company_name ?? '',
            'contact_name'          => $i->lead?->contact_name ?? '',
            'status'                => $i->status,
            'bucket'                => $this->resolveBucket($i),
            'ovp_allocation_status' => $i->ovp_allocation_status,
            'allocated_to'          => $i->ovpAllocatedTo ? ['id' => $i->ovpAllocatedTo->id, 'name' => $i->ovpAllocatedTo->name] : null,
            'allocated_by'          => $i->ovpAllocatedBy ? ['id' => $i->ovpAllocatedBy->id, 'name' => $i->ovpAllocatedBy->name] : null,
            'allocated_at'          => $i->ovp_allocated_at?->toIso8601String(),
            'reviewed_by'           => $i->reviewedBy ? ['id' => $i->reviewedBy->id, 'name' => $i->reviewedBy->name] : null,
            'reviewed_at'           => $i->reviewed_at?->toIso8601String(),
            'total_amount'          => $totalAmount,
            'received_amount'       => $receivedAmount,
            'pending_amount'        => max(0, $totalAmount - $receivedAmount),
            'sales_person'          => $i->lead?->assignedTo?->name ?? '',
            'branch'                => $i->lead?->branch?->name ?? '',
            'can_review'            => $this->canReview($i, $user),
            'can_allocate'          => $this->canAllocate($i, $user),
            'created_at'            => $i->created_at?->toIso8601String(),
            'is_overdue'            => $this->isOverdue($i),
            // New — the two fields the Flutter app was missing entirely.
            'ovp_form_schema'       => $this->ovpFormSchemaFor($i),
            'custom_form_data'      => $i->custom_form_data ?? [],
        ];
    }

    /**
     * Same shape/filtering as web's inline $ovpSchema block in ovp-index.blade.php
     * and ProductOvpFormController::schema() — is_active + use_in_ovp only,
     * ordered by sort_order then id.
     */
    private function ovpFormSchemaFor(ProductionInitiation $i): array
    {
        $fields = $i->product?->ovpFormFields
            ? $i->product->ovpFormFields
            ->where('is_active', true)
            ->where('use_in_ovp', true)
            ->sortBy(['sort_order', 'id'])
            ->values()
            : collect();

        return $fields->map(fn($field) => [
            'id'                => $field->id,
            'label'             => $field->label,
            'field_name'        => $field->field_name,
            'field_type'        => $field->field_type,
            'placeholder'       => $field->placeholder,
            'help_text'         => $field->help_text,
            'default_value'     => $field->default_value,
            'is_required'       => $field->is_required,
            'options'           => $field->options ?? [],
            'validation_rules'  => $field->validation_rules ?? [],
        ])->all();
    }

    /**
     * Direct port of web OvpModuleController::prepareOvpCustomFormData() —
     * same validation rules per field_type, same file storage disk/path, same
     * "preserve entries for fields not in the current OVP schema" behavior.
     */
    private function prepareOvpCustomFormData(Request $request, ProductionInitiation $productionInitiation): array
    {
        $product = $productionInitiation->product;
        $fields = $product?->ovpFormFields()
            ->where('is_active', true)
            ->where('use_in_ovp', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get() ?? collect();

        if ($fields->isEmpty()) {
            return $productionInitiation->custom_form_data ?? [];
        }

        $rules = [];
        foreach ($fields as $field) {
            $fieldKey = 'custom_fields.' . $field->field_name;
            $fileKey  = 'custom_files.' . $field->field_name;
            $baseRules = $field->is_required ? ['required'] : ['nullable'];

            switch ($field->field_type) {
                case 'number':
                    $rules[$fieldKey] = array_merge($baseRules, ['numeric']);
                    if (($field->validation_rules['min'] ?? null) !== null) {
                        $rules[$fieldKey][] = 'min:' . $field->validation_rules['min'];
                    }
                    if (($field->validation_rules['max'] ?? null) !== null) {
                        $rules[$fieldKey][] = 'max:' . $field->validation_rules['max'];
                    }
                    break;
                case 'checkbox':
                    $rules[$fieldKey] = array_merge($baseRules, ['array']);
                    $rules[$fieldKey . '.*'] = ['string'];
                    break;
                case 'select':
                case 'radio':
                    $allowed = collect($field->options ?? [])->pluck('value')->filter()->all();
                    $rules[$fieldKey] = array_merge($baseRules, ['string'], $allowed ? ['in:' . implode(',', $allowed)] : []);
                    break;
                case 'date':
                    $rules[$fieldKey] = array_merge($baseRules, ['date']);
                    break;
                case 'file':
                    $rules[$fileKey] = array_merge($baseRules, ['file', 'max:10240']);
                    break;
                default:
                    $rules[$fieldKey] = array_merge($baseRules, ['string', 'max:5000']);
                    break;
            }
        }

        Validator::make($request->all(), $rules)->validate();

        $existingEntries = collect($productionInitiation->custom_form_data ?? [])->keyBy('field_name');
        $ovpFieldNames = $fields->pluck('field_name')->filter()->values()->all();
        $customValues = (array) $request->input('custom_fields', []);
        $stored = [];

        foreach ($fields as $field) {
            $fieldName = $field->field_name;

            if ($field->field_type === 'file') {
                $uploadedFile = $request->file('custom_files.' . $fieldName);

                if ($uploadedFile) {
                    $path = $uploadedFile->store('production-initiations/custom-fields', 'public');
                    $stored[] = [
                        'field_id'   => $field->id,
                        'field_name' => $fieldName,
                        'label'      => $field->label,
                        'type'       => $field->field_type,
                        'value'      => [
                            'path' => $path,
                            'name' => $uploadedFile->getClientOriginalName(),
                            'url'  => Storage::disk('public')->url($path),
                        ],
                    ];
                    continue;
                }

                if ($existingEntries->has($fieldName)) {
                    $stored[] = $existingEntries->get($fieldName);
                }

                continue;
            }

            $value = $customValues[$fieldName] ?? null;

            if (is_array($value)) {
                $value = array_values(array_filter($value, fn($item) => $item !== null && $item !== ''));
            }

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $stored[] = [
                'field_id'   => $field->id,
                'field_name' => $fieldName,
                'label'      => $field->label,
                'type'       => $field->field_type,
                'value'      => $value,
            ];
        }

        $preservedEntries = $existingEntries
            ->reject(fn($entry, $fieldName) => in_array((string) $fieldName, $ovpFieldNames, true))
            ->values()
            ->all();

        return array_merge($preservedEntries, $stored);
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

        return in_array($status, ['ovp_pending', 'initiated', 'pending'], true);
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
        $company = User::where('is_active', true)
            ->when($user->company_id, fn($q) => $q->where('company_id', $user->company_id))
            ->with(['roles.department'])->get();

        $allCandidates = $managed->concat($company)->unique('id')->values();

        if ($this->isTlScopedUser($user) && $user->is_active) {
            $user->loadMissing(['roles.department']);
            $allCandidates = $allCandidates->prepend($user)->unique('id')->values();
        }

        return $format($allCandidates);
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
