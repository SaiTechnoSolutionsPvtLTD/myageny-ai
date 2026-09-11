<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Lead;
use App\Models\Product;
use App\Models\ProductionInitiation;
use App\Models\SmmSheet;
use App\Models\SmmSheetLog;
use App\Services\DataVisibilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SmmSheetController extends Controller
{
    public function __construct(private readonly DataVisibilityService $visibility) {}

    public function index(Request $request): View
    {
        $user = auth()->user();
        $companyId = $this->visibility->companyIdFor($user);

        // Ensure existing count-wise renewal production initiations are synced into smm_sheets
        $this->ensureInitiationsSynced($companyId);

        // Department role detection
        $isDesignUser = (bool) $user?->belongsToDesigningDepartment();
        $isDmUser     = (bool) $user?->belongsToDigitalMarketingDepartment();
        $isAdminLike  = (bool) $user?->hasAdminLikeRole() || (bool) $user?->canViewProjectsDashboardSwitcher();

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

        // Fetch only allocated accounts with renewal count-wise products
        $allocatedLeadIdsQuery = DB::table('smm_sheets as s')
            ->join('products as p', 'p.id', '=', 's.product_id')
            ->leftJoin('production_initiations as pi', 'pi.id', '=', 's.production_initiation_id')
            ->leftJoin('departments as d', 'd.id', '=', 's.department_id')
            ->where('p.count_wise_report', true)
            ->where('p.is_this_renewal_product', true)
            ->whereNull('s.deleted_at')
            ->when($companyId, fn($q) => $q->where('s.company_id', $companyId));

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
            ->get(['id', 'company_name', 'contact_name']);

        $rows = $this->buildSmmSheetData($request, $companyId);

        $filters = [
            'date_from'  => is_array($request->input('date_from')) ? '' : (string) $request->input('date_from', ''),
            'date_to'    => is_array($request->input('date_to')) ? '' : (string) $request->input('date_to', ''),
            'lead_id'    => is_array($request->input('lead_id')) ? (reset($request->input('lead_id')) ?: '') : (string) ($request->input('lead_id') ?? ''),
            'status'     => is_array($request->input('status')) ? (reset($request->input('status')) ?: '') : (string) ($request->input('status') ?? ''),
            'team_view'  => $teamView,
        ];

        $allActiveAccounts = SmmSheet::with(['lead', 'product'])
            ->whereHas('product', function ($q) {
                $q->where('count_wise_report', true)
                  ->where('is_this_renewal_product', true);
            })
            ->whereNull('deleted_at')
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->get()
            ->map(function ($s) {
                $accountName = trim(($s->lead?->company_name ?? '') ?: ($s->lead?->contact_name ?? 'N/A'));
                return [
                    'smm_sheet_id'             => $s->id,
                    'account_name'             => $accountName,
                    'product_name'             => $s->product?->package_name ?? 'N/A',
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

        return view('pages.projects.smm-sheet.index', compact(
            'rows',
            'filters',
            'leads',
            'allActiveAccounts',
            'teamView',
            'isDesignUser',
            'isDmUser',
            'isAdminLike'
        ));
    }

    public function export(Request $request): Response
    {
        $user = auth()->user();
        $companyId = $this->visibility->companyIdFor($user);

        // Ensure sync
        $this->ensureInitiationsSynced($companyId);

        // Department role detection
        $isDesignUser = (bool) $user?->belongsToDesigningDepartment();
        $isDmUser     = (bool) $user?->belongsToDigitalMarketingDepartment();
        $isAdminLike  = (bool) $user?->hasAdminLikeRole() || (bool) $user?->canViewProjectsDashboardSwitcher();

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

        $rows = $this->buildSmmSheetData($request, $companyId);

        $filters = [
            'date_from' => $request->input('date_from', now()->startOfMonth()->toDateString()),
            'date_to'   => $request->input('date_to', now()->endOfMonth()->toDateString()),
            'team_view' => $teamView,
        ];

        $html = view('pages.projects.smm-sheet.export', compact('rows', 'filters', 'teamView'))->render();
        $fileName = 'smm_sheet_' . now()->format('Y_m_d_His') . '.xls';

        return response($html, 200, [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    /**
     * Store Done Counts entry and record log history
     */
    public function storeEntry(Request $request)
    {
        $validated = $request->validate([
            'smm_sheet_id'   => 'required|exists:smm_sheets,id',
            'team'           => 'nullable|in:design,dm',
            'start_date'     => 'nullable|date',
            'end_date'       => 'nullable|date',
            'done_posters'   => 'required|integer|min:0',
            'done_videos'    => 'required|integer|min:0',
            'remarks'        => 'nullable|string|max:1000',
        ]);

        $user = auth()->user();
        $isDesignUser = (bool) $user?->belongsToDesigningDepartment();
        $isDmUser     = (bool) $user?->belongsToDigitalMarketingDepartment();

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
            'user_id'               => $user?->id,
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

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'SMM Done Counts updated successfully!',
                'data'    => [
                    'smm_sheet_id'             => $smmSheet->id,
                    'design_completed_posters' => $smmSheet->design_completed_posters,
                    'design_pending_posters'   => $smmSheet->design_pending_posters,
                    'design_completed_videos'  => $smmSheet->design_completed_videos,
                    'design_pending_videos'    => $smmSheet->design_pending_videos,
                    'dm_completed_posters'     => $smmSheet->dm_completed_posters,
                    'dm_pending_posters'       => $smmSheet->dm_pending_posters,
                    'dm_completed_videos'      => $smmSheet->dm_completed_videos,
                    'dm_pending_videos'        => $smmSheet->dm_pending_videos,
                    'status'                   => $smmSheet->status,
                ],
            ]);
        }

        return redirect()->back()->with('success', 'SMM Done Counts updated successfully!');
    }

    /**
     * Get history logs for a specific SMM Sheet record
     */
    public function history(int $id): JsonResponse
    {
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
                'id'                    => $log->id,
                'user_name'             => $user?->name ?: 'System',
                'user_initial'          => strtoupper(substr($user?->name ?: 'S', 0, 1)),
                'department'            => $log->department,
                'department_label'      => $deptLabel,
                'action'                => $log->action,
                'posters_added'         => $log->posters_added,
                'videos_added'          => $log->videos_added,
                'design_posters_before' => $log->design_posters_before,
                'design_posters_after'  => $log->design_posters_after,
                'design_videos_before'  => $log->design_videos_before,
                'design_videos_after'   => $log->design_videos_after,
                'dm_posters_before'     => $log->dm_posters_before,
                'dm_posters_after'      => $log->dm_posters_after,
                'dm_videos_before'      => $log->dm_videos_before,
                'dm_videos_after'       => $log->dm_videos_after,
                'remarks'               => $log->remarks ?: null,
                'created_at_human'      => $log->created_at ? $log->created_at->diffForHumans() : '-',
                'created_at_formatted'  => $log->created_at ? $log->created_at->format('d M Y, h:i A') : '-',
            ];
        });

        return response()->json([
            'success' => true,
            'smm_sheet' => [
                'id'                       => $smmSheet->id,
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
            'logs' => $logs,
        ]);
    }

    /**
     * Active accounts list for modal dropdown
     */
    public function activeAccounts(Request $request): JsonResponse
    {
        $user = auth()->user();
        $companyId = $this->visibility->companyIdFor($user);

        $sheets = SmmSheet::with(['lead', 'product'])
            ->whereHas('product', function ($q) {
                $q->where('count_wise_report', true)
                  ->where('is_this_renewal_product', true);
            })
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->get()
            ->map(function ($s) {
                $accountName = trim(($s->lead?->company_name ?? '') ?: ($s->lead?->contact_name ?? 'N/A'));
                return [
                    'id'                       => $s->id,
                    'account_name'             => $accountName,
                    'product_name'             => $s->product?->package_name ?? 'N/A',
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
            'success' => true,
            'accounts' => $sheets,
        ]);
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
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->whereDoesntHave('smmSheet')
                ->get();

            foreach ($initiations as $pi) {
                SmmSheet::syncFromInitiation($pi);
            }
        } catch (\Throwable $e) {
            \Log::warning('ensureInitiationsSynced warning: ' . $e->getMessage());
        }
    }

    private function buildSmmSheetData(Request $request, ?int $companyId): \Illuminate\Support\Collection
    {
        $user      = auth()->user();
        $isDesignUser = (bool) $user?->belongsToDesigningDepartment();
        $isDmUser     = (bool) $user?->belongsToDigitalMarketingDepartment();
        $isAdminLike  = (bool) $user?->hasAdminLikeRole() || (bool) $user?->canViewProjectsDashboardSwitcher();

        $dateFrom  = $request->input('date_from', '');
        $dateTo    = $request->input('date_to', '');
        $leadId    = $request->input('lead_id');
        $status    = $request->input('status');

        // Fetch all users with their departments to map allocations
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

        // Query smm_sheets joining leads, products, and production_initiations
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
            ->when($companyId, fn($q) => $q->where('s.company_id', $companyId))
            ->where('p.count_wise_report', true)
            ->where('p.is_this_renewal_product', true)
            ->when($leadId, fn($q) => $q->where('s.lead_id', $leadId))
            ->orderBy('l.company_name')
            ->orderBy('s.id');

        // Scope non-admin users to their allocated items / department
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

        // Date filter against delivery date / end date
        if ($dateFrom) {
            $query->where(function ($q) use ($dateFrom, $dateTo) {
                $q->where(function ($sq) use ($dateFrom, $dateTo) {
                    $sq->whereNotNull('s.delivery_date')
                       ->whereBetween('s.delivery_date', [$dateFrom, $dateTo]);
                })->orWhere(function ($sq) use ($dateFrom, $dateTo) {
                    $sq->whereNull('s.delivery_date')
                       ->whereBetween('s.end_date', [$dateFrom, $dateTo]);
                });
            });
        }

        $sheets = $query->get();
        $piIds = $sheets->pluck('pi_id')->filter()->unique()->values()->all();

        // Timesheet completions fallback or additional contributors mapping
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

        $rows = $sheets->map(function ($s) use ($timesheetSums, $today, $status, $usersWithDept) {
            $committedPosters = (int) $s->committed_posters;
            $committedVideos  = (int) $s->committed_videos;

            $designCompletedPosters = (int) $s->design_completed_posters;
            $designCompletedVideos  = (int) $s->design_completed_videos;
            $dmCompletedPosters     = (int) $s->dm_completed_posters;
            $dmCompletedVideos      = (int) $s->dm_completed_videos;

            // Pending calculations: Pending = Committed - Done (clamped to 0)
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

            $allocatedUserIds = array_unique(array_filter(array_merge($allocatedEmployeeIds, $allocatedTlIds), fn($u) => is_numeric($u)));

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

            // Fallback to timesheet persons if any
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

            if ($status && $computedStatus !== $status) {
                return null;
            }

            $accountName = trim(($s->company_name ?? '') ?: ($s->contact_name ?? ''));

            return [
                'smm_sheet_id'             => $s->smm_sheet_id,
                'pi_id'                    => $s->pi_id,
                'lead_id'                  => $s->lead_id,
                'account_name'             => $accountName ?: 'N/A',
                'product_name'             => $s->product_name ?? '-',
                'start_date'               => $s->start_date,
                'end_date'                 => $s->end_date,
                'delivery_date'            => $s->delivery_date ?: $s->project_delivery_date,
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
        })->filter()->values();

        return $rows;
    }
}
