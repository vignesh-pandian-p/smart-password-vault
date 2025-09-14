<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

function decrypt_password($encrypted) {
    $key = "my_secret_encryption_key_123";
    $iv = "1234567890123456";
    return openssl_decrypt($encrypted, "AES-256-CBC", $key, 0, $iv);
}

$decrypted_password = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vault_id'])) {
    $vault_id = $_POST['vault_id'];
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT site_name, encrypted_password FROM vault WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $vault_id, $user_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($site_name, $encrypted);
        $stmt->fetch();
        $decrypted_password = decrypt_password($encrypted);
    } else {
        $error = "Entry not found or you don't have permission to view it.";
    }
} else {
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Decrypted Password - Smart Password Vault</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 flex items-center justify-center h-screen">

    <div class="w-full max-w-md bg-white dark:bg-gray-800 rounded-lg shadow-md p-8">
        <div class="flex justify-center mb-6">
            <h1 class="text-2xl font-bold text-gray-700 dark:text-gray-200 flex items-center">
                <i data-lucide="lock" class="w-6 h-6 mr-2"></i>
                Smart Password Vault
            </h1>
        </div>

        <?php if ($decrypted_password !== null): ?>
            <h2 class="text-xl font-semibold text-center text-gray-600 dark:text-gray-300 mb-4">Decrypted Password for <?= htmlspecialchars($site_name) ?></h2>

            <div class="relative p-4 bg-gray-100 dark:bg-gray-700 rounded-md mb-4">
                <p id="password-to-copy" class="text-lg font-mono text-gray-800 dark:text-gray-200 break-all"><?= htmlspecialchars($decrypted_password) ?></p>
                <button id="copy-button" class="absolute top-2 right-2 p-2 text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400" title="Copy to Clipboard">
                    <i data-lucide="copy" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="text-center text-sm text-green-600 dark:text-green-400 mb-4" id="copy-success" style="display: none;">
                Password copied to clipboard!
            </div>

        <?php else: ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?= $error ?></span>
            </div>
        <?php endif; ?>

        <a href="dashboard.php" class="w-full flex justify-center items-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
            Back to Dashboard
        </a>
    </div>

    <script>
        lucide.createIcons();

        function getCookie(name) {
            const nameEQ = name + "=";
            const ca = document.cookie.split(';');
            for(let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
            }
            return null;
        }

        function applyTheme() {
            const theme = getCookie('theme');
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }

        applyTheme();

        const copyButton = document.getElementById('copy-button');
        if (copyButton) {
            copyButton.addEventListener('click', () => {
                const password = document.getElementById('password-to-copy').innerText;
                navigator.clipboard.writeText(password).then(() => {
                    const successMessage = document.getElementById('copy-success');
                    successMessage.style.display = 'block';
                    setTimeout(() => {
                        successMessage.style.display = 'none';
                    }, 2000);
                });
            });
        }
    </script>
</body>
</html>
