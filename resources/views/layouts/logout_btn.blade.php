{{-- =====================================================================
     Logout Button Partial — include in sidebar footer or header
     FILE: resources/views/layouts/partials/logout-btn.blade.php
===================================================================== --}}


<form method="POST" action="{{ route('logout') }}" id="logoutForm">
    @csrf
</form>

{{-- Logout Confirmation Modal --}}
<div id="logoutModal" style="
    display: none; position: absolute; inset: 0; z-index: 999999;
    background: rgba(14,10,20,0.8); backdrop-filter: blur(8px);
    align-items: center; justify-content: center;
">
    <div style="
        background: #1a1225; border: 1px solid rgba(255,255,255,0.08);
        border-radius: 16px; padding: 32px; max-width: 360px; width: 90%;
        text-align: center; box-shadow: 0 24px 64px rgba(0,0,0,0.4);
        animation: popIn 0.2s ease;
    ">
        <div style="
            width: 56px; height: 56px; border-radius: 16px;
            background: rgba(254,95,4,0.12); border: 1px solid rgba(254,95,4,0.2);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 16px;
        ">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                 viewBox="0 0 24 24" stroke="#fe5f04" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
        </div>

        <h3 style="font-size: 18px; font-weight: 700; color: #fff; margin: 0 0 8px;">Sign out?</h3>
        <p style="font-size: 13px; color: #9e8fb5; margin: 0 0 24px; line-height: 1.6;">
            You'll be signed out of your workspace. Any unsaved changes may be lost.
        </p>

        <div style="display: flex; gap: 10px;">
            <button
                onclick="closeLogoutModal()"
                style="
                    flex: 1; padding: 10px; border-radius: 8px;
                    background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.08);
                    color: #c8bdd8; font-size: 14px; font-weight: 600; cursor: pointer;
                    font-family: inherit; transition: all 0.2s;
                "
                onmouseover="this.style.background='rgba(255,255,255,0.1)';"
                onmouseout="this.style.background='rgba(255,255,255,0.06)';"
            >
                Cancel
            </button>
            <button
                onclick="document.getElementById('logoutForm').submit()"
                style="
                    flex: 1; padding: 10px; border-radius: 8px;
                    background: linear-gradient(135deg, #fe5f04, #ff7c30);
                    border: none; color: white; font-size: 14px; font-weight: 700;
                    cursor: pointer; font-family: inherit;
                    box-shadow: 0 4px 12px rgba(254,95,4,0.35);
                    transition: all 0.2s;
                "
                onmouseover="this.style.transform='translateY(-1px)';"
                onmouseout="this.style.transform='translateY(0)';"
            >
                Sign Out
            </button>
        </div>
    </div>
</div>

<style>
@keyframes popIn {
    from { opacity: 0; transform: scale(0.92) translateY(8px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}
</style>

<script>
function confirmLogout() {
    const modal = document.getElementById('logoutModal');
    modal.style.display = 'flex';
}
function closeLogoutModal() {
    document.getElementById('logoutModal').style.display = 'none';
}
// Close on backdrop click
document.getElementById('logoutModal').addEventListener('click', function(e) {
    if (e.target === this) closeLogoutModal();
});
// Close on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeLogoutModal();
});
</script>
