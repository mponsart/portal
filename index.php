<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';

use Google\Client;

$config = require __DIR__ . '/config.php';

// Si déjà connecté, afficher le portail
if (isset($_SESSION['user'])) {
    include __DIR__ . '/views/portal.php';
    exit;
}

// Non connecté → redirection immédiate vers Google OAuth
$client = new Client();
$client->setClientId($config['google']['client_id']);
$client->setClientSecret($config['google']['client_secret']);
$client->setRedirectUri($config['google']['redirect_uri']);
$client->addScope('email');
$client->addScope('profile');

header('Location: ' . $client->createAuthUrl());
exit;
