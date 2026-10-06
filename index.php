<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$page_title = "Smart Password Vault";
include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="flex-grow flex flex-col justify-center py-16 sm:py-24">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center animate-fade-in">
        
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-100 border border-slate-200 text-slate-700 text-xs font-medium mb-6">
            <i data-lucide="shield" class="w-3.5 h-3.5 text-indigo-600"></i>
            <span>Local & Encrypted Credential Manager</span>
        </div>

        <h1 class="text-3xl sm:text-5xl font-bold tracking-tight text-slate-900 leading-tight">
            Simple, secure password storage.
        </h1>

        <p class="mt-4 text-base sm:text-lg text-slate-600 max-w-xl mx-auto leading-relaxed">
            Store, search, and generate website credentials directly on your local system with AES-256 encryption.
        </p>

        <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="login.php" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm rounded-lg shadow-sm transition-colors">
                <i data-lucide="log-in" class="w-4 h-4"></i>
                <span>Sign In to Vault</span>
            </a>
            <a href="register.php" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-medium text-sm rounded-lg shadow-sm transition-colors">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                <span>Create an Account</span>
            </a>
        </div>

        <!-- Minimal 3-Point Highlights -->
        <div class="mt-16 grid grid-cols-1 sm:grid-cols-3 gap-6 text-left border-t border-slate-200 pt-12">
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center mb-3">
                    <i data-lucide="database" class="w-4 h-4"></i>
                </div>
                <h3 class="text-sm font-semibold text-slate-900">Local Database</h3>
                <p class="mt-1 text-xs text-slate-500 leading-normal">
                    Stored securely in your local MySQL instance. No cloud syncing or third-party tracking.
                </p>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center mb-3">
                    <i data-lucide="lock" class="w-4 h-4"></i>
                </div>
                <h3 class="text-sm font-semibold text-slate-900">AES-256 Encryption</h3>
                <p class="mt-1 text-xs text-slate-500 leading-normal">
                    Credentials are encrypted before saving, with quick inline decryption when needed.
                </p>
            </div>

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center mb-3">
                    <i data-lucide="key" class="w-4 h-4"></i>
                </div>
                <h3 class="text-sm font-semibold text-slate-900">Password Tools</h3>
                <p class="mt-1 text-xs text-slate-500 leading-normal">
                    Includes a built-in random password generator and live strength indicator.
                </p>
            </div>
        </div>

    </div>
</main>

<?php include 'includes/footer.php'; ?>