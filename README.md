# Tasks Manager v3.0 - (Formerly Church Funds Manager)

A comprehensive web-based solution for tracking department funds, expenses, tasks, and subscriptions.

## 🚀 New Features in v3.0
- **Automated Notifications**: Get email alerts for expiring subscriptions (30, 10, 0 days).
- **Monthly Task Reports**: Automated email summary of all outstanding tasks sent on the 1st of every month.
- **Subscription Management**: Dedicated dashboard tile for tracking internet/service subscriptions.
- **Enhanced Reporting**: "Tasks Manager" branding and improved PDF/WhatsApp exports.
- **User Management**: Role-based access control.

## 🛠 Features
1. **Fund & Expense Tracking**:
   - Record fund releases and expenses with receipt uploads.
   - View real-time balance and expense breakdowns.
   - Generate detailed PDF/JPG reports.

2. **Task Management**:
   - Create, assign, and track tasks (Outstanding/Completed).
   - "Break-word" support for long descriptions.
   - WhatsApp export with direct report links.

3. **Subscription Tracking**:
   - Track provider, plan, amount, and expiry dates.
   - Color-coded status indicators.

## ⚙️ Logic & Automation
**Cron Notification System**:
The system acts as an "Alarm Clock" (`cron_notifications.php`) that runs once a day.
- **Subscriptions**: Checks if any subscription is 30, 10, or 0 days from expiry.
- **Monthly Reports**: Checks if today is the 1st of the month.
- **Action**: Sends formatted emails to the recipients configured in `Settings`.

## 📦 Installation
1. Upload files to your server (e.g., `public_html/tasks`).
2. Create a MySQL database and import `database.sql`.
3. Configure `db.php` with your database credentials.
4. Go to `setup.php` (if fresh install) or `update_db_v3.php` (if updating) to finalize schema.

## ⏰ Cron Job Setup (cPanel)
To enable automated emails, set up a Cron Job to run once daily (e.g., at 8:00 AM).

1. Log in to cPanel.
2. Go to **Cron Jobs**.
3. Under **Common Settings**, select **Once per day (0 0 * * *)** or set Hour: 8, Minute: 0.
4. Command:
   ```bash
   /usr/local/bin/php /home/YOUR_USERNAME/public_html/tasks/cron_notifications.php
   ```
   *(Verify the path to PHP and your file with your host).*

## 🔐 Default Access
- **Login**: `index.php`
- **Default User**: Created during setup (or manually in database).
- **System Email Details**:
    - Manage URL: `https://wcs.afmweca.com/tasks/`
    - View Reports: `https://wcs.afmweca.com/tasks/task_report.php`

## 📅 Version history
- **v1.0**: Initial release with core fund/expense tracking.
- **v2.0**: Premium UI/UX, Dynamic Branding, SMTP Emailing, and Consolidated Reporting.

## 🤝 Support
Developed for **Apostolic Faith Church (WECA) ICT Department**.
