@extends('layouts.app')

@section('title', 'Production Mapping')

@push('styles')
<style>
.pmap-page{min-height:100%;padding:28px;background:linear-gradient(180deg,#f7f9fc 0%,#f3f6f9 100%)}
.pmap-topbar{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;margin-bottom:22px}
.pmap-title{margin:0;font-size:28px;font-weight:900;color:#111827}
.pmap-subtitle{margin-top:8px;max-width:760px;font-size:14px;line-height:1.7;color:#6b7280}
.pmap-actions{display:flex;gap:10px;flex-wrap:wrap}
.pmap-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:42px;padding:10px 16px;border-radius:12px;border:1px solid #e5e7eb;background:#fff;color:#111827;text-decoration:none;font-size:13px;font-weight:800;transition:all .16s ease}
.pmap-btn:hover{border-color:#fdba74;color:#c2410c}
.pmap-btn-primary{background:#111827;border-color:#111827;color:#fff}
.pmap-btn-primary:hover{background:#1f2937;border-color:#1f2937;color:#fff}
.pmap-btn[disabled]{opacity:.55;cursor:not-allowed;pointer-events:none}
.pmap-hero{display:grid;grid-template-columns:1.15fr .85fr;gap:18px;margin-bottom:18px}
.pmap-card{background:#fff;border:1px solid #e5e7eb;border-radius:22px;box-shadow:0 14px 34px rgba(15,23,42,.05)}
.pmap-hero-main{padding:24px;background:linear-gradient(135deg,#fffdf8 0%,#ffffff 58%,#f0fdf4 100%);position:relative;overflow:hidden}
.pmap-hero-main::after{content:'';position:absolute;right:-48px;top:-44px;width:180px;height:180px;border-radius:50%;background:radial-gradient(circle,rgba(16,185,129,.16),transparent 70%)}
.pmap-eyebrow{display:inline-flex;align-items:center;gap:8px;padding:7px 12px;border-radius:999px;background:#ecfdf5;color:#047857;font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase}
.pmap-hero-title{margin:16px 0 0;font-size:25px;font-weight:900;line-height:1.15;color:#111827;max-width:640px}
.pmap-hero-copy{margin:12px 0 0;max-width:640px;font-size:14px;line-height:1.7;color:#6b7280}
.pmap-hero-note{margin-top:16px;display:inline-flex;align-items:center;gap:8px;padding:10px 12px;border-radius:14px;background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;font-size:12px;font-weight:700}
.pmap-stats{display:grid;gap:14px;padding:20px}
.pmap-stat{padding:16px 18px;border-radius:18px;border:1px solid #eef2f7;background:linear-gradient(135deg,#ffffff,#f8fafc)}
.pmap-stat-label{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:#6b7280}
.pmap-stat-value{margin-top:8px;font-size:30px;font-weight:900;color:#111827}
.pmap-stat-sub{margin-top:4px;font-size:12px;color:#6b7280}
.pmap-section{padding:22px}
.pmap-section-head{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:18px}
.pmap-section-title{margin:0;font-size:18px;font-weight:900;color:#111827}
.pmap-section-copy{margin-top:6px;font-size:13px;line-height:1.6;color:#6b7280}
.pmap-count{display:inline-flex;align-items:center;justify-content:center;min-width:42px;height:42px;padding:0 14px;border-radius:999px;background:#eef2ff;color:#4338ca;font-size:13px;font-weight:900}
.pmap-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:18px}
.pmap-dept-card{position:relative;padding:20px;border-radius:20px;border:1px solid #e5e7eb;background:linear-gradient(135deg,#ffffff 0%,#fbfdff 100%);cursor:pointer;transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease}
.pmap-dept-card:hover{transform:translateY(-2px);border-color:#a7f3d0;box-shadow:0 18px 34px rgba(15,23,42,.08)}
.pmap-dept-card.is-selected{border-color:#10b981;box-shadow:0 20px 40px rgba(16,185,129,.14);background:linear-gradient(135deg,#f0fdf4 0%,#ffffff 100%)}
.pmap-dept-icon{width:52px;height:52px;border-radius:16px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#ecfdf5,#d1fae5);color:#047857;margin-bottom:16px}
.pmap-dept-name{margin:0;font-size:18px;font-weight:900;color:#111827}
.pmap-dept-desc{margin-top:8px;font-size:13px;line-height:1.65;color:#6b7280;min-height:64px}
.pmap-dept-meta{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:16px}
.pmap-route{display:inline-flex;align-items:center;padding:7px 10px;border-radius:999px;background:#f8fafc;border:1px solid #e5e7eb;color:#475569;font-size:11px;font-weight:800}
.pmap-arrow{width:34px;height:34px;border-radius:999px;display:flex;align-items:center;justify-content:center;background:#111827;color:#fff;transition:transform .16s ease}
.pmap-dept-card:hover .pmap-arrow,.pmap-dept-card.is-selected .pmap-arrow{transform:translateX(2px)}
.pmap-selected{margin-top:18px;padding:18px 20px;border-radius:18px;border:1px dashed #cbd5e1;background:#f8fafc;display:flex;align-items:center;justify-content:space-between;gap:14px}
.pmap-selected-title{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:#6b7280}
.pmap-selected-name{margin-top:6px;font-size:18px;font-weight:900;color:#111827}
.pmap-selected-copy{margin-top:4px;font-size:13px;color:#6b7280}
.pmap-empty{padding:26px;border:1px dashed #dbe2ea;border-radius:18px;background:#fafafa;color:#6b7280;font-size:13px;line-height:1.6;text-align:center}
.pmap-next-panel{margin-top:18px;padding:18px 20px;border-radius:18px;background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;font-size:13px;line-height:1.6}
@media (max-width: 1024px){
    .pmap-page{padding:18px}
    .pmap-topbar{flex-direction:column;align-items:flex-start}
    .pmap-hero{grid-template-columns:1fr}
}
@media (max-width: 640px){
    .pmap-selected{flex-direction:column;align-items:flex-start}
}
</style>
@endpush

@section('content')
<div class="pmap-page">
    <div class="pmap-topbar">
        <div>
            <h2 class="pmap-title">Production Mapping</h2>
            <div class="pmap-subtitle">This is the department selection step for production mapping. Choose a department to continue to the workflow configuration screen.</div>
        </div>
        <div class="pmap-actions">
            <a href="{{ route('auth.index') }}" class="pmap-btn">Back</a>
            <button type="button" id="pmapNextBtn" class="pmap-btn pmap-btn-primary" disabled>Next</button>
        </div>
    </div>

    <div class="pmap-hero">
        <div class="pmap-card pmap-hero-main">
            <div class="pmap-eyebrow">
                <i class="bi bi-diagram-3"></i>
                Step 1
            </div>
            <div class="pmap-hero-title">Choose the department you want to configure for production.</div>
            <div class="pmap-hero-copy">Before configuring the full production workflow, start by selecting a department. Once a department is selected, you can move to the next step and define the workflow roles.</div>
            <div class="pmap-hero-note">
                <i class="bi bi-stars"></i>
                Click Next to open the production workflow mapping screen for the selected department.
            </div>
        </div>

        <div class="pmap-stats">
            <div class="pmap-card pmap-stat">
                <div class="pmap-stat-label">Departments</div>
                <div class="pmap-stat-value">{{ $departments->count() }}</div>
                <div class="pmap-stat-sub">available for production setup</div>
            </div>
            <div class="pmap-card pmap-stat">
                <div class="pmap-stat-label">Mapped Products</div>
                <div class="pmap-stat-value">{{ $mappedProductsCount }}</div>
                <div class="pmap-stat-sub">already linked from product master</div>
            </div>
            <div class="pmap-card pmap-stat">
                <div class="pmap-stat-label">Status</div>
                <div class="pmap-stat-value" id="pmapStatusText">0</div>
                <div class="pmap-stat-sub">department selected</div>
            </div>
        </div>
    </div>

    <div class="pmap-card pmap-section">
        <div class="pmap-section-head">
            <div>
                <h3 class="pmap-section-title">Departments</h3>
                <div class="pmap-section-copy">Select a department to continue to the production workflow mapping screen.</div>
            </div>
            <span class="pmap-count">{{ $departments->count() }}</span>
        </div>

        @if($departments->isEmpty())
            <div class="pmap-empty">No departments found. Create departments first to continue with production mapping.</div>
        @else
            <div class="pmap-grid">
                @foreach($departments as $department)
                    <button type="button"
                            class="pmap-dept-card"
                            data-department-card
                            data-department-id="{{ $department->id }}"
                            data-department-name="{{ $department->name }}"
                            data-department-route="{{ $department->dashboard_route_label }}"
                            data-next-url="{{ route('auth.production-mappings.show', $department) }}">
                        <div class="pmap-dept-icon">
                            <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path d="M3 7h18"/>
                                <path d="M6 12h12"/>
                                <path d="M9 17h6"/>
                            </svg>
                        </div>
                        <h4 class="pmap-dept-name">{{ $department->name }}</h4>
                        <div class="pmap-dept-desc">{{ $department->description ?: 'No description added yet for this department. You can still continue with setup.' }}</div>
                        <div class="pmap-dept-meta">
                            <span class="pmap-route">{{ $department->dashboard_route_label }}</span>
                            <span class="pmap-arrow">
                                <i class="bi bi-arrow-right"></i>
                            </span>
                        </div>
                    </button>
                @endforeach
            </div>

            <div class="pmap-selected" id="pmapSelectedBox">
                <div>
                    <div class="pmap-selected-title">Selected Department</div>
                    <div class="pmap-selected-name" id="pmapSelectedName">No department selected</div>
                    <div class="pmap-selected-copy" id="pmapSelectedCopy">Select a department card to continue to the next step.</div>
                </div>
                <span class="pmap-route" id="pmapSelectedRoute">Waiting for selection</span>
            </div>

            <div class="pmap-next-panel" id="pmapNextPanel">
                The Next button will be enabled after you select a department. Continue to the selected department's production workflow setup screen.
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const cards = document.querySelectorAll('[data-department-card]');
    const nextButton = document.getElementById('pmapNextBtn');
    const selectedName = document.getElementById('pmapSelectedName');
    const selectedCopy = document.getElementById('pmapSelectedCopy');
    const selectedRoute = document.getElementById('pmapSelectedRoute');
    const statusText = document.getElementById('pmapStatusText');
    let selectedDepartmentId = null;
    let selectedNextUrl = null;

    cards.forEach(function (card) {
        card.addEventListener('click', function () {
            cards.forEach(function (item) {
                item.classList.remove('is-selected');
            });

            card.classList.add('is-selected');
            selectedDepartmentId = card.dataset.departmentId;
            selectedNextUrl = card.dataset.nextUrl || null;

            if (selectedName) {
                selectedName.textContent = card.dataset.departmentName || 'Department Selected';
            }

            if (selectedCopy) {
                selectedCopy.textContent = 'This department is now ready for the next production mapping step.';
            }

            if (selectedRoute) {
                selectedRoute.textContent = card.dataset.departmentRoute || 'Default Dashboard';
            }

            if (statusText) {
                statusText.textContent = '1';
            }

            if (nextButton) {
                nextButton.disabled = false;
            }
        });
    });

    nextButton?.addEventListener('click', function () {
        if (!selectedDepartmentId || !selectedNextUrl) {
            return;
        }
        window.location.href = selectedNextUrl;
    });
});
</script>
@endpush
