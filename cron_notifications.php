<?php
// cron_notifications.php
// Run this script daily via cron job: php /path/to/churchfunds/cron_notifications.php

require_once 'db.php';
require_once 'mail_helper.php';

// Fetch Settings
$stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

$recipients = explode(',', $settings['notification_emails'] ?? '');
$recipients = array_map('trim', array_filter($recipients)); // Clean array

if (empty($recipients)) {
    echo "No notification recipients configured in Settings.\n";
    exit;
}

$today = date('Y-m-d');
$appName = $settings['app_name'] ?? 'Tasks Manager';

// --- 1. SUBSCRIPTION EXPIRY ALERTS ---
$subs = $pdo->query("SELECT * FROM internet_subscriptions WHERE renewal_status != 'Done'")->fetchAll(PDO::FETCH_ASSOC);

foreach ($subs as $sub) {
    if (!$sub['expiry_date']) continue;
    
    $expiry = new DateTime($sub['expiry_date']);
    $now = new DateTime($today);
    $diff = $now->diff($expiry);
    $daysLeft = (int)$diff->format('%r%a'); // Signed integer (negative if expired)

    $sendAlert = false;
    $alertType = "";

    if ($daysLeft === 30) {
        $sendAlert = true;
        $alertType = "30 Days Notice";
    } elseif ($daysLeft === 10) {
        $sendAlert = true;
        $alertType = "Urgent: 10 Days Left";
    } elseif ($daysLeft === 0) {
        $sendAlert = true;
        $alertType = "EXPIRED TODAY";
    }

    if ($sendAlert) {
        $subject = "[$appName] Subscription Alert: {$sub['location_dept']} ($alertType)";
        $body = "
            <h3>Internet Subscription Expiry Alert</h3>
            <p>The following subscription is expiring soon or has expired:</p>
            <ul>
                <li><strong>Location:</strong> {$sub['location_dept']}</li>
                <li><strong>Provider:</strong> {$sub['provider']} ({$sub['plan_name']})</li>
                <li><strong>Expiry Date:</strong> " . date('M d, Y', strtotime($sub['expiry_date'])) . "</li>
                <li><strong>Status:</strong> {$daysLeft} days remaining</li>
            </ul>
            <p>Please log in to manage this subscription:</p>
            <p><a href='https://wcs.afmweca.com/tasks/'>https://wcs.afmweca.com/tasks/</a></p>
        ";
        
        foreach ($recipients as $email) {
            send_mail($email, $subject, getEmailTemplate($subject, $body));
        }
        echo "Sent subscription alert for {$sub['location_dept']} ($daysLeft days).\n";
    }
}

// --- 2. MONTHLY OUTSTANDING TASK REPORT ---
// Check if it's the 1st of the month
if (date('j') == 1) {
    $stmt = $pdo->query("SELECT * FROM tasks WHERE status != 'Completed' ORDER BY priority DESC");
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($tasks) > 0) {
        $subject = "[$appName] Monthly Outstanding Tasks Report - " . date('M Y');
        
        $taskListHtml = "<table style='width:100%; border-collapse: collapse; border:1px solid #ddd;'>
            <thead style='background:#f4f4f4;'>
                <tr>
                    <th style='padding:8px; border:1px solid #ddd;'>Task</th>
                    <th style='padding:8px; border:1px solid #ddd;'>Priority</th>
                    <th style='padding:8px; border:1px solid #ddd;'>Assigned To</th>
                    <th style='padding:8px; border:1px solid #ddd;'>Due Date</th>
                </tr>
            </thead>
            <tbody>";
        
        foreach ($tasks as $task) {
            $priorityColor = match($task['priority']) { 'High' => '#ef4444', 'Medium' => '#f59e0b', default => '#10b981' };
            $taskListHtml .= "<tr>
                <td style='padding:8px; border:1px solid #ddd;'><strong>" . htmlspecialchars($task['task_name']) . "</strong><br><small>" . htmlspecialchars($task['location_dept']) . "</small></td>
                <td style='padding:8px; border:1px solid #ddd; color:$priorityColor;'>" . $task['priority'] . "</td>
                <td style='padding:8px; border:1px solid #ddd;'>" . htmlspecialchars($task['assigned_to']) . "</td>
                <td style='padding:8px; border:1px solid #ddd;'>" . ($task['due_date'] ? date('M d', strtotime($task['due_date'])) : '-') . "</td>
            </tr>";
        }
        $taskListHtml .= "</tbody></table>";

        $body = "
            <h3>Monthly Outstanding Tasks Overview</h3>
            <p>Here is the summary of pending tasks for " . date('F Y') . ".</p>
            $taskListHtml
            <br>
            <p><strong>Management Access:</strong></p>
            <p>URL: <a href='https://wcs.afmweca.com/tasks/'>https://wcs.afmweca.com/tasks/</a></p>
        ";

        foreach ($recipients as $email) {
            send_mail($email, $subject, getEmailTemplate($subject, $body));
        }
        echo "Sent monthly task report (" . count($tasks) . " tasks).\n";
    }
} else {
    echo "Not the 1st of the month. Skipping monthly report.\n";
}

?>
