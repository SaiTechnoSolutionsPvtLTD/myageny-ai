<?php

namespace App\Http\Controllers;

use App\Models\ProductionInitiation;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class OvpModuleController extends Controller
{
    private const OVP_TL_ROLE_KEYS = [
        'customer_support_team_tl',
    ];

    private const OVP_EXECUTIVE_ROLE_KEYS = [
        'customer_support_team_executive',
    ];

    public function index(Request $request): View
    {
        $user = auth()->user();
        $isTlScopedView = $this->isTlScopedUser($user);
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
            ->when($isExecutiveScopedView, function ($query) use ($user) {
                $query->where('ovp_allocated_to', $user->id);
            })
            ->latest()
            ->get();

        $buckets = [
            'new' => [
                'title' => 'New',
                'status_label' => 'New',
                'count' => 0,
                'items' => collect(),
            ],
            'pending' => [
                'title' => 'Pending',
                'status_label' => 'Pending',
                'count' => 0,
                'items' => collect(),
            ],
            'overdue' => [
                'title' => 'Overdue',
                'status_label' => 'Overdue',
                'count' => 0,
                'items' => collect(),
            ],
            'approved' => [
                'title' => 'Approved',
                'status_label' => 'Approved',
                'count' => 0,
                'items' => collect(),
            ],
            'reject' => [
                'title' => 'Reject',
                'status_label' => 'Rejected',
                'count' => 0,
                'items' => collect(),
            ],
        ];

        foreach ($initiations as $initiation) {
            $bucket = $this->resolveBucket($initiation);

            if (! $bucket) {
                continue;
            }

            /** @var Collection $items */
            $items = $buckets[$bucket]['items'];
            $items->push($initiation);

            $buckets[$bucket]['items'] = $items;
            $buckets[$bucket]['count']++;
        }

        $selectedBucket = (string) $request->query('bucket', 'new');

        if (! array_key_exists($selectedBucket, $buckets)) {
            $selectedBucket = 'new';
        }

        return view('pages.ovp_module.index', [
            'cards' => $buckets,
            'selectedBucket' => $selectedBucket,
            'selectedCard' => $buckets[$selectedBucket],
            'executiveUsers' => $this->availableExecutiveUsers($user),
            'isTlScopedView' => $isTlScopedView,
            'isExecutiveScopedView' => $isExecutiveScopedView,
        ]);
    }

    public function allocate(Request $request, ProductionInitiation $productionInitiation): RedirectResponse
    {
        $user = auth()->user();

        abort_unless($this->canAllocate($productionInitiation, $user), 403);

        $validated = $request->validate([
            'executive_user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $selectedExecutiveId = $this->availableExecutiveUsers($user)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->first(fn ($id) => $id === (int) $validated['executive_user_id']);

        abort_if(! $selectedExecutiveId, 422, 'Please select a valid OVP executive.');

        $productionInitiation->update([
            'ovp_allocation_status' => 'allocated',
            'ovp_allocated_to' => $selectedExecutiveId,
            'ovp_allocated_by' => $user->id,
            'ovp_allocated_at' => Carbon::now(),
        ]);

        return redirect()
            ->route('ovp-module.index', ['bucket' => (string) $request->query('bucket', 'new')])
            ->with('success', 'OVP item allocated to executive successfully.');
    }

    public function review(Request $request, ProductionInitiation $productionInitiation): RedirectResponse
    {
        abort_unless($this->canReview($productionInitiation, auth()->user()), 403);

        $validated = $request->validate([
            'decision' => ['required', 'in:approval,rejected'],
        ]);

        $customFormData = $validated['decision'] === 'approval'
            ? $this->prepareOvpCustomFormData($request, $productionInitiation)
            : ($productionInitiation->custom_form_data ?? []);

        $productionInitiation->update([
            'status' => $validated['decision'] === 'approval' ? 'approved' : 'rejected',
            'ovp_allocation_status' => 'submitted',
            'custom_form_data' => $customFormData,
            'reviewed_at' => Carbon::now(),
            'reviewed_by' => auth()->id(),
            'production_approval_status' => $validated['decision'] === 'approval' ? 'pending' : null,
            'production_approval_reviewed_at' => null,
            'production_approval_reviewed_by' => null,
        ]);

        return redirect()
            ->route('ovp-module.index', ['bucket' => $validated['decision'] === 'approval' ? 'approved' : 'reject'])
            ->with('success', $validated['decision'] === 'approval'
                ? 'OVP item moved to approved.'
                : 'OVP item moved to rejected.');
    }

    private function resolveBucket(ProductionInitiation $initiation): ?string
    {
        $status = strtolower(trim((string) $initiation->status));

        return match ($status) {
            'ovp_pending', 'initiated' => $this->isOverdue($initiation) ? 'overdue' : 'new',
            'pending' => 'pending',
            'approval', 'approved' => 'approved',
            'rejected', 'reject' => 'reject',
            default => null,
        };
    }

    private function isOverdue(ProductionInitiation $initiation): bool
    {
        if (! $initiation->created_at) {
            return false;
        }

        return $initiation->created_at->lt(Carbon::now()->subDays(3));
    }

    private function canReview(ProductionInitiation $productionInitiation, ?User $user): bool
    {
        if (! $user || ! $this->isPendingOvpItem($productionInitiation)) {
            return false;
        }

        if ($this->isTlScopedUser($user) || $user->hasAdminLikeRole()) {
            return true;
        }

        return (int) $productionInitiation->ovp_allocated_to === (int) $user->id;
    }

    private function canAllocate(ProductionInitiation $productionInitiation, ?User $user): bool
    {
        if (! $user || ! $this->isPendingOvpItem($productionInitiation)) {
            return false;
        }

        return $this->isTlScopedUser($user) || $user->hasAdminLikeRole();
    }

    private function isPendingOvpItem(ProductionInitiation $productionInitiation): bool
    {
        $status = strtolower(trim((string) $productionInitiation->status));

        return in_array($status, ['ovp_pending', 'initiated', 'pending'], true);
    }

    private function isTlScopedUser(?User $user): bool
    {
        return $user
            && ! $user->hasAdminLikeRole()
            && ($this->hasAnyRoleKey($user, self::OVP_TL_ROLE_KEYS) || $user->hasTlLikeRole());
    }

    private function isExecutiveScopedUser(?User $user): bool
    {
        return $user
            && ! $user->hasAdminLikeRole()
            && ! $this->isTlScopedUser($user)
            && ($this->hasAnyRoleKey($user, self::OVP_EXECUTIVE_ROLE_KEYS) || $user->hasExecutiveLikeRole());
    }

    private function availableExecutiveUsers(?User $user): Collection
    {
        if (! $user) {
            return collect();
        }

        $formatCandidates = function (Collection $candidates): Collection {
            return $candidates
                ->filter(function (User $candidate) {
                    $isSelfTlOption = (int) $candidate->id === (int) auth()->id() && $this->isTlScopedUser($candidate);

                    return ! $candidate->hasAdminLikeRole()
                        && (! $this->isTlScopedUser($candidate) || $isSelfTlOption)
                        && ($isSelfTlOption
                            || $this->hasAnyRoleKey($candidate, self::OVP_EXECUTIVE_ROLE_KEYS)
                            || $candidate->hasExecutiveLikeRole());
                })
                ->map(function (User $candidate) {
                    $roles = $candidate->resolvedRoles(withDepartment: true);
                    $departments = $roles
                        ->map(fn ($role) => $role->department?->name)
                        ->filter()
                        ->unique()
                        ->values();

                    return (object) [
                        'id' => $candidate->id,
                        'name' => $candidate->name,
                        'role_label' => $roles
                            ->map(fn ($role) => $role->display_name ?: $role->name)
                            ->filter()
                            ->unique()
                            ->implode(', ') ?: 'Mapped User',
                        'department_label' => $departments->isNotEmpty()
                            ? $departments->implode(', ')
                            : 'All Departments',
                    ];
                })
                ->sortBy('name')
                ->values();
        };

        $managedUsers = $user->hasAdminLikeRole()
            ? collect()
            : $user->managedUsers()
                ->where('users.is_active', true)
                ->with(['roles.department'])
                ->get();

        if ($this->isTlScopedUser($user) && $user->is_active) {
            $user->loadMissing(['roles.department']);
            $managedUsers = $managedUsers
                ->prepend($user)
                ->unique('id')
                ->values();
        }

        $managedExecutives = $formatCandidates($managedUsers);
        if ($managedExecutives->isNotEmpty()) {
            return $managedExecutives;
        }

        $companyUsers = User::query()
            ->where('is_active', true)
            ->when($user->company_id, fn ($query) => $query->where('company_id', $user->company_id))
            ->with(['roles.department'])
            ->get();

        if ($this->isTlScopedUser($user) && $user->is_active) {
            $companyUsers = $companyUsers
                ->prepend($user)
                ->unique('id')
                ->values();
        }

        return $formatCandidates($companyUsers);
    }

    private function hasAnyRoleKey(User $user, array $keys): bool
    {
        $normalizedKeys = collect($keys)
            ->map(fn (string $key) => $this->normalizeRoleKey($key))
            ->filter()
            ->unique();

        return $user->resolvedRoles(withDepartment: true)->contains(function ($role) use ($normalizedKeys) {
            return $normalizedKeys->contains($this->normalizeRoleKey((string) $role->name))
                || $normalizedKeys->contains($this->normalizeRoleKey((string) ($role->display_name ?? '')));
        });
    }

    private function normalizeRoleKey(string $value): string
    {
        $value = Str::contains($value, '__') ? Str::afterLast($value, '__') : $value;

        return Str::of($value)
            ->lower()
            ->replace('&', 'and')
            ->replace(['-', ' '], '_')
            ->replaceMatches('/[^a-z0-9_]+/', '')
            ->replaceMatches('/_+/', '_')
            ->trim('_')
            ->value();
    }

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
            $fileKey = 'custom_files.' . $field->field_name;
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
                        'field_id' => $field->id,
                        'field_name' => $fieldName,
                        'label' => $field->label,
                        'type' => $field->field_type,
                        'value' => [
                            'path' => $path,
                            'name' => $uploadedFile->getClientOriginalName(),
                            'url' => Storage::disk('public')->url($path),
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
                $value = array_values(array_filter($value, fn ($item) => $item !== null && $item !== ''));
            }

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $stored[] = [
                'field_id' => $field->id,
                'field_name' => $fieldName,
                'label' => $field->label,
                'type' => $field->field_type,
                'value' => $value,
            ];
        }

        $preservedEntries = $existingEntries
            ->reject(fn ($entry, $fieldName) => in_array((string) $fieldName, $ovpFieldNames, true))
            ->values()
            ->all();

        return array_merge($preservedEntries, $stored);
    }
}
