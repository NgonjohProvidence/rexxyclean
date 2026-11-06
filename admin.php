<?php
declare(strict_types=1);

require_once __DIR__ . '/config/security.php';
start_secure_session();

$admin_must_change_password = false;
if (isset($_SESSION['admin_last_activity']) && time() - (int) $_SESSION['admin_last_activity'] > 1800) {
    unset($_SESSION['admin_logged_in'], $_SESSION['admin_last_activity']);
}
if (!empty($_SESSION['admin_logged_in'])) {
    $_SESSION['admin_last_activity'] = time();
    $admin_must_change_password = !empty($_SESSION['admin_must_change_password']);
}

$error = '';
$notice = '';
$status_options = ['pending', 'contacted', 'confirmed', 'completed', 'cancelled'];
$dashboard_stats = [
    'total' => 0,
    'pending' => 0,
    'contacted' => 0,
    'confirmed' => 0,
    'completed' => 0,
    'cancelled' => 0,
    'today' => 0,
];
$monthly_bookings = [];
$service_analytics = [];
$estimated_value_by_currency = [];

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
        $username_matches = hash_equals($admin_config['username'], $username);
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
                $authenticated = $username_matches && $password_matches;
                if ($authenticated) {
                    $clear_attempts = $pdo->prepare('DELETE FROM admin_login_attempts WHERE attempt_key = :attempt_key');
                    $clear_attempts->execute([':attempt_key' => $attempt_key]);
                    $pdo->commit();
                    session_regenerate_id(true);
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_last_activity'] = time();
                    $_SESSION['admin_must_change_password'] = !empty($admin_config['must_change_password']);
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
    } elseif (!empty($_SESSION['admin_logged_in']) && !empty($_SESSION['admin_must_change_password']) && isset($_POST['change_password'])) {
        $new_password = isset($_POST['new_password']) && is_string($_POST['new_password'])
            ? $_POST['new_password']
            : '';
        $confirm_password = isset($_POST['confirm_password']) && is_string($_POST['confirm_password'])
            ? $_POST['confirm_password']
            : '';
        if (strlen($new_password) < 12 || strlen($new_password) > 1024) {
            $error = 'Choose a password between 12 and 1,024 characters.';
        } elseif (!hash_equals($new_password, $confirm_password)) {
            $error = 'The new passwords do not match.';
        } else {
            try {
                $config['admin']['password_hash'] = password_hash($new_password, PASSWORD_DEFAULT);
                $config['admin']['must_change_password'] = false;
                save_private_config($config);
                $_SESSION['admin_must_change_password'] = false;
                $admin_must_change_password = false;
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $notice = 'Your password has been changed. Continue to the booking dashboard.';
            } catch (RuntimeException $exception) {
                error_log('Bexxy admin password update error: ' . $exception->getMessage());
                http_response_code(503);
                $error = 'Could not save your new password to the private config. Check that Apache can write to C:\\xampp\\private, then try again.';
            }
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
        $search_query = isset($_POST['search_query']) && is_string($_POST['search_query'])
            ? trim($_POST['search_query'])
            : '';
        if (function_exists('mb_substr')) {
            $search_query = mb_substr($search_query, 0, 100, 'UTF-8');
        } else {
            $search_query = substr($search_query, 0, 100);
        }
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
                    $redirect_parameters = [];
                    if (in_array($status_filter, $status_options, true)) {
                        $redirect_parameters['status'] = $status_filter;
                    }
                    if ($search_query !== '') {
                        $redirect_parameters['q'] = $search_query;
                    }
                    if (isset($_POST['today_only']) && $_POST['today_only'] === '1') {
                        $redirect_parameters['today'] = '1';
                    }
                    $redirect_query = $redirect_parameters ? '?' . http_build_query($redirect_parameters) : '';
                    header('Location: admin.php' . $redirect_query, true, 303);
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
$total = 0;
$search_query = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
if (function_exists('mb_substr')) {
    $search_query = mb_substr($search_query, 0, 100, 'UTF-8');
} else {
    $search_query = substr($search_query, 0, 100);
}
$page = isset($_GET['page']) && is_string($_GET['page']) ? filter_var($_GET['page'], FILTER_VALIDATE_INT) : 1;
$page = $page && $page > 0 ? min($page, 1000000) : 1;
$status_filter = isset($_GET['status']) && is_string($_GET['status']) ? $_GET['status'] : 'all';
if ($status_filter !== 'all' && !in_array($status_filter, $status_options, true)) {
    $status_filter = 'all';
}
$today_only = isset($_GET['today']) && $_GET['today'] === '1';
$page_size = 25;
$total_pages = 1;
if (!empty($_SESSION['admin_logged_in'])) {
    try {
        $pdo = require __DIR__ . '/config/database.php';
        $stats_statement = $pdo->query(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(status = 'pending'), 0) AS pending,
                    COALESCE(SUM(status = 'contacted'), 0) AS contacted,
                    COALESCE(SUM(status = 'confirmed'), 0) AS confirmed,
                    COALESCE(SUM(status = 'completed'), 0) AS completed,
                    COALESCE(SUM(status = 'cancelled'), 0) AS cancelled,
                    COALESCE(SUM(status = 'confirmed' AND preferred_date = CURRENT_DATE()), 0) AS today
             FROM booking_requests"
        );
        $dashboard_stats = $stats_statement->fetch();
        $dashboard_stats = array_map('intval', $dashboard_stats);

        $month_start = date('Y-m-01', strtotime('-5 months'));
        $month_end = date('Y-m-01', strtotime('+1 month'));
        $monthly_statement = $pdo->prepare(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS booking_month, COUNT(*) AS booking_count
             FROM booking_requests
             WHERE created_at >= :month_start AND created_at < :month_end
             GROUP BY DATE_FORMAT(created_at, '%Y-%m')
             ORDER BY booking_month"
        );
        $monthly_statement->execute([
            ':month_start' => $month_start,
            ':month_end' => $month_end,
        ]);
        $monthly_counts = [];
        foreach ($monthly_statement->fetchAll() as $month_row) {
            $monthly_counts[(string) $month_row['booking_month']] = (int) $month_row['booking_count'];
        }
        for ($month_offset = 5; $month_offset >= 0; $month_offset--) {
            $month_timestamp = strtotime(date('Y-m-01') . ' -' . $month_offset . ' months');
            $month_key = date('Y-m', $month_timestamp);
            $monthly_bookings[] = [
                'label' => date('M', $month_timestamp),
                'count' => $monthly_counts[$month_key] ?? 0,
            ];
        }
        $monthly_maximum = 0;
        foreach ($monthly_bookings as $month_row) {
            $monthly_maximum = max($monthly_maximum, $month_row['count']);
        }

        $service_statement = $pdo->query(
            'SELECT service, COUNT(*) AS booking_count
             FROM booking_requests
             GROUP BY service
             ORDER BY booking_count DESC, service ASC
             LIMIT 5'
        );
        $service_analytics = $service_statement->fetchAll();
        $service_maximum = 0;
        foreach ($service_analytics as $service_row) {
            $service_maximum = max($service_maximum, (int) $service_row['booking_count']);
        }
        foreach ($service_analytics as &$service_row) {
            $service_row['booking_count'] = (int) $service_row['booking_count'];
            $service_row['percentage'] = $service_maximum > 0
                ? (int) round($service_row['booking_count'] / $service_maximum * 100)
                : 0;
        }
        unset($service_row);

        $value_statement = $pdo->query(
            "SELECT currency_code, COUNT(*) AS booking_count, COALESCE(SUM(estimated_total), 0) AS estimated_total
             FROM booking_requests
             WHERE status IN ('confirmed', 'completed') AND estimated_total IS NOT NULL
             GROUP BY currency_code
             ORDER BY currency_code"
        );
        $estimated_value_by_currency = $value_statement->fetchAll();

        $booking_conditions = [];
        $booking_parameters = [];
        if ($status_filter !== 'all') {
            $booking_conditions[] = 'status = :status';
            $booking_parameters[':status'] = $status_filter;
        }
        if ($today_only) {
            $booking_conditions[] = 'preferred_date = CURRENT_DATE()';
        }
        if ($search_query !== '') {
            $search_like = '%' . strtr($search_query, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
            $booking_conditions[] = "(full_name LIKE :search_name ESCAPE '!'
                OR email LIKE :search_email ESCAPE '!'
                OR phone LIKE :search_phone ESCAPE '!'
                OR service LIKE :search_service ESCAPE '!'
                OR service_address LIKE :search_address ESCAPE '!'
                OR CAST(id AS CHAR) LIKE :search_id ESCAPE '!')";
            foreach (['name', 'email', 'phone', 'service', 'address', 'id'] as $field) {
                $booking_parameters[':search_' . $field] = $search_like;
            }
        }

        $where_clause = $booking_conditions ? ' WHERE ' . implode(' AND ', $booking_conditions) : '';
        $count_statement = $pdo->prepare('SELECT COUNT(*) FROM booking_requests' . $where_clause);
        $count_statement->execute($booking_parameters);
        $total = (int) $count_statement->fetchColumn();
        $total_pages = max(1, (int) ceil($total / $page_size));
        $page = min($page, $total_pages);
        $booking_sql = 'SELECT id, service, rooms, estimated_hours, cleaner_count, preferred_date, arrival_time, extra_services, estimated_total, currency_code, full_name, email, phone, service_address, notes, internal_notes, status, created_at
                        FROM booking_requests' . $where_clause;
        $booking_sql .= ' ORDER BY created_at DESC, id DESC LIMIT :limit OFFSET :offset';
        $statement = $pdo->prepare($booking_sql);
        foreach ($booking_parameters as $parameter => $value) {
            $statement->bindValue($parameter, $value, PDO::PARAM_STR);
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
                <form method="post" action="admin.php" class="form-grid" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= escape_html($csrf_token) ?>">
                    <label class="form-field form-field-wide" for="admin-username">
                        <span>Username</span>
                        <input id="admin-username" name="username" autocomplete="off" required>
                    </label>
                    <label class="form-field form-field-wide" for="admin-password">
                        <span>Password</span>
                        <input id="admin-password" name="password" type="password" autocomplete="off" required>
                    </label>
                    <button class="button button-primary form-field-wide" type="submit" name="login" value="1">Sign in</button>
                </form>
            </section>
        <?php elseif ($admin_must_change_password): ?>
            <section class="admin-login booking-form" aria-labelledby="admin-password-heading">
                <div class="booking-heading">
                    <div class="eyebrow">One-time setup</div>
                    <h1 id="admin-password-heading">Set your<br><span class="headline-accent">new password.</span></h1>
                    <p>You signed in with a temporary password. Create a new password before viewing booking requests.</p>
                </div>
                <form method="post" action="admin.php" class="form-grid" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= escape_html($csrf_token) ?>">
                    <label class="form-field form-field-wide" for="new-admin-password">
                        <span>New password (at least 12 characters)</span>
                        <input id="new-admin-password" name="new_password" type="password" autocomplete="new-password" minlength="12" maxlength="1024" required>
                    </label>
                    <label class="form-field form-field-wide" for="confirm-admin-password">
                        <span>Confirm new password</span>
                        <input id="confirm-admin-password" name="confirm_password" type="password" autocomplete="new-password" minlength="12" maxlength="1024" required>
                    </label>
                    <button class="button button-primary form-field-wide" type="submit" name="change_password" value="1">Save new password</button>
                </form>
            </section>
        <?php else: ?>
            <div class="admin-heading">
                <div>
                    <div class="eyebrow">Private area</div>
                    <h1>Operations dashboard</h1>
                    <p>Manage customer requests from first contact through completion. Team notes stay private.</p>
                </div>
            </div>
            <section class="admin-overview" aria-label="Booking overview">
                <a class="admin-stat" href="admin.php">
                    <span class="admin-stat-label">All requests</span>
                    <strong><?= $dashboard_stats['total'] ?></strong>
                    <span class="admin-stat-foot">Across every status</span>
                </a>
                <a class="admin-stat admin-stat-pending" href="admin.php?status=pending">
                    <span class="admin-stat-label">Needs attention</span>
                    <strong><?= $dashboard_stats['pending'] ?></strong>
                    <span class="admin-stat-foot">Awaiting first contact</span>
                </a>
                <a class="admin-stat admin-stat-confirmed" href="admin.php?status=confirmed">
                    <span class="admin-stat-label">Confirmed</span>
                    <strong><?= $dashboard_stats['confirmed'] ?></strong>
                    <span class="admin-stat-foot">Scheduled requests</span>
                </a>
                <a class="admin-stat admin-stat-today" href="admin.php?status=confirmed&amp;today=1">
                    <span class="admin-stat-label">Today</span>
                    <strong><?= $dashboard_stats['today'] ?></strong>
                    <span class="admin-stat-foot">Confirmed appointments</span>
                </a>
            </section>
            <section class="admin-analytics" aria-label="Booking analytics">
                <article class="admin-analytics-card admin-trend-card">
                    <div class="admin-analytics-heading">
                        <div>
                            <div class="eyebrow">Demand</div>
                            <h2>Booking trend</h2>
                        </div>
                        <span class="admin-analytics-caption">Last 6 months</span>
                    </div>
                    <?php if ($dashboard_stats['total'] === 0): ?>
                        <p class="admin-analytics-empty">Booking activity will appear here when customers submit requests.</p>
                    <?php else: ?>
                        <div class="admin-month-chart" role="img" aria-label="Monthly booking requests over the last six months">
                            <?php foreach ($monthly_bookings as $month_row): ?>
                                <?php
                                $month_height = $monthly_maximum > 0
                                    ? max(4, (int) round($month_row['count'] / $monthly_maximum * 100))
                                    : 4;
                                ?>
                                <div class="admin-month-column" aria-label="<?= escape_html($month_row['label']) ?>: <?= $month_row['count'] ?> requests">
                                    <span class="admin-month-count"><?= $month_row['count'] ?></span>
                                    <div class="admin-month-track">
                                        <span class="admin-month-bar" style="height: <?= $month_height ?>%"></span>
                                    </div>
                                    <span class="admin-month-label"><?= escape_html($month_row['label']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>
                <article class="admin-analytics-card">
                    <div class="admin-analytics-heading">
                        <div>
                            <div class="eyebrow">Pipeline</div>
                            <h2>Request status</h2>
                        </div>
                        <span class="admin-analytics-caption">All requests</span>
                    </div>
                    <?php if ($dashboard_stats['total'] === 0): ?>
                        <p class="admin-analytics-empty">Status breakdown will appear here when requests arrive.</p>
                    <?php else: ?>
                        <div class="admin-breakdown">
                            <?php foreach ($status_options as $status): ?>
                                <?php
                                $status_count = $dashboard_stats[$status];
                                $status_percentage = (int) round($status_count / $dashboard_stats['total'] * 100);
                                ?>
                                <div class="admin-breakdown-row">
                                    <div class="admin-breakdown-label">
                                        <span><?= escape_html(ucfirst($status)) ?></span>
                                        <span><?= $status_count ?> <span class="admin-breakdown-percent">(<?= $status_percentage ?>%)</span></span>
                                    </div>
                                    <div class="admin-breakdown-track">
                                        <span class="admin-breakdown-bar admin-breakdown-<?= escape_html($status) ?>" style="width: <?= $status_percentage ?>%"></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>
                <article class="admin-analytics-card">
                    <div class="admin-analytics-heading">
                        <div>
                            <div class="eyebrow">Services</div>
                            <h2>Most requested</h2>
                        </div>
                        <span class="admin-analytics-caption">Top 5</span>
                    </div>
                    <?php if (!$service_analytics): ?>
                        <p class="admin-analytics-empty">Popular services will appear here when requests arrive.</p>
                    <?php else: ?>
                        <div class="admin-breakdown">
                            <?php foreach ($service_analytics as $service_row): ?>
                                <div class="admin-breakdown-row">
                                    <div class="admin-breakdown-label">
                                        <span><?= escape_html((string) $service_row['service']) ?></span>
                                        <span><?= $service_row['booking_count'] ?></span>
                                    </div>
                                    <div class="admin-breakdown-track">
                                        <span class="admin-breakdown-bar admin-service-bar" style="width: <?= $service_row['percentage'] ?>%"></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>
                <article class="admin-analytics-card admin-value-card">
                    <div class="admin-analytics-heading">
                        <div>
                            <div class="eyebrow">Confirmed work</div>
                            <h2>Estimated value</h2>
                        </div>
                        <span class="admin-analytics-caption">Confirmed + completed</span>
                    </div>
                    <?php if (!$estimated_value_by_currency): ?>
                        <p class="admin-analytics-empty">There is no estimated value for confirmed or completed requests yet.</p>
                    <?php else: ?>
                        <div class="admin-value-list">
                            <?php foreach ($estimated_value_by_currency as $value_row): ?>
                                <?php $value_decimals = $value_row['currency_code'] === 'CAD' ? 2 : 0; ?>
                                <div class="admin-value-row">
                                    <span class="admin-value-currency"><?= escape_html((string) $value_row['currency_code']) ?></span>
                                    <strong><?= escape_html(number_format((float) $value_row['estimated_total'], $value_decimals)) ?></strong>
                                    <span class="admin-value-count"><?= (int) $value_row['booking_count'] ?> request<?= (int) $value_row['booking_count'] === 1 ? '' : 's' ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="admin-value-note">Estimates only—not collected revenue or invoices.</p>
                    <?php endif; ?>
                </article>
            </section>
            <section class="admin-workspace" aria-labelledby="requests-heading">
                <div class="admin-workspace-heading">
                    <div>
                        <div class="eyebrow">Bookings</div>
                        <h2 id="requests-heading">Customer requests</h2>
                    </div>
                    <span class="admin-results-count"><?= number_format($total) ?> result<?= $total === 1 ? '' : 's' ?></span>
                </div>
                <form class="admin-filter" method="get" action="admin.php" role="search">
                    <?php if ($today_only): ?>
                        <input type="hidden" name="today" value="1">
                    <?php endif; ?>
                    <label class="form-field admin-search-field" for="booking-search">
                        <span>Search requests</span>
                        <input id="booking-search" name="q" type="search" value="<?= escape_html($search_query) ?>" maxlength="100" placeholder="Name, email, phone or request #">
                    </label>
                    <label class="form-field admin-status-filter" for="booking-status-filter">
                        <span>Status</span>
                        <select id="booking-status-filter" name="status">
                            <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All statuses</option>
                            <?php foreach ($status_options as $status): ?>
                                <option value="<?= escape_html($status) ?>" <?= $status_filter === $status ? 'selected' : '' ?>><?= escape_html(ucfirst($status)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button class="button button-dark" type="submit">Search</button>
                    <?php if ($search_query !== '' || $status_filter !== 'all' || $today_only): ?>
                        <a class="admin-clear-filter" href="admin.php">Clear</a>
                    <?php endif; ?>
                </form>
            <?php if (!$bookings): ?>
                <div class="admin-empty">
                    <strong><?= $search_query !== '' || $status_filter !== 'all' || $today_only ? 'No matching requests.' : 'Your dashboard is ready.' ?></strong>
                    <span><?= $search_query !== '' || $status_filter !== 'all' || $today_only ? 'Try another search or clear the filters.' : 'New customer booking requests will appear here as soon as they are submitted on your website.' ?></span>
                </div>
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
                                <?php if ($booking['estimated_hours'] !== null): ?>
                                    <div><dt>Estimated duration</dt><dd><?= escape_html(rtrim(rtrim(number_format((float) $booking['estimated_hours'], 1), '0'), '.')) ?> hours with <?= (int) $booking['cleaner_count'] ?> cleaner(s)</dd></div>
                                <?php endif; ?>
                                <div><dt>Estimated total</dt><dd><?= $booking['estimated_total'] === null ? 'To be quoted' : escape_html((string) $booking['currency_code'] . ' ' . number_format((float) $booking['estimated_total'], $booking['currency_code'] === 'CAD' ? 2 : 0)) ?></dd></div>
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
                                <input type="hidden" name="search_query" value="<?= escape_html($search_query) ?>">
                                <?php if ($today_only): ?>
                                    <input type="hidden" name="today_only" value="1">
                                <?php endif; ?>
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
                        <?php
                        $page_parameters = [];
                        if ($status_filter !== 'all') {
                            $page_parameters['status'] = $status_filter;
                        }
                        if ($search_query !== '') {
                            $page_parameters['q'] = $search_query;
                        }
                        if ($today_only) {
                            $page_parameters['today'] = '1';
                        }
                        ?>
                        <?php if ($page > 1): ?>
                            <?php $page_parameters['page'] = $page - 1; ?>
                            <a class="button button-dark" href="admin.php?<?= escape_html(http_build_query($page_parameters)) ?>">Newer requests</a>
                        <?php endif; ?>
                        <span>Page <?= $page ?> of <?= $total_pages ?></span>
                        <?php if ($page < $total_pages): ?>
                            <?php $page_parameters['page'] = $page + 1; ?>
                            <a class="button button-dark" href="admin.php?<?= escape_html(http_build_query($page_parameters)) ?>">Older requests</a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
    <script src="navigation.js" defer></script>
</body>
</html>
