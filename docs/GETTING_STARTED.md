# Getting Started with WCC

This guide helps you get WCC (Workshop Control Center) running on a development or small production server.

**Open beta (global distribution):** WCC is intended for **offline / on-prem** use — **one install per site**. There is no shared multi-tenant cloud demo and no feature hardlocks. See [OPEN_BETA.md](OPEN_BETA.md) for wording and expectations, and [DISTRIBUTION_CHECKLIST.md](DISTRIBUTION_CHECKLIST.md) if you are packaging a zip for others.

## Requirements

- PHP 8.0+
- MySQL 5.7+ or MariaDB 10.3+
- Web server (Apache or Nginx recommended)
- For quick start: XAMPP (Windows) or equivalent stack

## Installation Steps

### 1. Download / Place the Files

Copy the entire project folder into your web root:
- XAMPP: `C:\xampp\htdocs\wcc` (or directly in htdocs for simplicity)

### 2. Database Setup

1. Create a database named `workshop_db`.
2. Import the baseline schema:
   ```bash
   mysql -u root -p workshop_db < schema.sql
   ```
3. Run pending migrations:
   ```bash
   php migrations/migrate.php
   ```

The `migrations/migrate.php` script will apply any new changes safely.

### 3. Configuration

- Edit `inc/db.php` if your database credentials differ from the defaults.
- The app uses a centralized connection helper.

### 4. First Run

1. Open your browser and go to `http://localhost/wcc` (adjust path as needed).
2. You will be redirected to the login page.
3. Use your admin credentials (created during initial setup or via `users.php` or seed scripts).

If no users exist, you may need to manually insert an admin user or use one of the setup scripts in the root (review carefully).

### 5. Initial Configuration

Log in with an admin account and visit:
- **Admin Panel** (`admin_panel.php`)
- **App Settings** (`app_settings.php`)

Key things to set up:
- Session lockout time
- Theme preferences
- Add workshops, production lines, departments
- Add users with appropriate roles

### 6. Basic Workflow Test

1. Go to the **Tickets Hub** (index.php).
2. Click **Register New Event**.
3. Select equipment and log a test ticket.
4. Go to **Active Tickets** and practice takeover / closeout.
5. Create a simple Work Order from the PM Calendar or Admin Panel.

### Common First Issues

- **"DB Error" or connection problems**: Check `inc/db.php` credentials and that MySQL is running.
- **Permission errors**: Make sure the web server user can write to necessary folders (if using file uploads later).
- **Paths broken**: After the modular reorganization, all links use root-absolute paths starting with `/`. Make sure your virtual host or XAMPP is serving from the correct root.

### Production Notes (Small/Mid Companies)

- Do **not** use XAMPP in production.
- Use a proper stack: Nginx/Apache + PHP-FPM + MariaDB.
- Enable HTTPS.
- Set up regular database backups (see Admin Guide).
- Review `migrations/README.md` for upgrade process.

### Next Steps

- Read the [User Guide](USER_GUIDE.md) for daily operations.
- Read the [Admin Guide](ADMIN_GUIDE.md) for configuration and user management.
- Explore the REST API at `/api/v1/` (see `rest_api_core.md`).

Need help? Check the AI context layer in `_ai_ctxt/` for deep technical understanding of the system.
