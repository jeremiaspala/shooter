# Project Summary

## Overall Goal
Build and deploy "Shooter" - a PHP/MySQL email reminder system with user management, reminder scheduling (with repeat options), SMTP configuration, and automated email sending via cron.

## Key Knowledge

**Technology Stack:**
- PHP 7+ with MySQL
- PHPMailer for email sending
- URL rewriting via .htaccess (clean URLs without .php extension)
- JavaScript/jQuery for AJAX functionality
- Summernote WYSIWYG editor for email content

**Project Location:** `/var/www/html/shooter/`

**Default Credentials:**
- Admin: `admin` / `admin123`
- Visor: `visor` / `visor123`

**Database Tables:** users, reminders, reminder_logs, smtp_config, app_config

**URL Structure:**
- `/shooter/dashboard` - Statistics
- `/shooter/reminders` - Reminder list
- `/shooter/users` - User management (admin only)
- `/shooter/settings/smtp` - SMTP configuration
- `/shooter/settings` - General app settings

## Recent Actions

**Issues Fixed:**
1. SMTP activation toggle - Created dedicated endpoint `/api/smtp_toggle.php` because the inline PHP code wasn't executing properly
2. SMTP test email - Created dedicated API endpoint `/api/smtp_test.php` to avoid HTML rendering issues
3. Reminder CRUD - Created dedicated endpoints in `/api/` directory:
   - `reminder_create.php`, `reminder_view.php`, `reminder_edit.php`, `reminder_delete.php`
4. User CRUD - Created dedicated endpoints:
   - `user_create.php`, `user_edit.php`, `user_delete.php`
5. CSS/JS paths - Changed from relative to absolute paths (`/shooter/assets/css/style.css`)
6. Pagination links - Fixed from `reminders?p=X` to `/shooter/reminders?p=X`
7. Sidebar navigation - Updated all links to use absolute paths
8. Install.php - Rewrote to properly write database config and show debug info

**Discovery:** The production server needed the updated files copied - the local changes weren't visible there.

## Current Plan

- [DONE] Fix SMTP activation and testing
- [DONE] Create dedicated API endpoints for reminders and users CRUD
- [DONE] Fix all URL links to use absolute paths
- [DONE] Explain cron job setup
- [PENDING] User to copy updated files to production server

**Cron Job Setup:**
```bash
* * * * * php /var/www/html/shooter/cron/send_reminders.php >> /var/log/reminders.log 2>&1
```

---

## Summary Metadata
**Update time**: 2026-03-05T22:27:44.198Z 
