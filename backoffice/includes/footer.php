</div><!-- /content-wrap -->
</div><!-- /main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Sidebar mobile
const _sidebar = document.getElementById('sidebar');
const _overlay = document.getElementById('sbOverlay');
document.getElementById('sbToggle')?.addEventListener('click', () => {
    _sidebar.classList.add('open'); _overlay.classList.add('show');
});
_overlay?.addEventListener('click', () => {
    _sidebar.classList.remove('open'); _overlay.classList.remove('show');
});
// Auto dismiss toast
document.querySelectorAll('.toast-item').forEach(el => {
    setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity .3s'; setTimeout(() => el.remove(), 300); }, 3500);
});
</script>
<?php if (!empty($page_scripts)) echo $page_scripts; ?>
</body>
</html>