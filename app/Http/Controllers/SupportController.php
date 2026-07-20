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
        $userId = Auth::id();

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

        // Get users for Select2 dropdown (exclude current user)
        $users = User::where('id', '!=', $userId)
            ->where('is_active', true)
            ->orderBy('name')
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

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('support-tickets', 'public');
        }

        $ticket = SupportTicket::create([
            'company_id' => Auth::user()->company_id,
            'subject' => $request->subject,
            'message' => $request->message,
            'attachment_path' => $attachmentPath,
            'created_by' => Auth::id(),
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

                    if ($ticket->attachment_path && Storage::disk('public')->exists($ticket->attachment_path)) {
                        $message->attach(storage_path('app/public/' . $ticket->attachment_path));
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

        $request->validate([
            'status' => ['required', 'string', 'in:pending,resolved,closed'],
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
