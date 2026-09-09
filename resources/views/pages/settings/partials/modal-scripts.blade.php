<script>
function openModal(id)  { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }
// Close on backdrop click
document.querySelectorAll('.crm-modal-overlay').forEach(el => {
    el.addEventListener('click', function(e) {
        if (e.target === this) closeModal(this.id);
    });
});
// Auto-open add modal if there are validation errors (page reloaded)
@if(isset($errors) && $errors->any())
    document.addEventListener('DOMContentLoaded', () => openModal('addModal'));
@endif
</script>
