@php
    $startDateDefault = \Carbon\Carbon::now()->toDateString();
    $endDateDefault = \Carbon\Carbon::now()->toDateString();
@endphp

<div class="hrms-card hrms-panel" style="grid-column: span 3; background: #ffffff; border-radius: 20px; border: 1px solid #e2e8f0; padding: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.03);">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 44px; height: 44px; border-radius: 12px; background: #fff7ed; color: #fe5f04; display: flex; align-items: center; justify-content: center; font-size: 22px; border: 1px solid #ffedd5;">
                <i class="bi bi-wallet2"></i>
            </div>
            <div>
                <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: #0f172a;">Petty Cash Account Report (DR & CR)</h3>
                <p style="margin: 2px 0 0; font-size: 13px; color: #64748b;">Ledger report showing Debit, Credit, and Running Balances.</p>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <button type="button" onclick="openPettyCashModal()" style="display: inline-flex; align-items: center; gap: 6px; background: linear-gradient(135deg, #fe5f04 0%, #ff8c3a 100%); color: #fff; font-weight: 700; font-size: 13px; padding: 9px 16px; border-radius: 10px; border: none; cursor: pointer; box-shadow: 0 4px 12px rgba(254,95,4,0.25);">
                <i class="bi bi-plus-circle-fill"></i> Add Transaction
            </button>
            <button type="button" onclick="exportPettyCashExcel()" style="display: inline-flex; align-items: center; gap: 6px; background: #10b981; color: #fff; font-weight: 700; font-size: 13px; padding: 9px 16px; border-radius: 10px; border: none; cursor: pointer; box-shadow: 0 4px 12px rgba(16,185,129,0.2);">
                <i class="bi bi-file-earmark-excel-fill"></i> Excel Export
            </button>
            <button type="button" onclick="exportPettyCashPdf()" style="display: inline-flex; align-items: center; gap: 6px; background: #0f172a; color: #fff; font-weight: 700; font-size: 13px; padding: 9px 16px; border-radius: 10px; border: none; cursor: pointer; box-shadow: 0 4px 12px rgba(15,23,42,0.2);">
                <i class="bi bi-file-earmark-pdf-fill"></i> PDF Export
            </button>
        </div>
    </div>

    <!-- Filters Bar -->
    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; margin-bottom: 24px; display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)) 130px; gap: 14px; align-items: end;">
        <div>
            <label style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px;">Start Date</label>
            <input type="date" id="pc_start_date" value="{{ $startDateDefault }}" class="form-control" style="width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 8px 12px; font-size: 13px; color: #0f172a; background: #fff;">
        </div>

        <div>
            <label style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px;">End Date</label>
            <input type="date" id="pc_end_date" value="{{ $endDateDefault }}" class="form-control" style="width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 8px 12px; font-size: 13px; color: #0f172a; background: #fff;">
        </div>

        <div>
            <button type="button" onclick="loadPettyCashReport()" style="width: 100%; background: #fe5f04; color: #fff; font-weight: 700; font-size: 13px; padding: 9px; border-radius: 8px; border: none; cursor: pointer;">
                <i class="bi bi-funnel-fill"></i> Filter
            </button>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 14px; padding: 16px;">
            <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #1d4ed8; letter-spacing: 0.5px;">Opening Balance</div>
            <div id="pc_ob_val" style="font-size: 20px; font-weight: 800; color: #1e40af; margin-top: 4px;">₹ 0.00</div>
            <div style="font-size: 11px; color: #60a5fa; margin-top: 2px;">Balance prior to start date</div>
        </div>

        <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 14px; padding: 16px;">
            <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #b91c1c; letter-spacing: 0.5px;">Total Debit (DR)</div>
            <div id="pc_dr_val" style="font-size: 20px; font-weight: 800; color: #991b1b; margin-top: 4px;">₹ 0.00</div>
            <div style="font-size: 11px; color: #f87171; margin-top: 2px;">Expenses / Cash Out</div>
        </div>

        <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 14px; padding: 16px;">
            <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #047857; letter-spacing: 0.5px;">Total Credit (CR)</div>
            <div id="pc_cr_val" style="font-size: 20px; font-weight: 800; color: #065f46; margin-top: 4px;">₹ 0.00</div>
            <div style="font-size: 11px; color: #34d399; margin-top: 2px;">Cash In Hand & Deposits</div>
        </div>

        <div style="background: #fff7ed; border: 1px solid #ffedd5; border-radius: 14px; padding: 16px;">
            <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #c2410c; letter-spacing: 0.5px;">Closing Balance</div>
            <div id="pc_cb_val" style="font-size: 20px; font-weight: 800; color: #9a3412; margin-top: 4px;">₹ 0.00</div>
            <div style="font-size: 11px; color: #fb923c; margin-top: 2px;">OB + CR - DR</div>
        </div>
    </div>

    <!-- Data Table -->
    <div style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 14px;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                    <th style="padding: 12px 16px; font-weight: 700; color: #475569; width: 110px;">Date</th>
                    <th style="padding: 12px 16px; font-weight: 700; color: #475569; width: 130px;">Voucher / Ref</th>
                    <th style="padding: 12px 16px; font-weight: 700; color: #475569; width: 160px;">Name (Person / Vendor)</th>
                    <th style="padding: 12px 16px; font-weight: 700; color: #475569;">Particulars / Narration</th>
                    <th style="padding: 12px 16px; font-weight: 700; color: #b91c1c; text-align: right; width: 130px;">Debit (DR)</th>
                    <th style="padding: 12px 16px; font-weight: 700; color: #047857; text-align: right; width: 130px;">Credit (CR)</th>
                    <th style="padding: 12px 16px; font-weight: 700; color: #475569; text-align: right; width: 150px;">Balance</th>
                    <th style="padding: 12px 16px; font-weight: 700; color: #475569; text-align: center; width: 90px;">Actions</th>
                </tr>
            </thead>
            <tbody id="pc_table_body">
                <tr>
                    <td colspan="8" style="padding: 30px; text-align: center; color: #94a3b8;">
                        Loading Petty Cash Account data...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ADD PETTY CASH TRANSACTION MODAL -->
<div id="addPettyCashModal" class="support-modal" style="position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); display: none; justify-content: center; align-items: center; z-index: 9999;">
    <div style="background: #ffffff; border-radius: 20px; width: 540px; max-width: calc(100vw - 32px); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden;">
        <form id="addPettyCashForm" method="POST" action="{{ route('hrms.petty-cash.store') }}">
            @csrf
            <div style="background: #fafbfe; border-bottom: 1px solid #e5e7eb; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center;">
                <h4 style="margin: 0; font-size: 17px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-wallet-fill text-orange"></i> Record Petty Cash Transaction
                </h4>
                <button type="button" onclick="closePettyCashModal()" style="background: none; border: none; font-size: 24px; color: #94a3b8; cursor: pointer;">&times;</button>
            </div>

            <div style="padding: 24px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">Transaction Date <span style="color: #ef4444;">*</span></label>
                        <input type="date" name="entry_date" value="{{ date('Y-m-d') }}" class="form-control" required style="width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px 12px; font-size: 14px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">Transaction Type <span style="color: #ef4444;">*</span></label>
                        <select name="type" class="form-control" required style="width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px 12px; font-size: 14px;">
                            <option value="cash_in_hand">Cash In Hand (Initial Allocation / Top-Up)</option>
                            <option value="credit">Credit (CR - Cash IN)</option>
                            <option value="debit">Debit (DR - Expense / Cash OUT)</option>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">Name (Paid To / Received From)</label>
                    <input type="text" name="name" placeholder="Enter person or vendor name..." class="form-control" style="width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px 12px; font-size: 14px;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">Voucher / Ref No</label>
                        <input type="text" name="voucher_no" placeholder="e.g. VCH-00123" class="form-control" style="width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px 12px; font-size: 14px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">Amount (₹) <span style="color: #ef4444;">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" placeholder="0.00" class="form-control" required style="width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px 12px; font-size: 14px;">
                    </div>
                </div>

                <div style="margin-bottom: 0;">
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">Particulars / Narration <span style="color: #ef4444;">*</span></label>
                    <textarea name="particulars" rows="3" placeholder="Enter reason or details of expense/top-up..." class="form-control" required style="width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px 12px; font-size: 14px;"></textarea>
                </div>
            </div>

            <div style="border-top: 1px solid #e5e7eb; padding: 16px 24px; background: #fafbfe; display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" onclick="closePettyCashModal()" style="background: #ffffff; border: 1px solid #cbd5e1; color: #475569; font-weight: 700; padding: 9px 18px; border-radius: 8px; cursor: pointer;">Cancel</button>
                <button type="submit" id="pc_submit_btn" style="background: linear-gradient(135deg, #fe5f04 0%, #ff8c3a 100%); color: #ffffff; font-weight: 700; border: none; padding: 9px 20px; border-radius: 8px; cursor: pointer;">Save Transaction</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT PETTY CASH TRANSACTION MODAL -->
<div id="editPettyCashModal" class="support-modal" style="position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); display: none; justify-content: center; align-items: center; z-index: 9999;">
    <div style="background: #ffffff; border-radius: 20px; width: 540px; max-width: calc(100vw - 32px); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden;">
        <form id="editPettyCashForm" method="POST" action="">
            @csrf
            @method('PUT')
            <div style="background: #fafbfe; border-bottom: 1px solid #e5e7eb; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center;">
                <h4 style="margin: 0; font-size: 17px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-pencil-square text-orange"></i> Edit Petty Cash Transaction
                </h4>
                <button type="button" onclick="closeEditPettyCashModal()" style="background: none; border: none; font-size: 24px; color: #94a3b8; cursor: pointer;">&times;</button>
            </div>

            <div style="padding: 24px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">Transaction Date <span style="color: #ef4444;">*</span></label>
                        <input type="date" id="edit_pc_entry_date" name="entry_date" class="form-control" required style="width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px 12px; font-size: 14px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">Transaction Type <span style="color: #ef4444;">*</span></label>
                        <select id="edit_pc_type" name="type" class="form-control" required style="width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px 12px; font-size: 14px;">
                            <option value="cash_in_hand">Cash In Hand (Initial Allocation / Top-Up)</option>
                            <option value="credit">Credit (CR - Cash IN)</option>
                            <option value="debit">Debit (DR - Expense / Cash OUT)</option>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">Name (Paid To / Received From)</label>
                    <input type="text" id="edit_pc_name" name="name" placeholder="Enter person or vendor name..." class="form-control" style="width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px 12px; font-size: 14px;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">Voucher / Ref No</label>
                        <input type="text" id="edit_pc_voucher_no" name="voucher_no" placeholder="e.g. VCH-00123" class="form-control" style="width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px 12px; font-size: 14px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">Amount (₹) <span style="color: #ef4444;">*</span></label>
                        <input type="number" step="0.01" min="0.01" id="edit_pc_amount" name="amount" placeholder="0.00" class="form-control" required style="width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px 12px; font-size: 14px;">
                    </div>
                </div>

                <div style="margin-bottom: 0;">
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">Particulars / Narration <span style="color: #ef4444;">*</span></label>
                    <textarea id="edit_pc_particulars" name="particulars" rows="3" placeholder="Enter reason or details of expense/top-up..." class="form-control" required style="width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px 12px; font-size: 14px;"></textarea>
                </div>
            </div>

            <div style="border-top: 1px solid #e5e7eb; padding: 16px 24px; background: #fafbfe; display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" onclick="closeEditPettyCashModal()" style="background: #ffffff; border: 1px solid #cbd5e1; color: #475569; font-weight: 700; padding: 9px 18px; border-radius: 8px; cursor: pointer;">Cancel</button>
                <button type="submit" id="edit_pc_submit_btn" style="background: linear-gradient(135deg, #fe5f04 0%, #ff8c3a 100%); color: #ffffff; font-weight: 700; border: none; padding: 9px 20px; border-radius: 8px; cursor: pointer;">Update Transaction</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const addForm = document.getElementById('addPettyCashForm');
        if (addForm) {
            addForm.addEventListener('submit', function(e) {
                const btn = document.getElementById('pc_submit_btn');
                if (btn) {
                    if (btn.disabled) {
                        e.preventDefault();
                        return false;
                    }
                    btn.disabled = true;
                    btn.style.opacity = '0.7';
                    btn.style.cursor = 'not-allowed';
                    btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Saving...';
                }
            });
        }

        const editForm = document.getElementById('editPettyCashForm');
        if (editForm) {
            editForm.addEventListener('submit', function(e) {
                const btn = document.getElementById('edit_pc_submit_btn');
                if (btn) {
                    if (btn.disabled) {
                        e.preventDefault();
                        return false;
                    }
                    btn.disabled = true;
                    btn.style.opacity = '0.7';
                    btn.style.cursor = 'not-allowed';
                    btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Updating...';
                }
            });
        }
    });

    window.pettyCashTxMap = {};

    function openPettyCashModal() {
        document.getElementById('addPettyCashModal').style.display = 'flex';
    }

    function closePettyCashModal() {
        document.getElementById('addPettyCashModal').style.display = 'none';
        const btn = document.getElementById('pc_submit_btn');
        if (btn) {
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.cursor = 'pointer';
            btn.innerHTML = 'Save Transaction';
        }
    }

    function openEditPettyCashModalById(id) {
        const tx = window.pettyCashTxMap[id];
        if (!tx) return;
        const form = document.getElementById('editPettyCashForm');
        form.action = '/hrms/petty-cash/' + tx.id;
        document.getElementById('edit_pc_entry_date').value = tx.entry_date || '';
        document.getElementById('edit_pc_type').value = tx.type || 'debit';
        document.getElementById('edit_pc_name').value = (tx.name === '-' || !tx.name) ? '' : tx.name;
        document.getElementById('edit_pc_voucher_no').value = (tx.voucher_no === '-' || !tx.voucher_no) ? '' : tx.voucher_no;
        document.getElementById('edit_pc_amount').value = tx.amount || '';
        document.getElementById('edit_pc_particulars').value = tx.particulars || '';
        document.getElementById('editPettyCashModal').style.display = 'flex';
    }

    function openEditPettyCashModal(id, date, type, name, voucher, amount, particulars) {
        const form = document.getElementById('editPettyCashForm');
        form.action = '/hrms/petty-cash/' + id;
        document.getElementById('edit_pc_entry_date').value = date || '';
        document.getElementById('edit_pc_type').value = type || 'debit';
        document.getElementById('edit_pc_name').value = (name === '-' || !name) ? '' : name;
        document.getElementById('edit_pc_voucher_no').value = (voucher === '-' || !voucher) ? '' : voucher;
        document.getElementById('edit_pc_amount').value = amount || '';
        document.getElementById('edit_pc_particulars').value = particulars || '';
        document.getElementById('editPettyCashModal').style.display = 'flex';
    }

    function closeEditPettyCashModal() {
        document.getElementById('editPettyCashModal').style.display = 'none';
        const btn = document.getElementById('edit_pc_submit_btn');
        if (btn) {
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.cursor = 'pointer';
            btn.innerHTML = 'Update Transaction';
        }
    }

    const addModalEl = document.getElementById('addPettyCashModal');
    if (addModalEl) {
        addModalEl.addEventListener('click', function(e) {
            if (e.target === this) closePettyCashModal();
        });
    }

    const editModalEl = document.getElementById('editPettyCashModal');
    if (editModalEl) {
        editModalEl.addEventListener('click', function(e) {
            if (e.target === this) closeEditPettyCashModal();
        });
    }

    function escapeHtml(str) {
        if (!str || str === '-') return '-';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatINR(val) {
        return new Intl.NumberFormat('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val);
    }

    function loadPettyCashReport() {
        const startDate = document.getElementById('pc_start_date').value;
        const endDate = document.getElementById('pc_end_date').value;

        const tableBody = document.getElementById('pc_table_body');
        tableBody.innerHTML = `
            <tr>
                <td colspan="8" style="padding: 30px; text-align: center; color: #64748b;">
                    <i class="bi bi-arrow-repeat spin" style="font-size: 24px; display: block; margin-bottom: 8px;"></i>
                    Fetching report data...
                </td>
            </tr>
        `;

        const url = `/hrms/petty-cash?start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}`;

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(res => {
            if (!res.success) return;
            const data = res.data;

            document.getElementById('pc_ob_val').innerText = '₹ ' + formatINR(data.openingBalance);
            document.getElementById('pc_dr_val').innerText = '₹ ' + formatINR(data.totalDebit);
            document.getElementById('pc_cr_val').innerText = '₹ ' + formatINR(data.totalCredit);
            document.getElementById('pc_cb_val').innerText = '₹ ' + formatINR(data.closingBalance);

            window.pettyCashTxMap = {};
            let html = '';

            // Opening Balance Row
            html += `
                <tr style="background: #f8fafc; font-weight: 700; border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 12px 16px;">${startDate}</td>
                    <td style="padding: 12px 16px; text-align: center;">-</td>
                    <td style="padding: 12px 16px; text-align: center;">-</td>
                    <td style="padding: 12px 16px; color: #1d4ed8;">OPENING BALANCE B/F</td>
                    <td style="padding: 12px 16px; text-align: right;">-</td>
                    <td style="padding: 12px 16px; text-align: right;">-</td>
                    <td style="padding: 12px 16px; text-align: right; color: #1d4ed8;">₹ ${formatINR(data.openingBalance)}</td>
                    <td style="padding: 12px 16px; text-align: center;">-</td>
                </tr>
            `;

            if (data.transactions.length === 0) {
                html += `
                    <tr>
                        <td colspan="8" style="padding: 30px; text-align: center; color: #94a3b8;">
                            No transactions found for the selected date range.
                        </td>
                    </tr>
                `;
            } else {
                data.transactions.forEach(tx => {
                    window.pettyCashTxMap[tx.id] = tx;
                    const safeName = escapeHtml(tx.name);
                    const safeVoucher = escapeHtml(tx.voucher_no);
                    const safeParticulars = escapeHtml(tx.particulars);
                    html += `
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 12px 16px; color: #334155;">${tx.entry_date_formatted}</td>
                            <td style="padding: 12px 16px; color: #475569; font-family: monospace;">${safeVoucher}</td>
                            <td style="padding: 12px 16px; color: #0f172a; font-weight: 600;">${safeName}</td>
                            <td style="padding: 12px 16px; color: #334155;">${safeParticulars}</td>
                            <td style="padding: 12px 16px; text-align: right; color: #b91c1c; font-weight: 700;">
                                ${tx.debit > 0 ? '₹ ' + formatINR(tx.debit) : '-'}
                            </td>
                            <td style="padding: 12px 16px; text-align: right; color: #047857; font-weight: 700;">
                                ${tx.credit > 0 ? '₹ ' + formatINR(tx.credit) : '-'}
                            </td>
                            <td style="padding: 12px 16px; text-align: right; font-weight: 800; color: #0f172a;">
                                ₹ ${formatINR(tx.running_balance)}
                            </td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <button type="button" onclick="openEditPettyCashModalById(${tx.id})" style="background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; font-size: 12px; font-weight: 700; padding: 5px 10px; border-radius: 6px; cursor: pointer;" title="Edit Transaction">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </button>
                            </td>
                        </tr>
                    `;
                });
            }

            // Totals Row
            html += `
                <tr style="background: #f1f5f9; font-weight: 700; border-top: 2px solid #cbd5e1; border-bottom: 1px solid #cbd5e1;">
                    <td colspan="4" style="padding: 12px 16px; text-align: right; color: #334155; text-transform: uppercase;">Total Debit & Credit</td>
                    <td style="padding: 12px 16px; text-align: right; color: #b91c1c; font-size: 14px;">₹ ${formatINR(data.totalDebit)}</td>
                    <td style="padding: 12px 16px; text-align: right; color: #047857; font-size: 14px;">₹ ${formatINR(data.totalCredit)}</td>
                    <td style="padding: 12px 16px; text-align: right;">-</td>
                    <td style="padding: 12px 16px;"></td>
                </tr>
                <tr style="background: #fff7ed; font-weight: 800; border-bottom: 2px solid #ffedd5;">
                    <td colspan="4" style="padding: 12px 16px; text-align: right; color: #c2410c; text-transform: uppercase;">Closing Balance C/F</td>
                    <td colspan="2" style="padding: 12px 16px; text-align: right; color: #9a3412;">Statement Net Balance</td>
                    <td style="padding: 12px 16px; text-align: right; color: #c2410c; font-size: 15px;">₹ ${formatINR(data.closingBalance)}</td>
                    <td style="padding: 12px 16px;"></td>
                </tr>
            `;

            tableBody.innerHTML = html;
        })
        .catch(err => {
            console.error(err);
            tableBody.innerHTML = `
                <tr>
                    <td colspan="8" style="padding: 30px; text-align: center; color: #ef4444;">
                        Failed to load Petty Cash report. Please refresh and try again.
                    </td>
                </tr>
            `;
        });
    }

    function exportPettyCashExcel() {
        const startDate = document.getElementById('pc_start_date').value;
        const endDate = document.getElementById('pc_end_date').value;
        window.location.href = `/hrms/petty-cash/export-excel?start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}`;
    }

    function exportPettyCashPdf() {
        const startDate = document.getElementById('pc_start_date').value;
        const endDate = document.getElementById('pc_end_date').value;
        window.open(`/hrms/petty-cash/export-pdf?start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}`, '_blank');
    }

    document.addEventListener('DOMContentLoaded', function() {
        loadPettyCashReport();
    });
</script>
@endpush
