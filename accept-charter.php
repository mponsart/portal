<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: /');
    exit;
}

require_once __DIR__ . '/db.php';
$config = require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

$postedToken = (string)($_POST['csrf_token'] ?? '');
$serverToken = (string)($_SESSION['csrf_token'] ?? '');
if ($serverToken === '' || !hash_equals($serverToken, $postedToken)) {
    http_response_code(403);
    exit('Token CSRF invalide.');
}

$confirm = (string)($_POST['accept_charter'] ?? '');
if ($confirm !== 'yes') {
    header('Location: /');
    exit;
}

$email = (string)($_SESSION['user']['email'] ?? '');
if ($email === '') {
    header('Location: /logout.php');
    exit;
}

$pdo = db_connect();
$version = current_charter_version($config);
$ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
$ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');

db_accept_charter($pdo, $email, $version, $ip !== '' ? $ip : null, $ua !== '' ? $ua : null);

header('Location: /');
exit;
