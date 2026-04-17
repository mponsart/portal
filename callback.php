<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/db.php';

$config = require __DIR__ . '/config.php';

// Créer le client Google
$googleClientClass = 'Google\\Client';
$googleOauth2Class = 'Google\\Service\\Oauth2';

if (!class_exists($googleClientClass) || !class_exists($googleOauth2Class)) {
    http_response_code(500);
    exit('Dépendances Google manquantes. Exécutez composer install.');
}

$client = new $googleClientClass();
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
    $oauth2 = new $googleOauth2Class($client);
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
        <title>Erreur d'authentification — Speed Cloud</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    </head>
    <body class="min-h-screen flex items-center justify-center px-4" style="background:#07080e;font-family:'Inter',sans-serif;">
        <div class="text-center max-w-md">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4"
                 style="background:rgba(220,38,38,.18);border:1px solid rgba(220,38,38,.35);">
                <span class="text-3xl">⚠️</span>
            </div>
            <h1 class="text-xl font-bold text-white mb-2">Erreur d'authentification</h1>
            <p class="text-white/55 mb-6 text-sm"><?= htmlspecialchars($e->getMessage()) ?></p>
            <a href="/" class="text-violet-400 hover:text-violet-300 text-sm">← Retour</a>
        </div>
    </body>
    </html>
    <?php
}
