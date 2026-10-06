<?php
session_start();
include 'db.php';
include 'includes/crypto.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Flash messages
$flash_message = $_SESSION['flash_message'] ?? null;
$flash_type = $_SESSION['flash_type'] ?? 'success';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// Fetch user's credentials
$stmt = $conn->prepare("SELECT id, site_name, site_username, encrypted_password, created_at FROM vault WHERE user_id = ? ORDER BY id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$vault_items = [];
$weakCount = 0;
$staleCount = 0;

while ($row = $result->fetch_assoc()) {
    $plain = decrypt_password($row['encrypted_password']);
    $isWeak = false;
    $isStale = false;

    if ($plain !== null) {
        $len = strlen($plain);
        $hasLower = preg_match('/[a-z]/', $plain);
        $hasUpper = preg_match('/[A-Z]/', $plain);
        $hasDigit = preg_match('/[0-9]/', $plain);
        $hasSymbol = preg_match('/[^a-zA-Z0-9]/', $plain);
        $typeCount = ($hasLower ? 1 : 0) + ($hasUpper ? 1 : 0) + ($hasDigit ? 1 : 0) + ($hasSymbol ? 1 : 0);

        if ($len < 8 || $typeCount < 2) {
            $isWeak = true;
            $weakCount++;
        }
    }

    $createdTime = strtotime($row['created_at']);
    $ageDays = floor((time() - $createdTime) / 86400);
    if ($ageDays >= 90) {
        $isStale = true;
        $staleCount++;
    }

    $row['is_weak'] = $isWeak;
    $row['is_stale'] = $isStale;
    $row['age_days'] = $ageDays;

    $vault_items[] = $row;
}
$stmt->close();
$conn->close();

$page_title = "Vault Dashboard";
$itemCount = count($vault_items);
include 'includes/header.php';
?>

<div class="min-h-screen flex flex-col md:flex-row bg-slate-50">
    <!-- Left Sidebar Navigation -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="flex-grow flex flex-col min-w-0">
        
        <!-- ========================================== -->
        <!-- VIEW 1: ALL PASSWORDS (DEFAULT VIEW)       -->
        <!-- ========================================== -->
        <div id="tab-view-all" class="flex-grow flex flex-col">
            <!-- Header for All Passwords -->
            <header class="bg-white border-b border-slate-200 px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 sticky top-0 z-30">
                <div>
                    <h1 class="text-xl font-bold text-slate-900">Stored Passwords</h1>
                    <p class="text-xs text-slate-500">View and manage your encrypted login credentials</p>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Top Search Input -->
                    <div class="relative w-full sm:w-64">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <i data-lucide="search" class="w-4 h-4"></i>
                        </div>
                        <input type="text" id="vault-search-input" placeholder="Search site or account..."
                            class="w-full pl-9 pr-8 py-2 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 placeholder-slate-400 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-colors">
                        <button type="button" id="clear-search-btn" class="hidden absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>

                    <!-- Add Button -->
                    <button type="button" onclick="switchTab('add')" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-xs rounded-xl shadow-sm transition-colors flex-shrink-0">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        <span>Add New</span>
                    </button>
                </div>
            </header>

            <!-- Table View Body -->
            <main class="p-6 flex-grow">

                <!-- Security Advisory Banner -->
                <?php if ($weakCount > 0): ?>
                    <div class="mb-5 p-4 bg-amber-50 border border-amber-200 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-amber-900 text-xs shadow-sm">
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center flex-shrink-0">
                                <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <p class="font-bold text-sm text-amber-950">Security Alert: <?= $weakCount ?> Weak <?= $weakCount === 1 ? 'Password' : 'Passwords' ?> Found</p>
                                <p class="text-amber-700 mt-0.5">Weak passwords put your accounts at risk. Upgrade them using the built-in generator.</p>
                            </div>
                        </div>
                        <button type="button" onclick="filterWeakPasswords()" class="px-3.5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-semibold rounded-xl text-xs transition-colors flex-shrink-0 shadow-sm">
                            Review Weak Passwords
                        </button>
                    </div>
                <?php elseif ($staleCount > 0): ?>
                    <div class="mb-5 p-4 bg-blue-50 border border-blue-200 rounded-2xl flex items-center gap-3 text-blue-900 text-xs shadow-sm">
                        <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="shield-alert" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <p class="font-bold text-sm text-blue-950">Password Update Reminder</p>
                            <p class="text-blue-700 mt-0.5">Some of your passwords haven't been updated in over 90 days. Updating periodically helps keep accounts safe.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">All Entries</span>
                        <span class="text-xs text-slate-400 font-medium"><?= count($vault_items) ?> items</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-left">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th scope="col" class="px-5 py-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Site / Service</th>
                                    <th scope="col" class="px-5 py-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Account</th>
                                    <th scope="col" class="px-5 py-3 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Password Status</th>
                                    <th scope="col" class="px-5 py-3 text-right text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="vault-table-body" class="divide-y divide-slate-100 bg-white">
                                <?php if (!empty($vault_items)): ?>
                                    <?php foreach ($vault_items as $item): ?>
                                        <tr class="vault-row hover:bg-slate-50/80 transition-colors"
                                            data-site="<?= strtolower(htmlspecialchars($item['site_name'])) ?>"
                                            data-user="<?= strtolower(htmlspecialchars($item['site_username'])) ?>"
                                            data-weak="<?= $item['is_weak'] ? 'true' : 'false' ?>">
                                            
                                            <!-- Site Name -->
                                            <td class="px-5 py-3 whitespace-nowrap">
                                                <div class="flex items-center gap-2.5">
                                                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs flex-shrink-0">
                                                        <?= strtoupper(substr($item['site_name'], 0, 1)) ?>
                                                    </div>
                                                    <div>
                                                        <div class="flex items-center gap-2">
                                                            <span class="font-semibold text-slate-900 text-xs block"><?= htmlspecialchars($item['site_name']) ?></span>
                                                            <?php if ($item['is_weak']): ?>
                                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200" title="Weak password. Click edit to upgrade.">
                                                                    <i data-lucide="alert-triangle" class="w-3 h-3 text-amber-500"></i> Weak
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <span class="text-[10px] text-slate-400"><?= date('M d, Y', strtotime($item['created_at'])) ?></span>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Username with Copy -->
                                            <td class="px-5 py-3 whitespace-nowrap">
                                                <?php if (!empty($item['site_username'])): ?>
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="text-xs text-slate-600 font-mono"><?= htmlspecialchars($item['site_username']) ?></span>
                                                        <button type="button" onclick="copyToClipboard('<?= htmlspecialchars(addslashes($item['site_username'])) ?>', 'Username copied!')" class="p-1 text-slate-400 hover:text-indigo-600 rounded hover:bg-slate-100 transition-colors" title="Copy Username">
                                                            <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-[11px] text-slate-400 italic">None</span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Password Mask with Quick Copy -->
                                            <td class="px-5 py-3 whitespace-nowrap">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-mono text-xs tracking-widest text-slate-400 select-none">••••••••</span>
                                                    <button type="button" onclick="quickCopyPassword(<?= $item['id'] ?>)" class="p-1 text-slate-400 hover:text-indigo-600 rounded hover:bg-slate-100 transition-colors" title="Quick Copy Password">
                                                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                                    </button>
                                                </div>
                                            </td>

                                            <!-- Actions -->
                                            <td class="px-5 py-3 whitespace-nowrap text-right">
                                                <div class="flex items-center justify-end gap-1">
                                                    <button type="button" onclick="openDecryptModal(<?= $item['id'] ?>)" class="p-1 text-slate-400 hover:text-indigo-600 hover:bg-slate-100 rounded-lg transition-colors" title="View Password">
                                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                                    </button>

                                                    <button type="button" onclick="openEditModal(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['site_name'])) ?>', '<?= htmlspecialchars(addslashes($item['site_username'])) ?>')" class="p-1 text-slate-400 hover:text-amber-600 hover:bg-slate-100 rounded-lg transition-colors" title="Edit Credential">
                                                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                                    </button>

                                                    <form method="post" action="delete_password.php" class="inline" onsubmit="return confirm('Delete this credential?')">
                                                        <input type="hidden" name="vault_id" value="<?= $item['id'] ?>">
                                                        <button type="submit" class="p-1 text-slate-400 hover:text-red-600 hover:bg-slate-100 rounded-lg transition-colors" title="Delete Credential">
                                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr id="empty-vault-row">
                                        <td colspan="4" class="px-6 py-16 text-center text-slate-500">
                                            <div class="w-12 h-12 bg-slate-100 text-slate-400 rounded-xl flex items-center justify-center mx-auto mb-3">
                                                <i data-lucide="folder" class="w-6 h-6"></i>
                                            </div>
                                            <p class="font-semibold text-sm text-slate-800">No passwords stored yet</p>
                                            <p class="text-xs text-slate-400 mt-1">Click "Add Credential" to save your first password.</p>
                                            <button type="button" onclick="switchTab('add')" class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-xs rounded-xl shadow-sm transition-colors">
                                                <i data-lucide="plus" class="w-4 h-4"></i>
                                                <span>Add First Credential</span>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>

        <!-- ========================================== -->
        <!-- VIEW 2: ADD CREDENTIAL (DEDICATED FORM)    -->
        <!-- ========================================== -->
        <div id="tab-view-add" class="flex-grow flex flex-col hidden">
            <!-- Header for Add Credential -->
            <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between sticky top-0 z-30">
                <div>
                    <h1 class="text-xl font-bold text-slate-900">Add New Credential</h1>
                    <p class="text-xs text-slate-500">Store a new login password securely in your vault</p>
                </div>

                <button type="button" onclick="switchTab('all')" class="text-xs font-medium text-slate-500 hover:text-indigo-600 flex items-center gap-1">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    <span>Back to All Passwords</span>
                </button>
            </header>

            <!-- Form Body -->
            <main class="p-6 flex-grow flex justify-center items-start">
                <div class="w-full max-w-lg bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sm:p-8 animate-fade-in">
                    
                    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <i data-lucide="plus-circle" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Credential Details</h2>
                            <p class="text-xs text-slate-400">Encrypted before saving to database</p>
                        </div>
                    </div>

                    <form method="post" action="add_password.php" id="add-credential-form" class="space-y-4">
                        <div>
                            <label for="site_name" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                                Website / Service <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                    <i data-lucide="globe" class="w-4 h-4"></i>
                                </div>
                                <input type="text" id="site_name" name="site_name" placeholder="e.g. GitHub, Google, Netflix" required autofocus
                                    class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 placeholder-slate-400 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-colors">
                            </div>
                        </div>

                        <div>
                            <label for="site_username" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                                Username / Email
                            </label>
                            <div class="relative">
                                <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                    <i data-lucide="mail" class="w-4 h-4"></i>
                                </div>
                                <input type="text" id="site_username" name="site_username" placeholder="e.g. user@example.com"
                                    class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 placeholder-slate-400 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-colors">
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="site_password" class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                    Password <span class="text-red-500">*</span>
                                </label>
                                <button type="button" id="quick-fill-generate-btn" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 flex items-center gap-1 transition-colors">
                                    <i data-lucide="wand-2" class="w-3.5 h-3.5"></i>
                                    <span>Generate Strong Password</span>
                                </button>
                            </div>
                            <div class="relative">
                                <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                    <i data-lucide="lock" class="w-4 h-4"></i>
                                </div>
                                <input type="password" id="site_password" name="site_password" placeholder="Enter or generate password" required
                                    class="w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 placeholder-slate-400 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-colors">
                                <button type="button" id="toggle-add-pass-visibility" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors" title="Toggle visibility">
                                    <i data-lucide="eye" id="toggle-add-pass-icon" class="w-4 h-4"></i>
                                </button>
                            </div>

                            <!-- Strength Meter -->
                            <div class="mt-2.5">
                                <div class="flex justify-between items-center text-[11px] text-slate-500 mb-1">
                                    <span>Password Strength: <strong id="add-strength-label" class="font-semibold text-slate-600">None</strong></span>
                                </div>
                                <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                                    <div id="add-strength-bar" class="h-full w-0 transition-all duration-300 bg-red-500 rounded-full"></div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-3 flex items-center gap-3">
                            <button type="button" onclick="switchTab('all')" class="w-1/3 py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium text-xs rounded-xl transition-colors">
                                Cancel
                            </button>
                            <button type="submit" class="w-2/3 flex items-center justify-center gap-2 py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-xs rounded-xl shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <i data-lucide="save" class="w-4 h-4"></i>
                                <span>Save Credential</span>
                            </button>
                        </div>
                    </form>
                </div>
            </main>
        </div>

        <!-- Footer -->
        <footer class="border-t border-slate-200 bg-white py-4 px-6 text-xs text-slate-500 flex justify-between items-center mt-auto">
            <p>&copy; <?= date('Y') ?> Smart Password Vault</p>
            <div class="flex items-center space-x-1.5 text-slate-400">
                <i data-lucide="shield" class="w-3.5 h-3.5 text-indigo-600"></i>
                <span>Encrypted AES-256</span>
            </div>
        </footer>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: INLINE DECRYPT CREDENTIAL           -->
<!-- ========================================== -->
<div id="decrypt-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm hidden opacity-0 transition-opacity duration-150">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-md p-6 transform scale-95 transition-transform duration-150" id="decrypt-modal-card">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200">
            <div class="flex items-center gap-2 text-slate-900 font-bold text-base">
                <i data-lucide="lock-open" class="w-4 h-4 text-indigo-600"></i>
                <span>Decrypted Credential</span>
            </div>
            <button type="button" onclick="closeDecryptModal()" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <div class="mt-4 space-y-3" id="decrypt-modal-body">
            <!-- Animated Scan Loading State -->
            <div id="decrypt-loading" class="py-8 text-center text-slate-500 relative overflow-hidden rounded-xl bg-slate-50 border border-slate-200">
                <div class="decrypt-scan-line"></div>
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto mb-3 animate-pulse-decrypt">
                    <i data-lucide="shield-check" class="w-6 h-6"></i>
                </div>
                <p class="text-xs font-semibold text-slate-800">Decrypting AES-256 Cipher...</p>
                <p class="text-[11px] text-slate-400 mt-0.5">Verifying credentials & signature</p>
            </div>

            <!-- Content State -->
            <div id="decrypt-content" class="hidden space-y-3">
                <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                    <label class="block text-[11px] font-semibold uppercase text-slate-500 mb-0.5">Service</label>
                    <p id="decrypt-site-name" class="font-bold text-slate-900 text-sm"></p>
                </div>

                <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200" id="decrypt-user-box">
                    <label class="block text-[11px] font-semibold uppercase text-slate-500 mb-0.5">Username / Email</label>
                    <div class="flex items-center justify-between">
                        <span id="decrypt-username" class="font-mono text-xs text-slate-800"></span>
                        <button type="button" id="copy-modal-user-btn" class="p-1 text-slate-400 hover:text-indigo-600 rounded">
                            <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                </div>

                <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                    <label class="block text-[11px] font-semibold uppercase text-slate-500 mb-0.5">Password</label>
                    <div class="flex items-center justify-between gap-2">
                        <span id="decrypt-password" class="font-mono text-sm font-bold text-indigo-600 select-all break-all"></span>
                        <button type="button" id="copy-modal-pass-btn" class="px-2.5 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold flex items-center gap-1 flex-shrink-0 transition-colors">
                            <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                            <span>Copy</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-5 flex justify-end">
            <button type="button" onclick="closeDecryptModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium text-xs rounded-xl transition-colors">
                Close
            </button>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: EDIT CREDENTIAL                     -->
<!-- ========================================== -->
<div id="edit-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm hidden opacity-0 transition-opacity duration-150">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-md p-6 transform scale-95 transition-transform duration-150" id="edit-modal-card">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200">
            <div class="flex items-center gap-2 text-slate-900 font-bold text-base">
                <i data-lucide="pencil" class="w-4 h-4 text-indigo-600"></i>
                <span>Edit Credential</span>
            </div>
            <button type="button" onclick="closeEditModal()" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <form method="post" action="update_password.php" class="mt-4 space-y-3.5">
            <input type="hidden" name="vault_id" id="edit-vault-id">

            <div>
                <label for="edit_site_name" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">Website / Service</label>
                <input type="text" id="edit_site_name" name="site_name" required
                    class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm focus:ring-2 focus:ring-indigo-500 focus:bg-white focus:outline-none">
            </div>

            <div>
                <label for="edit_site_username" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">Username / Email</label>
                <input type="text" id="edit_site_username" name="site_username"
                    class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm focus:ring-2 focus:ring-indigo-500 focus:bg-white focus:outline-none">
            </div>

            <div>
                <label for="edit_site_password" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                    New Password <span class="text-[11px] font-normal text-slate-400">(leave blank to keep current)</span>
                </label>
                <input type="password" id="edit_site_password" name="site_password" placeholder="••••••••"
                    class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm font-mono focus:ring-2 focus:ring-indigo-500 focus:bg-white focus:outline-none">
            </div>

            <div class="mt-5 flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeEditModal()" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium text-xs rounded-xl transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-xs rounded-xl shadow-sm transition-colors">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: FULL PASSWORD GENERATOR             -->
<!-- ========================================== -->
<div id="generator-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm hidden opacity-0 transition-opacity duration-150">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-md p-6 transform scale-95 transition-transform duration-150" id="generator-modal-card">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200">
            <div class="flex items-center gap-2 text-slate-900 font-bold text-base">
                <i data-lucide="key" class="w-4 h-4 text-indigo-600"></i>
                <span>Password Generator</span>
            </div>
            <button type="button" onclick="closeGeneratorModal()" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <div class="mt-4 space-y-4">
            <!-- Output Display -->
            <div class="relative bg-slate-50 p-3.5 rounded-xl border border-slate-200 flex items-center justify-between">
                <span id="gen-output" class="font-mono text-sm font-bold text-indigo-600 break-all select-all"></span>
                <div class="flex items-center gap-1 ml-2">
                    <button type="button" id="gen-refresh-btn" class="p-1.5 text-slate-400 hover:text-indigo-600 rounded-lg transition-colors" title="Regenerate">
                        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    </button>
                    <button type="button" id="gen-copy-btn" class="p-1.5 text-slate-400 hover:text-indigo-600 rounded-lg transition-colors" title="Copy">
                        <i data-lucide="copy" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <!-- Strength Bar -->
            <div>
                <div class="flex justify-between items-center text-[11px] text-slate-500 mb-1">
                    <span>Generated Strength: <strong id="gen-strength-label" class="text-emerald-600 font-semibold">Strong</strong></span>
                </div>
                <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                    <div id="gen-strength-bar" class="h-full w-full bg-emerald-500 rounded-full"></div>
                </div>
            </div>

            <!-- Length Slider -->
            <div>
                <div class="flex justify-between text-xs font-semibold text-slate-600 mb-1.5">
                    <span>Length:</span>
                    <span id="gen-length-val" class="font-mono text-indigo-600 font-bold">16</span>
                </div>
                <input type="range" id="gen-length-slider" min="8" max="64" value="16"
                    class="w-full accent-indigo-600 bg-slate-200 h-1.5 rounded-lg cursor-pointer">
            </div>

            <!-- Toggles -->
            <div class="grid grid-cols-2 gap-2 text-xs">
                <label class="flex items-center gap-2 cursor-pointer text-slate-700">
                    <input type="checkbox" id="gen-opt-upper" checked class="rounded accent-indigo-600">
                    <span>Uppercase (A-Z)</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer text-slate-700">
                    <input type="checkbox" id="gen-opt-lower" checked class="rounded accent-indigo-600">
                    <span>Lowercase (a-z)</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer text-slate-700">
                    <input type="checkbox" id="gen-opt-numbers" checked class="rounded accent-indigo-600">
                    <span>Numbers (0-9)</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer text-slate-700">
                    <input type="checkbox" id="gen-opt-symbols" checked class="rounded accent-indigo-600">
                    <span>Symbols (!@#$)</span>
                </label>
            </div>

            <!-- Actions -->
            <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                <button type="button" onclick="closeGeneratorModal()" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium text-xs rounded-xl transition-colors">
                    Cancel
                </button>
                <button type="button" id="gen-use-password-btn" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-xs rounded-xl shadow-sm transition-colors flex items-center gap-1.5">
                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                    <span>Use Password</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div id="toast-container"></div>

<!-- Offline Scripts -->
<script src="assets/js/lucide.min.js"></script>
<script src="assets/js/vault.js"></script>
<script src="assets/js/generator.js"></script>

<script>
    if (window.lucide) {
        window.lucide.createIcons();
    }

    <?php if ($flash_message): ?>
        document.addEventListener('DOMContentLoaded', () => {
            showToast("<?= htmlspecialchars(addslashes($flash_message)) ?>", "<?= $flash_type ?>");
        });
    <?php endif; ?>

    // Tab Switcher Function
    function switchTab(tabName) {
        const viewAll = document.getElementById('tab-view-all');
        const viewAdd = document.getElementById('tab-view-add');
        const btnAll = document.getElementById('nav-btn-all');
        const btnAdd = document.getElementById('nav-btn-add');

        if (tabName === 'add') {
            viewAll.classList.add('hidden');
            viewAdd.classList.remove('hidden');

            btnAll.className = "w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition-colors text-left";
            btnAdd.className = "w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors text-left bg-indigo-50 text-indigo-700";

            document.getElementById('site_name').focus();
        } else {
            viewAdd.classList.add('hidden');
            viewAll.classList.remove('hidden');

            btnAll.className = "w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-colors text-left bg-indigo-50 text-indigo-700";
            btnAdd.className = "w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition-colors text-left";
        }
        if (window.lucide) lucide.createIcons();
    }

    // Filter Weak Passwords Action
    function filterWeakPasswords() {
        switchTab('all');
        const searchInput = document.getElementById('vault-search-input');
        if (searchInput) searchInput.value = '';
        
        const tableBody = document.getElementById('vault-table-body');
        const rows = tableBody ? tableBody.getElementsByClassName('vault-row') : [];
        let weakMatches = 0;

        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            const isWeak = row.getAttribute('data-weak') === 'true';
            if (isWeak) {
                row.style.display = '';
                weakMatches++;
            } else {
                row.style.display = 'none';
            }
        }

        const clearBtn = document.getElementById('clear-search-btn');
        if (clearBtn) clearBtn.classList.remove('hidden');

        showToast(`Showing ${weakMatches} weak password ${weakMatches === 1 ? 'entry' : 'entries'}`, 'info');
    }

    // Auto-check URL hash or parameter
    if (window.location.hash === '#add') {
        switchTab('add');
    }

    // Sidebar Generator Button Listener
    const sidebarGenBtn = document.getElementById('open-generator-modal-btn-sidebar');
    if (sidebarGenBtn) {
        sidebarGenBtn.addEventListener('click', () => {
            refreshGeneratedPassword();
            showModal('generator-modal', 'generator-modal-card');
        });
    }

    // Toggle Add Password Visibility
    const toggleAddPassBtn = document.getElementById('toggle-add-pass-visibility');
    const addPassInput = document.getElementById('site_password');
    const toggleAddPassIcon = document.getElementById('toggle-add-pass-icon');

    if (toggleAddPassBtn && addPassInput) {
        toggleAddPassBtn.addEventListener('click', () => {
            const isPassword = addPassInput.getAttribute('type') === 'password';
            addPassInput.setAttribute('type', isPassword ? 'text' : 'password');
            toggleAddPassIcon.setAttribute('data-lucide', isPassword ? 'eye-off' : 'eye');
            lucide.createIcons();
        });
    }

    // Live Strength for Add Credential Input
    const addStrengthBar = document.getElementById('add-strength-bar');
    const addStrengthLabel = document.getElementById('add-strength-label');

    if (addPassInput && addStrengthBar && window.PasswordGenerator) {
        addPassInput.addEventListener('input', (e) => {
            const val = e.target.value;
            if (!val) {
                addStrengthBar.style.width = '0%';
                addStrengthLabel.innerText = 'None';
                addStrengthLabel.className = 'font-semibold text-slate-600';
                return;
            }
            const res = window.PasswordGenerator.evaluateStrength(val);
            addStrengthBar.style.width = res.percent + '%';
            addStrengthBar.className = `h-full rounded-full transition-all duration-300 ${res.colorClass}`;
            addStrengthLabel.innerText = res.label;
        });
    }

    // Quick Fill Generator inside Add Credential Card
    const quickFillBtn = document.getElementById('quick-fill-generate-btn');
    if (quickFillBtn && addPassInput) {
        quickFillBtn.addEventListener('click', () => {
            const generated = PasswordGenerator.generate({ length: 16 });
            addPassInput.value = generated;
            addPassInput.setAttribute('type', 'text');
            if (toggleAddPassIcon) toggleAddPassIcon.setAttribute('data-lucide', 'eye-off');
            addPassInput.dispatchEvent(new Event('input'));
            lucide.createIcons();
            showToast('Generated strong password!', 'info');
        });
    }

    // Table Live Search Filter
    const searchInput = document.getElementById('vault-search-input');
    const clearSearchBtn = document.getElementById('clear-search-btn');
    const tableBody = document.getElementById('vault-table-body');
    const rows = tableBody ? tableBody.getElementsByClassName('vault-row') : [];

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            if (clearSearchBtn) {
                clearSearchBtn.classList.toggle('hidden', query === '');
            }

            let matchCount = 0;
            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const site = row.getAttribute('data-site') || '';
                const user = row.getAttribute('data-user') || '';

                if (site.includes(query) || user.includes(query)) {
                    row.style.display = '';
                    matchCount++;
                } else {
                    row.style.display = 'none';
                }
            }

            let noMatchRow = document.getElementById('no-match-row');
            if (matchCount === 0 && rows.length > 0) {
                if (!noMatchRow) {
                    noMatchRow = document.createElement('tr');
                    noMatchRow.id = 'no-match-row';
                    noMatchRow.innerHTML = '<td colspan="4" class="px-6 py-8 text-center text-slate-500"><i data-lucide="search-x" class="w-6 h-6 mx-auto mb-1 text-slate-400"></i><p class="text-xs font-medium">No matching credentials found.</p></td>';
                    tableBody.appendChild(noMatchRow);
                    lucide.createIcons();
                }
            } else if (noMatchRow) {
                noMatchRow.remove();
            }
        });

        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', () => {
                searchInput.value = '';
                // Restore default view
                for (let i = 0; i < rows.length; i++) {
                    rows[i].style.display = '';
                }
                const noMatchRow = document.getElementById('no-match-row');
                if (noMatchRow) noMatchRow.remove();
                clearSearchBtn.classList.add('hidden');
                searchInput.focus();
            });
        }
    }

    // Modal Helpers
    function showModal(modalId, cardId) {
        const modal = document.getElementById(modalId);
        const card = document.getElementById(cardId);
        if (!modal || !card) return;
        modal.classList.remove('hidden');
        requestAnimationFrame(() => {
            modal.classList.remove('opacity-0');
            card.classList.remove('scale-95');
        });
        if (window.lucide) lucide.createIcons();
    }

    function hideModal(modalId, cardId) {
        const modal = document.getElementById(modalId);
        const card = document.getElementById(cardId);
        if (!modal || !card) return;
        modal.classList.add('opacity-0');
        card.classList.add('scale-95');
        setTimeout(() => modal.classList.add('hidden'), 150);
    }

    // Inline Decrypt Modal Logic with Animated Scan & Deliberate Text Scramble (1200ms)
    async function openDecryptModal(vaultId) {
        showModal('decrypt-modal', 'decrypt-modal-card');
        const loading = document.getElementById('decrypt-loading');
        const content = document.getElementById('decrypt-content');
        const passText = document.getElementById('decrypt-password');
        
        loading.classList.remove('hidden');
        content.classList.add('hidden');

        try {
            const formData = new FormData();
            formData.append('vault_id', vaultId);
            formData.append('format', 'json');

            const startTime = Date.now();
            const resp = await fetch('decrypt.php', {
                method: 'POST',
                body: formData
            });

            const data = await resp.json();

            const elapsed = Date.now() - startTime;
            const remainingDelay = Math.max(0, 850 - elapsed);

            setTimeout(() => {
                if (data.success) {
                    document.getElementById('decrypt-site-name').innerText = data.site_name;
                    const userBox = document.getElementById('decrypt-user-box');
                    if (data.site_username) {
                        userBox.classList.remove('hidden');
                        document.getElementById('decrypt-username').innerText = data.site_username;
                        document.getElementById('copy-modal-user-btn').onclick = () => copyToClipboard(data.site_username, 'Username copied!');
                    } else {
                        userBox.classList.add('hidden');
                    }

                    document.getElementById('copy-modal-pass-btn').onclick = () => copyToClipboard(data.decrypted_password, 'Password copied!');

                    loading.classList.add('hidden');
                    content.classList.remove('hidden');
                    if (window.lucide) lucide.createIcons();

                    animateTextScramble(passText, data.decrypted_password, 1200);
                } else {
                    showToast(data.error || 'Failed to decrypt', 'error');
                    closeDecryptModal();
                }
            }, remainingDelay);

        } catch (err) {
            showToast('Decryption error', 'error');
            closeDecryptModal();
        }
    }

    function closeDecryptModal() {
        hideModal('decrypt-modal', 'decrypt-modal-card');
    }

    // Quick Copy Password Directly From Table
    async function quickCopyPassword(vaultId) {
        try {
            const formData = new FormData();
            formData.append('vault_id', vaultId);
            formData.append('format', 'json');

            const resp = await fetch('decrypt.php', {
                method: 'POST',
                body: formData
            });

            const data = await resp.json();
            if (data.success && data.decrypted_password) {
                await copyToClipboard(data.decrypted_password, `Decrypted & copied password for ${data.site_name}!`);
            } else {
                showToast(data.error || 'Could not decrypt password', 'error');
            }
        } catch (err) {
            showToast('Decryption failed', 'error');
        }
    }

    // Edit Modal Logic
    function openEditModal(id, site, username) {
        document.getElementById('edit-vault-id').value = id;
        document.getElementById('edit_site_name').value = site;
        document.getElementById('edit_site_username').value = username;
        document.getElementById('edit_site_password').value = '';
        showModal('edit-modal', 'edit-modal-card');
    }

    function closeEditModal() {
        hideModal('edit-modal', 'edit-modal-card');
    }

    // Full Password Generator Modal Logic
    const genLengthSlider = document.getElementById('gen-length-slider');
    const genLengthVal = document.getElementById('gen-length-val');
    const genOutput = document.getElementById('gen-output');
    const genRefreshBtn = document.getElementById('gen-refresh-btn');
    const genCopyBtn = document.getElementById('gen-copy-btn');
    const genUseBtn = document.getElementById('gen-use-password-btn');
    const genStrengthBar = document.getElementById('gen-strength-bar');
    const genStrengthLabel = document.getElementById('gen-strength-label');

    function refreshGeneratedPassword() {
        if (!PasswordGenerator) return;
        const length = parseInt(genLengthSlider.value);
        const uppercase = document.getElementById('gen-opt-upper').checked;
        const lowercase = document.getElementById('gen-opt-lower').checked;
        const numbers = document.getElementById('gen-opt-numbers').checked;
        const symbols = document.getElementById('gen-opt-symbols').checked;

        const pwd = PasswordGenerator.generate({ length, uppercase, lowercase, numbers, symbols });
        genOutput.innerText = pwd;

        const str = PasswordGenerator.evaluateStrength(pwd);
        genStrengthBar.style.width = str.percent + '%';
        genStrengthBar.className = `h-full rounded-full transition-all duration-300 ${str.colorClass}`;
        genStrengthLabel.innerText = str.label;
    }

    function closeGeneratorModal() {
        hideModal('generator-modal', 'generator-modal-card');
    }

    if (genLengthSlider) {
        genLengthSlider.addEventListener('input', (e) => {
            genLengthVal.innerText = e.target.value;
            refreshGeneratedPassword();
        });
    }

    ['gen-opt-upper', 'gen-opt-lower', 'gen-opt-numbers', 'gen-opt-symbols'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', refreshGeneratedPassword);
    });

    if (genRefreshBtn) genRefreshBtn.addEventListener('click', refreshGeneratedPassword);
    if (genCopyBtn) genCopyBtn.addEventListener('click', () => copyToClipboard(genOutput.innerText, 'Generated password copied!'));

    if (genUseBtn && addPassInput) {
        genUseBtn.addEventListener('click', () => {
            addPassInput.value = genOutput.innerText;
            addPassInput.setAttribute('type', 'text');
            if (toggleAddPassIcon) toggleAddPassIcon.setAttribute('data-lucide', 'eye-off');
            addPassInput.dispatchEvent(new Event('input'));
            closeGeneratorModal();
            switchTab('add');
            lucide.createIcons();
            showToast('Applied generated password', 'success');
        });
    }
</script>
</body>
</html>