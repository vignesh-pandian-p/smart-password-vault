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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vault_id'])) {
    $vault_id = $_POST['vault_id'];
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT encrypted_password FROM vault WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $vault_id, $user_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($encrypted);
        $stmt->fetch();
        $decrypted = decrypt_password($encrypted);
        echo "<h3>🔓 Decrypted Password: $decrypted</h3>";
        echo '<a href="dashboard.php">← Back to Dashboard</a>';
    } else {
        echo "Entry not found.";
    }
}
?>
