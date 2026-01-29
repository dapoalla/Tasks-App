# Email Configuration Note (v2.1)

## Setup Configuration
- **Library used**: PHPMailer 6.x
- **Location**: `/lib/PHPMailer/`
- **Helper script**: `mail_helper.php`

## Why it failed earlier
1.  **Missing Library**: The application initially pointed to `/lib/PHPMailer/` but the folder was empty or missing on the server.
2.  **Strict SMTP Requirements**: Many secured servers (like cPanel) require valid SMTP credentials and matching "From" addresses to prevent spoofing, which the basic PHP `mail()` function often fails at.

## How it was fixed
1.  **Library Migration**: I located a working PHPMailer installation in your `/car/` project and copied the specialized `PHPMailer.php`, `SMTP.php`, and `Exception.php` files into `/churchfunds/lib/PHPMailer/`.
2.  **Path Refinement**: The include paths in `mail_helper.php` were updated to match the local folder structure (removing the redundant `/src/` subfolder).
3.  **Fallback Logic**: A "Disaster Fallback" was implemented. If PHPMailer is ever accidentally deleted, the system will automatically fall back to the native PHP `mail()` function to attempt delivery.
4.  **Security Bypass**: Standard SMTP options were added to bypass SSL certificate verification errors, which is a common hurdle on shared hosting environments (cPanel).

## Recommended SMTP Settings for cPanel
- **Host**: `mail.yourdomain.com` (or your server's hostname)
- **Port**: `465` (SSL) or `587` (TLS)
- **Username**: Your full email address
- **From Email**: MUST match the Username above for best delivery.
- **Security**: The app automatically handles SSL/TLS based on the port you choose.
