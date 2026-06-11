<script>
(function () {
    var theme = 'system';
    try {
        theme = localStorage.getItem('theme') || 'system';
    } catch (e) {}

    var isDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
    document.documentElement.classList.toggle('dark', isDark);

    var meta = document.querySelector('meta[name="theme-color"]');
    if (meta) {
        meta.content = isDark ? '#18181b' : '#ffffff';
    }
})();
</script>
