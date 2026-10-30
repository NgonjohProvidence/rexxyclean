# Run Bexxy locally with XAMPP

This site uses PHP and MySQL/MariaDB. A booking request is saved to the database; it is not a confirmed appointment and no payment is taken.

## Install in XAMPP

1. Copy the whole project into `C:\xampp\htdocs\bexxy` (keep `image`, `config`, and all project files together).
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin/`, choose **Import**, and import `database.sql` for a fresh install. For an existing installation, import `database-update-hourly-pricing.sql` once to add hourly duration and team-size fields while preserving existing booking records.
4. In phpMyAdmin, create a database user for the website with a unique password. Give it only `SELECT`, `INSERT`, and `UPDATE` on `bexxy_cleaning.booking_requests`; give it `SELECT`, `INSERT`, `UPDATE`, and `DELETE` on `bexxy_cleaning.admin_login_attempts`. Do not use `root` for the site.
5. In File Explorer, create the folder `C:\xampp\private` if it does not exist. Copy `C:\xampp\htdocs\rexxyclean\config\private-config.example.php` into that folder and rename the copy to `bexxy-config.php`. Do not edit the example file inside `htdocs`; the private copy must be outside `htdocs`.
6. Open `C:\xampp\private\bexxy-config.php` in VS Code or Notepad. Find the `database` section near the top and replace `bexxy_app` and `REPLACE_WITH_A_UNIQUE_DATABASE_PASSWORD` with the database username and password you created in step 4.

   You already generated an admin password and its hash. In the `admin` section, the three settings mean:

   - `username`: leave this as `admin`. This is the username you type on the login page.
   - `password_hash`: replace the placeholder text with the **Password hash** value from the command output. Do not put the plain **Password** value here.
   - `rate_limit_key`: replace the placeholder text with the full output from the separate random-key command below. This is a private security key used by the website to limit repeated login attempts; it is not a username or password.

   For example, edit the section so it has this shape. Replace the two all-caps instructions with your generated values, keep the single quotes and commas, and do not literally paste the instruction text:

   ```php
   'admin' => [
       'username' => 'admin',
       'password_hash' => 'PASTE YOUR PASSWORD HASH HERE',
       'rate_limit_key' => 'PASTE YOUR RANDOM KEY HERE',
   ],
   ```

   If you still need to generate a password and its hash, run this in **Command Prompt (cmd.exe)**:

   ```text
   C:\xampp\php\php.exe -r "$password = bin2hex(random_bytes(18)); echo 'Username: admin', PHP_EOL, 'Password: ', $password, PHP_EOL, 'Password hash: ', password_hash($password, PASSWORD_DEFAULT), PHP_EOL;"
   ```

   Save the **Password** value somewhere private, such as a password manager. You will need it to sign in. The website stores only the **Password hash** in `bexxy-config.php`.

   Generate the random key by running this in Command Prompt:

   ```text
   C:\xampp\php\php.exe -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
   ```

   Copy the entire key it prints into the `rate_limit_key` setting. Save the edited `bexxy-config.php` file.

   Now open `http://localhost/rexxyclean/admin.php`. Sign in with username `admin` and the **Password** value you saved. If the dashboard says it is not configured, check that the filename is exactly `bexxy-config.php`, it is inside `C:\xampp\private`, and you replaced both the `password_hash` and `rate_limit_key` placeholders. If login says the username or password was not recognized, check that you used the plain **Password** value (not the hash) and that the hash was copied correctly.

   You can leave the `notifications` email placeholders unchanged for now; they only need real values when you set up booking emails. Keep the password, hash, random key, and private config file out of `htdocs`, Git, and public messages. Do not send them to anyone.
7. To enable booking emails, choose a mail provider and configure its SMTP relay in XAMPP's `sendmail.ini` (including credentials stored only on your computer). Set PHP's `sendmail_path` in `php.ini` to the XAMPP Sendmail executable, then restart Apache. Keep provider credentials private; a successful PHP `mail()` result does not guarantee inbox delivery.
8. Open `http://localhost/bexxy/` (Apache will load `index.php`; do not open the site as a `file:///` URL).
   Apache's rewrite rules in `.htaccess` serve the custom 404 page for missing paths whether the project is installed at the document root or in a subdirectory. If the custom page does not appear on your host, make sure Apache's `mod_rewrite` module is enabled.
9. Submit a test request. Confirm the request and saved estimate in phpMyAdmin under `bexxy_cleaning` → `booking_requests`, and download the request receipt from the success notice.
10. Open `http://localhost/bexxy/admin.php` (or `http://localhost/rexxyclean/admin.php` for this workspace) and sign in with username `admin` and the generated password to filter requests, manage their status from pending through completed, and keep private internal follow-up notes.

The app looks for the private config at `C:\xampp\private\bexxy-config.php`. To use a different path, set the `BEXXY_PRIVATE_CONFIG` environment variable for Apache.

## What is included

- Server-side validation, prepared SQL statements, HTML output escaping, CSRF protection, secure session-cookie settings, a hidden spam trap, and a minimum form-completion time.
- English/French language switch on the public pages. The visitor's choice is saved in the browser and reused on the next visit; English is the default.
- A private staff dashboard at `admin.php` with a username/password sign-in form, password-hash authentication, session timeout, database-backed login throttling keyed by an HMAC of the username and client IP, CSRF-protected status changes, private team notes, status filtering, and paginated requests. The example configuration uses `admin` as the initial username; generate a unique password during setup.
- A PDF **booking request receipt** downloadable in the browser session that submitted the form. It contains personal information and should be treated as private. It is not an invoice, payment receipt, or confirmation.
- Booking submissions attempt to send a customer acknowledgement and a new-request email to the configured business address via PHP's `mail()` transport. Configure a mail provider and valid notification addresses before enabling email. A successful `mail()` result is not a guarantee of inbox delivery. The acknowledgement explicitly says the appointment is not yet confirmed.
- Pricing is defined in `pricing.json`: each `per_hour` value is CAD per cleaner-hour, and extras can have a fixed rate or `null` for a custom quote. The configured hourly amounts are provisional estimates, not an official local standard; review and edit them before advertising.
- The homepage's three "How it works" cards link to the About page, booking form, and pricing page. Internal page links use a short spiral transition; devices set to reduced motion skip the animation.
- A privacy notice at `privacy.php`. Review and adapt it to the business and privacy laws that apply before public launch.

## Before a public launch

XAMPP is for local development, not public production hosting. Use a maintained PHP/MySQL host, HTTPS on every page, a restricted database user, and private configuration outside the web root. Add host-level login rate limiting and preferably multi-factor authentication for staff; the included dashboard is a local starter, not a complete enterprise admin system. Set up encrypted backups and a tested restore process, monitor errors without recording customer form contents, and decide a record-retention period.

SMS, confirmed-appointment emails, and reminders are not included yet. Also confirm the service area, business jurisdiction, and privacy contact details. The service area is Ottawa & Gatineau and availability is listed as 24/7; verify the operating schedule before launch. Review the provisional CAD rates and update them to the business's approved prices before launch. The flyer lists 613-710-2787; verify that the number is active and correct before launch.
