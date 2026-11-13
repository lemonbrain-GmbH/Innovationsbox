<?php

// tests/e2e/email-log-server/index.php
declare(strict_types=1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Load .env manually
$envFile = dirname(__DIR__, 3) . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, " \t\n\r\0\x0B\"");
        }
    }
}

$dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=%s',
    $_ENV['DB_HOSTNAME'] ?? '127.0.0.1',
    $_ENV['DB_DATABASE'] ?? '',
    $_ENV['DB_CHARSET'] ?? 'utf8mb4',
);

try {
    $pdo = new PDO($dsn, $_ENV['DB_USERNAME'] ?? '', $_ENV['DB_PASSWORD'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'DELETE') {
    $pdo->exec("DELETE FROM Logs WHERE channel = 'sent_emails'");
    echo json_encode(['status' => 'cleared']);
    exit;
}

if ($method === 'GET') {
    $since = isset($_GET['since']) ? (int) $_GET['since'] : 0;
    $stmt = $pdo->prepare('SELECT logID, message, time FROM Logs WHERE channel = ? AND time >= ? ORDER BY time DESC LIMIT 100');
    $stmt->execute(['sent_emails', $since]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['data' => $rows]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
