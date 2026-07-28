# Backup Functionality (Exposed)

## Overview

WCC provides a simple, admin-accessible backup tool that generates a SQL dump of the core tables.

**Location**: Administration → Admin Panel → "🗄️ Database Backup Tool" link  
Or directly: `/_mgmt/admin_backup.php`

## How It Works

- Only users with `manage_settings` permission can access it.
- Clicking "Download Full SQL Backup Now" triggers a PHP-generated SQL export of the main tables:
  - users, equipment, active_tickets, ticket_actions, work_orders, inventory_parts, purchase_orders, purchase_requests, vendors_suppliers, production_lines, workshops, etc.
- The file is downloaded with a timestamped name: `wcc_backup_YYYYMMDD_HHMMSS.sql`
- You can restore it later using:
  ```bash
  mysql -u root -p workshop_db < wcc_backup_....sql
  ```

## Important Notes

**This is a basic tool**, not a replacement for proper database backups.

**Recommendations for small/mid companies**:
1. Use the tool before major changes or on a regular schedule (e.g. weekly).
2. Store the downloaded `.sql` files in a safe, off-server location (external drive, cloud storage with encryption).
3. For production, set up automated backups using `mysqldump` via a scheduled task or cron:
   ```bash
   mysqldump -u root -p workshop_db > /backups/wcc_$(date +%F).sql
   ```
4. Test restoring the backup periodically.
5. The tool does **not** include uploaded files/attachments (if you add that feature later).

## Security

- The backup is only available to admins.
- Never expose this page publicly.
- Consider password-protecting or encrypting your backup files.

## Future Improvements (recommended)

- Add file + database combined backup.
- Scheduled automatic backups.
- One-click restore (with confirmation).
- Backup of theme prefs and app_settings.

See also the deployment notes in the main plan document.
