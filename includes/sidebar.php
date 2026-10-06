<?php
$username = $_SESSION['username'] ?? 'User';
$userInitial = strtoupper(substr($username, 0, 1));
$itemCount = $itemCount ?? 0;
$weakCount = $weakCount ?? 0;
?>
<aside class="w-full md:w-64 bg-white border-r border-slate-200 flex-shrink-0 flex flex-col md:min-h-screen">
    <!-- Sidebar Header / Brand -->
    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
        <a href="dashboard.php" class="flex items-center space-x-3">
            <div class="w-9 h-9 rounded-lg bg-indigo-600 text-white flex items-center justify-center shadow-sm">
                <i data-lucide="shield-check" class="w-5 h-5"></i>
            </div>
            <div>
                <span class="font-bold text-base text-slate-900 block leading-tight">Smart Vault</span>
                <span class="text-[11px] text-slate-400">Password Manager</span>
            </div>
        </a>
    </div>

    <!-- Navigation Menu -->
    <nav class="p-4 space-y-1 flex-grow" id="sidebar-nav">
        <button type="button" id="nav-btn-all" onclick="switchTab('all')" class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors text-left bg-indigo-50 text-indigo-700">
            <i data-lucide="database" class="w-4 h-4 text-indigo-600"></i>
            <span>All Passwords</span>
            <?php if (isset($itemCount)): ?>
                <span id="sidebar-count-badge" class="ml-auto text-xs px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 font-bold"><?= $itemCount ?></span>
            <?php endif; ?>
        </button>

        <button type="button" id="nav-btn-add" onclick="switchTab('add')" class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition-colors text-left">
            <i data-lucide="plus-circle" class="w-4 h-4 text-slate-400"></i>
            <span>Add Credential</span>
        </button>

        <button type="button" id="open-generator-modal-btn-sidebar" class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition-colors text-left">
            <i data-lucide="key" class="w-4 h-4 text-slate-400"></i>
            <span>Generator</span>
        </button>

        <?php if ($weakCount > 0): ?>
        <button type="button" onclick="filterWeakPasswords()" class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium text-amber-700 bg-amber-50 hover:bg-amber-100 transition-colors text-left mt-2 border border-amber-200/60">
            <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600"></i>
            <span>Weak Passwords</span>
            <span class="ml-auto text-xs px-2 py-0.5 rounded-full bg-amber-200/80 text-amber-800 font-bold"><?= $weakCount ?></span>
        </button>
        <?php endif; ?>
    </nav>

    <!-- Sidebar Footer / Account Info -->
    <div class="p-4 border-t border-slate-100 bg-slate-50/50">
        <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs flex-shrink-0">
                    <?= $userInitial ?>
                </div>
                <span class="text-xs font-medium text-slate-700 truncate" title="<?= htmlspecialchars($username) ?>">
                    <?= htmlspecialchars($username) ?>
                </span>
            </div>
            <a href="logout.php" class="p-1.5 text-slate-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition-colors flex-shrink-0" title="Logout">
                <i data-lucide="log-out" class="w-4 h-4"></i>
            </a>
        </div>
    </div>
</aside>
