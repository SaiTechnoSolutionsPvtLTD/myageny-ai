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
</style>
@endpush

@section('content')
<div class="support-page">
    @if(session('success'))
        <div class="alert-success">
            {{ session('success') }}
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
                                <div><strong>{{ $ticket->subject }}</strong></div>
                                <div class="ticket-message-preview">{!! strip_tags($ticket->message) !!}</div>
                            </td>
                            <td>{{ $ticket->creator?->name }}</td>
                            <td>{{ $ticket->created_at?->format('d M Y, h:i A') }}</td>
                            <td>
                                <span class="status-badge {{ $ticket->status }}">
                                    {{ $ticket->status }}
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
                                    <a href="{{ asset('storage/' . $ticket->attachment_path) }}" target="_blank" style="color: #fe5f04; font-weight: 700;">
                                        <i class="bi bi-file-earmark-arrow-down"></i> View File
                                    </a>
                                @else
                                    <span class="text-gray">-</span>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                <button type="button" class="btn-update-status" 
                                        onclick="openUpdateModal({{ $ticket->id }}, '{{ $ticket->status }}', '{{ addslashes($ticket->remark ?? '') }}')">
                                    <i class="bi bi-pencil-square"></i> Update Status
                                </button>
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
                                <div><strong>{{ $ticket->subject }}</strong></div>
                                <div class="ticket-message-preview">{!! strip_tags($ticket->message) !!}</div>
                            </td>
                            <td>{{ $ticket->assignedTo?->name }}</td>
                            <td>{{ $ticket->created_at?->format('d M Y, h:i A') }}</td>
                            <td>
                                <span class="status-badge {{ $ticket->status }}">
                                    {{ $ticket->status }}
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
                                    <a href="{{ asset('storage/' . $ticket->attachment_path) }}" target="_blank" style="color: #fe5f04; font-weight: 700;">
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
                <button type="submit" class="btn-submit">Update Ticket</button>
            </div>
        </form>
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

    // Close modals on clicking background wrapper
    window.addEventListener('click', function(e) {
        const createModal = document.getElementById('createTicketModal');
        const updateModal = document.getElementById('updateStatusModal');
        if (e.target === createModal) {
            closeCreateModal();
        }
        if (e.target === updateModal) {
            closeUpdateModal();
        }
    });

    // Form submission confirmation prompt
    document.getElementById('createTicketForm').addEventListener('submit', function(e) {
        // Ensure tinymce content is synced before confirmation check
        if (window.tinymce) {
            window.tinymce.triggerSave();
        }
        
        const confirmMsg = "Are you sure you want to submit this support ticket?";
        if (!confirm(confirmMsg)) {
            e.preventDefault();
        }
    });
</script>
@endpush
