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
    $vault_id = intval($_POST['vault_id'] ?? 0);
    $site = trim($_POST['site_name'] ?? '');
    $username = trim($_POST['site_username'] ?? '');
    $password = $_POST['site_password'] ?? '';

    if ($vault_id > 0 && !empty($site)) {
        if (!empty($password)) {
            $encrypted = encrypt_password($password);
            $stmt = $conn->prepare("UPDATE vault SET site_name = ?, site_username = ?, encrypted_password = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("sssii", $site, $username, $encrypted, $vault_id, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE vault SET site_name = ?, site_username = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ssii", $site, $username, $vault_id, $user_id);
        }

        if ($stmt->execute()) {
            $_SESSION['flash_message'] = "Credential updated successfully!";
            $_SESSION['flash_type'] = "success";
        } else {
            $_SESSION['flash_message'] = "Error updating credential.";
            $_SESSION['flash_type'] = "error";
        }
        $stmt->close();
    }
}

header("Location: dashboard.php");
exit;
