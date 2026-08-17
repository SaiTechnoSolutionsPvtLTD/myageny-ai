@extends('layouts.app')

@section('title', 'Support - myAgenci.ai')

@push('styles')
<style>
.support-page {
    min-height: 100%;
    padding: 28px;
    background:
        radial-gradient(circle at top left, rgba(254, 95, 4, 0.08), transparent 35%),
        linear-gradient(180deg, #f8f6f2 0%, #f3f5f8 100%);
}
.support-hero {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 28px;
    padding: 28px;
    border: 1px solid #e6e8ee;
    border-radius: 22px;
    background: linear-gradient(135deg, #fff9f3 0%, #ffffff 55%, #f7f9fc 100%);
    box-shadow: 0 14px 40px rgba(15, 23, 42, 0.04);
}
.support-kicker {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 12px;
    border-radius: 999px;
    background: #fff1e8;
    color: #c2410c;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .7px;
    text-transform: uppercase;
    margin-bottom: 12px;
}
.support-title {
    margin: 0;
    font-size: 30px;
    font-weight: 800;
    line-height: 1.1;
    color: #111827;
}
.support-subtitle {
    margin: 10px 0 0;
    max-width: 640px;
    font-size: 14px;
    line-height: 1.7;
    color: #6b7280;
}
.btn-create-ticket {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, #fe5f04 0%, #ff8c3a 100%);
    color: #ffffff;
    font-weight: 700;
    font-size: 14px;
    padding: 12px 20px;
    border-radius: 14px;
    box-shadow: 0 10px 20px rgba(254, 95, 4, 0.2);
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
}
.btn-create-ticket:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px rgba(254, 95, 4, 0.25);
}

/* Tabs */
.tabs-container {
    display: flex;
    border-bottom: 2px solid #e5e7eb;
    margin-bottom: 24px;
    gap: 28px;
}
.tab-btn {
    padding: 12px 6px;
    font-size: 15px;
    font-weight: 700;
    color: #6b7280;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    background: none;
    border-top: none;
    border-left: none;
    border-right: none;
    transition: all 0.25s ease;
}
.tab-btn:hover {
    color: #fe5f04;
}
.tab-btn.active {
    color: #fe5f04;
    border-bottom-color: #fe5f04;
}
.tab-panel {
    display: none;
}
.tab-panel.active {
    display: block;
}

/* Table Design */
.ticket-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 18px;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.02);
    overflow: hidden;
}
.support-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
}
.support-table th {
    background: #f9fafb;
    padding: 16px 20px;
    font-size: 12px;
    font-weight: 700;
    color: #4b5563;
    border-bottom: 1px solid #e5e7eb;
    text-transform: uppercase;
}
.support-table td {
    padding: 18px 20px;
    font-size: 14px;
    color: #1f2937;
    border-bottom: 1px solid #e5e7eb;
}
.support-table tr:last-child td {
    border-bottom: none;
}
.support-table tr:hover td {
    background: #fafbfe;
}

/* Badges */
.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
}
.status-badge.pending {
    background-color: #fff1e8;
    color: #c2410c;
}
.status-badge.onprocess {
    background-color: #eff6ff;
    color: #1d4ed8;
}
.status-badge.resolved {
    background-color: #ecfdf5;
    color: #047857;
}
.status-badge.closed {
    background-color: #f3f4f6;
    color: #374151;
}

.btn-update-status {
    background-color: #f3f4f6;
    border: 1px solid #d1d5db;
    color: #374151;
    font-weight: 600;
    font-size: 13px;
    padding: 8px 14px;
    border-radius: 10px;
    transition: all 0.15s ease;
    cursor: pointer;
}
.btn-update-status:hover {
    background-color: #fe5f04;
    border-color: #fe5f04;
    color: #ffffff;
}

.ticket-message-preview {
    max-width: 320px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: #6b7280;
    font-size: 13px;
}

.ticket-subject-link:hover {
    color: #fe5f04 !important;
    text-decoration: underline;
}

/* Modals */
.support-modal {
    position: fixed;
    inset: 0;
    background: rgba(18, 24, 38, 0.45);
    backdrop-filter: blur(4px);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 2000;
    opacity: 0;
    transition: opacity 0.25s ease;
}
.support-modal.show {
    display: flex;
    opacity: 1;
}
.support-modal-content {
    background: #ffffff;
    border-radius: 20px;
    width: 650px;
    max-width: calc(100vw - 32px);
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    transform: translateY(20px);
    transition: transform 0.25s ease;
}
.support-modal.show .support-modal-content {
    transform: translateY(0);
}
.support-modal-header {
    background: #fafbfe;
    border-bottom: 1px solid #e5e7eb;
    padding: 20px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.support-modal-title {
    font-size: 18px;
    font-weight: 800;
    color: #111827;
    margin: 0;
}
.support-modal-close {
    color: #9ca3af;
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    transition: color 0.15s ease;
}
.support-modal-close:hover {
    color: #374151;
}
.support-modal-body {
    padding: 24px;
    max-height: calc(85vh - 120px);
    overflow-y: auto;
}
.form-group {
    margin-bottom: 20px;
}
.form-group label {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: #374151;
    margin-bottom: 8px;
}
.form-control {
    width: 100%;
    border: 1px solid #d1d5db;
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 14px;
    color: #1f2937;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.form-control:focus {
    outline: none;
    border-color: #fe5f04;
    box-shadow: 0 0 0 3px rgba(254, 95, 4, 0.12);
}
.support-modal-footer {
    border-top: 1px solid #e5e7eb;
    padding: 16px 24px;
    background: #fafbfe;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}
.btn-secondary {
    background: #ffffff;
    border: 1px solid #d1d5db;
    color: #374151;
    font-weight: 700;
    padding: 10px 18px;
    border-radius: 10px;
    cursor: pointer;
}
.btn-secondary:hover {
    background: #f9fafb;
}
.btn-submit {
    background: linear-gradient(135deg, #fe5f04 0%, #ff8c3a 100%);
    color: #ffffff;
    font-weight: 700;
    border: none;
    padding: 10px 20px;
    border-radius: 10px;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(254, 95, 4, 0.15);
}
.btn-submit:hover {
    box-shadow: 0 6px 16px rgba(254, 95, 4, 0.22);
}

.tox-tinymce {
    border-radius: 12px !important;
    border-color: #d1d5db !important;
}

/* Select2 overrides inside modal */
.select2-container--default .select2-selection--single {
    border-radius: 10px !important;
    border-color: #d1d5db !important;
    height: 42px !important;
    display: flex;
    align-items: center;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 40px !important;
}

.alert-success {
    background-color: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #065f46;
    padding: 14px 20px;
    border-radius: 12px;
    margin-bottom: 24px;
    font-size: 14px;
    font-weight: 600;
}

/* Process / Progress Bar Overlay */
.support-process-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.75);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 99999;
    animation: fadeInOverlay 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes fadeInOverlay {
    from { opacity: 0; }
    to { opacity: 1; }
}

.support-process-card {
    background: #ffffff;
    border-radius: 24px;
    padding: 40px 36px;
    width: 460px;
    max-width: calc(100vw - 32px);
    box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.35);
    text-align: center;
    transform: scale(0.95);
    animation: scaleInCard 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes scaleInCard {
    to { transform: scale(1); }
}

.support-process-icon-wrap {
    position: relative;
    width: 80px;
    height: 80px;
    margin: 0 auto 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.support-process-spinner {
    position: absolute;
    inset: 0;
    border: 3.5px solid #ffe6d5;
    border-top-color: #fe5f04;
    border-radius: 50%;
    animation: spinOverlay 0.9s linear infinite;
}

@keyframes spinOverlay {
    to { transform: rotate(360deg); }
}

.support-process-icon {
    font-size: 32px;
    color: #fe5f04;
    animation: pulseIcon 1.5s ease-in-out infinite alternate;
}

@keyframes pulseIcon {
    from { transform: scale(0.88); opacity: 0.85; }
    to { transform: scale(1.12); opacity: 1; }
}

.support-process-title {
    font-size: 19px;
    font-weight: 800;
    color: #111827;
    margin: 0 0 6px;
}

.support-process-subtitle {
    font-size: 13px;
    color: #6b7280;
    margin: 0 0 24px;
    line-height: 1.5;
}

.support-progress-wrapper {
    width: 100%;
}

.support-progress-bar {
    width: 100%;
    height: 10px;
    background: #e2e8f0;
    border-radius: 999px;
    overflow: hidden;
    position: relative;
}

.support-progress-fill {
    height: 100%;
    width: 0%;
    background: linear-gradient(90deg, #fe5f04 0%, #ff8c3a 50%, #fe5f04 100%);
    background-size: 200% 100%;
    border-radius: 999px;
    transition: width 0.3s ease;
    animation: gradientMove 2s linear infinite;
}

@keyframes gradientMove {
    0% { background-position: 0% 0%; }
    100% { background-position: 200% 0%; }
}

.support-progress-status {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 10px;
    font-size: 12px;
    font-weight: 700;
    color: #4b5563;
}
</style>
@endpush

@section('content')
<div class="support-page">
    @if(session('success'))
        <div class="alert-success">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert-error" style="background-color: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 14px 20px; border-radius: 12px; margin-bottom: 24px; font-size: 14px; font-weight: 600;">
            {{ session('error') }}
        </div>
    @endif

    <div class="support-hero">
        <div>
            <div class="support-kicker">
                <i class="bi bi-chat-left-text-fill"></i> Helpdesk Portal
            </div>
            <h2 class="support-title">Support Tickets</h2>
            <p class="support-subtitle">Create internal support tickets, collaborate with members, and manage incoming ticket updates.</p>
        </div>

        <button type="button" class="btn-create-ticket" onclick="openCreateModal()">
            <i class="bi bi-plus-lg"></i> Create Ticket
        </button>
    </div>

    <!-- Tabs Layout -->
    <div class="tabs-container">
        <button type="button" class="tab-btn active" onclick="switchTab(event, 'received')">
            Received Tickets ({{ $receivedTickets->count() }})
        </button>
        <button type="button" class="tab-btn" onclick="switchTab(event, 'created')">
            Created Tickets ({{ $createdTickets->count() }})
        </button>
    </div>

    <!-- Tab Panels -->
    <div id="received" class="tab-panel active">
        <div class="ticket-card">
            <table class="support-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">Ticket ID</th>
                        <th>Subject</th>
                        <th>From User</th>
                        <th>Created At</th>
                        <th>Status</th>
                        <th>Remark</th>
                        <th>Attachment</th>
                        <th style="width: 140px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receivedTickets as $ticket)
                        <tr>
                            <td><strong>#{{ $ticket->id }}</strong></td>
                            <td>
                                <div>
                                    <a href="javascript:void(0)" class="ticket-subject-link"
                                       data-ticket-id="{{ $ticket->id }}"
                                       data-subject="{{ $ticket->subject }}"
                                       data-creator="{{ $ticket->creator?->name ?? '-' }}"
                                       data-assigned="{{ $ticket->assignedTo?->name ?? '-' }}"
                                       data-created-at="{{ $ticket->created_at?->format('d M Y, h:i A') ?? '-' }}"
                                       data-status="{{ $ticket->status }}"
                                       data-remark="{{ $ticket->remark ?? '' }}"
                                       data-attachment="{{ $ticket->attachment_url ?? '' }}"
                                       style="color: #111827; text-decoration: none; font-weight: 800; transition: color 0.15s ease;">
                                        {{ $ticket->subject }}
                                    </a>
                                </div>
                                <div class="ticket-message-preview">{!! strip_tags($ticket->message) !!}</div>
                                <div id="ticket-msg-{{ $ticket->id }}" style="display:none;">{!! $ticket->message !!}</div>
                            </td>
                            <td>{{ $ticket->creator?->name }}</td>
                            <td>{{ $ticket->created_at?->format('d M Y, h:i A') }}</td>
                            <td>
                                <span class="status-badge {{ $ticket->status }}">
                                    {{ $ticket->status === 'onprocess' ? 'On Process' : ucfirst($ticket->status) }}
                                </span>
                            </td>
                            <td>
                                @if($ticket->remark)
                                    <em class="text-gray">{{ $ticket->remark }}</em>
                                @else
                                    <span class="text-gray" style="font-size: 12px; opacity: 0.65;">No remarks yet</span>
                                @endif
                            </td>
                            <td>
                                @if($ticket->attachment_path)
                                    <a href="{{ $ticket->attachment_url }}" target="_blank" style="color: #fe5f04; font-weight: 700;">
                                        <i class="bi bi-file-earmark-arrow-down"></i> View File
                                    </a>
                                @else
                                    <span class="text-gray">-</span>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                @if($ticket->status === 'closed')
                                    <button type="button" class="btn-update-status" disabled style="opacity: 0.55; cursor: not-allowed; background-color: #e5e7eb; border-color: #d1d5db; color: #6b7280;" title="Ticket is closed and cannot be updated.">
                                        <i class="bi bi-lock-fill"></i> Closed
                                    </button>
                                @else
                                    <button type="button" class="btn-update-status" 
                                            onclick="openUpdateModal({{ $ticket->id }}, '{{ $ticket->status }}', '{{ addslashes($ticket->remark ?? '') }}')">
                                        <i class="bi bi-pencil-square"></i> Update Status
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: #8e8e8e;">
                                <i class="bi bi-inbox" style="font-size: 32px; display: block; margin-bottom: 10px;"></i>
                                No received tickets found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="created" class="tab-panel">
        <div class="ticket-card">
            <table class="support-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">Ticket ID</th>
                        <th>Subject</th>
                        <th>To Person</th>
                        <th>Created At</th>
                        <th>Status</th>
                        <th>Remark</th>
                        <th>Attachment</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($createdTickets as $ticket)
                        <tr>
                            <td><strong>#{{ $ticket->id }}</strong></td>
                            <td>
                                <div>
                                    <a href="javascript:void(0)" class="ticket-subject-link"
                                       data-ticket-id="{{ $ticket->id }}"
                                       data-subject="{{ $ticket->subject }}"
                                       data-creator="{{ $ticket->creator?->name ?? '-' }}"
                                       data-assigned="{{ $ticket->assignedTo?->name ?? '-' }}"
                                       data-created-at="{{ $ticket->created_at?->format('d M Y, h:i A') ?? '-' }}"
                                       data-status="{{ $ticket->status }}"
                                       data-remark="{{ $ticket->remark ?? '' }}"
                                       data-attachment="{{ $ticket->attachment_url ?? '' }}"
                                       style="color: #111827; text-decoration: none; font-weight: 800; transition: color 0.15s ease;">
                                        {{ $ticket->subject }}
                                    </a>
                                </div>
                                <div class="ticket-message-preview">{!! strip_tags($ticket->message) !!}</div>
                                <div id="ticket-msg-{{ $ticket->id }}" style="display:none;">{!! $ticket->message !!}</div>
                            </td>
                            <td>{{ $ticket->assignedTo?->name }}</td>
                            <td>{{ $ticket->created_at?->format('d M Y, h:i A') }}</td>
                            <td>
                                <span class="status-badge {{ $ticket->status }}">
                                    {{ $ticket->status === 'onprocess' ? 'On Process' : ucfirst($ticket->status) }}
                                </span>
                            </td>
                            <td>
                                @if($ticket->remark)
                                    <em class="text-gray">{{ $ticket->remark }}</em>
                                @else
                                    <span class="text-gray" style="font-size: 12px; opacity: 0.65;">Pending review</span>
                                @endif
                            </td>
                            <td>
                                @if($ticket->attachment_path)
                                    <a href="{{ $ticket->attachment_url }}" target="_blank" style="color: #fe5f04; font-weight: 700;">
                                        <i class="bi bi-file-earmark-arrow-down"></i> View File
                                    </a>
                                @else
                                    <span class="text-gray">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #8e8e8e;">
                                <i class="bi bi-send" style="font-size: 32px; display: block; margin-bottom: 10px;"></i>
                                You haven't created any support tickets yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- CREATE TICKET MODAL -->
<div id="createTicketModal" class="support-modal">
    <div class="support-modal-content" style="width: 750px;">
        <form id="createTicketForm" method="POST" action="{{ route('support.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="support-modal-header">
                <h3 class="support-modal-title">Create Support Ticket</h3>
                <button type="button" class="support-modal-close" onclick="closeCreateModal()">&times;</button>
            </div>
            <div class="support-modal-body">
                <div class="form-group">
                    <label for="to_user_id">Submit To (Person)</label>
                    <select name="to_user_id" id="to_user_id" class="form-control select2" required style="width: 100%;">
                        <option value="">Select a user...</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" name="subject" id="subject" class="form-control" placeholder="Brief summary of the issue" required>
                </div>

                <div class="form-group">
                    <label for="ticketMessage">Message</label>
                    <textarea name="message" id="ticketMessage" class="form-control" rows="8"></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label for="attachment">Attachment</label>
                    <input type="file" name="attachment" id="attachment" class="form-control" style="padding: 6px 12px;">
                    <small style="color: #8e8e8e; margin-top: 4px; display: block;">Supported files: PDF, Word, Excel, Images, Zip, Txt (Max: 10MB)</small>
                </div>
            </div>
            <div class="support-modal-footer">
                <button type="button" class="btn-secondary" onclick="closeCreateModal()">Cancel</button>
                <button type="submit" class="btn-submit">Submit Ticket</button>
            </div>
        </form>
    </div>
</div>

<!-- UPDATE STATUS MODAL -->
<div id="updateStatusModal" class="support-modal">
    <div class="support-modal-content">
        <form id="updateStatusForm" method="POST" action="">
            @csrf
            <div class="support-modal-header">
                <h3 class="support-modal-title">Update Ticket Status & Remarks</h3>
                <button type="button" class="support-modal-close" onclick="closeUpdateModal()">&times;</button>
            </div>
            <div class="support-modal-body">
                <div class="form-group">
                    <label for="ticket_status">Status</label>
                    <select name="status" id="ticket_status" class="form-control" required>
                        <option value="pending">Pending</option>
                        <option value="onprocess">On Process</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label for="ticket_remark">Remark / Update Message</label>
                    <textarea name="remark" id="ticket_remark" class="form-control" rows="5" placeholder="Enter details about status resolution, notes, or messages for the creator..."></textarea>
                </div>
            </div>
            <div class="support-modal-footer">
                <button type="button" class="btn-secondary" onclick="closeUpdateModal()">Cancel</button>
                <button type="button" class="btn-submit" onclick="showConfirmModal()">Update Ticket</button>
            </div>
        </form>
    </div>
</div>

<!-- VIEW TICKET MODAL -->
<div id="viewTicketModal" class="support-modal">
    <div class="support-modal-content" style="width: 700px;">
        <div class="support-modal-header">
            <h3 class="support-modal-title" id="vt_subject_title">Ticket Details</h3>
            <button type="button" class="support-modal-close" onclick="closeViewModal()">&times;</button>
        </div>
        <div class="support-modal-body" style="padding: 28px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; border-bottom: 1px dashed #e5e7eb; padding-bottom: 16px;">
                <div>
                    <div style="font-size: 11px; font-weight: 800; color: #4b5563; text-transform: uppercase; letter-spacing: 0.5px;">Ticket ID</div>
                    <div id="vt_id" style="font-size: 16px; font-weight: 800; color: #fe5f04; margin-top: 4px;">#123</div>
                </div>
                <div>
                    <div style="font-size: 11px; font-weight: 800; color: #4b5563; text-transform: uppercase; letter-spacing: 0.5px; text-align: right;">Status</div>
                    <div style="margin-top: 4px;">
                        <span class="status-badge" id="vt_status">Pending</span>
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
                <div>
                    <div style="font-size: 11px; font-weight: 800; color: #4b5563; text-transform: uppercase; letter-spacing: 0.5px;">From</div>
                    <div id="vt_from" style="font-size: 14px; font-weight: 700; color: #111827; margin-top: 4px;">John Doe</div>
                </div>
                <div>
                    <div style="font-size: 11px; font-weight: 800; color: #4b5563; text-transform: uppercase; letter-spacing: 0.5px;">To</div>
                    <div id="vt_to" style="font-size: 14px; font-weight: 700; color: #111827; margin-top: 4px;">Jane Smith</div>
                </div>
            </div>

            <div style="margin-bottom: 24px;">
                <div style="font-size: 11px; font-weight: 800; color: #4b5563; text-transform: uppercase; letter-spacing: 0.5px;">Date Created</div>
                <div id="vt_date" style="font-size: 14px; color: #1f2937; margin-top: 4px;">17 Jul 2026, 12:00 PM</div>
            </div>

            <div style="margin-bottom: 24px; background: #f8fafc; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0;">
                <div style="font-size: 11px; font-weight: 800; color: #4b5563; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px;">Message</div>
                <div id="vt_message" style="font-size: 14px; color: #334155; line-height: 1.6; word-break: break-word;"></div>
            </div>

            <div style="margin-bottom: 24px;" id="vt_attachment_section">
                <div style="font-size: 11px; font-weight: 800; color: #4b5563; text-transform: uppercase; letter-spacing: 0.5px;">Attachment</div>
                <div style="margin-top: 6px;">
                    <a id="vt_attachment_link" href="#" target="_blank" style="color: #fe5f04; font-weight: 700; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="bi bi-file-earmark-arrow-down" style="font-size: 18px;"></i> View Attached File
                    </a>
                    <span id="vt_no_attachment" class="text-gray" style="font-size: 14px; color: #9ca3af;">No attachment provided</span>
                </div>
            </div>

            <div style="padding-top: 20px; border-top: 1px dashed #e5e7eb;" id="vt_remark_section">
                <div style="font-size: 11px; font-weight: 800; color: #4b5563; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Resolution Remarks</div>
                <div id="vt_remark" style="font-size: 14px; color: #475569; background: #fffbeb; border: 1px solid #fef3c7; border-radius: 12px; padding: 14px 18px; line-height: 1.5; font-style: italic; display: block;">
                    No remarks yet.
                </div>
            </div>
        </div>
        <div class="support-modal-footer">
            <button type="button" class="btn-secondary" onclick="closeViewModal()">Close</button>
        </div>
    </div>
</div>

<!-- CONFIRMATION MODAL -->
<div id="confirmUpdateModal" class="support-modal" style="z-index: 2100;">
    <div class="support-modal-content" style="width: 450px;">
        <div class="support-modal-header" style="background: #fffbeb; border-bottom: 1px solid #fef3c7;">
            <h3 class="support-modal-title" style="color: #b45309; display: flex; align-items: center; gap: 8px;">
                <i class="bi bi-exclamation-triangle-fill"></i> Confirm Update
            </h3>
            <button type="button" class="support-modal-close" onclick="closeConfirmModal()">&times;</button>
        </div>
        <div class="support-modal-body" style="padding: 24px; text-align: center;">
            <p style="font-size: 15px; font-weight: 700; color: #1f2937; margin: 0 0 10px;">Are you sure you want to update this ticket?</p>
            <p style="font-size: 13px; color: #6b7280; margin: 0; line-height: 1.5;">An email notification will be sent to the creator with the updated status and remarks.</p>
        </div>
        <div class="support-modal-footer">
            <button type="button" class="btn-secondary" onclick="closeConfirmModal()">Cancel</button>
            <button type="button" class="btn-submit" id="btn-confirm-submit" style="background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%); box-shadow: 0 4px 12px rgba(217, 119, 6, 0.25);">Yes, Update</button>
        </div>
    </div>
</div>

<!-- PROCESS OVERLAY / PROGRESS BAR MODAL -->
<div id="supportProcessOverlay" class="support-process-overlay" style="display: none;">
    <div class="support-process-card">
        <div class="support-process-icon-wrap">
            <div class="support-process-spinner"></div>
            <i class="bi bi-envelope-paper-fill support-process-icon"></i>
        </div>
        <h4 id="processOverlayTitle" class="support-process-title">Sending Email & Processing...</h4>
        <p id="processOverlaySubtitle" class="support-process-subtitle">Please wait while the email notification is being sent...</p>

        <div class="support-progress-wrapper">
            <div class="support-progress-bar">
                <div id="supportProgressFill" class="support-progress-fill"></div>
            </div>
            <div class="support-progress-status">
                <span id="supportProgressText">Preparing email notification...</span>
                <span id="supportProgressPercent">0%</span>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
{{-- TinyMCE self-hosted CDN build --}}
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.5/tinymce.min.js" referrerpolicy="origin"></script>

<script>
    // Tab switching logic
    function switchTab(evt, tabName) {
        // Declare all variables
        var i, tabcontent, tablinks;

        // Get all elements with class="tab-panel" and hide them
        tabcontent = document.getElementsByClassName("tab-panel");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].classList.remove("active");
        }

        // Get all elements with class="tab-btn" and remove the class "active"
        tablinks = document.getElementsByClassName("tab-btn");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].classList.remove("active");
        }

        // Show the current tab, and add an "active" class to the button that opened the tab
        document.getElementById(tabName).classList.add("active");
        evt.currentTarget.classList.add("active");
    }

    // Modal Control Logic
    function openCreateModal() {
        const modal = document.getElementById('createTicketModal');
        modal.classList.add('show');

        // TinyMCE Init
        if (window.tinymce && !window.tinymce.get('ticketMessage')) {
            window.tinymce.init({
                selector: 'textarea#ticketMessage',
                base_url: 'https://cdn.jsdelivr.net/npm/tinymce@6.8.5',
                height: 280,
                menubar: false,
                plugins: 'lists link code wordcount',
                toolbar: 'undo redo | bold italic underline | bullist numlist | link code',
                setup: function (editor) {
                    editor.on('change keyup', function () {
                        window.tinymce.triggerSave();
                    });
                }
            });
        }

        // Select2 re-initialization if needed
        if (window.jQuery && jQuery().select2) {
            $('#to_user_id').select2({
                dropdownParent: $('#createTicketModal')
            });
        }
    }

    function closeCreateModal() {
        const modal = document.getElementById('createTicketModal');
        modal.classList.remove('show');
    }

    function openUpdateModal(ticketId, currentStatus, currentRemark) {
        const modal = document.getElementById('updateStatusModal');
        const form = document.getElementById('updateStatusForm');
        
        // Dynamically set action URL
        form.action = `/support/${ticketId}/update-status`;
        
        // Pre-fill form fields
        document.getElementById('ticket_status').value = currentStatus;
        document.getElementById('ticket_remark').value = currentRemark;
        
        modal.classList.add('show');
    }

    function closeUpdateModal() {
        const modal = document.getElementById('updateStatusModal');
        modal.classList.remove('show');
    }

    function openViewModal(ticket) {
        document.getElementById('vt_id').innerText = '#' + ticket.id;
        document.getElementById('vt_subject_title').innerText = ticket.subject;
        document.getElementById('vt_from').innerText = ticket.creator_name;
        document.getElementById('vt_to').innerText = ticket.assigned_to_name;
        document.getElementById('vt_date').innerText = ticket.created_at;
        document.getElementById('vt_message').innerHTML = ticket.message;

        // Status Badge
        const statusBadge = document.getElementById('vt_status');
        statusBadge.innerText = ticket.status === 'onprocess' ? 'On Process' : (ticket.status.charAt(0).toUpperCase() + ticket.status.slice(1));
        statusBadge.className = 'status-badge ' + ticket.status;

        // Attachment section
        const attachmentLink = document.getElementById('vt_attachment_link');
        const noAttachment = document.getElementById('vt_no_attachment');
        if (ticket.attachment_path) {
            attachmentLink.href = ticket.attachment_path;
            attachmentLink.style.display = 'inline-flex';
            noAttachment.style.display = 'none';
        } else {
            attachmentLink.style.display = 'none';
            noAttachment.style.display = 'inline';
        }

        // Remarks section
        const remarkEl = document.getElementById('vt_remark');
        if (ticket.remark) {
            remarkEl.innerText = ticket.remark;
        } else {
            remarkEl.innerText = 'No remarks yet';
        }

        const modal = document.getElementById('viewTicketModal');
        modal.classList.add('show');
    }

    function closeViewModal() {
        const modal = document.getElementById('viewTicketModal');
        modal.classList.remove('show');
    }

    function showConfirmModal() {
        // Validate form fields are filled (status is required)
        const statusField = document.getElementById('ticket_status');
        if (!statusField.value) {
            statusField.reportValidity();
            return;
        }
        
        const modal = document.getElementById('confirmUpdateModal');
        modal.classList.add('show');
    }

    function closeConfirmModal() {
        const modal = document.getElementById('confirmUpdateModal');
        modal.classList.remove('show');
    }

    // Close modals on clicking background wrapper
    window.addEventListener('click', function(e) {
        const createModal = document.getElementById('createTicketModal');
        const updateModal = document.getElementById('updateStatusModal');
        const viewModal = document.getElementById('viewTicketModal');
        const confirmModal = document.getElementById('confirmUpdateModal');
        if (e.target === createModal) {
            closeCreateModal();
        }
        if (e.target === updateModal) {
            closeUpdateModal();
        }
        if (e.target === viewModal) {
            closeViewModal();
        }
        if (e.target === confirmModal) {
            closeConfirmModal();
        }
    });

    // Progress bar overlay logic
    let processProgressInterval = null;

    function showProcessOverlay(title, subtitle) {
        if (title) document.getElementById('processOverlayTitle').innerText = title;
        if (subtitle) document.getElementById('processOverlaySubtitle').innerText = subtitle;

        const overlay = document.getElementById('supportProcessOverlay');
        const fill = document.getElementById('supportProgressFill');
        const percentText = document.getElementById('supportProgressPercent');
        const statusText = document.getElementById('supportProgressText');

        overlay.style.display = 'flex';

        let currentProgress = 5;
        fill.style.width = currentProgress + '%';
        percentText.innerText = currentProgress + '%';
        statusText.innerText = 'Connecting to server...';

        if (processProgressInterval) clearInterval(processProgressInterval);

        processProgressInterval = setInterval(function() {
            if (currentProgress < 30) {
                currentProgress += Math.floor(Math.random() * 8) + 4;
                statusText.innerText = 'Building email notification...';
            } else if (currentProgress < 70) {
                currentProgress += Math.floor(Math.random() * 6) + 3;
                statusText.innerText = 'Sending email via SMTP...';
            } else if (currentProgress < 92) {
                currentProgress += Math.floor(Math.random() * 3) + 1;
                statusText.innerText = 'Finalizing support ticket process...';
            }

            if (currentProgress > 94) {
                currentProgress = 94;
            }

            fill.style.width = currentProgress + '%';
            percentText.innerText = currentProgress + '%';
        }, 250);
    }

    // Add confirmation click listener for Status Update
    document.addEventListener('DOMContentLoaded', function() {
        const btnConfirmSubmit = document.getElementById('btn-confirm-submit');
        if (btnConfirmSubmit) {
            btnConfirmSubmit.addEventListener('click', function() {
                const updateForm = document.getElementById('updateStatusForm');
                if (updateForm && !updateForm.checkValidity()) {
                    updateForm.reportValidity();
                    return;
                }

                closeConfirmModal();
                closeUpdateModal();
                showProcessOverlay(
                    "Sending Email & Updating Ticket Status...",
                    "Please wait while the ticket status is updated and email notification is sent..."
                );

                HTMLFormElement.prototype.submit.call(updateForm);
            });
        }
    });

    // Add click listeners to subject links once DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.ticket-subject-link').forEach(function(link) {
            link.addEventListener('click', function() {
                const ticketId = this.getAttribute('data-ticket-id');
                const subject = this.getAttribute('data-subject');
                const creator = this.getAttribute('data-creator');
                const assigned = this.getAttribute('data-assigned');
                const createdAt = this.getAttribute('data-created-at');
                const status = this.getAttribute('data-status');
                const remark = this.getAttribute('data-remark');
                const attachment = this.getAttribute('data-attachment');
                const messageHtml = document.getElementById('ticket-msg-' + ticketId).innerHTML;

                openViewModal({
                    id: ticketId,
                    subject: subject,
                    creator_name: creator,
                    assigned_to_name: assigned,
                    created_at: createdAt,
                    status: status,
                    remark: remark,
                    attachment_path: attachment,
                    message: messageHtml
                });
            });
        });
    });

    // Form submission validation & progress bar trigger for Ticket Create
    document.getElementById('createTicketForm').addEventListener('submit', function(e) {
        if (window.tinymce) {
            window.tinymce.triggerSave();
        }

        const toUser = document.getElementById('to_user_id')?.value;
        const subject = document.getElementById('subject')?.value;
        const message = document.getElementById('ticketMessage')?.value;

        if (!toUser) {
            alert('Please select a person to submit the ticket to.');
            e.preventDefault();
            return;
        }

        if (!subject || !subject.trim()) {
            alert('Please enter a ticket subject.');
            e.preventDefault();
            return;
        }

        if (!message || !message.trim() || message === '<p></p>') {
            alert('Please enter a ticket message.');
            e.preventDefault();
            return;
        }

        closeCreateModal();
        showProcessOverlay(
            "Sending Email & Creating Support Ticket...",
            "Please wait while your support ticket is created and email notification is sent..."
        );
    });
</script>
@endpush
