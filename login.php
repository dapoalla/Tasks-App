<?php
session_start();
require_once 'db.php';

$error = '';

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo htmlspecialchars($settings['app_name'] ?? 'Church Funds'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;900&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen flex items-center justify-center p-4">

    <div class="bg-gray-800 border border-gray-700 rounded-3xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="p-8 text-center border-b border-gray-700">
            <h1 class="text-3xl font-black text-white uppercase tracking-tight mb-2">
                <?php echo htmlspecialchars($settings['app_name'] ?? 'Tasks Manager'); ?>
            </h1>
            <p class="text-gray-400 text-sm">Sign in to access the dashboard</p>
        </div>
        
        <form method="POST" class="p-8 space-y-6">
            <?php if ($error): ?>
                <div class="bg-rose-500/10 border border-rose-500/30 text-rose-400 p-3 rounded-xl text-sm font-bold text-center">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Username</label>
                <input type="text" name="username" required class="w-full bg-gray-900 border border-gray-700 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-blue-500 outline-none transition-all">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Password</label>
                <input type="password" name="password" required class="w-full bg-gray-900 border border-gray-700 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-blue-500 outline-none transition-all">
            </div>

            <button type="submit" name="login" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-black py-4 rounded-xl transition-all shadow-lg text-lg uppercase tracking-widest transform active:scale-95 shadow-blue-900/20">
                Sign In
            </button>
        </form>
        
        <div class="bg-gray-900/50 p-6 text-center text-xs text-gray-500 border-t border-gray-700">
            Authorized Personnel Only
        </div>
    </div>

</body>
</html>
