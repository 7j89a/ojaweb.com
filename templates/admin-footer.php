</main> <!-- End of .main-content -->

<footer class="admin-footer">
    <p>&copy; <?= date('Y') ?> لوحة تحكم الموقع. جميع الحقوق محفوظة.</p>
    <div class="theme-switch" title="تبديل المظهر">
        <input type="checkbox" id="theme-switch-checkbox" class="theme-switch-checkbox">
        <label for="theme-switch-checkbox" class="theme-switch-label"></label>
    </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const themeSwitch = document.getElementById('theme-switch-checkbox');
    const currentTheme = localStorage.getItem('adminTheme');

    if (currentTheme) {
        document.documentElement.setAttribute('data-theme', currentTheme);
        if (currentTheme === 'dark') {
            themeSwitch.checked = true;
        }
    }

    themeSwitch.addEventListener('change', function() {
        if (this.checked) {
            document.documentElement.setAttribute('data-theme', 'dark');
            localStorage.setItem('adminTheme', 'dark');
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
            localStorage.setItem('adminTheme', 'light');
        }
    });
});
</script>

</body>
</html>
