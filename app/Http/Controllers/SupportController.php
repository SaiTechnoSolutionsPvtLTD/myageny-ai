<?php

namespace App\Http\Controllers;

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

        // Get received tickets (where current user is the target)
        $receivedTickets = SupportTicket::with(['creator', 'assignedTo'])
            ->where('to_user_id', $userId)
            ->latest()
            ->get();

        // Get created tickets (where current user is the creator)
        $createdTickets = SupportTicket::with(['creator', 'assignedTo'])
            ->where('created_by', $userId)
            ->latest()
            ->get();

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

        return view('pages.support.index', compact('receivedTickets', 'createdTickets', 'users'));
    }

    /**
     * Store a newly created support ticket in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'to_user_id' => ['required', 'exists:users,id'],
            'message' => ['required', 'string'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,zip,txt', 'max:10240'],
        ]);

        $user = Auth::user();
        $toUser = User::find($request->to_user_id);

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

        if (!$companyId && $toUser) {
            $companyId = \App\Models\EmployeeOnboarding::withoutGlobalScopes()
                ->where(function ($q) use ($toUser) {
                    $q->where('portal_user_id', $toUser->id)
                      ->orWhere('email', $toUser->email);
                })
                ->whereNotNull('company_id')
                ->value('company_id');
        }

        if (!$companyId) {
            $companyId = \App\Models\Company::orderBy('id')->value('id');
        }

        // Auto-update creator user's company_id if it was null
        if ($user && !$user->company_id && $companyId) {
            $user->update(['company_id' => $companyId]);
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = time() . '_' . \Illuminate\Support\Str::random(8) . '.' . $file->getClientOriginalExtension();
            $targetDir = public_path('uploads/support-tickets');
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            $file->move($targetDir, $filename);
            $attachmentPath = 'uploads/support-tickets/' . $filename;
        }

        $ticket = SupportTicket::create([
            'company_id' => $companyId,
            'subject' => $request->subject,
            'message' => $request->message,
            'attachment_path' => $attachmentPath,
            'created_by' => $user->id,
            'to_user_id' => $request->to_user_id,
            'status' => 'pending',
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
        if ($ticket->to_user_id !== Auth::id()) {
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

        // Send email to the ticket creator
        try {
            $creator = $ticket->creator;
            $updater = Auth::user();

            if ($creator && !empty($creator->email) && filter_var($creator->email, FILTER_VALIDATE_EMAIL)) {
                Mail::send('emails.support_ticket_updated', [
                    'ticket' => $ticket,
                    'creator' => $creator,
                    'updater' => $updater,
                ], function ($message) use ($ticket, $creator) {
                    $message->to($creator->email, $creator->name)
                        ->subject('Support Ticket Updated: ' . $ticket->subject);
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
