<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of the activity logs.
     */
    public function index(Request $request): View
    {
        $query = ActivityLog::query()->with(['user:id,name,email', 'lead:id,company_name,contact_name']);

        // 1. User filter
        if ($request->filled('user_id')) {
            $query->forUser($request->user_id);
        }

        // 2. Lead filter
        if ($request->filled('lead_id')) {
            $query->forLead($request->lead_id);
        }

        // 3. Module filter
        if ($request->filled('module')) {
            $query->forModule($request->module);
        }

        // 4. Action filter
        if ($request->filled('action')) {
            $query->forAction($request->action);
        }

        // 5. Date range filter
        if ($request->filled('start_date') || $request->filled('end_date')) {
            $query->dateBetween($request->start_date, $request->end_date);
        }

        // 6. Search keyword
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Summary metrics
        $today = now()->toDateString();
        $totalActivities = (clone $query)->count();
        $activeUsersCount = ActivityLog::whereDate('created_at', $today)->whereNotNull('user_id')->distinct('user_id')->count('user_id');
        $leadActivitiesCount = ActivityLog::whereNotNull('lead_id')->count();
        $todayActionsCount = ActivityLog::whereDate('created_at', $today)->count();

        $viewMode = $request->query('view_mode', 'all');

        $logs = $query->latest()->paginate(30)->withQueryString();

        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        $modules = ActivityLog::select('module')->distinct()->pluck('module')->filter()->values();
        $actions = ActivityLog::select('action')->distinct()->pluck('action')->filter()->values();

        return view('pages.settings.activity_logs.index', compact(
            'logs',
            'users',
            'modules',
            'actions',
            'totalActivities',
            'activeUsersCount',
            'leadActivitiesCount',
            'todayActionsCount',
            'viewMode'
        ));
    }

    /**
     * Get single activity log details (JSON).
     */
    public function show(ActivityLog $activityLog): JsonResponse
    {
        $activityLog->load(['user:id,name,email', 'lead:id,company_name,contact_name']);

        return response()->json([
            'status' => true,
            'log'    => [
                'id'          => $activityLog->id,
                'user_name'   => $activityLog->user_name ?: ($activityLog->user?->name ?: 'System'),
                'user_email'  => $activityLog->user_email ?: ($activityLog->user?->email ?: 'N/A'),
                'lead_id'     => $activityLog->lead_id,
                'lead_title'  => $activityLog->lead_title ?: ($activityLog->lead?->company_name ?: ($activityLog->lead?->contact_name ?: null)),
                'module'      => $activityLog->module,
                'action'      => $activityLog->action,
                'description' => $activityLog->description,
                'url'         => $activityLog->url,
                'method'      => $activityLog->method,
                'ip_address'  => $activityLog->ip_address,
                'user_agent'  => $activityLog->user_agent,
                'properties'  => $activityLog->properties,
                'created_at'  => $activityLog->created_at->format('d M Y, h:i:s A'),
                'time_ago'    => $activityLog->created_at->diffForHumans(),
            ],
        ]);
    }

    /**
     * Export activity logs to CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = ActivityLog::query()->with(['user:id,name,email', 'lead:id,company_name,contact_name']);

        if ($request->filled('user_id')) {
            $query->forUser($request->user_id);
        }
        if ($request->filled('lead_id')) {
            $query->forLead($request->lead_id);
        }
        if ($request->filled('module')) {
            $query->forModule($request->module);
        }
        if ($request->filled('action')) {
            $query->forAction($request->action);
        }
        if ($request->filled('start_date') || $request->filled('end_date')) {
            $query->dateBetween($request->start_date, $request->end_date);
        }
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $filename = 'activity_logs_' . date('Y_m_d_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Log ID',
                'Date & Time',
                'User Name',
                'User Email',
                'Lead ID',
                'Lead / Company',
                'Module',
                'Action',
                'Description',
                'IP Address',
                'HTTP Method',
                'URL',
            ]);

            $query->latest()->chunk(200, function ($logs) use ($handle) {
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $log->id,
                        $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '',
                        $log->user_name ?: ($log->user?->name ?: 'System'),
                        $log->user_email ?: ($log->user?->email ?: ''),
                        $log->lead_id ? 'LD-' . $log->lead_id : '',
                        $log->lead_title ?: ($log->lead?->company_name ?: ($log->lead?->contact_name ?: '')),
                        $log->module,
                        $log->action,
                        $log->description,
                        $log->ip_address,
                        $log->method,
                        $log->url,
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
