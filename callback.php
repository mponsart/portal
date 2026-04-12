<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/db.php';

use Google\Client;
use Google\Service\Oauth2;

$config = require __DIR__ . '/config.php';

// Créer le client Google
$client = new Client();
$client->setClientId($config['google']['client_id']);
$client->setClientSecret($config['google']['client_secret']);
$client->setRedirectUri($config['google']['redirect_uri']);

// Récupérer le code d'autorisation
if (!isset($_GET['code'])) {
    header('Location: /');
    exit;
}

try {
    // Échanger le code contre un token
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    
    if (isset($token['error'])) {
        throw new Exception($token['error_description'] ?? $token['error']);
    }
    
    $client->setAccessToken($token);
    
    // Récupérer les infos utilisateur
    $oauth2 = new Oauth2($client);
    $userInfo = $oauth2->userinfo->get();
    
    // Parser le nom
    $nameParts = explode(' ', $userInfo->name, 2);
    $firstName = $nameParts[0] ?? '';
    $lastName = $nameParts[1] ?? '';
    
    // Stocker en session
    $_SESSION['user'] = [
        'email' => $userInfo->email,
        'name' => $userInfo->name,
        'firstName' => $firstName,
        'lastName' => $lastName,
        'picture' => $userInfo->picture,
        'token' => $token,
        'token_created' => time(),
    ];

    $pdo = db_connect();
    $isAdmin = in_array($userInfo->email, $config['admins'] ?? [], true);
    db_upsert_user_from_session($pdo, $_SESSION['user'], $isAdmin);
    
    header('Location: /');
    exit;
    
} catch (Exception $e) {
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Erreur — Portail</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="min-h-screen bg-gray-900 flex items-center justify-center">
        <div class="text-center">
            <div class="text-red-500 text-6xl mb-4">⚠️</div>
            <h1 class="text-2xl font-bold text-white mb-2">Erreur d'authentification</h1>
            <p class="text-gray-400 mb-6"><?= htmlspecialchars($e->getMessage()) ?></p>
            <a href="/" class="text-purple-400 hover:text-purple-300">← Retour</a>
        </div>
    </body>
    </html>
    <?php
}
