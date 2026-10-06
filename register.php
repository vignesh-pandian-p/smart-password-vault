<?php
session_start();
include 'db.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "All fields are required.";
    } elseif (strlen($username) < 3) {
        $error = "Username must be at least 3 characters long.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, $hashed);

        if ($stmt->execute()) {
            $_SESSION['flash_message'] = "Account created successfully! Please login.";
            $_SESSION['flash_type'] = "success";
            header("Location: login.php");
            exit;
        } else {
            if ($conn->errno == 1062) {
                $error = "This username is already taken. Please choose another one.";
            } else {
                $error = "An unexpected error occurred. Please try again.";
            }
        }
        $stmt->close();
    }
}

$page_title = "Create Account";
include 'includes/header.php';
?>

<main class="min-h-screen flex flex-col justify-between bg-slate-50 py-8 px-4 sm:px-6">

    <!-- Top Navigation Link -->
    <div class="max-w-md w-full mx-auto">
        <a href="index.php" class="inline-flex items-center space-x-1.5 text-slate-500 hover:text-indigo-600 text-xs font-medium transition-colors">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
            <span>Back to Home</span>
        </a>
    </div>

    <!-- Register Card -->
    <div class="w-full max-w-md mx-auto bg-white rounded-2xl border border-slate-200 shadow-sm p-8 sm:p-10 my-auto">
        
        <!-- Brand Header inside Card -->
        <div class="text-center mb-8">
            <a href="index.php" class="inline-flex items-center gap-2.5 mb-3 group">
                <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-sm">
                    <i data-lucide="shield-check" class="w-5 h-5"></i>
                </div>
                <span class="font-bold text-xl text-slate-900 tracking-tight">Smart Vault</span>
            </a>
            <h1 class="text-lg font-bold text-slate-900">Create your account</h1>
            <p class="text-xs text-slate-500 mt-1">Set up a local personal password vault</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-3.5 py-2.5 rounded-xl mb-6 flex items-start gap-2.5 text-xs">
                <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0 text-red-500 mt-0.5"></i>
                <span class="font-medium"><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="post" action="register.php" class="space-y-4">
            <div>
                <label for="username" class="block text-xs font-semibold text-slate-700 mb-1.5">Username</label>
                <input type="text" id="username" name="username" placeholder="Choose a username" required autofocus
                    class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 placeholder-slate-400 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold text-slate-700 mb-1.5">Master Password</label>
                <div class="relative">
                    <input type="password" id="password" name="password" placeholder="Create password" required
                        class="w-full pl-3.5 pr-10 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-900 placeholder-slate-400 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                    <button type="button" id="toggle-password-btn" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors" title="Toggle visibility">
                        <i data-lucide="eye" id="toggle-password-icon" class="w-4 h-4"></i>
                    </button>
                </div>
                
                <!-- Strength Meter -->
                <div class="mt-2">
                    <div class="flex justify-between items-center text-[11px] text-slate-500 mb-1">
                        <span>Strength: <strong id="strength-label" class="font-semibold text-slate-600">None</strong></span>
                        <span class="text-[11px] text-slate-400">Min 6 characters</span>
                    </div>
                    <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                        <div id="strength-bar" class="h-full w-0 transition-all duration-300 bg-red-500 rounded-full"></div>
                    </div>
                </div>
            </div>

            <button type="submit"
                class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm rounded-xl shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 mt-2">
                Create Account
            </button>
        </form>

        <div class="mt-6 text-center border-t border-slate-100 pt-5">
            <p class="text-xs text-slate-500">
                Already have an account? 
                <a href="login.php" class="font-semibold text-indigo-600 hover:underline">
                    Sign in
                </a>
            </p>
        </div>
    </div>

    <!-- Footer -->
    <div class="text-center text-xs text-slate-400 max-w-md w-full mx-auto">
        &copy; <?= date('Y') ?> Smart Password Vault. All rights reserved.
    </div>
</main>

<script src="assets/js/lucide.min.js"></script>
<script src="assets/js/generator.js"></script>
<script>
    if (window.lucide) lucide.createIcons();
    const toggleBtn = document.getElementById('toggle-password-btn');
    const passInput = document.getElementById('password');
    const passIcon = document.getElementById('toggle-password-icon');
    const strengthBar = document.getElementById('strength-bar');
    const strengthLabel = document.getElementById('strength-label');

    if (toggleBtn && passInput) {
        toggleBtn.addEventListener('click', () => {
            const isPassword = passInput.getAttribute('type') === 'password';
            passInput.setAttribute('type', isPassword ? 'text' : 'password');
            passIcon.setAttribute('data-lucide', isPassword ? 'eye-off' : 'eye');
            lucide.createIcons();
        });
    }

    if (passInput && strengthBar && window.PasswordGenerator) {
        passInput.addEventListener('input', (e) => {
            const val = e.target.value;
            if (!val) {
                strengthBar.style.width = '0%';
                strengthLabel.innerText = 'None';
                strengthLabel.className = 'font-semibold text-slate-600';
                return;
            }
            const res = window.PasswordGenerator.evaluateStrength(val);
            strengthBar.style.width = res.percent + '%';
            strengthBar.className = `h-full rounded-full transition-all duration-300 ${res.colorClass}`;
            strengthLabel.innerText = res.label;
        });
    }
</script>
</body>
</html>