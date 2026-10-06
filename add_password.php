<?php
session_start();
include 'db.php';
include 'includes/crypto.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $site = trim($_POST['site_name'] ?? '');
    $username = trim($_POST['site_username'] ?? '');
    $password = $_POST['site_password'] ?? '';
    
    if (!empty($site) && !empty($password)) {
        $encrypted = encrypt_password($password);

        $stmt = $conn->prepare("INSERT INTO vault (user_id, site_name, site_username, encrypted_password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $site, $username, $encrypted);

        if ($stmt->execute()) {
            $_SESSION['flash_message'] = "Credential saved successfully!";
            $_SESSION['flash_type'] = "success";
        } else {
            $_SESSION['flash_message'] = "Error saving credential.";
            $_SESSION['flash_type'] = "error";
        }
        $stmt->close();
    }
}

header("Location: dashboard.php");
exit;
