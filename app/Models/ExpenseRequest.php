<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseRequest extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'user_id',
        'expense_category_id',
        'amount',
        'description',
        'attachment',
        'current_step',
        'current_approver_role_id',
        'status',
        'approver_id',
        'rejection_reason',
        'stage_history',
        'actioned_at',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'current_step'  => 'integer',
        'stage_history' => 'array',
        'actioned_at'   => 'datetime',
    ];

    protected $appends = [
        'attachment_url',
        'attachment_urls',
    ];

    /**
     * Get all attachment relative paths as an array.
     * Supports legacy single string and JSON array format.
     *
     * @return array<int, string>
     */
    public function getAttachmentsListAttribute(): array
    {
        if (empty($this->attachment)) {
            return [];
        }

        if (is_array($this->attachment)) {
            return array_values(array_filter($this->attachment));
        }

        // Check if JSON array
        if (is_string($this->attachment) && str_starts_with(trim($this->attachment), '[')) {
            $decoded = json_decode($this->attachment, true);
            if (is_array($decoded)) {
                return array_values(array_filter($decoded));
            }
        }

        return [$this->attachment];
    }

    /**
     * Get all attachment full URLs as an array.
     *
     * @return array<int, string>
     */
    public function getAttachmentUrlsAttribute(): array
    {
        $list = $this->attachments_list;
        $urls = [];

        foreach ($list as $path) {
            if (! $path) {
                continue;
            }

            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                $urls[] = $path;
            } elseif (str_starts_with($path, 'uploads/') || file_exists(public_path($path))) {
                $urls[] = asset($path);
            } elseif (str_starts_with($path, 'expense_attachments/')) {
                $urls[] = asset('storage/' . $path);
            } else {
                $urls[] = asset($path);
            }
        }

        return $urls;
    }

    /**
     * Get the first attachment full URL for backward compatibility.
     */
    public function getAttachmentUrlAttribute(): ?string
    {
        $urls = $this->attachment_urls;
        return ! empty($urls) ? $urls[0] : null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function currentApproverRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'current_approver_role_id');
    }

    /**
     * Get the configured ExpensePipeline for this request's applicant.
     */
    public function getPipelineAttribute(): ?ExpensePipeline
    {
        $applicantUser = $this->user;
        if (! $applicantUser) {
            return null;
        }

        $applicantRoleIds = \DB::table('model_has_roles')
            ->where('model_type', User::class)
            ->where('model_id', $applicantUser->id)
            ->pluck('role_id')
            ->toArray();

        if (empty($applicantRoleIds) && $applicantUser->relationLoaded('roles')) {
            $applicantRoleIds = $applicantUser->roles->pluck('id')->toArray();
        }

        $companyId = $applicantUser->company_id ?: $this->company_id;

        if (empty($applicantRoleIds)) {
            return null;
        }

        // 1. Exact role ID match
        $pipeline = ExpensePipeline::withoutGlobalScopes()
            ->whereIn('role_id', $applicantRoleIds)
            ->when($companyId, fn($q) => $q->where(fn($q2) => $q2->where('company_id', $companyId)->orWhereNull('company_id')))
            ->where('is_active', true)
            ->first();

        // 2. Normalized role name fallback match
        if (! $pipeline) {
            $userRoles = Role::withoutGlobalScopes()->whereIn('id', $applicantRoleIds)->get();
            foreach ($userRoles as $uRole) {
                $baseName = strtolower(preg_replace('/^company_\d+__/', '', $uRole->name));
                $matchingRoleIds = Role::withoutGlobalScopes()
                    ->where('name', 'like', "%{$baseName}%")
                    ->pluck('id')
                    ->toArray();

                $pipeline = ExpensePipeline::withoutGlobalScopes()
                    ->whereIn('role_id', $matchingRoleIds)
                    ->when($companyId, fn($q) => $q->where(fn($q2) => $q2->where('company_id', $companyId)->orWhereNull('company_id')))
                    ->where('is_active', true)
                    ->first();

                if ($pipeline) {
                    break;
                }
            }
        }

        return $pipeline;
    }

    /**
     * Get detailed list of all stages in the pipeline with their status and history.
     */
    public function getApprovalStagesAttribute(): array
    {
        $pipeline = $this->pipeline;
        $chain = $pipeline?->approval_chain ?? [];

        if (empty($chain) && $this->current_approver_role_id) {
            $chain = [(int) $this->current_approver_role_id];
        }

        $history = collect($this->stage_history ?? []);
        $stages = [];

        foreach ($chain as $index => $roleId) {
            $step = $index + 1;
            $role = Role::withoutGlobalScopes()->find($roleId);
            $roleName = $role?->display_name ?: ($role ? ucfirst(str_replace('_', ' ', preg_replace('/^company_\d+__/', '', $role->name))) : ('Role #' . $roleId));

            // Find history entry for this step
            $stepHistory = $history->firstWhere('step', $step);

            $stageStatus = 'upcoming';
            if ($this->status === 'approved') {
                $stageStatus = 'completed';
            } elseif ($this->status === 'rejected') {
                if ($step < $this->current_step || ($stepHistory && ($stepHistory['action'] ?? '') === 'approved')) {
                    $stageStatus = 'completed';
                } elseif ($step === $this->current_step) {
                    $stageStatus = 'rejected';
                } else {
                    $stageStatus = 'upcoming';
                }
            } else {
                // Pending status
                if ($step < $this->current_step) {
                    $stageStatus = 'completed';
                } elseif ($step === $this->current_step) {
                    $stageStatus = 'current';
                } else {
                    $stageStatus = 'upcoming';
                }
            }

            $stages[] = [
                'step'         => $step,
                'role_id'      => (int) $roleId,
                'role_name'    => $roleName,
                'status'       => $stageStatus,
                'actioned_by'  => $stepHistory['user_name'] ?? null,
                'actioned_at'  => isset($stepHistory['actioned_at']) ? \Carbon\Carbon::parse($stepHistory['actioned_at']) : null,
                'remarks'      => $stepHistory['remarks'] ?? null,
            ];
        }

        return $stages;
    }

    /**
     * Check if a given user is authorized to action (Approve/Reject) the current stage.
     */
    public function canUserAction(?User $user = null): bool
    {
        $user = $user ?: auth()->user();
        if (! $user || $this->status !== 'pending') {
            return false;
        }

        // Applicant cannot approve their own request
        if ((int) $user->id === (int) $this->user_id) {
            return false;
        }

        // Super Admin / Company Admin override
        if ($user->isCompanyAdmin() || $user->isSystemAdmin()) {
            return true;
        }

        $approverRoleId = $this->current_approver_role_id;
        if (! $approverRoleId) {
            $pipeline = $this->pipeline;
            $chain = $pipeline?->approval_chain ?? [];
            $stepIndex = max(0, ($this->current_step ?? 1) - 1);
            $approverRoleId = $chain[$stepIndex] ?? null;
        }

        if (! $approverRoleId) {
            return false;
        }

        // Check user's assigned roles (matching by ID or normalized role name)
        $userRoleIds = \DB::table('model_has_roles')
            ->where('model_type', User::class)
            ->where('model_id', $user->id)
            ->pluck('role_id')
            ->toArray();

        if (empty($userRoleIds) && $user->relationLoaded('roles')) {
            $userRoleIds = $user->roles->pluck('id')->toArray();
        }

        if (in_array((int) $approverRoleId, array_map('intval', $userRoleIds), true)) {
            return true;
        }

        $targetRole = Role::withoutGlobalScopes()->find($approverRoleId);
        if ($targetRole) {
            $targetBaseName = strtolower(trim(preg_replace('/^company_\d+__/', '', $targetRole->name)));
            $targetDisplayName = strtolower(trim($targetRole->display_name ?? ''));

            $userRoles = Role::withoutGlobalScopes()->whereIn('id', $userRoleIds)->get();
            foreach ($userRoles as $uRole) {
                $userBaseName = strtolower(trim(preg_replace('/^company_\d+__/', '', $uRole->name)));
                $userDisplayName = strtolower(trim($uRole->display_name ?? ''));

                if ($userBaseName === $targetBaseName || 
                    ($targetDisplayName && $userDisplayName === $targetDisplayName) ||
                    str_contains($userBaseName, $targetBaseName) ||
                    str_contains($targetBaseName, $userBaseName)) {
                    return true;
                }
            }
        }

        return false;
    }
}
