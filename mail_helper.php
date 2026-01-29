<?php
// mail_helper.php
// Universal SMTP Email Implementation for Church Funds Manager

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if PHPMailer exists, otherwise provide a fallback or instructions
$phpMailerPath = __DIR__ . '/lib/PHPMailer/PHPMailer.php';
if (file_exists($phpMailerPath)) {
    require __DIR__ . '/lib/PHPMailer/Exception.php';
    require __DIR__ . '/lib/PHPMailer/PHPMailer.php';
    require __DIR__ . '/lib/PHPMailer/SMTP.php';
}

/**
 * Send an email using SMTP settings from global $settings
 */
function send_mail($to, $subject, $message, $isHtml = true) {
    global $settings;

    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        error_log("CRITICAL: PHPMailer library not found. Falling back to PHP mail().");
        
        $fromEmail = !empty($settings['smtp_from_email']) ? $settings['smtp_from_email'] : ($settings['smtp_user'] ?? 'no-reply@church.org');
        $fromName = !empty($settings['smtp_from_name']) ? $settings['smtp_from_name'] : ($settings['app_name'] ?? 'Church Funds Manager');
        
        $headers = "From: $fromName <$fromEmail>\r\n";
        $headers .= "Reply-To: $fromEmail\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        if ($isHtml) {
            $headers .= "\r\nMIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        }
        
        return mail($to, $subject, $message, $headers);
    }

    // Validate settings
    if (empty($settings['smtp_host']) || empty($settings['smtp_user']) || empty($settings['smtp_pass'])) {
        error_log("Email Error: SMTP settings are incomplete in the database.");
        return false;
    }

    // Recommendation: SMTP User should usually match From Email
    $fromEmail = !empty($settings['smtp_from_email']) ? $settings['smtp_from_email'] : $settings['smtp_user'];
    if (strtolower($fromEmail) !== strtolower($settings['smtp_user'])) {
        error_log("WARNING: SMTP Username (" . $settings['smtp_user'] . ") does not match From Email (" . $fromEmail . "). This often causes failures on secured servers.");
    }

    $mail = new PHPMailer(true);

    try {
        // SMTP Server Settings
        $mail->SMTPDebug  = 0; // Set to 2 for verbose debug output in logs
        $mail->isSMTP();
        $mail->Host       = $settings['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $settings['smtp_user'];
        $mail->Password   = $settings['smtp_pass'];
        
        // Handle Port/Security mapping
        $port = intval($settings['smtp_port']);
        $mail->Port = $port;
        
        if ($port == 465) {
            $mail->SMTPSecure = 'ssl';
        } elseif ($port == 587) {
            $mail->SMTPSecure = 'tls';
        } else {
            $mail->SMTPSecure = false;
            $mail->SMTPAutoTLS = false;
        }

        // SSL Certificate Verification Bypass (Commonly needed on cPanel)
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Sender Identity
    $fromName = !empty($settings['smtp_from_name']) ? $settings['smtp_from_name'] : ($settings['app_name'] ?? 'Church Funds Manager');
    
    // DMARC Fix: Ensure setFrom matches authenticated user domain
    // Set Reply-To as the intended "From" address if it differs
    $mail->setFrom($settings['smtp_user'], $fromName);
    if (!empty($settings['smtp_from_email']) && strtolower($settings['smtp_from_email']) !== strtolower($settings['smtp_user'])) {
        $mail->addReplyTo($settings['smtp_from_email'], $fromName);
    }

    $mail->addAddress($to);

        // Content
        $mail->isHTML($isHtml);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        return $mail->send();
    } catch (Exception $e) {
        error_log("PHPMailer Exception: " . $e->getMessage());
        error_log("SMTP Detail Error: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Premium HTML Template Shell
 */
function getEmailTemplate($title, $content) {
    global $settings;
    $church = htmlspecialchars($settings['church_name'] ?? 'Church Funds Manager');
    $dept = htmlspecialchars($settings['dept_name'] ?? 'ICT Department');
    $year = date('Y');

    return "
    <html>
    <head>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7f6; padding: 20px; color: #333; }
            .container { max-width: 600px; margin: auto; background: #fff; border-radius: 12px; overflow: hidden; shadow: 0 4px 6px rgba(0,0,0,0.1); }
            .header { background: #1e293b; color: #fff; padding: 40px 30px; text-align: center; }
            .header h1 { margin: 0; font-size: 24px; color: #38bdf8; }
            .header p { margin: 5px 0 0; font-size: 14px; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; }
            .content { padding: 40px 30px; line-height: 1.6; font-size: 16px; }
            .footer { padding: 20px; text-align: center; color: #94a3b8; font-size: 12px; border-top: 1px solid #f1f5f9; }
            .btn { display: inline-block; padding: 12px 24px; background: #0ea5e9; color: #fff; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>$title</h1>
                <p>$church - $dept</p>
            </div>
            <div class='content'>
                $content
            </div>
            <div class='footer'>
                &copy; $year $church Fund Management System<br>
                Sent via Church Funds Manager v2.0
            </div>
        </div>
    </body>
    </html>";
}
