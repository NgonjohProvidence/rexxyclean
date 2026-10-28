# Run Bexxy locally with XAMPP

This site uses PHP and MySQL/MariaDB. A booking request is saved to the database; it is not a confirmed appointment and no payment is taken.

## Install in XAMPP

1. Copy the whole project into `C:\xampp\htdocs\bexxy` (keep `image`, `config`, and all project files together).
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin/`, choose **Import**, and import `database.sql` for a fresh install. For an existing installation, import any applicable older pricing update (`database-update-pricing.sql`), then import `database-update-workflow.sql` to add private staff notes and the completed status.
4. In phpMyAdmin, create a database user for the website with a unique password. Give it only `SELECT`, `INSERT`, and `UPDATE` on `bexxy_cleaning.booking_requests`; give it `SELECT`, `INSERT`, `UPDATE`, and `DELETE` on `bexxy_cleaning.admin_login_attempts`. Do not use `root` for the site.
5. Create `C:\xampp\private` if it does not exist. Copy `config\private-config.example.php` there as `bexxy-config.php`.
6. Edit `C:\xampp\private\bexxy-config.php` with the database username/password you created. Set a private dashboard username, generate a strong dashboard password hash using **Command Prompt (cmd.exe)**, and generate a separate random key for login throttling:

   ```text
   C:\xampp\php\php.exe -r "$p = readline('Dashboard password: '); echo password_hash($p, PASSWORD_DEFAULT), PHP_EOL;"
   ```

   ```texttext
   C:\xampp\php\php.exe -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
   ```

   Paste the outputs into `admin.password_hash` and `admin.rate_limit_key`. When you are ready to enable email, set `notifications.from_email` to a sender address verified with your chosen email provider and `notifications.admin_email` to the address that should receive booking alerts. Never put the plain-text password, an email provider credential, or the private config file in `htdocs`, Git, or a public download.
7. To enable booking emails, choose a mail provider and configure its SMTP relay in XAMPP's `sendmail.ini` (including credentials stored only on your computer). Set PHP's `sendmail_path` in `php.ini` to the XAMPP Sendmail executable, then restart Apache. Keep provider credentials private; a successful PHP `mail()` result does not guarantee inbox delivery.
8. Open `http://localhost/bexxy/` (Apache will load `index.php`; do not open the site as a `file:///` URL).
   Apache's rewrite rules in `.htaccess` serve the custom 404 page for missing paths whether the project is installed at the document root or in a subdirectory. If the custom page does not appear on your host, make sure Apache's `mod_rewrite` module is enabled.
9. Submit a test request. The provisional estimate is XAF 4,000 per room, with no base fee; extras are quoted separately. Confirm the request and saved estimate in phpMyAdmin under `bexxy_cleaning` → `booking_requests`, and download the request receipt from the success notice.
10. Open `http://localhost/bexxy/admin.php` and sign in with the private dashboard credentials to filter requests, manage their status from pending through completed, and keep private internal follow-up notes.

The app looks for the private config at `C:\xampp\private\bexxy-config.php`. To use a different path, set the `BEXXY_PRIVATE_CONFIG` environment variable for Apache.

## What is included

- Server-side validation, prepared SQL statements, HTML output escaping, CSRF protection, secure session-cookie settings, a hidden spam trap, and a minimum form-completion time.
- English/French language switch on the public pages. The visitor's choice is saved in the browser and reused on the next visit; English is the default.
- A private staff dashboard with password-hash authentication, session timeout, database-backed login throttling keyed by an HMAC of the username and client IP, CSRF-protected status changes, private team notes, status filtering, and paginated requests.
- A PDF **booking request receipt** downloadable in the browser session that submitted the form. It contains personal information and should be treated as private. It is not an invoice, payment receipt, or confirmation.
- Booking submissions attempt to send a customer acknowledgement and a new-request email to the configured business address via PHP's `mail()` transport. Configure a mail provider and valid notification addresses before enabling email. A successful `mail()` result is not a guarantee of inbox delivery. The acknowledgement explicitly says the appointment is not yet confirmed.
- Pricing is defined in `pricing.json`: `base` is the service's starting charge, `per_room` is added for each room, and extras can have a fixed rate or `null` for a custom quote. The current XAF 4,000/room rate is provisional; confirm it is correct for each service before advertising.
- The homepage's three "How it works" cards link to the About page, booking form, and pricing page. Internal page links use a short spiral transition; devices set to reduced motion skip the animation.
- A privacy notice at `privacy.php`. Review and adapt it to the business and privacy laws that apply before public launch.

## Before a public launch

XAMPP is for local development, not public production hosting. Use a maintained PHP/MySQL host, HTTPS on every page, a restricted database user, and private configuration outside the web root. Add host-level login rate limiting and preferably multi-factor authentication for staff; the included dashboard is a local starter, not a complete enterprise admin system. Set up encrypted backups and a tested restore process, monitor errors without recording customer form contents, and decide a record-retention period.

SMS, confirmed-appointment emails, and reminders are not included yet. Also confirm the service area, correct business phone number, business jurisdiction, and privacy contact details. The current page still says Ottawa & Gatineau while pricing is shown in XAF; update the service area and booking-timezone handling to match the real operating location before launch. The phone number was left off the public site because its previously displayed country code and digits were not verified.
