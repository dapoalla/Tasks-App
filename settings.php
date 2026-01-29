<?php
require_once 'db.php';
$success_msg = '';
$error_msg = '';

// Handle Settings Update
if (isset($_POST['update_settings'])) {
    $church_name = $_POST['church_name'] ?? '';
    $dept_name = $_POST['dept_name'] ?? '';
    $smtp_host = $_POST['smtp_host'] ?? '';
    $smtp_user = $_POST['smtp_user'] ?? '';
    $smtp_pass = $_POST['smtp_pass'] ?? '';
    $smtp_port = intval($_POST['smtp_port'] ?? 587);
    $smtp_from_email = $_POST['smtp_from_email'] ?? '';
    $smtp_from_name = $_POST['smtp_from_name'] ?? '';
    $notification_emails = $_POST['notification_emails'] ?? '';
    $report_recipient = $_POST['report_recipient'] ?? '';

    $stmt = $pdo->prepare("UPDATE settings SET 
        church_name = ?, dept_name = ?, 
        smtp_host = ?, smtp_user = ?, smtp_pass = ?, 
        smtp_port = ?, smtp_from_email = ?, smtp_from_name = ?,
        notification_emails = ?, report_recipient_email = ?
        WHERE id = 1");
    
    if ($stmt->execute([
        $church_name, $dept_name, 
        $smtp_host, $smtp_user, $smtp_pass, 
        $smtp_port, $smtp_from_email, $smtp_from_name,
        $notification_emails, $report_recipient
    ])) {
        $success_msg = "Settings updated successfully!";
        // Refresh local settings array
        $settingsStmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
        $settings = $settingsStmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $error_msg = "Failed to update settings.";
    }
}

// Handle Test Email
if (isset($_POST['test_email'])) {
    require_once 'mail_helper.php';
    $to = $_POST['test_recipient'] ?? '';
    if (empty($to)) {
        $error_msg = "Please provide a recipient email for the test.";
    } else {
        $subject = "SMTP Test - " . ($settings['app_name'] ?? 'Church Funds Manager');
        $message = getEmailTemplate("SMTP Connection Success", "<p>This is a test email from your <strong>" . ($settings['app_name'] ?? 'Church Funds Manager') . " v2</strong> suite. Your SMTP settings are correctly configured!</p>");
        if (send_mail($to, $subject, $message)) {
            $success_msg = "Test email sent successfully to $to!";
        } else {
            $error_msg = "Failed to send test email. Check your SMTP settings and logs.";
        }
    }
}

// Handle Backup Action
if (isset($_POST['backup'])) {
    $tables = ['funds', 'fund_items', 'expenses', 'settings', 'tasks', 'internet_subscriptions', 'users'];
    $sqlScript = "-- Database Backup for " . ($settings['app_name'] ?? 'Church Funds Manager') . "\n";
    $sqlScript .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";

    foreach ($tables as $table) {
        $sqlScript .= "-- Table: $table\n";
        
        // Get Create Table Statement
        $stmt = $pdo->query("SHOW CREATE TABLE $table");
        $row = $stmt->fetch(PDO::FETCH_NUM);
        $sqlScript .= "DROP TABLE IF EXISTS `$table`;\n";
        $sqlScript .= $row[1] . ";\n\n";

        // Get Data
        $stmt = $pdo->query("SELECT * FROM $table");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($rows) > 0) {
            $sqlScript .= "INSERT INTO `$table` VALUES\n";
            $valuesArr = [];
            foreach ($rows as $row) {
                $values = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $values[] = "NULL";
                    } else {
                        $values[] = $pdo->quote($value);
                    }
                }
                $valuesArr[] = "(" . implode(", ", $values) . ")";
            }
            $sqlScript .= implode(",\n", $valuesArr) . ";\n\n";
        }
    }

    // Force Download
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="church_funds_backup_' . date('Y-m-d_H-i') . '.sql"');
    header('Content-Length: ' . strlen($sqlScript));
    echo $sqlScript;
    exit;
}

// Handle Restore Action
if (isset($_POST['restore'])) {
    if (isset($_FILES['restore_file']) && $_FILES['restore_file']['error'] === UPLOAD_ERR_OK) {
        $fileType = strtolower(pathinfo($_FILES['restore_file']['name'], PATHINFO_EXTENSION));
        
        if ($fileType === 'sql') {
            $sqlContent = file_get_contents($_FILES['restore_file']['tmp_name']);
            
            // Basic validation
            if (strpos($sqlContent, 'funds') !== false || strpos($sqlContent, 'settings') !== false || strpos($sqlContent, 'tasks') !== false) {
                try {
                    // Disable foreign key checks for restore
                    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                    
                    // Improved SQL Splitter (ignores semicolons in quotes)
                    $queries = preg_split("/;(?=(?:[^']*'[^']*')*[^']*$)/", $sqlContent);

                    foreach ($queries as $query) {
                        $query = trim($query);
                        if (!empty($query)) {
                            $pdo->exec($query);
                        }
                    }
                    
                    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                    $success_msg = "Database restored successfully!";
                } catch (PDOException $e) {
                    $error_msg = "Restore Error: " . $e->getMessage();
                }
            } else {
                $error_msg = "Invalid backup file. Tables not recognized.";
            }
        } else {
             $error_msg = "Please upload a valid .sql file.";
        }
    } else {
        $error_msg = "Error uploading file.";
    }
}

// Handle User Management
if (isset($_POST['add_user'])) {
    $new_user = trim($_POST['new_username']);
    $new_pass = $_POST['new_password'];
    $role = $_POST['role'] ?? 'admin';

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$new_user]);
    if ($stmt->fetchColumn() > 0) {
        $error_msg = "Username already exists.";
    } else {
        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        if ($stmt->execute([$new_user, $hashed, $role])) {
            $success_msg = "User added successfully!";
        } else {
            $error_msg = "Failed to add user.";
        }
    }
}

// Handle Password Update
if (isset($_POST['update_password'])) {
    $user_id = $_POST['user_id'];
    $new_pass = $_POST['new_password'];
    
    $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    if ($stmt->execute([$hashed, $user_id])) {
        $success_msg = "Password updated successfully!";
    } else {
        $error_msg = "Failed to update password.";
    }
}

// Handle User Delete
if (isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    // Prevent self-delete
    if ($user_id == $_SESSION['user_id']) {
        $error_msg = "You cannot delete your own account.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$user_id])) {
            $success_msg = "User deleted successfully!";
        } else {
            $error_msg = "Failed to delete user.";
        }
    }
}

include 'header.php';
?>

<div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-8">
        <h2 class="text-3xl font-bold text-white">Application Settings</h2>
        <span class="text-gray-500 bg-gray-800 px-3 py-1 rounded-full text-xs font-mono uppercase tracking-tighter border border-gray-700">Version 3.0</span>
    </div>

    <?php if ($success_msg): ?>
        <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 p-4 rounded-lg mb-8 shadow-sm">
            <div class="flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 mr-3">
                  <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.74-5.24Z" clip-rule="evenodd" />
                </svg>
                <?php echo $success_msg; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($error_msg): ?>
        <div class="bg-rose-500/10 border border-rose-500/20 text-rose-400 p-4 rounded-lg mb-8 shadow-sm">
             <div class="flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 mr-3">
                  <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-1.72 6.97a.75.75 0 1 0-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 1 0 1.06 1.06L12 13.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L13.06 12l1.72-1.72a.75.75 0 1 0-1.06-1.06L12 10.94l-1.72-1.72Z" clip-rule="evenodd" />
                </svg>
                <?php echo $error_msg; ?>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-10">
        <!-- Dashboard Branding -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-gray-800 rounded-xl shadow-lg border border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2 text-accent-500">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581a2.25 2.25 0 0 0 3.182 0l4.318-4.318a2.25 2.25 0 0 0 0-3.182L11.159 3.659A2.25 2.25 0 0 0 9.568 3Z" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
                    </svg>
                    Organization Branding
                </h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Church Name</label>
                        <input type="text" name="church_name" value="<?php echo htmlspecialchars($settings['church_name'] ?? ''); ?>" required
                            class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-accent-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Department / Unit</label>
                        <input type="text" name="dept_name" value="<?php echo htmlspecialchars($settings['dept_name'] ?? ''); ?>" required
                            class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-accent-500 outline-none">
                    </div>
                     <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Default Report Recipient</label>
                        <input type="email" name="report_recipient" value="<?php echo htmlspecialchars($settings['report_recipient_email'] ?? ''); ?>" placeholder="admin@church.org"
                            class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-accent-500 outline-none">
                    </div>
                     <div>
                        <label class="block text-xs font-bold text-white uppercase mb-1 bg-accent-600/20 px-2 py-0.5 rounded w-fit text-accent-400">Notification Recipients</label>
                        <p class="text-[10px] text-gray-500 mb-1">Comma-separated emails for automated alerts (subscriptions, reports)</p>
                        <textarea name="notification_emails" rows="2" placeholder="admin@church.org, pastor@church.org"
                            class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-accent-500 outline-none"><?php echo htmlspecialchars($settings['notification_emails'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- System Status Integration -->
             <div class="bg-gray-800 rounded-xl shadow-lg border border-gray-700 p-6 overflow-hidden relative">
                 <div class="absolute right-0 top-0 p-4 opacity-10">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-16 h-16">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.744c0 1.506.279 2.946.788 4.256a11.94 11.94 0 0 0 3.018 4.332L12 21.75l5.194-3.418a11.94 11.94 0 0 0 3.018-4.332c.509-1.31.788-2.75.788-4.256 0-1.352-.249-2.646-.698-3.84a11.959 11.959 0 0 1-8.402-3.84Z" />
                    </svg>
                 </div>
                <h3 class="text-lg font-semibold text-gray-200 border-b border-gray-700 pb-2 mb-4">Diagnostics</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-400">Database</span>
                        <span class="text-emerald-400 font-medium">Connected</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-400">PHP Version</span>
                        <span class="text-gray-300"><?php echo phpversion(); ?></span>
                    </div>
                     <div class="flex justify-between items-center">
                        <span class="text-gray-400">Environment</span>
                        <span class="text-gray-500 uppercase text-[10px] tracking-widest font-bold">Production</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- SMTP Configuration -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-gray-800 rounded-xl shadow-lg border border-gray-700 p-8">
                <h3 class="text-xl font-bold text-white mb-6 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 mr-2 text-accent-500">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg>
                    SMTP Email Configuration
                </h3>


                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">SMTP Host</label>
                        <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>" placeholder="mail.yourdomain.com"
                            class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-accent-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">SMTP Port</label>
                        <input type="number" name="smtp_port" value="<?php echo htmlspecialchars($settings['smtp_port'] ?? '587'); ?>"
                            class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-accent-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">SMTP Username</label>
                        <input type="text" name="smtp_user" value="<?php echo htmlspecialchars($settings['smtp_user'] ?? ''); ?>" placeholder="notifications@domain.com"
                            class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-accent-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">SMTP Password</label>
                        <input type="password" name="smtp_pass" value="<?php echo htmlspecialchars($settings['smtp_pass'] ?? ''); ?>" placeholder="••••••••••••"
                            class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-accent-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">From Email</label>
                        <input type="email" name="smtp_from_email" value="<?php echo htmlspecialchars($settings['smtp_from_email'] ?? ''); ?>" placeholder="notifications@domain.com"
                            class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-accent-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">From Name</label>
                        <input type="text" name="smtp_from_name" value="<?php echo htmlspecialchars($settings['smtp_from_name'] ?? ''); ?>" placeholder="Church Funds Manager"
                            class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-accent-500 outline-none">
                    </div>
                </div>

                <div class="flex flex-col md:flex-row gap-4 border-t border-gray-700 pt-6">
                    <button type="submit" name="update_settings" class="flex-1 bg-accent-600 hover:bg-accent-700 text-white font-bold py-4 rounded-xl transition-all shadow-lg flex justify-center items-center">
                         <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 mr-3">
                          <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Save Configuration
                    </button>
                    
                    <button type="button" onclick="document.getElementById('test-panel').classList.toggle('hidden')" class="bg-gray-700 hover:bg-gray-600 text-gray-200 font-bold py-4 px-8 rounded-xl transition-all border border-gray-600">
                        Test Connection
                    </button>
                </div>

                <!-- Test Email Panel (Hidden by default) -->
                <div id="test-panel" class="hidden mt-6 bg-gray-900 rounded-lg p-6 border border-gray-700 animate-in fade-in slide-in-from-top-4 duration-300">
                    <h4 class="text-sm font-bold text-white mb-4 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-2 text-rose-500">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                        </svg>
                        Send Test Email
                    </h4>
                    <div class="flex gap-2">
                        <input type="email" name="test_recipient" placeholder="Enter recipient email"
                            class="flex-1 bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white outline-none">
                        <button type="submit" name="test_email" class="bg-rose-600 hover:bg-rose-700 text-white font-bold px-6 py-2 rounded-lg transition-colors">
                            Send
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
         <!-- Backup -->
        <div class="bg-gray-800 rounded-xl shadow-lg border border-gray-700 p-8">
            <h3 class="text-xl font-bold text-white mb-4">Database Backup</h3>
            <p class="text-sm text-gray-400 mb-8 leading-relaxed">Safety first! Download a full SQL dump containing all your data including branding and SMTP settings. Recommended before any updates.</p>
            
            <form method="POST">
                <button type="submit" name="backup" class="w-full bg-emerald-600/10 hover:bg-emerald-600/20 text-emerald-400 font-bold py-4 rounded-xl border border-emerald-500/20 flex justify-center items-center transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-3">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Spool Database Backup (.sql)
                </button>
            </form>
        </div>

        <!-- Restore -->
        <div class="bg-gray-800 rounded-xl shadow-lg border border-gray-700 p-8 border-l-rose-500/50">
            <h3 class="text-xl font-bold text-rose-500 mb-4">Disaster Recovery</h3>
            <p class="text-sm text-gray-400 mb-8 leading-relaxed">Restore your system to a previous state. This action is <span class="text-rose-400 font-bold underline">irreversible</span> and will overwrite all current live data.</p>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="file" name="restore_file" accept=".sql" required
                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2 text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-rose-500/10 file:text-rose-400 hover:file:bg-rose-500/20 cursor-pointer">
                
                <button type="submit" name="restore" onclick="return confirm('CRITICAL: This will wipe ALL current data. Continue?')" class="w-full bg-rose-700 hover:bg-rose-600 text-white font-bold py-4 rounded-xl shadow border border-rose-800 flex justify-center items-center transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 mr-3">
                       <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    Restore from Spool File
                </button>
            </form>
        </div>
    </div>

    <!-- User Management Section -->
    <div class="mb-10">
        <div class="bg-gray-800 rounded-xl shadow-lg border border-gray-700 p-8">
            <h3 class="text-xl font-bold text-white mb-6 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 mr-2 text-blue-500">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
                User Management
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Add User Form -->
                <div>
                    <h4 class="text-white font-bold mb-4">Add New User</h4>
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Username</label>
                            <input type="text" name="new_username" required class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Password</label>
                            <input type="password" name="new_password" required class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <button type="submit" name="add_user" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-lg transition-all shadow-lg">
                            Create User
                        </button>
                    </form>
                </div>

                <!-- User List -->
                <div>
                    <h4 class="text-white font-bold mb-4">Existing Users</h4>
                    <div class="bg-gray-900 border border-gray-700 rounded-lg overflow-hidden">
                        <?php 
                        $users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
                        foreach($users as $u): 
                        ?>
                            <div class="p-4 border-b border-gray-700 last:border-0 flex justify-between items-center group">
                                <div class="flex items-center gap-3">
                                    <div class="bg-gray-800 p-2 rounded-full text-gray-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                                    </div>
                                    <div>
                                        <div class="font-bold text-white"><?php echo htmlspecialchars($u['username']); ?></div>
                                        <div class="text-[10px] text-gray-500 uppercase"><?php echo htmlspecialchars($u['role']); ?></div>
                                    </div>
                                </div>
                                <div class="flex gap-2 opacity-15 group-hover:opacity-100 transition-opacity">
                                    <form method="POST" class="flex gap-2 items-center">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <input type="password" name="new_password" placeholder="New Pass" class="bg-gray-800 border border-gray-600 rounded px-2 py-1 text-xs text-white w-24">
                                        <button type="submit" name="update_password" onclick="return confirm('Change password this user?')" class="text-blue-400 hover:text-white" title="Update Password">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                                        </button>
                                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                            <button type="submit" name="delete_user" onclick="return confirm('Delete this user?')" class="text-rose-500 hover:text-white" title="Delete User">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
