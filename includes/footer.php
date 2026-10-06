<footer class="mt-auto border-t border-slate-200 bg-white py-5">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-xs text-slate-500 flex flex-col sm:flex-row justify-between items-center gap-2">
        <p>&copy; <?= date('Y') ?> Smart Password Vault</p>
        <div class="flex items-center space-x-1.5 text-slate-500">
            <i data-lucide="shield" class="w-3.5 h-3.5 text-indigo-600"></i>
            <span>Encrypted with AES-256</span>
        </div>
    </div>
</footer>

<!-- Toast Container -->
<div id="toast-container"></div>

<!-- Local Scripts (100% Offline) -->
<script src="assets/js/lucide.min.js"></script>
<script src="assets/js/vault.js"></script>
<script src="assets/js/generator.js"></script>
<script>
    if (window.lucide) {
        window.lucide.createIcons();
    }
</script>
</body>
</html>
