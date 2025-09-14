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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Smart Password Vault</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-100">

    <nav class="bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <i data-lucide="lock" class="w-6 h-6 mr-2 text-indigo-600"></i>
                    <span class="font-bold text-xl text-gray-700">Password Vault</span>
                </div>
                <div class="flex items-center">
                    <span class="text-gray-600 mr-4">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                    <a href="logout.php" class="flex items-center text-sm font-medium text-gray-500 hover:text-indigo-600">
                        <i data-lucide="log-out" class="w-4 h-4 mr-1"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <!-- Add New Credential Form -->
            <div class="md:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-700 flex items-center mb-4">
                        <i data-lucide="plus-circle" class="w-5 h-5 mr-2"></i>
                        Add New Credential
                    </h3>
                    <form method="post" action="add_password.php" class="space-y-4">
                        <div>
                            <label for="site_name" class="block text-sm font-medium text-gray-700">Website Name</label>
                            <input type="text" id="site_name" name="site_name" placeholder="e.g., Google" required
                                   class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="site_username" class="block text-sm font-medium text-gray-700">Username/Email</label>
                            <input type="text" id="site_username" name="site_username" placeholder="e.g., user@example.com" required
                                   class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="site_password" class="block text-sm font-medium text-gray-700">Password</label>
                            <input type="password" id="site_password" name="site_password" placeholder="Enter Password" required
                                   class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <button type="submit"
                                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Add Credential
                        </button>
                    </form>
                </div>
            </div>

            <!-- Saved Passwords Table -->
            <div class="md:col-span-2">
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-700 flex items-center">
                            <i data-lucide="list" class="w-5 h-5 mr-2"></i>
                            Saved Passwords
                        </h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Site</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Password (Encrypted)</th>
                                    <th scope="col" class="relative px-6 py-3">
                                        <span class="sr-only">Actions</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($row['site_name']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($row['site_username']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono"><?= substr($row['encrypted_password'], 0, 20) ?>...</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end space-x-2">
                                            <form method="post" action="decrypt.php" class="inline">
                                                <input type="hidden" name="vault_id" value="<?= $row['id'] ?>">
                                                <button type="submit" class="text-indigo-600 hover:text-indigo-900" title="Show Password">
                                                    <i data-lucide="eye" class="w-5 h-5"></i>
                                                </button>
                                            </form>
                                            <form method="post" action="delete_password.php" class="inline" onsubmit="return confirm('Are you sure you want to delete this entry?')">
                                                <input type="hidden" name="vault_id" value="<?= $row['id'] ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-900" title="Delete Entry">
                                                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script>
      lucide.createIcons();
    </script>
</body>
</html>
