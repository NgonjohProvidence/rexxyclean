<?php
declare(strict_types=1);

require_once __DIR__ . '/security.php';

$config = private_config();
$database_config = $config['database'] ?? null;
if (!is_array($database_config)) {
    throw new RuntimeException('The private configuration is missing its database settings.');
}

$host = $database_config['host'] ?? '';
$database = $database_config['name'] ?? '';
$username = $database_config['username'] ?? '';
$password = $database_config['password'] ?? null;
if (!is_string($host) || $host === '' || !is_string($database) || $database === ''
    || !is_string($username) || $username === '' || !is_string($password)) {
    throw new RuntimeException('The private configuration has incomplete database settings.');
}

$dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $database);

return new PDO($dsn, $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
