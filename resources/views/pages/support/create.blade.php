@extends('layouts.app')

@section('title', 'Create Support Ticket - myAgenci.ai')

@push('styles')
<style>
.support-create-page {
    min-height: 100%;
    padding: 28px;
    background:
        radial-gradient(circle at top left, rgba(254, 95, 4, 0.08), transparent 35%),
        linear-gradient(180deg, #f8f6f2 0%, #f3f5f8 100%);
}

.create-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 24px;
    padding: 24px 28px;
    border: 1px solid #e6e8ee;
    border-radius: 20px;
    background: linear-gradient(135deg, #fff9f3 0%, #ffffff 55%, #f7f9fc 100%);
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
}

.create-kicker {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    border-radius: 999px;
    background: #fff1e8;
    color: #c2410c;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .7px;
    text-transform: uppercase;
    margin-bottom: 8px;
}

.create-title {
    margin: 0;
    font-size: 26px;
    font-weight: 800;
    line-height: 1.2;
    color: #111827;
}

.create-subtitle {
    margin: 6px 0 0;
    font-size: 14px;
    color: #6b7280;
}

.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #ffffff;
    color: #374151;
    font-weight: 700;
    font-size: 13px;
    padding: 10px 18px;
    border-radius: 12px;
    border: 1px solid #d1d5db;
    text-decoration: none;
    transition: all 0.2s ease;
}
.btn-back:hover {
    background: #f9fafb;
    border-color: #9ca3af;
    color: #111827;
    transform: translateX(-2px);
}

.form-card {
    background: #ffffff;
    border-radius: 20px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
    overflow: hidden;
}

.form-section {
    padding: 28px 32px;
    border-bottom: 1px solid #f1f5f9;
}
.form-section:last-child {
    border-bottom: none;
}

.section-label {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}
.section-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 8px;
    background: #fff1e8;
    color: #c2410c;
    font-weight: 800;
    font-size: 13px;
    border: 1px solid #fed7aa;
}
.section-title {
    margin: 0;
    font-size: 17px;
    font-weight: 800;
    color: #111827;
}
.section-desc {
    font-size: 13px;
    color: #6b7280;
    margin: 2px 0 0 40px;
}

/* Category Grid */
.category-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 16px;
    margin-top: 18px;
}

.category-card {
    position: relative;
    padding: 18px 20px;
    border-radius: 16px;
    border: 2px solid #e5e7eb;
    background: #ffffff;
    cursor: pointer;
    transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    align-items: flex-start;
    gap: 14px;
}
.category-card:hover {
    border-color: #fdba74;
    background: #fffdfb;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(254, 95, 4, 0.08);
}
.category-card.selected {
    border-color: #fe5f04;
    background: #fff8f4;
    box-shadow: 0 8px 20px rgba(254, 95, 4, 0.14);
}
.category-card-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #f3f4f6;
    color: #4b5563;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
    transition: all 0.2s ease;
}
.category-card.selected .category-card-icon {
    background: #fe5f04;
    color: #ffffff;
}
.category-card-body {
    flex: 1;
    min-width: 0;
}
.category-card-name {
    font-size: 15px;
    font-weight: 800;
    color: #111827;
    margin-bottom: 4px;
}
.category-card.selected .category-card-name {
    color: #c2410c;
}
.category-card-desc {
    font-size: 12px;
    color: #6b7280;
    line-height: 1.4;
}
.category-card-check {
    position: absolute;
    top: 14px;
    right: 14px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid #cbd5e1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    color: #ffffff;
    transition: all 0.2s ease;
}
.category-card.selected .category-card-check {
    background: #fe5f04;
    border-color: #fe5f04;
}

/* Subcategory Buttons (Small Buttons / Chips) */
.subcat-chips-wrapper {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 18px;
}

.subcat-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 18px;
    border-radius: 999px;
    border: 1.5px solid #d1d5db;
    background: #ffffff;
    color: #374151;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);
}
.subcat-chip:hover {
    border-color: #fed7aa;
    background: #fff7ed;
    color: #c2410c;
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(254, 95, 4, 0.1);
}
.subcat-chip.selected {
    border-color: #fe5f04;
    background: linear-gradient(135deg, #fe5f04 0%, #ea580c 100%);
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(254, 95, 4, 0.3);
    transform: translateY(-1px);
}
.subcat-chip.selected .subcat-check {
    display: inline-block;
}
.subcat-check {
    display: none;
    font-size: 13px;
}

.empty-subcats-hint {
    padding: 20px;
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    border-radius: 12px;
    color: #64748b;
    font-size: 13px;
    text-align: center;
    margin-top: 14px;
}

.subcat-assignment-notice {
    display: none;
    align-items: center;
    gap: 10px;
    margin-top: 14px;
    padding: 10px 16px;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 10px;
    font-size: 13px;
    color: #1e40af;
}

/* Form inputs */
.form-group {
    margin-bottom: 22px;
}
.form-group label {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: #374151;
    margin-bottom: 8px;
}
.form-group label .required {
    color: #dc2626;
    margin-left: 2px;
}
.form-control-custom {
    width: 100%;
    padding: 11px 16px;
    border: 1.5px solid #d1d5db;
    border-radius: 12px;
    font-size: 14px;
    color: #111827;
    transition: all 0.2s ease;
    background: #ffffff;
}
.form-control-custom:focus {
    outline: none;
    border-color: #fe5f04;
    box-shadow: 0 0 0 3px rgba(254, 95, 4, 0.15);
}

/* Upload drop zone */
.attachment-dropzone {
    border: 2px dashed #cbd5e1;
    border-radius: 14px;
    padding: 24px;
    text-align: center;
    background: #fafaf9;
    cursor: pointer;
    transition: all 0.2s ease;
}
.attachment-dropzone:hover, .attachment-dropzone.dragover {
    border-color: #fe5f04;
    background: #fff8f4;
}
.attachment-icon {
    font-size: 32px;
    color: #94a3b8;
    margin-bottom: 8px;
    transition: color 0.2s ease;
}
.attachment-dropzone:hover .attachment-icon {
    color: #fe5f04;
}
.attachment-text {
    font-size: 14px;
    font-weight: 700;
    color: #374151;
    margin-bottom: 4px;
}
.attachment-subtext {
    font-size: 12px;
    color: #6b7280;
}

.file-preview-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 14px;
}
.file-preview-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    border-radius: 10px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    font-size: 13px;
}
.file-preview-info {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}
.file-preview-name {
    font-weight: 700;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 320px;
}
.file-preview-size {
    font-size: 11px;
    color: #64748b;
}
.file-preview-remove {
    background: none;
    border: none;
    color: #ef4444;
    cursor: pointer;
    font-size: 16px;
    padding: 2px 6px;
    border-radius: 6px;
    transition: background 0.15s ease;
}
.file-preview-remove:hover {
    background: #fee2e2;
}

/* Footer Actions */
.form-footer {
    padding: 24px 32px;
    background: #fafaf9;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 14px;
}
.btn-cancel {
    padding: 12px 24px;
    border-radius: 12px;
    border: 1px solid #d1d5db;
    background: #ffffff;
    color: #374151;
    font-weight: 700;
    font-size: 14px;
    text-decoration: none;
    transition: all 0.2s ease;
}
.btn-cancel:hover {
    background: #f3f4f6;
    color: #111827;
}
.btn-submit-ticket {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 28px;
    border-radius: 12px;
    background: linear-gradient(135deg, #fe5f04 0%, #ff8c3a 100%);
    color: #ffffff;
    font-weight: 800;
    font-size: 14px;
    border: none;
    cursor: pointer;
    box-shadow: 0 8px 18px rgba(254, 95, 4, 0.28);
    transition: all 0.2s ease;
}
.btn-submit-ticket:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 22px rgba(254, 95, 4, 0.35);
}

/* Overlay */
.support-process-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}
.support-process-card {
    background: #ffffff;
    border-radius: 20px;
    padding: 36px 32px;
    max-width: 440px;
    width: 90%;
    text-align: center;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}
.support-process-icon-wrap {
    position: relative;
    width: 68px;
    height: 68px;
    margin: 0 auto 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.support-process-spinner {
    position: absolute;
    inset: 0;
    border: 3px solid #ffedd5;
    border-top-color: #fe5f04;
    border-radius: 50%;
    animation: process-spin 1s linear infinite;
}
.support-process-icon {
    font-size: 28px;
    color: #fe5f04;
}
@keyframes process-spin {
    to { transform: rotate(360deg); }
}
.support-process-title {
    margin: 0 0 8px;
    font-size: 18px;
    font-weight: 800;
    color: #111827;
}
.support-process-subtitle {
    margin: 0 0 22px;
    font-size: 13px;
    color: #6b7280;
    line-height: 1.5;
}
.support-progress-wrapper {
    background: #f1f5f9;
    border-radius: 999px;
    height: 8px;
    overflow: hidden;
    margin-bottom: 10px;
}
.support-progress-fill {
    height: 100%;
    width: 0%;
    background: linear-gradient(90deg, #fe5f04 0%, #ff8c3a 100%);
    border-radius: 999px;
    transition: width 0.25s ease;
}
.support-progress-status {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
}
</style>
@endpush

@section('content')
<div class="support-create-page">

    {{-- Header Banner --}}
    <div class="create-header">
        <div>
            <div class="create-kicker">
                <i class="bi bi-ticket-perforated-fill"></i> New Request
            </div>
            <h1 class="create-title">Create Support Ticket</h1>
            <p class="create-subtitle">
                Select your category and subcategory, then provide the details of your request or issue.
            </p>
        </div>

        <a href="{{ route('support.index') }}" class="btn-back">
            <i class="bi bi-arrow-left"></i> Back to Tickets
        </a>
    </div>

    {{-- Main Form Card --}}
    <form id="createTicketForm" method="POST" action="{{ route('support.store') }}" enctype="multipart/form-data">
        @csrf

        {{-- Hidden Category & Subcategory IDs --}}
        <input type="hidden" name="account_support_category_id" id="selected_category_id" value="{{ old('account_support_category_id') }}">
        <input type="hidden" name="account_support_subcategory_id" id="selected_subcategory_id" value="{{ old('account_support_subcategory_id') }}">

        <div class="form-card">

            {{-- STEP 1: CATEGORY SELECTION --}}
            <div class="form-section">
                <div class="section-label">
                    <span class="section-badge">1</span>
                    <div>
                        <h2 class="section-title">Select Category</h2>
                    </div>
                </div>
                <p class="section-desc">Choose the broad service area or department for your ticket.</p>

                <div class="category-grid" id="categoryGrid">
                    @forelse($categories as $cat)
                        <div class="category-card" 
                             data-cat-id="{{ $cat->id }}" 
                             data-cat-name="{{ $cat->name }}"
                             onclick="selectCategory({{ $cat->id }})">
                            <div class="category-card-icon">
                                <i class="bi bi-folder2-open"></i>
                            </div>
                            <div class="category-card-body">
                                <div class="category-card-name">{{ $cat->name }}</div>
                                <div class="category-card-desc">
                                    {{ $cat->description ?: ($cat->activeSubcategories->count() . ' Subcategories available') }}
                                </div>
                            </div>
                            <div class="category-card-check">
                                <i class="bi bi-check-lg"></i>
                            </div>
                        </div>
                    @empty
                        <div class="empty-subcats-hint" style="grid-column: 1 / -1;">
                            No support categories found. Please contact the administrator.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- STEP 2: SUBCATEGORY SELECTION (SMALL BUTTONS / CHIPS) --}}
            <div class="form-section" id="subcategorySection">
                <div class="section-label">
                    <span class="section-badge">2</span>
                    <div>
                        <h2 class="section-title">Select Subcategory</h2>
                    </div>
                </div>
                <p class="section-desc">Choose the specific topic or request type (click any button below).</p>

                <div id="subcategoryChipsContainer" class="subcat-chips-wrapper">
                    {{-- Dynamically populated based on chosen category --}}
                </div>

                <div id="noSubcategoriesMessage" class="empty-subcats-hint">
                    <i class="bi bi-hand-index-thumb" style="font-size: 18px; display: inline-block; margin-right: 6px;"></i>
                    Please select a category above to view available subcategories.
                </div>

                <div id="assignmentNotice" class="subcat-assignment-notice">
                    <i class="bi bi-info-circle-fill"></i>
                    <span>Assigned to: <strong id="assignedUserName">-</strong> (Level 1 Auto-assigned)</span>
                </div>
            </div>

            {{-- STEP 3: TICKET DETAILS (SUBJECT, MESSAGE, ATTACHMENTS) --}}
            <div class="form-section">
                <div class="section-label">
                    <span class="section-badge">3</span>
                    <div>
                        <h2 class="section-title">Ticket Details</h2>
                    </div>
                </div>
                <p class="section-desc">Provide a descriptive subject, full explanation, and any supporting files.</p>

                {{-- Subject --}}
                <div class="form-group" style="margin-top: 18px;">
                    <label for="subject">
                        Subject <span class="required">*</span>
                    </label>
                    <input type="text" 
                           name="subject" 
                           id="subject" 
                           class="form-control-custom" 
                           placeholder="e.g. Need assistance with Domain transfer, SSL setup, or Hosting renewal..."
                           value="{{ old('subject') }}"
                           required>
                </div>

                {{-- Submit To (Person / Assignee) --}}
                <div class="form-group">
                    <label for="to_user_id">
                        Submit To / Assignee <span class="text-muted" style="font-weight: normal; font-size: 12px;">(Auto-assigned based on Subcategory, or choose specific team member)</span>
                    </label>
                    <select name="to_user_id" id="to_user_id" class="form-control-custom select2" style="width: 100%;">
                        <option value="">-- Auto Assign based on Subcategory Hierarchy --</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ old('to_user_id') == $u->id ? 'selected' : '' }}>
                                {{ $u->name }} ({{ $u->email }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Message with TinyMCE --}}
                <div class="form-group">
                    <label for="ticketMessage">
                        Message / Request Details <span class="required">*</span>
                    </label>
                    <textarea name="message" 
                              id="ticketMessage" 
                              rows="8" 
                              class="form-control-custom" 
                              placeholder="Please describe the issue or support request in full detail...">{{ old('message') }}</textarea>
                </div>

                {{-- Attachments --}}
                <div class="form-group" style="margin-bottom: 0;">
                    <label>
                        Attachments <span class="text-muted" style="font-weight: normal; font-size: 12px;">(Multiple files supported - PDF, Word, Excel, Images, Zip, Txt - Max 10MB each)</span>
                    </label>

                    {{-- Hidden file input --}}
                    <input type="file" 
                           name="attachments[]" 
                           id="attachmentsInput" 
                           multiple 
                           accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.zip,.txt" 
                           style="display: none;">

                    {{-- Modern Dropzone --}}
                    <div class="attachment-dropzone" id="attachmentDropzone" onclick="document.getElementById('attachmentsInput').click();">
                        <i class="bi bi-cloud-arrow-up-fill attachment-icon"></i>
                        <div class="attachment-text">Click to browse or drag and drop files here</div>
                        <div class="attachment-subtext">Images (PNG, JPG), Documents (PDF, DOCX, XLSX, TXT) or Archives (ZIP) up to 10MB</div>
                    </div>

                    {{-- Selected Files Preview --}}
                    <div id="filePreviewList" class="file-preview-list"></div>
                </div>

            </div>

            {{-- Form Footer Actions --}}
            <div class="form-footer">
                <a href="{{ route('support.index') }}" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-submit-ticket" id="btnSubmitTicket">
                    <i class="bi bi-send-fill"></i> Submit Support Ticket
                </button>
            </div>

        </div>
    </form>
</div>

{{-- Submission Progress Overlay --}}
<div id="supportProcessOverlay" class="support-process-overlay" style="display: none;">
    <div class="support-process-card">
        <div class="support-process-icon-wrap">
            <div class="support-process-spinner"></div>
            <i class="bi bi-envelope-paper-fill support-process-icon"></i>
        </div>
        <h4 id="processOverlayTitle" class="support-process-title">Sending Notification & Creating Ticket...</h4>
        <p id="processOverlaySubtitle" class="support-process-subtitle">Please wait while your support ticket is being submitted...</p>

        <div class="support-progress-wrapper">
            <div class="support-progress-bar">
                <div id="supportProgressFill" class="support-progress-fill"></div>
            </div>
            <div class="support-progress-status">
                <span id="supportProgressText">Uploading attachments...</span>
                <span id="supportProgressPercent">0%</span>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- TinyMCE --}}
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.5/tinymce.min.js" referrerpolicy="origin"></script>

<script>
    // Categories and users passed from backend
    const categoriesData = @json($categories);
    const usersData = @json($users->keyBy('id'));

    let selectedCategoryId = null;
    let selectedSubcategoryId = null;
    let selectedFiles = [];

    // Initialize TinyMCE
    document.addEventListener('DOMContentLoaded', function() {
        if (window.tinymce) {
            window.tinymce.init({
                selector: '#ticketMessage',
                height: 280,
                menubar: false,
                plugins: 'advlist autolink lists link charmap preview searchreplace visualblocks code fullscreen table',
                toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link blockquote table | removeformat',
                content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; font-size: 14px; color: #1e293b; line-height: 1.6; }',
                placeholder: 'Please describe your issue or request in detail...'
            });
        }

        // Initialize Select2 if available
        if (window.$ && $.fn.select2) {
            $('#to_user_id').select2({
                placeholder: '-- Auto Assign based on Subcategory Hierarchy --',
                allowClear: true
            });
        }

        // Restore previously selected category/subcategory (e.g. from validation errors)
        const oldCatId = document.getElementById('selected_category_id').value;
        const oldSubId = document.getElementById('selected_subcategory_id').value;
        if (oldCatId) {
            selectCategory(parseInt(oldCatId), oldSubId ? parseInt(oldSubId) : null);
        }
    });

    // Step 1: Category Selection
    function selectCategory(catId, restoreSubId = null) {
        selectedCategoryId = catId;
        document.getElementById('selected_category_id').value = catId;

        // Visual selection
        document.querySelectorAll('.category-card').forEach(card => {
            if (parseInt(card.getAttribute('data-cat-id')) === catId) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
        });

        // Load subcategories
        renderSubcategoryChips(catId, restoreSubId);
    }

    // Step 2: Render Subcategory Chips / Small Buttons
    function renderSubcategoryChips(catId, preselectSubId = null) {
        const cat = categoriesData.find(c => c.id === catId);
        const container = document.getElementById('subcategoryChipsContainer');
        const emptyMsg = document.getElementById('noSubcategoriesMessage');
        const notice = document.getElementById('assignmentNotice');

        container.innerHTML = '';
        notice.style.display = 'none';

        if (!cat || !cat.active_subcategories || cat.active_subcategories.length === 0) {
            emptyMsg.style.display = 'block';
            emptyMsg.innerHTML = '<i class="bi bi-exclamation-circle" style="color: #ea580c; margin-right: 6px;"></i> No active subcategories found under this category.';
            selectedSubcategoryId = null;
            document.getElementById('selected_subcategory_id').value = '';
            return;
        }

        emptyMsg.style.display = 'none';

        cat.active_subcategories.forEach((sub, index) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'subcat-chip';
            btn.setAttribute('data-subcat-id', sub.id);
            btn.innerHTML = `
                <i class="bi bi-tag-fill" style="font-size: 11px; opacity: 0.8;"></i>
                <span>${sub.name}</span>
                <i class="bi bi-check-lg subcat-check"></i>
            `;

            btn.onclick = function() {
                selectSubcategory(sub, cat);
            };

            container.appendChild(btn);

            // Preselect if requested or if only 1 subcategory exists
            if (preselectSubId && sub.id === preselectSubId) {
                selectSubcategory(sub, cat);
            }
        });

        // If only 1 subcategory exists, auto-select it for better UX
        if (!preselectSubId && cat.active_subcategories.length === 1) {
            selectSubcategory(cat.active_subcategories[0], cat);
        }
    }

    // Step 2 Selection Event
    function selectSubcategory(sub, cat) {
        selectedSubcategoryId = sub.id;
        document.getElementById('selected_subcategory_id').value = sub.id;

        // Visual highlight for the selected chip
        document.querySelectorAll('.subcat-chip').forEach(chip => {
            if (parseInt(chip.getAttribute('data-subcat-id')) === sub.id) {
                chip.classList.add('selected');
            } else {
                chip.classList.remove('selected');
            }
        });

        // Auto-assign Level 1 user from hierarchy if available
        let level1User = null;
        if (sub.hierarchy && Array.isArray(sub.hierarchy)) {
            for (let lvl of sub.hierarchy) {
                if (lvl.user_ids && Array.isArray(lvl.user_ids) && lvl.user_ids.length > 0) {
                    const firstUid = parseInt(lvl.user_ids[0]);
                    if (usersData[firstUid]) {
                        level1User = usersData[firstUid];
                        break;
                    }
                } else if (lvl.user_id) {
                    const uid = parseInt(lvl.user_id);
                    if (usersData[uid]) {
                        level1User = usersData[uid];
                        break;
                    }
                }
            }
        }

        const notice = document.getElementById('assignmentNotice');
        const assignedNameSpan = document.getElementById('assignedUserName');
        const toUserSelect = document.getElementById('to_user_id');

        if (level1User) {
            assignedNameSpan.innerText = `${level1User.name} (${level1User.email})`;
            notice.style.display = 'flex';

            // Pre-select in select2 / dropdown
            if (toUserSelect) {
                toUserSelect.value = level1User.id;
                if (window.$ && $.fn.select2) {
                    $('#to_user_id').val(level1User.id).trigger('change');
                }
            }
        } else {
            notice.style.display = 'none';
        }
    }

    // Attachments Handling
    const dropzone = document.getElementById('attachmentDropzone');
    const attachmentsInput = document.getElementById('attachmentsInput');
    const filePreviewList = document.getElementById('filePreviewList');

    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('dragover');
        });
    });

    dropzone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        if (dt && dt.files && dt.files.length > 0) {
            handleNewFiles(dt.files);
        }
    });

    attachmentsInput.addEventListener('change', (e) => {
        if (e.target.files && e.target.files.length > 0) {
            handleNewFiles(e.target.files);
        }
    });

    function handleNewFiles(files) {
        for (let i = 0; i < files.length; i++) {
            // Check duplicate by name and size
            const exists = selectedFiles.some(f => f.name === files[i].name && f.size === files[i].size);
            if (!exists) {
                selectedFiles.push(files[i]);
            }
        }
        updateFileInputAndPreview();
    }

    function removeFile(index) {
        selectedFiles.splice(index, 1);
        updateFileInputAndPreview();
    }

    function updateFileInputAndPreview() {
        // Sync with DataTransfer
        const dt = new DataTransfer();
        selectedFiles.forEach(file => dt.items.add(file));
        attachmentsInput.files = dt.files;

        // Render file previews
        filePreviewList.innerHTML = '';
        selectedFiles.forEach((file, index) => {
            const item = document.createElement('div');
            item.className = 'file-preview-item';

            const fileSizeFormatted = formatBytes(file.size);
            const iconClass = getFileIcon(file.name);

            item.innerHTML = `
                <div class="file-preview-info">
                    <i class="${iconClass}" style="font-size: 18px; color: #fe5f04;"></i>
                    <div>
                        <div class="file-preview-name" title="${file.name}">${file.name}</div>
                        <div class="file-preview-size">${fileSizeFormatted}</div>
                    </div>
                </div>
                <button type="button" class="file-preview-remove" onclick="removeFile(${index})" title="Remove file">
                    <i class="bi bi-x-circle-fill"></i>
                </button>
            `;

            filePreviewList.appendChild(item);
        });
    }

    function formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function getFileIcon(filename) {
        const ext = filename.split('.').pop().toLowerCase();
        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) return 'bi bi-file-earmark-image';
        if (['pdf'].includes(ext)) return 'bi bi-file-earmark-pdf';
        if (['doc', 'docx'].includes(ext)) return 'bi bi-file-earmark-word';
        if (['xls', 'xlsx', 'csv'].includes(ext)) return 'bi bi-file-earmark-excel';
        if (['zip', 'rar', '7z', 'tar', 'gz'].includes(ext)) return 'bi bi-file-earmark-zip';
        return 'bi bi-file-earmark-text';
    }

    // Submission Progress Overlay logic
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
                statusText.innerText = 'Uploading attachments & preparing...';
            } else if (currentProgress < 70) {
                currentProgress += Math.floor(Math.random() * 6) + 3;
                statusText.innerText = 'Sending email notification via SMTP...';
            } else if (currentProgress < 92) {
                currentProgress += Math.floor(Math.random() * 3) + 1;
                statusText.innerText = 'Finalizing support ticket record...';
            }

            if (currentProgress > 94) {
                currentProgress = 94;
            }

            fill.style.width = currentProgress + '%';
            percentText.innerText = currentProgress + '%';
        }, 250);
    }

    // Form submission validation
    document.getElementById('createTicketForm').addEventListener('submit', function(e) {
        if (window.tinymce) {
            window.tinymce.triggerSave();
        }

        const categoryId = document.getElementById('selected_category_id').value;
        const subcategoryId = document.getElementById('selected_subcategory_id').value;
        const subject = document.getElementById('subject')?.value;
        const message = document.getElementById('ticketMessage')?.value;

        if (!categoryId) {
            alert('Please select a Category first.');
            document.getElementById('categoryGrid').scrollIntoView({ behavior: 'smooth' });
            e.preventDefault();
            return;
        }

        if (!subcategoryId) {
            alert('Please select a Subcategory button.');
            document.getElementById('subcategorySection').scrollIntoView({ behavior: 'smooth' });
            e.preventDefault();
            return;
        }

        if (!subject || !subject.trim()) {
            alert('Please enter a ticket subject.');
            document.getElementById('subject').focus();
            e.preventDefault();
            return;
        }

        if (!message || !message.trim() || message === '<p></p>') {
            alert('Please enter a ticket message.');
            if (window.tinymce && window.tinymce.get('ticketMessage')) {
                window.tinymce.get('ticketMessage').focus();
            } else {
                document.getElementById('ticketMessage').focus();
            }
            e.preventDefault();
            return;
        }

        // Show progress overlay
        showProcessOverlay(
            "Creating Support Ticket & Sending Notification...",
            "Please wait while your support ticket is recorded and notification email is sent..."
        );
    });
</script>
@endpush
