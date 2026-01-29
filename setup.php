<?php
$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = trim($_POST['db_host']);
    $db_name = trim($_POST['db_name']);
    $db_user = trim($_POST['db_user']);
    $db_pass = $_POST['db_pass'];
    $overwrite = isset($_POST['overwrite_db']);

    if (empty($db_host) || empty($db_name) || empty($db_user)) {
        $msg = "Please fill in all required fields (Host, Database Name, User).";
        $msg_type = "error";
    } else {
        try {
            $dsn_no_db = "mysql:host=$db_host;charset=utf8mb4";
            $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
            $pdo = new PDO($dsn_no_db, $db_user, $db_pass, $options);

            if ($overwrite) {
                $pdo->exec("DROP DATABASE IF EXISTS `$db_name`");
            }
            
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
            $pdo->exec("USE `$db_name`");

            // 2. Import Schema (Rollup)
            $sqlFile = __DIR__ . '/database.sql';
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                if ($sql) {
                    $pdo->exec($sql);
                }
            } else {
                throw new Exception("Schema file (database.sql) not found.");
            }

            // 2.5 Create Admin User
            $app_admin_user = trim($_POST['app_admin_user'] ?? 'admin');
            $app_admin_pass = $_POST['app_admin_pass'] ?? '';

            if (!empty($app_admin_user) && !empty($app_admin_pass)) {
                $hashedPass = password_hash($app_admin_pass, PASSWORD_DEFAULT);
                // Use REPLACE to ensure password is updated/set even if user exists
                $stmt = $pdo->prepare("REPLACE INTO users (username, password, role) VALUES (?, ?, 'admin')");
                $stmt->execute([$app_admin_user, $hashedPass]);
            }

            // 3. Write Config File
            $configContent = "<?php\n";
            $configContent .= "define('DB_HOST', '$db_host');\n";
            $configContent .= "define('DB_NAME', '$db_name');\n";
            $configContent .= "define('DB_USER', '$db_user');\n";
            $configContent .= "define('DB_PASS', '$db_pass');\n";
            
            if (file_put_contents('config.php', $configContent) === false) {
                 throw new Exception("Could not write config.php. Check permissions.");
            }

            // 4. Create Uploads Directory
            if (!is_dir('uploads/')) mkdir('uploads/', 0755, true);

            header("Location: index.php");
            exit;

        } catch (PDOException $e) {
            $msg = "Database Error: " . $e->getMessage();
            $msg_type = "error";
        } catch (Exception $e) {
            $msg = "Error: " . $e->getMessage();
            $msg_type = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - Fund & Expense Tracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: { 900: '#0f172a', 800: '#1e293b', 700: '#334155' },
                        accent: { 500: '#0ea5e9', 600: '#0284c7' }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-900 text-gray-100 font-sans antialiased min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full bg-gray-800 rounded-xl shadow-2xl border border-gray-700 p-8">
        <div class="text-center mb-8">
             <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-accent-500/10 text-accent-500 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
             </div>
            <h1 class="text-2xl font-bold text-white">Tasks Manager <span class="text-accent-500">v3.0</span></h1>
            <p class="text-sm text-gray-400 mt-2">Initialize your funds, tasks, and subscription tracker.</p>
        </div>

        <?php if ($msg): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $msg_type == 'error' ? 'bg-rose-500/10 border border-rose-500/20 text-rose-400' : 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-400'; ?>">
                <div class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 mr-3">
                      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    <?php echo $msg; ?>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Database Host</label>
                <input type="text" name="db_host" value="localhost" required 
                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2.5 text-white focus:ring-2 focus:ring-accent-500 outline-none transition-all placeholder-gray-600">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Database Name</label>
                <input type="text" name="db_name" value="tasks_manager" required 
                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2.5 text-white focus:ring-2 focus:ring-accent-500 outline-none transition-all placeholder-gray-600">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">User</label>
                    <input type="text" name="db_user" value="root" required 
                        class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2.5 text-white focus:ring-2 focus:ring-accent-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Password</label>
                    <input type="password" name="db_pass" placeholder="••••"
                        class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2.5 text-white focus:ring-2 focus:ring-accent-500 outline-none">
                </div>
            </div>

            <div class="border-t border-gray-700 pt-4 mt-6 mb-6">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-4">Application Admin Setup</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1">Admin Username</label>
                        <input type="text" name="app_admin_user" value="admin" required 
                            class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2.5 text-white focus:ring-2 focus:ring-accent-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1">Admin Password</label>
                        <input type="password" name="app_admin_pass" placeholder="New Password" required
                            class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2.5 text-white focus:ring-2 focus:ring-accent-500 outline-none">
                    </div>
                </div>
            </div>

            <div class="flex items-start p-3 bg-gray-900/50 rounded-lg border border-gray-700/50">
                <div class="flex items-center h-5">
                    <input id="overwrite_db" name="overwrite_db" type="checkbox" class="w-4 h-4 text-accent-600 bg-gray-900 border-gray-700 rounded focus:ring-accent-500 focus:ring-offset-gray-900">
                </div>
                <div class="ml-3 text-xs">
                    <label for="overwrite_db" class="font-medium text-gray-300">Overwrite Existing Database</label>
                    <p class="text-gray-500 mt-1">Check this if you want to wipe the existing database and start fresh with v3 schema.</p>
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full bg-accent-600 hover:bg-accent-500 text-white font-bold py-3.5 rounded-xl shadow-lg flex justify-center items-center transition-all transform active:scale-95">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 mr-2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Setup Tasks Manager
                </button>
            </div>
        </form>
    </div>

</body>
</html>
