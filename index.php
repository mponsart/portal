<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/db.php';

$config = require __DIR__ . '/config.php';

// Si déjà connecté, afficher le portail
if (isset($_SESSION['user'])) {
    $pdo = db_connect();
    $email = (string)($_SESSION['user']['email'] ?? '');
    $isAdmin = in_array($email, $config['admins'] ?? [], true);
    db_upsert_user_from_session($pdo, $_SESSION['user'], $isAdmin);

    $charterVersion = current_charter_version($config);
    $accepted = db_has_accepted_charter($pdo, $email, $charterVersion);

    if (!$accepted) {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        include __DIR__ . '/views/charter.php';
        exit;
    }

    include __DIR__ . '/views/portal.php';
    exit;
}

// Non connecté → redirection immédiate vers Google OAuth
$googleClientClass = 'Google\\Client';
if (!class_exists($googleClientClass)) {
    http_response_code(500);
    exit('Dépendances Google manquantes. Exécutez composer install.');
}

$client = new $googleClientClass();
$client->setClientId($config['google']['client_id']);
$client->setClientSecret($config['google']['client_secret']);
$client->setRedirectUri($config['google']['redirect_uri']);
$client->addScope('email');
$client->addScope('profile');

header('Location: ' . $client->createAuthUrl());
exit;
