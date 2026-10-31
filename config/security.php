<?php
declare(strict_types=1);

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Cache-Control: private, no-store, max-age=0');
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function escape_html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function private_config(): array
{
    static $config;
    if (is_array($config)) {
        return $config;
    }

    $config = load_private_config();
    return $config;
}

function private_config_path(): string
{
    static $resolved_path;
    if (is_string($resolved_path)) {
        return $resolved_path;
    }

    $config_path = getenv('BEXXY_PRIVATE_CONFIG');
    $candidate_paths = [];
    if (is_string($config_path) && $config_path !== '') {
        $candidate_paths[] = $config_path;
    }

    $project_root = dirname(__DIR__);
    $segment_roots = [
        $project_root,
        dirname($project_root),
        dirname($project_root, 2),
        dirname($project_root, 3),
    ];
    foreach ($segment_roots as $root) {
        if (is_string($root) && $root !== '') {
            $candidate_paths[] = $root . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'bexxy-config.php';
            $candidate_paths[] = $root . DIRECTORY_SEPARATOR . 'bexxy-config.php';
        }
    }

    $resolved_path = null;
    foreach (array_values(array_unique($candidate_paths)) as $candidate) {
        if (is_file($candidate) && is_readable($candidate)) {
            $resolved_path = $candidate;
            break;
        }
    }

    if ($resolved_path === null) {
        throw new RuntimeException('Private configuration is missing or unreadable. Follow SETUP.md to create it outside the web root.');
    }

    return $resolved_path;
}

function load_private_config(): array
{
    $loaded_config = require private_config_path();
    if (!is_array($loaded_config)) {
        throw new RuntimeException('Private configuration must return a PHP array.');
    }

    return $loaded_config;
}

function save_private_config(array $config): void
{
    $contents = "<?php\ndeclare(strict_types=1);\n\nreturn " . var_export($config, true) . ";\n";
    $path = private_config_path();
    $temporary_path = $path . '.' . bin2hex(random_bytes(8)) . '.tmp';
    if (file_put_contents($temporary_path, $contents, LOCK_EX) !== strlen($contents)) {
        if (is_file($temporary_path) && !unlink($temporary_path)) {
            error_log('Bexxy could not remove an incomplete temporary private config file.');
        }
        throw new RuntimeException('Could not write the updated private configuration.');
    }
    if (!rename($temporary_path, $path)) {
        unlink($temporary_path);
        throw new RuntimeException('Could not replace the private configuration file.');
    }
    if (function_exists('opcache_invalidate') && !opcache_invalidate($path, true)) {
        error_log('Bexxy could not invalidate the cached private configuration after updating it.');
    }
}

function new_csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function valid_csrf_token(string $token): bool
{
    return isset($_SESSION['csrf_token'])
        && is_string($_SESSION['csrf_token'])
        && $token !== ''
        && hash_equals($_SESSION['csrf_token'], $token);
}
