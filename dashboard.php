<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch passwords for the logged-in user
$stmt = $conn->prepare("SELECT id, site_name, site_username, encrypted_password, created_at FROM vault WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Password Vault</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
    <a href="logout.php">Logout</a>

    <h3>🔐 Add New Credential</h3>
    <form method="post" action="add_password.php">
        <input type="text" name="site_name" placeholder="Website Name" required><br>
        <input type="text" name="site_username" placeholder="Username/Email" required><br>
        <input type="password" name="site_password" placeholder="Password" required><br>
        <button type="submit">Add</button>
    </form>

    <h3>📄 Saved Passwords</h3>
    <table border="1" cellpadding="8">
        <tr>
            <th>Site</th>
            <th>Username</th>
            <th>Password (Encrypted)</th>
            <th>Show</th>
            <th>Delete</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['site_name']) ?></td>
            <td><?= htmlspecialchars($row['site_username']) ?></td>
            <td><?= substr($row['encrypted_password'], 0, 20) ?>...</td>
            <td>
                <form method="post" action="decrypt.php" style="display:inline;">
                    <input type="hidden" name="vault_id" value="<?= $row['id'] ?>">
                    <button type="submit">Show</button>
                </form>
            </td>
            <td>
                <form method="post" action="delete_password.php" style="display:inline;">
                    <input type="hidden" name="vault_id" value="<?= $row['id'] ?>">
                    <button type="submit" onclick="return confirm('Delete this entry?')">Delete</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
