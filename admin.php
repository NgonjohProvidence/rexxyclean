<?php
declare(strict_types=1);

require_once __DIR__ . '/config/security.php';
start_secure_session();

if (isset($_SESSION['admin_last_activity']) && time() - (int) $_SESSION['admin_last_activity'] > 1800) {
    unset($_SESSION['admin_logged_in'], $_SESSION['admin_last_activity']);
}
if (!empty($_SESSION['admin_logged_in'])) {
    $_SESSION['admin_last_activity'] = time();
}

$error = '';
$notice = '';
$status_options = ['pending', 'contacted', 'confirmed', 'completed', 'cancelled'];

try {
    $config = private_config();
    $admin_config = $config['admin'] ?? null;
    if (!is_array($admin_config)
        || !isset($admin_config['username'], $admin_config['password_hash'], $admin_config['rate_limit_key'])
        || !is_string($admin_config['username'])
        || !is_string($admin_config['password_hash'])
        || !is_string($admin_config['rate_limit_key'])
        || $admin_config['username'] === ''
        || $admin_config['password_hash'] === ''
        || strlen($admin_config['rate_limit_key']) < 32
        || strpos($admin_config['rate_limit_key'], 'REPLACE_') === 0
        || strpos($admin_config['password_hash'], 'REPLACE_') === 0) {
        throw new RuntimeException('Admin login settings, password hash, or rate-limit key are missing from the private configuration.');
    }
} catch (RuntimeException $exception) {
    error_log('Bexxy admin configuration error: ' . $exception->getMessage());
    http_response_code(503);
    exit('The booking dashboard is not configured yet. Follow SETUP.md to add a private admin username and password hash.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!valid_csrf_token(isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
        http_response_code(400);
        $error = 'Your session expired. Refresh this page and try again.';
    } elseif (isset($_POST['login'])) {
        $username = isset($_POST['username']) && is_string($_POST['username']) ? $_POST['username'] : '';
        $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
        $password_matches = password_verify($password, $admin_config['password_hash']);
        $attempt_key = hash_hmac(
            'sha256',
            strtolower($username) . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
            $admin_config['rate_limit_key']
        );

        try {
            $pdo = require __DIR__ . '/config/database.php';
            $pdo->beginTransaction();
            $insert_attempt = $pdo->prepare(
                'INSERT IGNORE INTO admin_login_attempts (attempt_key, attempt_count, window_started_at)
                 VALUES (:attempt_key, 0, CURRENT_TIMESTAMP)'
            );
            $insert_attempt->execute([':attempt_key' => $attempt_key]);
            $attempt_statement = $pdo->prepare(
                'SELECT attempt_count,
                        window_started_at < (CURRENT_TIMESTAMP - INTERVAL 15 MINUTE) AS window_expired,
                        locked_until > CURRENT_TIMESTAMP AS is_locked
                 FROM admin_login_attempts
                 WHERE attempt_key = :attempt_key
                 FOR UPDATE'
            );
            $attempt_statement->execute([':attempt_key' => $attempt_key]);
            $attempt = $attempt_statement->fetch();

            if ($attempt && (int) $attempt['is_locked'] === 1) {
                $pdo->commit();
                $error = 'Too many unsuccessful attempts. Wait 15 minutes before trying again.';
            } else {
                $window_expired = !$attempt || (int) $attempt['window_expired'] === 1;
                $attempt_count = $window_expired ? 0 : (int) $attempt['attempt_count'];
                $authenticated = hash_equals($admin_config['username'], $username) && $password_matches;
                if ($authenticated) {
                    $clear_attempts = $pdo->prepare('DELETE FROM admin_login_attempts WHERE attempt_key = :attempt_key');
                    $clear_attempts->execute([':attempt_key' => $attempt_key]);
                    $pdo->commit();
                    session_regenerate_id(true);
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_last_activity'] = time();
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    header('Location: admin.php', true, 303);
                    exit;
                }

                $attempt_count++;
                $save_attempt = $pdo->prepare(
                    'UPDATE admin_login_attempts
                     SET attempt_count = :attempt_count,
                         window_started_at = IF(:window_expired = 1, CURRENT_TIMESTAMP, window_started_at),
                         locked_until = IF(:lock_threshold = 1, CURRENT_TIMESTAMP + INTERVAL 15 MINUTE, NULL)
                     WHERE attempt_key = :attempt_key'
                );
                $save_attempt->execute([
                    ':attempt_count' => $attempt_count,
                    ':window_expired' => $window_expired ? 1 : 0,
                    ':lock_threshold' => $attempt_count >= 5 ? 1 : 0,
                    ':attempt_key' => $attempt_key,
                ]);
                $pdo->exec('DELETE FROM admin_login_attempts WHERE window_started_at < (CURRENT_TIMESTAMP - INTERVAL 30 DAY)');
                $pdo->commit();
                $error = 'The username or password was not recognized.';
            }
        } catch (PDOException $exception) {
            if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Bexxy admin login database error: ' . $exception->getMessage());
            http_response_code(503);
            $error = 'Sign-in is temporarily unavailable. Please try again later.';
        }
    } elseif (!empty($_SESSION['admin_logged_in']) && isset($_POST['logout'])) {
        $_SESSION = [];
        session_regenerate_id(true);
        header('Location: admin.php', true, 303);
        exit;
    } elseif (!empty($_SESSION['admin_logged_in']) && isset($_POST['update_status'])) {
        $booking_id = isset($_POST['booking_id']) && is_string($_POST['booking_id'])
            ? filter_var($_POST['booking_id'], FILTER_VALIDATE_INT)
            : false;
        $status = isset($_POST['status']) && is_string($_POST['status']) ? $_POST['status'] : '';
        $internal_notes = isset($_POST['internal_notes']) && is_string($_POST['internal_notes'])
            ? trim($_POST['internal_notes'])
            : '';
        $status_filter = isset($_POST['status_filter']) && is_string($_POST['status_filter'])
            ? $_POST['status_filter']
            : 'all';
        if (!$booking_id || $booking_id < 1 || !in_array($status, $status_options, true)) {
            $error = 'Choose a valid request and status.';
        } elseif ((function_exists('mb_strlen') ? mb_strlen($internal_notes, 'UTF-8') : strlen($internal_notes)) > 3000) {
            $error = 'Keep the internal team note under 3,000 characters.';
        } else {
            try {
                $pdo = require __DIR__ . '/config/database.php';
                $statement = $pdo->prepare(
                    'UPDATE booking_requests
                     SET status = :status, internal_notes = :internal_notes
                     WHERE id = :id'
                );
                $statement->execute([
                    ':status' => $status,
                    ':internal_notes' => $internal_notes,
                    ':id' => $booking_id,
                ]);
                if ($statement->rowCount() === 0) {
                    $check = $pdo->prepare('SELECT id FROM booking_requests WHERE id = :id');
                    $check->execute([':id' => $booking_id]);
                    if (!$check->fetch()) {
                        $error = 'That booking request could not be found.';
                    }
                }
                if ($error === '') {
                    $_SESSION['admin_notice'] = 'Booking #' . $booking_id . ' updated.';
                    $redirect_filter = in_array($status_filter, $status_options, true)
                        ? '?status=' . rawurlencode($status_filter)
                        : '';
                    header('Location: admin.php' . $redirect_filter, true, 303);
                    exit;
                }
            } catch (PDOException $exception) {
                error_log('Bexxy admin status update error: ' . $exception->getMessage());
                $error = 'The request could not be updated. Please try again.';
            }
        }
    }
}

if (isset($_SESSION['admin_notice']) && is_string($_SESSION['admin_notice'])) {
    $notice = $_SESSION['admin_notice'];
    unset($_SESSION['admin_notice']);
}

$bookings = [];
$page = isset($_GET['page']) && is_string($_GET['page']) ? filter_var($_GET['page'], FILTER_VALIDATE_INT) : 1;
$page = $page && $page > 0 ? min($page, 1000000) : 1;
$status_filter = isset($_GET['status']) && is_string($_GET['status']) ? $_GET['status'] : 'all';
if ($status_filter !== 'all' && !in_array($status_filter, $status_options, true)) {
    $status_filter = 'all';
}
$page_size = 25;
$total_pages = 1;
if (!empty($_SESSION['admin_logged_in'])) {
    try {
        $pdo = require __DIR__ . '/config/database.php';
        if ($status_filter === 'all') {
            $total = (int) $pdo->query('SELECT COUNT(*) FROM booking_requests')->fetchColumn();
        } else {
            $count_statement = $pdo->prepare('SELECT COUNT(*) FROM booking_requests WHERE status = :status');
            $count_statement->execute([':status' => $status_filter]);
            $total = (int) $count_statement->fetchColumn();
        }
        $total_pages = max(1, (int) ceil($total / $page_size));
        $page = min($page, $total_pages);
        $booking_sql = 'SELECT id, service, rooms, preferred_date, arrival_time, extra_services, estimated_total, currency_code, full_name, email, phone, service_address, notes, internal_notes, status, created_at
                        FROM booking_requests';
        if ($status_filter !== 'all') {
            $booking_sql .= ' WHERE status = :status';
        }
        $booking_sql .= ' ORDER BY created_at DESC, id DESC LIMIT :limit OFFSET :offset';
        $statement = $pdo->prepare($booking_sql);
        if ($status_filter !== 'all') {
            $statement->bindValue(':status', $status_filter, PDO::PARAM_STR);
        }
        $statement->bindValue(':limit', $page_size, PDO::PARAM_INT);
        $statement->bindValue(':offset', ($page - 1) * $page_size, PDO::PARAM_INT);
        $statement->execute();
        $bookings = $statement->fetchAll();
    } catch (PDOException $exception) {
        error_log('Bexxy admin dashboard database error: ' . $exception->getMessage());
        http_response_code(503);
        $error = 'Booking requests are temporarily unavailable.';
    }
}

$csrf_token = new_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Booking dashboard — Bexxy Care and Clean</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <header class="site-header">
        <a class="brand" href="index.php" aria-label="Bexxy Care and Clean home">
            <img src="image/download.png" alt="Bexxy Care and Clean" width="480" height="140">
        </a>
        <nav class="primary-nav" aria-label="Main navigation">
            <a href="index.php">View website</a>
            <?php if (!empty($_SESSION['admin_logged_in'])): ?>
                <form method="post" action="admin.php">
                    <input type="hidden" name="csrf_token" value="<?= escape_html($csrf_token) ?>">
                    <button class="admin-logout" type="submit" name="logout" value="1">Sign out</button>
                </form>
            <?php endif; ?>
        </nav>
    </header>

    <main class="admin-page page-shell">
        <?php if ($error !== ''): ?>
            <div class="booking-alert booking-alert-error" role="alert"><?= escape_html($error) ?></div>
        <?php endif; ?>
        <?php if ($notice !== ''): ?>
            <div class="booking-alert booking-alert-success" role="status"><?= escape_html($notice) ?></div>
        <?php endif; ?>

        <?php if (empty($_SESSION['admin_logged_in'])): ?>
            <section class="admin-login booking-form" aria-labelledby="admin-heading">
                <div class="booking-heading">
                    <div class="eyebrow">Private area</div>
                    <h1 id="admin-heading">Booking<br><span class="headline-accent">dashboard.</span></h1>
                    <p>Sign in with your private dashboard credentials to view booking requests.</p>
                </div>
                <form method="post" action="admin.php" class="form-grid">
                    <input type="hidden" name="csrf_token" value="<?= escape_html($csrf_token) ?>">
                    <label class="form-field form-field-wide" for="admin-username">
                        <span>Username</span>
                        <input id="admin-username" name="username" autocomplete="username" required>
                    </label>
                    <label class="form-field form-field-wide" for="admin-password">
                        <span>Password</span>
                        <input id="admin-password" name="password" type="password" autocomplete="current-password" required>
                    </label>
                    <button class="button button-primary form-field-wide" type="submit" name="login" value="1">Sign in</button>
                </form>
            </section>
        <?php else: ?>
            <div class="admin-heading">
                <div>
                    <div class="eyebrow">Private area</div>
                    <h1>Booking requests</h1>
                    <p>Track each request from first contact through completion. Staff notes are private and never shown to customers.</p>
                </div>
            </div>
            <form class="admin-filter" method="get" action="admin.php">
                <label class="form-field" for="booking-status-filter">
                    <span>Show requests</span>
                    <select id="booking-status-filter" name="status">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All statuses</option>
                        <?php foreach ($status_options as $status): ?>
                            <option value="<?= escape_html($status) ?>" <?= $status_filter === $status ? 'selected' : '' ?>><?= escape_html(ucfirst($status)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button class="button button-dark" type="submit">Filter</button>
            </form>
            <?php if (!$bookings): ?>
                <div class="admin-empty"><?= $status_filter === 'all' ? 'No booking requests yet.' : 'No requests currently have this status.' ?></div>
            <?php else: ?>
                <div class="admin-bookings">
                    <?php foreach ($bookings as $booking): ?>
                        <?php
                        $extras = json_decode((string) $booking['extra_services'], true);
                        if (!is_array($extras)) {
                            $extras = [];
                        }
                        ?>
                        <article class="admin-booking">
                            <div class="admin-booking-top">
                                <div>
                                    <span class="admin-reference">Request #<?= (int) $booking['id'] ?></span>
                                    <h2><?= escape_html((string) $booking['full_name']) ?></h2>
                                </div>
                                <span class="admin-status admin-status-<?= escape_html((string) $booking['status']) ?>"><?= escape_html(ucfirst((string) $booking['status'])) ?></span>
                            </div>
                            <dl class="admin-details">
                                <div><dt>Service</dt><dd><?= escape_html((string) $booking['service']) ?></dd></div>
                                <div><dt>Rooms / areas</dt><dd><?= (int) $booking['rooms'] ?></dd></div>
                                <div><dt>Estimated total</dt><dd><?= $booking['estimated_total'] === null ? 'To be quoted' : escape_html((string) $booking['currency_code'] . ' ' . number_format((float) $booking['estimated_total'], 0)) ?></dd></div>
                                <div><dt>Preferred date</dt><dd><?= escape_html(date('F j, Y', strtotime((string) $booking['preferred_date']))) ?></dd></div>
                                <div><dt>Arrival time</dt><dd><?= escape_html(date('g:i a', strtotime((string) $booking['arrival_time']))) ?></dd></div>
                                <div><dt>Email</dt><dd><a href="mailto:<?= escape_html((string) $booking['email']) ?>"><?= escape_html((string) $booking['email']) ?></a></dd></div>
                                <div><dt>Phone</dt><dd><a href="tel:<?= escape_html((string) $booking['phone']) ?>"><?= escape_html((string) $booking['phone']) ?></a></dd></div>
                                <div class="admin-detail-wide"><dt>Address</dt><dd><?= escape_html((string) $booking['service_address']) ?></dd></div>
                                <div class="admin-detail-wide"><dt>Extras</dt><dd><?= $extras ? escape_html(implode(', ', $extras)) : 'None selected' ?></dd></div>
                                <?php if ((string) $booking['notes'] !== ''): ?>
                                    <div class="admin-detail-wide"><dt>Notes</dt><dd><?= nl2br(escape_html((string) $booking['notes'])) ?></dd></div>
                                <?php endif; ?>
                                <div><dt>Submitted</dt><dd><?= escape_html(date('M j, Y g:i a', strtotime((string) $booking['created_at']))) ?></dd></div>
                            </dl>
                            <form class="admin-status-form" method="post" action="admin.php">
                                <input type="hidden" name="csrf_token" value="<?= escape_html($csrf_token) ?>">
                                <input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>">
                                <input type="hidden" name="status_filter" value="<?= escape_html($status_filter) ?>">
                                <label class="form-field" for="status-<?= (int) $booking['id'] ?>">
                                    <span>Update status</span>
                                    <select id="status-<?= (int) $booking['id'] ?>" name="status">
                                        <?php foreach ($status_options as $status): ?>
                                            <option value="<?= escape_html($status) ?>" <?= $booking['status'] === $status ? 'selected' : '' ?>><?= escape_html(ucfirst($status)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label class="form-field admin-internal-note" for="internal-notes-<?= (int) $booking['id'] ?>">
                                    <span>Private team note</span>
                                    <textarea id="internal-notes-<?= (int) $booking['id'] ?>" name="internal_notes" rows="2" maxlength="3000"><?= escape_html((string) $booking['internal_notes']) ?></textarea>
                                </label>
                                <button class="button button-dark" type="submit" name="update_status" value="1">Save status</button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>
                <?php if ($total_pages > 1): ?>
                    <nav class="admin-pagination" aria-label="Booking request pages">
                        <?php if ($page > 1): ?>
                            <a class="button button-dark" href="admin.php?page=<?= $page - 1 ?><?= $status_filter === 'all' ? '' : '&amp;status=' . rawurlencode($status_filter) ?>">Newer requests</a>
                        <?php endif; ?>
                        <span>Page <?= $page ?> of <?= $total_pages ?></span>
                        <?php if ($page < $total_pages): ?>
                            <a class="button button-dark" href="admin.php?page=<?= $page + 1 ?><?= $status_filter === 'all' ? '' : '&amp;status=' . rawurlencode($status_filter) ?>">Older requests</a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </main>
    <script src="navigation.js" defer></script>
</body>
</html>
