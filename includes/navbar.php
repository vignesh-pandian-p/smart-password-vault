<?php
$isLoggedIn = isset($_SESSION['user_id']);
?>
<header class="bg-white border-b border-slate-200 sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Brand Logo -->
            <a href="<?= $isLoggedIn ? 'dashboard.php' : 'index.php' ?>" class="flex items-center space-x-3 group">
                <div class="w-9 h-9 rounded-lg bg-indigo-600 text-white flex items-center justify-center shadow-sm">
                    <i data-lucide="shield-check" class="w-5 h-5"></i>
                </div>
                <div class="flex flex-col">
                    <span class="font-bold text-base text-slate-900 leading-tight">Smart Vault</span>
                    <span class="text-[11px] text-slate-500 font-normal">Password Manager</span>
                </div>
            </a>

            <!-- Right Controls -->
            <div class="flex items-center space-x-3">
                <?php if ($isLoggedIn): ?>
                    <div class="hidden sm:flex items-center space-x-2 text-sm text-slate-600 bg-slate-100 py-1.5 px-3 rounded-lg">
                        <i data-lucide="user" class="w-4 h-4 text-indigo-600"></i>
                        <span><?= htmlspecialchars($_SESSION['username'] ?? '') ?></span>
                    </div>

                    <a href="logout.php" class="flex items-center space-x-1.5 text-sm font-medium text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg transition-colors" title="Log Out">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                        <span class="hidden sm:inline">Logout</span>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="text-sm font-medium text-slate-600 hover:text-indigo-600 transition-colors px-3 py-2 rounded-lg">
                        Login
                    </a>
                    <a href="register.php" class="text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 px-3.5 py-1.5 rounded-lg shadow-sm transition-colors">
                        Sign Up
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>
