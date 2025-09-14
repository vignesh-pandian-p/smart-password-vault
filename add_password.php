<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

function encrypt_password($password) {
    $key = "my_secret_encryption_key_123"; // 🔐 should be 32 chars for AES-256
    $iv = "1234567890123456";              // 16 chars IV
    return openssl_encrypt($password, "AES-256-CBC", $key, 0, $iv);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $site = $_POST['site_name'];
    $username = $_POST['site_username'];
    $password = $_POST['site_password'];
    
    $encrypted = encrypt_password($password);

    $stmt = $conn->prepare("INSERT INTO vault (user_id, site_name, site_username, encrypted_password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $site, $username, $encrypted);

    if ($stmt->execute()) {
        header("Location: dashboard.php");
        exit;
    } else {
        echo "Error adding password.";
    }
}
?>
