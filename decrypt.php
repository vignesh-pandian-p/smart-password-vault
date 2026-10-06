<?php
session_start();
include 'db.php';
include 'includes/crypto.php';

if (!isset($_SESSION['user_id'])) {
    if (isset($_POST['format']) && $_POST['format'] === 'json') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
    header("Location: login.php");
    exit;
}

$decrypted_password = null;
$site_name = null;
$site_username = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vault_id'])) {
    $vault_id = intval($_POST['vault_id']);
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT site_name, site_username, encrypted_password FROM vault WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $vault_id, $user_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($fetched_site_name, $fetched_site_username, $encrypted);
        $stmt->fetch();
        $site_name = $fetched_site_name;
        $site_username = $fetched_site_username;
        $decrypted_password = decrypt_password($encrypted);

        if ($decrypted_password === null) {
            $error = "Failed to decrypt password. The record may be corrupted.";
        }
    } else {
        $error = "Entry not found or access denied.";
    }

    $stmt->close();
} else {
    header("Location: dashboard.php");
    exit;
}

// Return JSON if requested by AJAX
if (isset($_POST['format']) && $_POST['format'] === 'json') {
    header('Content-Type: application/json');
    if ($error === null && $decrypted_password !== null) {
        echo json_encode([
            'success' => true,
            'vault_id' => $vault_id,
            'site_name' => $site_name,
            'site_username' => $site_username,
            'decrypted_password' => $decrypted_password
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => $error ?? 'Unknown error'
        ]);
    }
    exit;
}

$page_title = "Decrypted Credential";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="flex-grow flex items-center justify-center py-12 px-4 sm:px-6">
    <div class="w-full max-w-lg bg-white rounded-2xl shadow-sm border border-slate-200 p-8 animate-fade-in relative">
        <a href="dashboard.php" class="inline-flex items-center space-x-1.5 text-xs font-medium text-slate-500 hover:text-indigo-600 transition-colors mb-6">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
            <span>Back to Dashboard</span>
        </a>

        <div class="text-center mb-6">
            <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center mx-auto mb-3">
                <i data-lucide="key-round" class="w-6 h-6"></i>
            </div>
            <h1 class="text-xl font-bold text-slate-900">Decrypted Credential</h1>
            <p class="text-xs text-slate-500 mt-1">Safely view your stored login</p>
        </div>

        <?php if ($decrypted_password !== null): ?>
            <div class="space-y-4 bg-slate-50 rounded-xl p-5 border border-slate-200 relative overflow-hidden">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Website</label>
                    <div class="text-sm font-semibold text-slate-900 flex items-center space-x-2">
                        <i data-lucide="globe" class="w-4 h-4 text-indigo-600"></i>
                        <span><?= htmlspecialchars($site_name) ?></span>
                    </div>
                </div>

                <?php if (!empty($site_username)): ?>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Username / Email</label>
                    <div class="text-sm text-slate-700 flex items-center justify-between">
                        <span id="site-username-text" class="font-mono"><?= htmlspecialchars($site_username) ?></span>
                        <button type="button" onclick="copyToClipboard('<?= htmlspecialchars(addslashes($site_username)) ?>', 'Username copied!')" class="p-1.5 text-slate-400 hover:text-indigo-600 rounded hover:bg-slate-200 transition-colors" title="Copy Username">
                            <i data-lucide="copy" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Decrypted Password</label>
                    <div class="flex items-center justify-between bg-white p-3 rounded-lg border border-slate-200">
                        <span id="decrypted-password-text" class="font-mono text-sm font-semibold text-indigo-600 select-all break-all"><?= htmlspecialchars($decrypted_password) ?></span>
                        <button type="button" onclick="copyToClipboard('<?= htmlspecialchars(addslashes($decrypted_password)) ?>', 'Password copied!')" class="ml-2 p-1.5 text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50 rounded-lg transition-colors flex-shrink-0" title="Copy Password">
                            <i data-lucide="copy" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <a href="dashboard.php" class="w-full text-center py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm rounded-xl shadow-sm transition-colors">
                    Done & Return to Dashboard
                </a>
            </div>
        <?php else: ?>
            <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-center">
                <i data-lucide="alert-triangle" class="w-6 h-6 text-red-500 mx-auto mb-2"></i>
                <p class="text-xs font-medium text-red-700"><?= htmlspecialchars($error) ?></p>
                <a href="dashboard.php" class="inline-block mt-3 text-xs font-semibold text-indigo-600 hover:underline">Return to Dashboard</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const passElem = document.getElementById('decrypted-password-text');
        if (passElem && window.animateTextScramble) {
            const rawText = passElem.innerText;
            animateTextScramble(passElem, rawText, 500);
        }
    });
</script>

<?php include 'includes/footer.php'; ?>