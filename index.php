<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';

use Google\Client;

$config = require __DIR__ . '/config.php';

// Si déjà connecté, afficher le générateur
if (isset($_SESSION['user'])) {
    include __DIR__ . '/views/announcements.php';
    exit;
}

// Créer le client Google
$client = new Client();
$client->setClientId($config['google']['client_id']);
$client->setClientSecret($config['google']['client_secret']);
$client->setRedirectUri($config['google']['redirect_uri']);
$client->addScope('email');
$client->addScope('profile');
$client->setHostedDomain($config['google']['hosted_domain']);

$authUrl = $client->createAuthUrl();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Annonces Discord - Groupe Speed Cloud</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'speed-purple': '#8a4dfd',
                    }
                }
            }
        }
    </script>
    <link rel="icon" type="image/png" href="https://sign.groupe-speed.cloud/assets/images/cloudy.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Titillium+Web:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-gray-900 flex items-center justify-center" style="font-family: 'Titillium Web', sans-serif;">
    <div class="text-center">
        <img src="/assets/images/cloudy.png" alt="Groupe Speed Cloud" class="w-24 h-24 mx-auto mb-6 rounded-2xl">
        <h1 class="text-3xl font-bold text-white mb-2">Groupe Speed Cloud</h1>
        <p class="text-gray-300 mb-8">Centre d'envoi des annonces Discord</p>
        
        <a href="<?= htmlspecialchars($authUrl) ?>" 
           class="inline-flex items-center gap-3 bg-white text-gray-800 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition shadow-lg">
            <svg class="w-5 h-5" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            Se connecter avec Google
        </a>
        
        <p class="text-gray-500 text-sm mt-6">Réservé aux collaborateurs de l'association Groupe Speed Cloud</p>
    </div>
</body>
</html>
