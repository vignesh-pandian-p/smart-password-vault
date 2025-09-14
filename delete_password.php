<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vault_id'])) {
    $vault_id = $_POST['vault_id'];
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("DELETE FROM vault WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $vault_id, $user_id);
    $stmt->execute();
}

header("Location: dashboard.php");
exit;
?>
