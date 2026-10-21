<?php

namespace App\Http\Controllers;

use App\Models\AccountSupportCategory;
use App\Models\AccountSupportSubcategory;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SupportController extends Controller
{
    /**
     * Display a listing of support tickets.
     */
    public function index()
    {
        $user = Auth::user();
        $userId = $user->id;
        $companyId = $user->company_id;

        if (!$companyId) {
            $companyId = \App\Models\EmployeeOnboarding::withoutGlobalScopes()
                ->where(function ($q) use ($user) {
                    $q->where('portal_user_id', $user->id)
                      ->orWhere('email', $user->email);
                })
                ->whereNotNull('company_id')
                ->value('company_id');

            if ($companyId) {
                $user->update(['company_id' => $companyId]);
            }
        }

        // Determine if user is Company Admin or Super Admin
        $isCompanyAdmin = $user->isSuperAdmin()
            || $user->isCompanyAdmin()
            || $user->hasRole('Company Admin')
            || $user->roles->contains(function ($role) {
                $name = strtolower($role->name);
                $display = strtolower($role->display_name ?? '');
                return str_contains($name, 'company_admin')
                    || str_contains($display, 'company admin')
                    || str_contains($name, 'super_admin')
                    || str_contains($display, 'super admin');
            });

        // Calculate total KPI statistics from database for card metrics
        if ($isCompanyAdmin) {
            $kpiQuery = SupportTicket::query();
            if ($companyId) {
                $kpiQuery->where(function ($q) use ($companyId) {
                    $q->where('company_id', $companyId)
                      ->orWhereHas('creator', fn($cq) => $cq->where('company_id', $companyId))
                      ->orWhereHas('assignedTo', fn($aq) => $aq->where('company_id', $companyId));
                });
            }
        } else {
            $kpiQuery = SupportTicket::where(function ($q) use ($userId) {
                $q->where('to_user_id', $userId)
                  ->orWhere('created_by', $userId);
            });
        }

        $statTotal     = (clone $kpiQuery)->count();
        $statPending   = (clone $kpiQuery)->where('status', 'pending')->count();
        $statOnProcess = (clone $kpiQuery)->where('status', 'onprocess')->count();
        $statResolved  = (clone $kpiQuery)->whereIn('status', ['resolved', 'closed'])->count();

        // If Company Admin or Super Admin, fetch all tickets in the company scope (Paginated)
        if ($isCompanyAdmin) {
            $allTicketsQuery = SupportTicket::with(['creator', 'assignedTo', 'category', 'subcategory']);
            if ($companyId) {
                $allTicketsQuery->where(function ($q) use ($companyId) {
                    $q->where('company_id', $companyId)
                      ->orWhereHas('creator', fn($cq) => $cq->where('company_id', $companyId))
                      ->orWhereHas('assignedTo', fn($aq) => $aq->where('company_id', $companyId));
                });
            }
            $allTickets = $allTicketsQuery->latest()->paginate(15, ['*'], 'all_page')->withQueryString();
        } else {
            $allTickets = collect();
        }

        // Get received tickets (where current user is the target) (Paginated)
        $receivedTickets = SupportTicket::with(['creator', 'assignedTo', 'category', 'subcategory'])
            ->where('to_user_id', $userId)
            ->latest()
            ->paginate(15, ['*'], 'received_page')
            ->withQueryString();

        // Get created tickets (where current user is the creator) (Paginated)
        $createdTickets = SupportTicket::with(['creator', 'assignedTo', 'category', 'subcategory'])
            ->where('created_by', $userId)
            ->latest()
            ->paginate(15, ['*'], 'created_page')
            ->withQueryString();

        // Get users for Select2 dropdown (exclude current user, include all active users for the company)
        $usersQuery = User::withoutGlobalScope('branch')
            ->where('id', '!=', $userId)
            ->where(function ($query) {
                $query->where('is_active', true)
                    ->orWhere('user_status', 'active');
            });

        if ($companyId) {
            $usersQuery->where('company_id', $companyId);
        }

        $users = $usersQuery->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('pages.support.index', compact(
            'receivedTickets',
            'createdTickets',
            'allTickets',
            'isCompanyAdmin',
            'users',
            'statTotal',
            'statPending',
            'statOnProcess',
            'statResolved'
        ));
    }

    /**
     * Show the dedicated form for creating a new support ticket.
     */
    public function create()
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        if (!$companyId) {
            $companyId = \App\Models\EmployeeOnboarding::withoutGlobalScopes()
                ->where(function ($q) use ($user) {
                    $q->where('portal_user_id', $user->id)
                      ->orWhere('email', $user->email);
                })
                ->whereNotNull('company_id')
                ->value('company_id');

            if ($companyId) {
                $user->update(['company_id' => $companyId]);
            }
        }

        // Fetch active Categories with active subcategories
        $categories = AccountSupportCategory::when($companyId, function ($q) use ($companyId) {
                $q->where(function ($sq) use ($companyId) {
                    $sq->where('company_id', $companyId)->orWhereNull('company_id');
                });
            })
            ->active()
            ->with(['activeSubcategories'])
            ->orderBy('name')
            ->get();

        // Get users for fallback or explicit assignee
        $usersQuery = User::withoutGlobalScope('branch')
            ->where('id', '!=', $user->id)
            ->where(function ($query) {
                $query->where('is_active', true)
                    ->orWhere('user_status', 'active');
            });

        if ($companyId) {
            $usersQuery->where('company_id', $companyId);
        }

        $users = $usersQuery->orderBy('name')->get(['id', 'name', 'email']);

        return view('pages.support.create', compact('categories', 'users'));
    }

    /**
     * Store a newly created support ticket in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'subject'                        => ['required', 'string', 'max:255'],
            'message'                        => ['required', 'string'],
            'account_support_category_id'    => ['nullable', 'integer'],
            'account_support_subcategory_id' => ['nullable', 'integer'],
            'to_user_id'                     => ['nullable', 'exists:users,id'],
            'attachment'                     => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,zip,txt', 'max:10240'],
            'attachments.*'                  => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,zip,txt', 'max:10240'],
        ]);

        $user = Auth::user();

        // Auto-resolve to_user_id if not explicitly provided
        $toUserId = $request->to_user_id;

        if (!$toUserId && $request->filled('account_support_subcategory_id')) {
            $subcat = AccountSupportSubcategory::find($request->account_support_subcategory_id);
            if ($subcat && is_array($subcat->hierarchy)) {
                foreach ($subcat->hierarchy as $lvl) {
                    if (!empty($lvl['user_ids']) && is_array($lvl['user_ids'])) {
                        foreach ($lvl['user_ids'] as $uid) {
                            if ($uid && User::where('id', $uid)->exists()) {
                                $toUserId = (int) $uid;
                                break 2;
                            }
                        }
                    } elseif (!empty($lvl['user_id'])) {
                        if (User::where('id', $lvl['user_id'])->exists()) {
                            $toUserId = (int) $lvl['user_id'];
                            break;
                        }
                    }
                }
            }
        }

        // If still not resolved, fallback to first available active user in company
        if (!$toUserId) {
            $toUserId = User::where('id', '!=', $user->id)
                ->when($user->company_id, fn($q) => $q->where('company_id', $user->company_id))
                ->where(function ($query) {
                    $query->where('is_active', true)->orWhere('user_status', 'active');
                })
                ->value('id');
        }

        if (!$toUserId) {
            $toUserId = User::where('id', '!=', $user->id)->value('id') ?? $user->id;
        }

        $toUser = User::find($toUserId);

        // Resolve company_id with fallbacks
        $companyId = $user?->company_id;

        if (!$companyId && $user) {
            $companyId = \App\Models\EmployeeOnboarding::withoutGlobalScopes()
                ->where(function ($q) use ($user) {
                    $q->where('portal_user_id', $user->id)
                      ->orWhere('email', $user->email);
                })
                ->whereNotNull('company_id')
                ->value('company_id');
        }

        if (!$companyId && $toUser) {
            $companyId = $toUser->company_id;
        }

        if (!$companyId) {
            $companyId = \App\Models\Company::orderBy('id')->value('id');
        }

        // Auto-update creator user's company_id if it was null
        if ($user && !$user->company_id && $companyId) {
            $user->update(['company_id' => $companyId]);
        }

        // Process file attachments (single or multiple)
        $attachmentPath = null;
        $attachmentList = [];
        $targetDir = public_path('uploads/support-tickets');
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = time() . '_' . \Illuminate\Support\Str::random(8) . '.' . $file->getClientOriginalExtension();
            $file->move($targetDir, $filename);
            $attachmentPath = 'uploads/support-tickets/' . $filename;
            $attachmentList[] = $attachmentPath;
        }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file && $file->isValid()) {
                    $filename = time() . '_' . \Illuminate\Support\Str::random(8) . '.' . $file->getClientOriginalExtension();
                    $file->move($targetDir, $filename);
                    $path = 'uploads/support-tickets/' . $filename;
                    $attachmentList[] = $path;
                    if (!$attachmentPath) {
                        $attachmentPath = $path;
                    }
                }
            }
        }

        $ticket = SupportTicket::create([
            'company_id'                     => $companyId,
            'account_support_category_id'    => $request->account_support_category_id,
            'account_support_subcategory_id' => $request->account_support_subcategory_id,
            'subject'                        => $request->subject,
            'message'                        => $request->message,
            'attachment_path'                => $attachmentPath,
            'attachments'                    => !empty($attachmentList) ? $attachmentList : null,
            'created_by'                     => $user->id,
            'to_user_id'                     => $toUserId,
            'status'                         => 'pending',
        ]);

        // Send email to the assigned user
        try {
            $toUser = User::findOrFail($request->to_user_id);
            $fromUser = Auth::user();

            if (!empty($toUser->email) && filter_var($toUser->email, FILTER_VALIDATE_EMAIL)) {
                Mail::send('emails.support_ticket', [
                    'ticket' => $ticket,
                    'toUser' => $toUser,
                    'fromUser' => $fromUser,
                ], function ($message) use ($ticket, $toUser) {
                    $message->to($toUser->email, $toUser->name)
                        ->subject('New Support Ticket: ' . $ticket->subject);

                    if ($ticket->attachment_path) {
                        $fullPath = public_path($ticket->attachment_path);
                        if (file_exists($fullPath)) {
                            $message->attach($fullPath);
                        } elseif (Storage::disk('public')->exists($ticket->attachment_path)) {
                            $message->attach(storage_path('app/public/' . $ticket->attachment_path));
                        }
                    }
                });
            }
        } catch (\Throwable $exception) {
            Log::error('Support Ticket email send failed.', [
                'ticket_id' => $ticket->id,
                'error' => $exception->getMessage(),
            ]);
            // We still proceed even if email fails, but let the user know.
            return redirect()
                ->route('support.index')
                ->with('success', 'Ticket created successfully, but email notification could not be sent.');
        }

        return redirect()
            ->route('support.index')
            ->with('success', 'Ticket created successfully.');
    }

    /**
     * Update the status and remark of the ticket.
     */
    public function updateStatus(Request $request, SupportTicket $ticket)
    {
        $user = Auth::user();

        $isCompanyAdmin = $user->isSuperAdmin()
            || $user->isCompanyAdmin()
            || $user->hasRole('Company Admin')
            || $user->roles->contains(function ($role) {
                $name = strtolower($role->name);
                $display = strtolower($role->display_name ?? '');
                return str_contains($name, 'company_admin')
                    || str_contains($display, 'company admin')
                    || str_contains($name, 'super_admin')
                    || str_contains($display, 'super admin');
            });

        if ($ticket->to_user_id !== $user->id && ! $isCompanyAdmin) {
            abort(403, 'Unauthorized action.');
        }

        if ($ticket->status === 'closed') {
            return redirect()
                ->route('support.index')
                ->with('error', 'This ticket has already been closed and cannot be updated.');
        }

        $request->validate([
            'status' => ['required', 'string', 'in:pending,onprocess,resolved,closed'],
            'remark' => ['nullable', 'string'],
        ]);

        $ticket->update([
            'status' => $request->status,
            'remark' => $request->remark,
        ]);

        // Send email to the ticket creator and optionally cc assigned user
        try {
            $creator = $ticket->creator;
            $updater = $user;

            if ($creator && !empty($creator->email) && filter_var($creator->email, FILTER_VALIDATE_EMAIL)) {
                Mail::send('emails.support_ticket_updated', [
                    'ticket' => $ticket,
                    'creator' => $creator,
                    'updater' => $updater,
                ], function ($message) use ($ticket, $creator, $updater) {
                    $message->to($creator->email, $creator->name)
                        ->subject('Support Ticket Updated: ' . $ticket->subject);

                    // If updater is not the assigned user (e.g. company admin updated it), also inform assigned user
                    if ($ticket->assignedTo && $ticket->assignedTo->id !== $updater->id && !empty($ticket->assignedTo->email) && filter_var($ticket->assignedTo->email, FILTER_VALIDATE_EMAIL)) {
                        $message->cc($ticket->assignedTo->email, $ticket->assignedTo->name);
                    }
                });
            }
        } catch (\Throwable $exception) {
            Log::error('Support Ticket update email send failed.', [
                'ticket_id' => $ticket->id,
                'error' => $exception->getMessage(),
            ]);
            // We still proceed even if email fails, but notify the user
            return redirect()
                ->route('support.index')
                ->with('success', 'Ticket status updated successfully, but email notification could not be sent.');
        }

        return redirect()
            ->route('support.index')
            ->with('success', 'Ticket status and remark updated successfully.');
    }
}
