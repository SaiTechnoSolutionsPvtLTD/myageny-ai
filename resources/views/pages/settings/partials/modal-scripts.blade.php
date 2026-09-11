<script>
function openModal(id) {
    const el = document.getElementById(id);
    if (el) {
        el.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}
function closeModal(id) {
    const el = document.getElementById(id);
    if (el) {
        el.style.display = 'none';
        // Only reset overflow if no other crm modal is open
        const openModals = Array.from(document.querySelectorAll('.crm-modal-overlay')).filter(m => m.style.display === 'flex');
        if (openModals.length === 0) {
            document.body.style.overflow = '';
        }
    }
}
// Close on backdrop click
document.querySelectorAll('.crm-modal-overlay').forEach(el => {
    el.addEventListener('click', function(e) {
        if (e.target === this) closeModal(this.id);
    });
});
// Close on Escape key press
window.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' || e.key === 'Esc') {
        document.querySelectorAll('.crm-modal-overlay').forEach(el => {
            if (el.style.display === 'flex') {
                closeModal(el.id);
            }
        });
    }
});
// Auto-open add modal if there are validation errors (page reloaded)
@if(isset($errors) && $errors->any())
    document.addEventListener('DOMContentLoaded', () => openModal('addModal'));
@endif
</script>
