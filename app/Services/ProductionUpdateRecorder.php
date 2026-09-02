<?php

namespace App\Services;

use App\Models\ProductionInitiation;
use App\Models\ProjectUpdate;
use App\Models\User;
use Illuminate\Support\Carbon;

class ProductionUpdateRecorder
{
    /**
     * Record Production Initiation update with form questions and submitted answers.
     */
    public function recordProductionInitiation(ProductionInitiation $initiation, ?User $actor = null, ?Carbon $customTimestamp = null): ProjectUpdate
    {
        $actor = $actor ?: ($initiation->initiatedBy ?: auth()->user());
        $actorName = $actor?->name ?: 'System';
        $timestamp = $customTimestamp ?: Carbon::now();
        $nowFormatted = $timestamp->format('d M Y, h:i A');

        $initiation->loadMissing(['department', 'lead', 'product']);
        $departmentName = $initiation->department?->name ?: 'N/A';
        $productName = $initiation->product_name ?: ($initiation->product?->product_name ?: 'N/A');
        $workingDays = $initiation->total_working_days ? $initiation->total_working_days . ' Days' : 'Not specified';
        $uiAvailable = $initiation->ui_available ? 'Yes' : 'No';
        $requirements = $initiation->requirements ?: 'None provided';

        $attachmentHtml = '';
        if ($initiation->attachment_path) {
            $attachmentUrl = $initiation->attachment_url;
            $attachmentName = htmlspecialchars($initiation->attachment_name ?: 'View Attachment');
            $attachmentHtml = <<<HTML
            <div style="margin-top: 8px;">
                <a href="{$attachmentUrl}" target="_blank" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; border-radius: 6px; font-weight: 600; font-size: 13px; text-decoration: none;">
                    <span>📎</span> <span>{$attachmentName}</span>
                </a>
            </div>
HTML;
        }

        $customFieldsHtml = $this->renderCustomFormFieldsHtml($initiation->custom_form_data ?? []);

        $content = <<<HTML
<div style="display: flex; flex-direction: column; gap: 14px; font-family: inherit;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; padding-bottom: 12px; border-bottom: 1px solid #e2e8f0;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                🚀 Production Initiation
            </span>
            <span style="color: #0f172a; font-size: 14px; font-weight: 700;">Initiated by {$actorName}</span>
        </div>
        <span style="color: #64748b; font-size: 12px; font-weight: 500;">📅 {$nowFormatted}</span>
    </div>

    <!-- Basic Initiation Details -->
    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px;">
        <div style="font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px;">
            📌 Initiation Details
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
            <div>
                <span style="display: block; font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase;">Product Name</span>
                <span style="display: block; font-size: 13px; color: #0f172a; font-weight: 600; margin-top: 2px;">{$productName}</span>
            </div>
            <div>
                <span style="display: block; font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase;">Department</span>
                <span style="display: block; font-size: 13px; color: #0f172a; font-weight: 600; margin-top: 2px;">{$departmentName}</span>
            </div>
            <div>
                <span style="display: block; font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase;">Working Days</span>
                <span style="display: block; font-size: 13px; color: #0f172a; font-weight: 600; margin-top: 2px;">{$workingDays}</span>
            </div>
            <div>
                <span style="display: block; font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase;">UI Available</span>
                <span style="display: block; font-size: 13px; color: #0f172a; font-weight: 600; margin-top: 2px;">{$uiAvailable}</span>
            </div>
        </div>

        <div style="margin-top: 12px; padding-top: 10px; border-top: 1px dashed #cbd5e1;">
            <span style="display: block; font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase;">Requirements / Scope</span>
            <div style="font-size: 13px; color: #334155; margin-top: 4px; line-height: 1.5; white-space: pre-wrap;">{$requirements}</div>
            {$attachmentHtml}
        </div>
    </div>

    <!-- Custom Form Questions & Responses -->
    {$customFieldsHtml}
</div>
HTML;

        return $this->createProjectUpdate($initiation, $content, $actor, $customTimestamp);
    }

    /**
     * Record OVP Review (Approve or Reject) with reviewer details and form responses.
     */
    public function recordOvpReview(ProductionInitiation $initiation, string $decision, ?string $remarks = null, ?User $actor = null, ?Carbon $customTimestamp = null): ProjectUpdate
    {
        $actor = $actor ?: ($initiation->reviewedBy ?: auth()->user());
        $actorName = $actor?->name ?: 'System';
        $timestamp = $customTimestamp ?: Carbon::now();
        $nowFormatted = $timestamp->format('d M Y, h:i A');
        $isApproval = strtolower($decision) === 'approval' || strtolower($decision) === 'approved';

        $badgeText = $isApproval ? '✅ OVP Review - Approved' : '❌ OVP Review - Rejected';
        $badgeBg = $isApproval ? '#f0fdf4' : '#fef2f2';
        $badgeBorder = $isApproval ? '#bbf7d0' : '#fecaca';
        $badgeColor = $isApproval ? '#166534' : '#991b1b';

        $remarksHtml = '';
        if ($remarks) {
            $safeRemarks = nl2br(htmlspecialchars(trim($remarks)));
            $remarksTitle = $isApproval ? 'OVP Review Remarks' : 'Rejection Reason / Remarks';
            $remarksHtml = <<<HTML
            <div style="margin-top: 12px; padding: 12px; background: {$badgeBg}; border: 1px solid {$badgeBorder}; border-radius: 8px;">
                <span style="display: block; font-size: 11px; color: {$badgeColor}; font-weight: 700; text-transform: uppercase;">{$remarksTitle}</span>
                <div style="font-size: 13px; color: #1e293b; margin-top: 4px; line-height: 1.5;">{$safeRemarks}</div>
            </div>
HTML;
        }

        $customFieldsHtml = '';
        if ($isApproval && !empty($initiation->custom_form_data)) {
            $customFieldsHtml = $this->renderCustomFormFieldsHtml($initiation->custom_form_data, '📋 OVP Form Details & Responses');
        }

        $content = <<<HTML
<div style="display: flex; flex-direction: column; gap: 14px; font-family: inherit;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; padding-bottom: 12px; border-bottom: 1px solid #e2e8f0;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="background: {$badgeBg}; color: {$badgeColor}; border: 1px solid {$badgeBorder}; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                {$badgeText}
            </span>
            <span style="color: #0f172a; font-size: 14px; font-weight: 700;">Reviewed by {$actorName}</span>
        </div>
        <span style="color: #64748b; font-size: 12px; font-weight: 500;">📅 {$nowFormatted}</span>
    </div>

    {$remarksHtml}
    {$customFieldsHtml}
</div>
HTML;

        return $this->createProjectUpdate($initiation, $content, $actor, $customTimestamp);
    }

    /**
     * Record Production Approval (Approve or Reject) with reviewer, remarks, and budget details.
     */
    public function recordProductionApproval(ProductionInitiation $initiation, string $decision, ?string $remarks = null, ?User $actor = null, ?array $budgetData = null, ?Carbon $customTimestamp = null): ProjectUpdate
    {
        $actor = $actor ?: ($initiation->productionApprovalReviewedBy ?: auth()->user());
        $actorName = $actor?->name ?: 'System';
        $timestamp = $customTimestamp ?: Carbon::now();
        $nowFormatted = $timestamp->format('d M Y, h:i A');
        $isApproval = strtolower($decision) === 'approval' || strtolower($decision) === 'approved';

        $badgeText = $isApproval ? '✅ Production Approval - Approved' : '❌ Production Approval - Rejected';
        $badgeBg = $isApproval ? '#f0fdf4' : '#fef2f2';
        $badgeBorder = $isApproval ? '#bbf7d0' : '#fecaca';
        $badgeColor = $isApproval ? '#166534' : '#991b1b';

        $budgetHtml = '';
        if ($isApproval && !empty($budgetData) && isset($budgetData['lead_budget_amount'])) {
            $formattedAmount = '₹' . number_format((float) $budgetData['lead_budget_amount'], 2);
            $budgetType = htmlspecialchars($budgetData['budget_amount_type'] ?? 'Standard');
            $budgetHtml = <<<HTML
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-top: 10px; padding: 10px 14px; background: #fff; border: 1px solid #bbf7d0; border-radius: 8px;">
                <div>
                    <span style="display: block; font-size: 11px; color: #166534; font-weight: 600; text-transform: uppercase;">Approved Budget Amount</span>
                    <span style="display: block; font-size: 15px; color: #166534; font-weight: 700; margin-top: 2px;">{$formattedAmount}</span>
                </div>
                <div>
                    <span style="display: block; font-size: 11px; color: #166534; font-weight: 600; text-transform: uppercase;">Budget Frequency / Type</span>
                    <span style="display: block; font-size: 14px; color: #0f172a; font-weight: 600; margin-top: 2px;">{$budgetType}</span>
                </div>
            </div>
HTML;
        }

        $safeRemarks = nl2br(htmlspecialchars(trim((string) $remarks)));
        $remarksTitle = $isApproval ? 'Approval Remarks' : 'Rejection Remarks';

        $content = <<<HTML
<div style="display: flex; flex-direction: column; gap: 14px; font-family: inherit;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; padding-bottom: 12px; border-bottom: 1px solid #e2e8f0;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="background: {$badgeBg}; color: {$badgeColor}; border: 1px solid {$badgeBorder}; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                {$badgeText}
            </span>
            <span style="color: #0f172a; font-size: 14px; font-weight: 700;">Reviewed by {$actorName}</span>
        </div>
        <span style="color: #64748b; font-size: 12px; font-weight: 500;">📅 {$nowFormatted}</span>
    </div>

    <div style="background: {$badgeBg}; border: 1px solid {$badgeBorder}; border-radius: 10px; padding: 14px;">
        <span style="display: block; font-size: 11px; color: {$badgeColor}; font-weight: 700; text-transform: uppercase;">{$remarksTitle}</span>
        <div style="font-size: 13px; color: #1e293b; margin-top: 4px; line-height: 1.5;">{$safeRemarks}</div>
        {$budgetHtml}
    </div>
</div>
HTML;

        return $this->createProjectUpdate($initiation, $content, $actor, $customTimestamp);
    }

    /**
     * Record Team Lead (TL) Allocation update.
     */
    public function recordTlAllocation(ProductionInitiation $initiation, array $tlUserIds, ?User $actor = null, ?Carbon $customTimestamp = null): ProjectUpdate
    {
        $actor = $actor ?: ($initiation->projectAllocatedBy ?: auth()->user());
        $actorName = $actor?->name ?: 'System';
        $timestamp = $customTimestamp ?: Carbon::now();
        $nowFormatted = $timestamp->format('d M Y, h:i A');

        $allocatedTls = User::whereIn('id', $tlUserIds)->get(['id', 'name', 'email']);
        $tlListHtml = '';
        foreach ($allocatedTls as $tl) {
            $name = htmlspecialchars($tl->name);
            $email = htmlspecialchars($tl->email ?: '');
            $emailSnippet = $email ? " <span style=\"color:#64748b; font-size:12px; font-weight:normal;\">({$email})</span>" : '';
            $tlListHtml .= <<<HTML
            <li style="display: flex; align-items: center; gap: 8px; padding: 6px 10px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px; font-weight: 600; color: #0f172a;">
                <span style="color: #7c3aed;">👤</span> <span>{$name}</span>{$emailSnippet}
            </li>
HTML;
        }

        $content = <<<HTML
<div style="display: flex; flex-direction: column; gap: 14px; font-family: inherit;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; padding-bottom: 12px; border-bottom: 1px solid #e2e8f0;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="background: #f5f3ff; color: #7c3aed; border: 1px solid #ddd6fe; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                👥 Team Lead Allocation
            </span>
            <span style="color: #0f172a; font-size: 14px; font-weight: 700;">Allocated by {$actorName}</span>
        </div>
        <span style="color: #64748b; font-size: 12px; font-weight: 500;">📅 {$nowFormatted}</span>
    </div>

    <div style="background: #faf5ff; border: 1px solid #e9d5ff; border-radius: 10px; padding: 14px;">
        <div style="font-size: 12px; font-weight: 700; color: #6b21a8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px;">
            📌 Allocated Team Lead(s)
        </div>
        <ul style="list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 8px;">
            {$tlListHtml}
        </ul>
    </div>
</div>
HTML;

        return $this->createProjectUpdate($initiation, $content, $actor, $customTimestamp);
    }

    /**
     * Record Team / Employee Member Allocation update.
     */
    public function recordTeamAllocation(ProductionInitiation $initiation, array $employeeUserIds, ?User $actor = null, ?Carbon $customTimestamp = null): ProjectUpdate
    {
        $actor = $actor ?: ($initiation->employeeAllocatedBy ?: auth()->user());
        $actorName = $actor?->name ?: 'Team Lead';
        $timestamp = $customTimestamp ?: Carbon::now();
        $nowFormatted = $timestamp->format('d M Y, h:i A');

        $allocatedEmployees = User::whereIn('id', $employeeUserIds)->get(['id', 'name', 'email']);
        $employeeListHtml = '';
        foreach ($allocatedEmployees as $emp) {
            $name = htmlspecialchars($emp->name);
            $email = htmlspecialchars($emp->email ?: '');
            $emailSnippet = $email ? " <span style=\"color:#64748b; font-size:12px; font-weight:normal;\">({$email})</span>" : '';
            $employeeListHtml .= <<<HTML
            <li style="display: flex; align-items: center; gap: 8px; padding: 6px 10px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px; font-weight: 600; color: #0f172a;">
                <span style="color: #0284c7;">👨‍💻</span> <span>{$name}</span>{$emailSnippet}
            </li>
HTML;
        }

        $content = <<<HTML
<div style="display: flex; flex-direction: column; gap: 14px; font-family: inherit;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; padding-bottom: 12px; border-bottom: 1px solid #e2e8f0;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="background: #f0f9ff; color: #0369a1; border: 1px solid #bae6fd; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                👨‍💻 Team Member Allocation
            </span>
            <span style="color: #0f172a; font-size: 14px; font-weight: 700;">Allocated by {$actorName}</span>
        </div>
        <span style="color: #64748b; font-size: 12px; font-weight: 500;">📅 {$nowFormatted}</span>
    </div>

    <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 10px; padding: 14px;">
        <div style="font-size: 12px; font-weight: 700; color: #0369a1; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px;">
            📌 Assigned Team Member(s)
        </div>
        <ul style="list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 8px;">
            {$employeeListHtml}
        </ul>
    </div>
</div>
HTML;

        return $this->createProjectUpdate($initiation, $content, $actor, $customTimestamp);
    }

    /**
     * Record OVP Executive Allocation update.
     */
    public function recordOvpAllocation(ProductionInitiation $initiation, int $executiveUserId, ?User $actor = null, ?Carbon $customTimestamp = null): ProjectUpdate
    {
        $actor = $actor ?: ($initiation->ovpAllocatedBy ?: auth()->user());
        $actorName = $actor?->name ?: 'System';
        $timestamp = $customTimestamp ?: Carbon::now();
        $nowFormatted = $timestamp->format('d M Y, h:i A');

        $executive = User::find($executiveUserId);
        $execName = htmlspecialchars($executive?->name ?: 'Executive');
        $execEmail = htmlspecialchars($executive?->email ?: '');

        $content = <<<HTML
<div style="display: flex; flex-direction: column; gap: 14px; font-family: inherit;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; padding-bottom: 12px; border-bottom: 1px solid #e2e8f0;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                📋 OVP Executive Allocation
            </span>
            <span style="color: #0f172a; font-size: 14px; font-weight: 700;">Allocated by {$actorName}</span>
        </div>
        <span style="color: #64748b; font-size: 12px; font-weight: 500;">📅 {$nowFormatted}</span>
    </div>

    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px;">
        <div style="font-size: 12px; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">
            📌 Assigned OVP Executive
        </div>
        <div style="display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; color: #0f172a;">
            <span>👤 {$execName}</span>
            <span style="color: #64748b; font-size: 12px; font-weight: normal;">({$execEmail})</span>
        </div>
    </div>
</div>
HTML;

        return $this->createProjectUpdate($initiation, $content, $actor, $customTimestamp);
    }

    /**
     * Helper to render custom form questions and submitted answers into a clean, modern HTML table/card.
     */
    private function renderCustomFormFieldsHtml(array $customFormData, string $sectionTitle = '📋 Form Questions & Responses'): string
    {
        if (empty($customFormData)) {
            return '';
        }

        $rowsHtml = '';
        foreach ($customFormData as $item) {
            $label = htmlspecialchars($item['label'] ?? $item['field_name'] ?? 'Question');
            $type = $item['type'] ?? 'text';
            $value = $item['value'] ?? null;

            $formattedValue = $this->formatCustomFieldValueHtml($type, $value);

            $rowsHtml .= <<<HTML
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 10px 14px; width: 35%; color: #475569; font-weight: 600; font-size: 13px; vertical-align: top; background: #fafbfc;">
                    {$label}
                </td>
                <td style="padding: 10px 14px; width: 65%; color: #0f172a; font-size: 13px; vertical-align: top;">
                    {$formattedValue}
                </td>
            </tr>
HTML;
        }

        return <<<HTML
    <div style="border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; background: #ffffff;">
        <div style="background: #f8fafc; padding: 12px 14px; border-bottom: 1px solid #e2e8f0; font-size: 12px; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.5px;">
            {$sectionTitle}
        </div>
        <table style="width: 100%; border-collapse: collapse; font-family: inherit;">
            <tbody>
                {$rowsHtml}
            </tbody>
        </table>
    </div>
HTML;
    }

    /**
     * Format a custom form field value into safe HTML based on its type.
     */
    private function formatCustomFieldValueHtml(string $type, mixed $value): string
    {
        if ($value === null || $value === '' || $value === []) {
            return '<span style="color: #94a3b8; font-style: italic; font-size: 12px;">Not provided</span>';
        }

        // File upload field
        if ($type === 'file' || (is_array($value) && (isset($value['path']) || isset($value['url'])))) {
            $url = is_array($value) ? ($value['url'] ?? asset($value['path'] ?? '')) : asset($value);
            $name = is_array($value) ? ($value['name'] ?? 'View Attachment') : basename($value);
            $safeUrl = htmlspecialchars($url);
            $safeName = htmlspecialchars($name);

            return <<<HTML
            <a href="{$safeUrl}" target="_blank" style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none;">
                <span>📎</span> <span>{$safeName}</span>
            </a>
HTML;
        }

        // Array of values (e.g. checkbox options)
        if (is_array($value)) {
            $pills = '';
            foreach ($value as $v) {
                if (is_scalar($v) && trim((string) $v) !== '') {
                    $pills .= '<span style="display: inline-block; padding: 2px 8px; margin: 2px 4px 2px 0; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 12px; font-weight: 600; color: #334155;">' . htmlspecialchars((string) $v) . '</span>';
                }
            }
            return $pills ?: '<span style="color: #94a3b8; font-style: italic;">Empty selection</span>';
        }

        // Boolean
        if (is_bool($value)) {
            return $value ? '<span style="color: #166534; font-weight: 600;">Yes</span>' : '<span style="color: #dc2626; font-weight: 600;">No</span>';
        }

        // String, number, date, text
        $stringVal = (string) $value;
        return nl2br(htmlspecialchars($stringVal));
    }

    /**
     * Persist ProjectUpdate with custom timestamps and actor.
     */
    private function createProjectUpdate(ProductionInitiation $initiation, string $content, ?User $actor, ?Carbon $customTimestamp): ProjectUpdate
    {
        $update = new ProjectUpdate();
        $update->production_initiation_id = $initiation->id;
        $update->type = 'production_update';
        $update->content = $content;
        $update->created_by = $actor?->id;

        if ($customTimestamp) {
            $update->timestamps = false;
            $update->created_at = $customTimestamp;
            $update->updated_at = $customTimestamp;
        }

        $update->save();

        return $update;
    }
}
