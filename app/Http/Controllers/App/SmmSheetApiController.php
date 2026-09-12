<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Lead;
use App\Models\Product;
use App\Models\ProductionInitiation;
use App\Models\SmmSheet;
use App\Models\SmmSheetLog;
use App\Models\User;
use App\Services\DataVisibilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SmmSheetApiController extends Controller
{
    public function __construct(private readonly DataVisibilityService $visibility) {}

    /**
     * Check if user is authorized to access SMM Sheet module.
     */
    private function authorizeSmmAccess(User $user): void
    {
        abort_unless(
            $user->belongsToDesigningDepartment() ||
            $user->belongsToDigitalMarketingDepartment() ||
            $user->hasAdminLikeRole() ||
            $user->canViewProjectsDashboardSwitcher() ||
            $user->canAccessProjectsModule(),
            403,
            'Unauthorized access to SMM Sheet.'
        );
    }

    /**
     * Ensure existing count-wise renewal production initiations exist in smm_sheets
     */
    private function ensureInitiationsSynced(?int $companyId): void
    {
        try {
            $initiations = ProductionInitiation::query()
                ->whereHas('product', function ($q) {
                    $q->where('count_wise_report', true)
                      ->where('is_this_renewal_product', true);
                })
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereDoesntHave('smmSheet')
                ->get();

            foreach ($initiations as $pi) {
                SmmSheet::syncFromInitiation($pi);
            }
        } catch (\Throwable $e) {
            Log::warning('ensureInitiationsSynced warning: ' . $e->getMessage());
        }
    }

    /**
     * GET /mobile/projects/smm-sheet
     * Returns paginated SMM Sheet records, KPI stats, leads dropdown, and active accounts.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->authorizeSmmAccess($user);
        $companyId = $this->visibility->companyIdFor($user);

        // Ensure sync from Production Initiations
        $this->ensureInitiationsSynced($companyId);

        // Department & Role detection
        $isDesignUser = (bool) $user->belongsToDesigningDepartment();
        $isDmUser     = (bool) $user->belongsToDigitalMarketingDepartment();
        $isAdminLike  = (bool) $user->hasAdminLikeRole() || (bool) $user->canViewProjectsDashboardSwitcher();

        if ($isDesignUser && ! $isAdminLike) {
            $teamView = 'design';
        } elseif ($isDmUser && ! $isAdminLike) {
            $teamView = 'dm';
        } else {
            $teamView = (string) $request->input('team_view', 'all');
            if (! in_array($teamView, ['all', 'design', 'dm'])) {
                $teamView = 'all';
            }
        }

        // Fetch allocated lead IDs
        $allocatedLeadIdsQuery = DB::table('smm_sheets as s')
            ->join('products as p', 'p.id', '=', 's.product_id')
            ->leftJoin('production_initiations as pi', 'pi.id', '=', 's.production_initiation_id')
            ->leftJoin('departments as d', 'd.id', '=', 's.department_id')
            ->where('p.count_wise_report', true)
            ->where('p.is_this_renewal_product', true)
            ->whereNull('s.deleted_at')
            ->when($companyId, fn ($q) => $q->where('s.company_id', $companyId));

        if (! $isAdminLike && $user) {
            $allocatedLeadIdsQuery->where(function ($q) use ($user, $isDesignUser, $isDmUser) {
                $q->whereJsonContains('pi.project_allocated_employee_user_ids', $user->id)
                  ->orWhereJsonContains('pi.project_allocated_tl_user_ids', $user->id);

                if ($isDesignUser) {
                    $q->orWhere('d.name', 'like', '%design%');
                } elseif ($isDmUser) {
                    $q->orWhere('d.name', 'like', '%marketing%')
                      ->orWhere('d.name', 'like', '%digital%');
                }
            });
        }

        $allocatedLeadIds = $allocatedLeadIdsQuery->distinct()->pluck('s.lead_id')->filter()->unique()->toArray();

        $leads = Lead::query()
            ->whereIn('id', $allocatedLeadIds)
            ->orderBy('company_name')
            ->get(['id', 'company_name', 'contact_name'])
            ->map(function ($lead) {
                $label = trim(($lead->company_name ?? '') ?: ($lead->contact_name ?? ''));
                return [
                    'id'   => $lead->id,
                    'name' => $label ?: 'Lead #' . $lead->id,
                ];
            });

        // Users mapping for allocations
        $usersWithDept = DB::table('users as u')
            ->leftJoin('employee_onboardings as eo', function ($join) {
                $join->on('eo.portal_user_id', '=', 'u.id')
                     ->whereNull('eo.deleted_at');
            })
            ->leftJoin('departments as dep', 'dep.id', '=', 'eo.department_id')
            ->select([
                'u.id',
                'u.name',
                'dep.name as dept_name',
            ])
            ->get()
            ->keyBy('id');

        // Main Query
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');
        $leadId   = $request->input('lead_id');
        $status   = $request->input('status');
        $search   = trim((string) $request->input('search', ''));

        $query = DB::table('smm_sheets as s')
            ->join('leads as l', 'l.id', '=', 's.lead_id')
            ->join('products as p', 'p.id', '=', 's.product_id')
            ->leftJoin('production_initiations as pi', 'pi.id', '=', 's.production_initiation_id')
            ->leftJoin('departments as d', 'd.id', '=', 's.department_id')
            ->select([
                's.id as smm_sheet_id',
                's.company_id',
                's.lead_id',
                's.lead_product_id',
                's.production_initiation_id',
                's.product_id',
                's.department_id',
                's.start_date',
                's.end_date',
                's.delivery_date',
                's.committed_posters',
                's.committed_videos',
                's.design_completed_posters',
                's.design_completed_videos',
                's.dm_completed_posters',
                's.dm_completed_videos',
                's.status as smm_status',
                's.custom_form_data',
                'pi.id as pi_id',
                'pi.project_delivery_date',
                'pi.tl_employee_allocations',
                'pi.project_allocated_employee_user_ids',
                'pi.project_allocated_tl_user_ids',
                'l.company_name',
                'l.contact_name',
                'p.package_name as product_name',
                'd.name as department_name',
            ])
            ->whereNull('s.deleted_at')
            ->when($companyId, fn ($q) => $q->where('s.company_id', $companyId))
            ->where('p.count_wise_report', true)
            ->where('p.is_this_renewal_product', true)
            ->when($leadId, fn ($q) => $q->where('s.lead_id', $leadId));

        if (! $isAdminLike && $user) {
            $query->where(function ($q) use ($user, $isDesignUser, $isDmUser) {
                $q->whereJsonContains('pi.project_allocated_employee_user_ids', $user->id)
                  ->orWhereJsonContains('pi.project_allocated_tl_user_ids', $user->id);

                if ($isDesignUser) {
                    $q->orWhere('d.name', 'like', '%design%');
                } elseif ($isDmUser) {
                    $q->orWhere('d.name', 'like', '%marketing%')
                      ->orWhere('d.name', 'like', '%digital%');
                }
            });
        }

        if ($dateFrom) {
            $query->where(function ($q) use ($dateFrom, $dateTo) {
                $q->where(function ($sq) use ($dateFrom, $dateTo) {
                    $sq->whereNotNull('s.delivery_date')
                       ->whereBetween('s.delivery_date', [$dateFrom, $dateTo ?: $dateFrom]);
                })->orWhere(function ($sq) use ($dateFrom, $dateTo) {
                    $sq->whereNull('s.delivery_date')
                       ->whereBetween('s.end_date', [$dateFrom, $dateTo ?: $dateFrom]);
                });
            });
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('l.company_name', 'like', "%{$search}%")
                  ->orWhere('l.contact_name', 'like', "%{$search}%")
                  ->orWhere('p.package_name', 'like', "%{$search}%");
            });
        }

        $query->orderBy('l.company_name')->orderBy('s.id');

        $sheets = $query->get();
        $piIds = $sheets->pluck('pi_id')->filter()->unique()->values()->all();

        // Timesheet completions / contributors
        $timesheetSums = ! empty($piIds) ? DB::table('project_timesheets as pt')
            ->join('users as u', 'u.id', '=', 'pt.user_id')
            ->leftJoin('employee_onboardings as eo', function ($join) {
                $join->on('eo.portal_user_id', '=', 'pt.user_id')
                     ->whereNull('eo.deleted_at');
            })
            ->leftJoin('departments as dep_eo', 'dep_eo.id', '=', 'eo.department_id')
            ->leftJoin('model_has_roles as mhr', function ($join) {
                $join->on('mhr.model_id', '=', 'pt.user_id')
                     ->where('mhr.model_type', '=', 'App\Models\User');
            })
            ->leftJoin('roles as r', 'r.id', '=', 'mhr.role_id')
            ->leftJoin('departments as dep_role', 'dep_role.id', '=', 'r.department_id')
            ->whereIn('pt.production_initiation_id', $piIds)
            ->select([
                'pt.production_initiation_id',
                DB::raw('COALESCE(dep_eo.name, dep_role.name) as dept_name'),
                DB::raw('SUM(COALESCE(pt.poster_count, 0)) as completed_posters'),
                DB::raw('SUM(COALESCE(pt.video_count, 0)) as completed_videos'),
                DB::raw('GROUP_CONCAT(DISTINCT u.name ORDER BY u.name SEPARATOR ", ") as persons'),
            ])
            ->groupBy('pt.production_initiation_id', DB::raw('COALESCE(dep_eo.name, dep_role.name)'))
            ->get()
            ->groupBy('production_initiation_id') : collect();

        $today = now()->toDateString();

        $allRows = $sheets->map(function ($s) use ($timesheetSums, $today, $usersWithDept) {
            $committedPosters = (int) $s->committed_posters;
            $committedVideos  = (int) $s->committed_videos;

            $designCompletedPosters = (int) $s->design_completed_posters;
            $designCompletedVideos  = (int) $s->design_completed_videos;
            $dmCompletedPosters     = (int) $s->dm_completed_posters;
            $dmCompletedVideos      = (int) $s->dm_completed_videos;

            $designPendingPosters = max(0, $committedPosters - $designCompletedPosters);
            $designPendingVideos  = max(0, $committedVideos  - $designCompletedVideos);
            $dmPendingPosters     = max(0, $committedPosters - $dmCompletedPosters);
            $dmPendingVideos      = max(0, $committedVideos  - $dmCompletedVideos);

            $completedPosters = $designCompletedPosters + $dmCompletedPosters;
            $completedVideos  = $designCompletedVideos + $dmCompletedVideos;
            $pendingPosters   = max(0, $committedPosters - $completedPosters);
            $pendingVideos    = max(0, $committedVideos - $completedVideos);

            // Allocated persons
            $designPersonsList = [];
            $dmPersonsList     = [];

            $allocatedEmployeeIds = is_array($s->project_allocated_employee_user_ids)
                ? $s->project_allocated_employee_user_ids
                : (json_decode($s->project_allocated_employee_user_ids ?? '[]', true) ?? []);
            $allocatedTlIds       = is_array($s->project_allocated_tl_user_ids)
                ? $s->project_allocated_tl_user_ids
                : (json_decode($s->project_allocated_tl_user_ids ?? '[]', true) ?? []);

            if (! is_array($allocatedEmployeeIds)) $allocatedEmployeeIds = [];
            if (! is_array($allocatedTlIds)) $allocatedTlIds = [];

            $allocatedUserIds = array_unique(array_filter(array_merge($allocatedEmployeeIds, $allocatedTlIds), fn ($u) => is_numeric($u)));

            foreach ($allocatedUserIds as $uId) {
                $userDept = $usersWithDept->get($uId);
                if ($userDept) {
                    $uName = $userDept->name;
                    $uDeptLower = strtolower($userDept->dept_name ?? '');

                    if (str_contains($uDeptLower, 'design')) {
                        $designPersonsList[] = $uName;
                    } elseif (str_contains($uDeptLower, 'digital') || str_contains($uDeptLower, 'dm') || str_contains($uDeptLower, 'marketing')) {
                        $dmPersonsList[] = $uName;
                    } else {
                        $projDeptLower = strtolower($s->department_name ?? '');
                        if (str_contains($projDeptLower, 'design')) {
                            $designPersonsList[] = $uName;
                        } elseif (str_contains($projDeptLower, 'digital') || str_contains($projDeptLower, 'dm') || str_contains($projDeptLower, 'marketing')) {
                            $dmPersonsList[] = $uName;
                        } else {
                            $designPersonsList[] = $uName;
                        }
                    }
                }
            }

            if ($s->pi_id && $timesheetSums->has($s->pi_id)) {
                foreach ($timesheetSums->get($s->pi_id) as $ts) {
                    $tsDeptLower = strtolower($ts->dept_name ?? '');
                    $tsPersons = array_filter(array_map('trim', explode(',', $ts->persons ?? '')));
                    if (str_contains($tsDeptLower, 'design')) {
                        $designPersonsList = array_merge($designPersonsList, $tsPersons);
                    } else {
                        $dmPersonsList = array_merge($dmPersonsList, $tsPersons);
                    }
                }
            }

            $designPersons = ! empty($designPersonsList) ? implode(', ', array_unique($designPersonsList)) : '-';
            $dmPersons     = ! empty($dmPersonsList)     ? implode(', ', array_unique($dmPersonsList))     : '-';

            // Status calculation
            $totalCommitted = $committedPosters + $committedVideos;
            $totalCompleted = $completedPosters + $completedVideos;
            $deliveryDate   = $s->delivery_date ?: $s->project_delivery_date ?: $s->end_date;

            $computedStatus = 'pending';
            if ($totalCommitted > 0 && $totalCompleted >= $totalCommitted) {
                $computedStatus = 'completed';
            } elseif ($deliveryDate && Carbon::parse($deliveryDate)->toDateString() < $today) {
                $computedStatus = 'overdue';
            }

            $accountName = trim(($s->company_name ?? '') ?: ($s->contact_name ?? ''));

            return [
                'smm_sheet_id'             => (int) $s->smm_sheet_id,
                'pi_id'                    => $s->pi_id ? (int) $s->pi_id : null,
                'lead_id'                  => (int) $s->lead_id,
                'account_name'             => $accountName ?: 'N/A',
                'product_name'             => $s->product_name ?? '-',
                'start_date'               => $s->start_date ? Carbon::parse($s->start_date)->toDateString() : null,
                'end_date'                 => $s->end_date ? Carbon::parse($s->end_date)->toDateString() : null,
                'delivery_date'            => $deliveryDate ? Carbon::parse($deliveryDate)->toDateString() : null,
                'committed_posters'        => $committedPosters,
                'committed_videos'         => $committedVideos,
                'completed_posters'        => $completedPosters,
                'pending_posters'          => $pendingPosters,
                'completed_videos'         => $completedVideos,
                'pending_videos'           => $pendingVideos,
                'design_completed_posters' => $designCompletedPosters,
                'design_pending_posters'   => $designPendingPosters,
                'design_completed_videos'  => $designCompletedVideos,
                'design_pending_videos'    => $designPendingVideos,
                'design_persons'           => $designPersons,
                'dm_completed_posters'     => $dmCompletedPosters,
                'dm_pending_posters'       => $dmPendingPosters,
                'dm_completed_videos'      => $dmCompletedVideos,
                'dm_pending_videos'        => $dmPendingVideos,
                'dm_persons'               => $dmPersons,
                'status'                   => $computedStatus,
                'department_name'          => $s->department_name ?? '-',
            ];
        });

        // Compute global KPI stats before status filtering
        $stats = [
            'total'     => $allRows->count(),
            'completed' => $allRows->where('status', 'completed')->count(),
            'pending'   => $allRows->where('status', 'pending')->count(),
            'overdue'   => $allRows->where('status', 'overdue')->count(),
        ];

        // Apply status filter if provided
        $filteredRows = $status
            ? $allRows->where('status', $status)->values()
            : $allRows->values();

        // Active accounts for entry modal dropdown
        $activeAccounts = SmmSheet::with(['lead', 'product'])
            ->whereHas('product', function ($q) {
                $q->where('count_wise_report', true)
                  ->where('is_this_renewal_product', true);
            })
            ->whereNull('deleted_at')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get()
            ->map(function ($s) {
                $accountName = trim(($s->lead?->company_name ?? '') ?: ($s->lead?->contact_name ?? 'N/A'));
                return [
                    'id'                       => (int) $s->id,
                    'smm_sheet_id'             => (int) $s->id,
                    'account_name'             => $accountName,
                    'product_name'             => $s->product?->package_name ?? 'N/A',
                    'start_date'               => $s->start_date ? Carbon::parse($s->start_date)->toDateString() : null,
                    'end_date'                 => $s->end_date ? Carbon::parse($s->end_date)->toDateString() : null,
                    'committed_posters'        => (int) $s->committed_posters,
                    'committed_videos'         => (int) $s->committed_videos,
                    'design_completed_posters' => (int) $s->design_completed_posters,
                    'design_pending_posters'   => $s->design_pending_posters,
                    'design_completed_videos'  => (int) $s->design_completed_videos,
                    'design_pending_videos'    => $s->design_pending_videos,
                    'dm_completed_posters'     => (int) $s->dm_completed_posters,
                    'dm_pending_posters'       => $s->dm_pending_posters,
                    'dm_completed_videos'      => (int) $s->dm_completed_videos,
                    'dm_pending_videos'        => $s->dm_pending_videos,
                ];
            });

        // Server-side pagination
        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(100, max(1, (int) $request->input('per_page', 20)));
        $totalItems = $filteredRows->count();
        $slice = $filteredRows->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'success' => true,
            'data'    => $slice,
            'stats'   => $stats,
            'leads'   => $leads,
            'active_accounts' => $activeAccounts,
            'user_context' => [
                'is_design_user' => $isDesignUser,
                'is_dm_user'     => $isDmUser,
                'is_admin_like'  => $isAdminLike,
                'team_view'      => $teamView,
                'default_team'   => ($isDmUser && ! $isDesignUser) ? 'dm' : 'design',
            ],
            'pagination' => [
                'current_page' => $page,
                'last_page'    => max(1, (int) ceil($totalItems / $perPage)),
                'per_page'     => $perPage,
                'total'        => $totalItems,
                'has_more'     => ($page * $perPage) < $totalItems,
            ],
        ]);
    }

    /**
     * POST /mobile/projects/smm-sheet/entry
     * Records completed deliverables and logs activity history.
     */
    public function storeEntry(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->authorizeSmmAccess($user);

        $validated = $request->validate([
            'smm_sheet_id' => 'required|exists:smm_sheets,id',
            'team'         => 'nullable|in:design,dm',
            'start_date'   => 'nullable|date',
            'end_date'     => 'nullable|date',
            'done_posters' => 'required|integer|min:0',
            'done_videos'  => 'required|integer|min:0',
            'remarks'      => 'nullable|string|max:1000',
        ]);

        $isDesignUser = (bool) $user->belongsToDesigningDepartment();
        $isDmUser     = (bool) $user->belongsToDigitalMarketingDepartment();

        $team = $validated['team'] ?? null;
        if (! $team) {
            if ($isDmUser && ! $isDesignUser) {
                $team = 'dm';
            } else {
                $team = 'design';
            }
        }

        $postersAdded = (int) $validated['done_posters'];
        $videosAdded  = (int) $validated['done_videos'];

        $smmSheet = SmmSheet::findOrFail($validated['smm_sheet_id']);

        if (! empty($validated['start_date'])) {
            $smmSheet->start_date = $validated['start_date'];
        }
        if (! empty($validated['end_date'])) {
            $smmSheet->end_date = $validated['end_date'];
        }

        $designPostersBefore = (int) $smmSheet->design_completed_posters;
        $designVideosBefore  = (int) $smmSheet->design_completed_videos;
        $dmPostersBefore     = (int) $smmSheet->dm_completed_posters;
        $dmVideosBefore      = (int) $smmSheet->dm_completed_videos;

        if ($team === 'design') {
            $smmSheet->design_completed_posters += $postersAdded;
            $smmSheet->design_completed_videos  += $videosAdded;
        } else {
            $smmSheet->dm_completed_posters += $postersAdded;
            $smmSheet->dm_completed_videos  += $videosAdded;
        }

        $smmSheet->recalculateStatus();
        $smmSheet->save();

        // Create log record
        $log = SmmSheetLog::create([
            'smm_sheet_id'          => $smmSheet->id,
            'user_id'               => $user->id,
            'department'            => $team,
            'action'                => 'added_counts',
            'posters_added'         => $postersAdded,
            'videos_added'          => $videosAdded,
            'design_posters_before' => $designPostersBefore,
            'design_posters_after'  => $smmSheet->design_completed_posters,
            'design_videos_before'  => $designVideosBefore,
            'design_videos_after'   => $smmSheet->design_completed_videos,
            'dm_posters_before'     => $dmPostersBefore,
            'dm_posters_after'      => $smmSheet->dm_completed_posters,
            'dm_videos_before'      => $dmVideosBefore,
            'dm_videos_after'       => $smmSheet->dm_completed_videos,
            'remarks'               => $validated['remarks'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'SMM Done deliverables updated successfully!',
            'data'    => [
                'smm_sheet_id'             => (int) $smmSheet->id,
                'design_completed_posters' => (int) $smmSheet->design_completed_posters,
                'design_pending_posters'   => $smmSheet->design_pending_posters,
                'design_completed_videos'  => (int) $smmSheet->design_completed_videos,
                'design_pending_videos'    => $smmSheet->design_pending_videos,
                'dm_completed_posters'     => (int) $smmSheet->dm_completed_posters,
                'dm_pending_posters'       => $smmSheet->dm_pending_posters,
                'dm_completed_videos'      => (int) $smmSheet->dm_completed_videos,
                'dm_pending_videos'        => $smmSheet->dm_pending_videos,
                'completed_posters'        => (int) ($smmSheet->design_completed_posters + $smmSheet->dm_completed_posters),
                'completed_videos'         => (int) ($smmSheet->design_completed_videos + $smmSheet->dm_completed_videos),
                'pending_posters'          => $smmSheet->pending_posters,
                'pending_videos'           => $smmSheet->pending_videos,
                'status'                   => $smmSheet->status,
            ],
        ]);
    }

    /**
     * GET /mobile/projects/smm-sheet/{id}/history
     * Returns activity timeline and audit log for a specific SMM Sheet record.
     */
    public function history(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $this->authorizeSmmAccess($user);

        $smmSheet = SmmSheet::with(['lead', 'product', 'logs.user'])->findOrFail($id);
        $accountName = trim(($smmSheet->lead?->company_name ?? '') ?: ($smmSheet->lead?->contact_name ?? 'N/A'));

        $logs = $smmSheet->logs->map(function ($log) {
            $user = $log->user;
            $deptLabel = match ($log->department) {
                'design' => '🎨 Design Team',
                'dm'     => '📢 Digital Marketing',
                default  => '⚙ System / Admin',
            };

            return [
                'id'                    => (int) $log->id,
                'user_name'             => $user?->name ?: 'System',
                'user_initial'          => strtoupper(substr($user?->name ?: 'S', 0, 1)),
                'department'            => $log->department,
                'department_label'      => $deptLabel,
                'action'                => $log->action,
                'posters_added'         => (int) $log->posters_added,
                'videos_added'          => (int) $log->videos_added,
                'design_posters_before' => (int) $log->design_posters_before,
                'design_posters_after'  => (int) $log->design_posters_after,
                'design_videos_before'  => (int) $log->design_videos_before,
                'design_videos_after'   => (int) $log->design_videos_after,
                'dm_posters_before'     => (int) $log->dm_posters_before,
                'dm_posters_after'      => (int) $log->dm_posters_after,
                'dm_videos_before'      => (int) $log->dm_videos_before,
                'dm_videos_after'       => (int) $log->dm_videos_after,
                'remarks'               => $log->remarks ?: null,
                'created_at_human'      => $log->created_at ? $log->created_at->diffForHumans() : '-',
                'created_at_formatted'  => $log->created_at ? $log->created_at->format('d M Y, h:i A') : '-',
            ];
        });

        return response()->json([
            'success'   => true,
            'smm_sheet' => [
                'id'                       => (int) $smmSheet->id,
                'account_name'             => $accountName,
                'product_name'             => $smmSheet->product?->package_name ?? 'N/A',
                'committed_posters'        => (int) $smmSheet->committed_posters,
                'committed_videos'         => (int) $smmSheet->committed_videos,
                'design_completed_posters' => (int) $smmSheet->design_completed_posters,
                'design_pending_posters'   => $smmSheet->design_pending_posters,
                'design_completed_videos'  => (int) $smmSheet->design_completed_videos,
                'design_pending_videos'    => $smmSheet->design_pending_videos,
                'dm_completed_posters'     => (int) $smmSheet->dm_completed_posters,
                'dm_pending_posters'       => $smmSheet->dm_pending_posters,
                'dm_completed_videos'      => (int) $smmSheet->dm_completed_videos,
                'dm_pending_videos'        => $smmSheet->dm_pending_videos,
                'status'                   => $smmSheet->status,
                'start_date'               => $smmSheet->start_date ? $smmSheet->start_date->format('d M Y') : null,
                'end_date'                 => $smmSheet->end_date ? $smmSheet->end_date->format('d M Y') : null,
            ],
            'logs'      => $logs,
        ]);
    }

    /**
     * GET /mobile/projects/smm-sheet/active-accounts
     * Returns list of active accounts for the Add SMM Entry modal.
     */
    public function activeAccounts(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->authorizeSmmAccess($user);
        $companyId = $this->visibility->companyIdFor($user);

        $sheets = SmmSheet::with(['lead', 'product'])
            ->whereHas('product', function ($q) {
                $q->where('count_wise_report', true)
                  ->where('is_this_renewal_product', true);
            })
            ->whereNull('deleted_at')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get()
            ->map(function ($s) {
                $accountName = trim(($s->lead?->company_name ?? '') ?: ($s->lead?->contact_name ?? 'N/A'));
                return [
                    'id'                       => (int) $s->id,
                    'smm_sheet_id'             => (int) $s->id,
                    'account_name'             => $accountName,
                    'product_name'             => $s->product?->package_name ?? 'N/A',
                    'start_date'               => $s->start_date ? Carbon::parse($s->start_date)->toDateString() : null,
                    'end_date'                 => $s->end_date ? Carbon::parse($s->end_date)->toDateString() : null,
                    'committed_posters'        => (int) $s->committed_posters,
                    'committed_videos'         => (int) $s->committed_videos,
                    'design_completed_posters' => (int) $s->design_completed_posters,
                    'design_pending_posters'   => $s->design_pending_posters,
                    'design_completed_videos'  => (int) $s->design_completed_videos,
                    'design_pending_videos'    => $s->design_pending_videos,
                    'dm_completed_posters'     => (int) $s->dm_completed_posters,
                    'dm_pending_posters'       => $s->dm_pending_posters,
                    'dm_completed_videos'      => (int) $s->dm_completed_videos,
                    'dm_pending_videos'        => $s->dm_pending_videos,
                ];
            });

        return response()->json([
            'success'  => true,
            'accounts' => $sheets,
        ]);
    }
}
