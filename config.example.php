<?php
/**
 * Configuration Google OAuth
 * 
 * Copiez ce fichier en config.php et remplissez vos identifiants :
 * cp config.example.php config.php
 * 
 * OAuth :
 * 1. Allez sur https://console.cloud.google.com/
 * 2. Créez un projet ou sélectionnez-en un existant
 * 3. APIs & Services > Credentials > Create Credentials > OAuth 2.0 Client IDs
 * 4. Type: Web application
 * 5. Authorized redirect URI: https://sign.groupe-speed.cloud/callback.php
 * 6. Copiez Client ID et Client Secret ci-dessous
 */

return [
    'google' => [
        'client_id'     => 'VOTRE_CLIENT_ID.apps.googleusercontent.com',
        'client_secret' => 'VOTRE_CLIENT_SECRET',
        'redirect_uri'  => 'https://portail.groupe-speed.cloud/callback.php',
    ],

    // Version de la charte informatique.
    // Incrémentez cette valeur (ex: 2026-05-01 ou v2) pour forcer une nouvelle validation.
    'charter' => [
        'version' => '2026-04-12',
    ],

    // Emails autorisés à accéder à l'espace admin (admin.php)
    'admins' => [
        'admin@groupe-speed.cloud',
    ],

    // Applications affichées sur le portail (optionnel — des valeurs par défaut sont utilisées si absent)
    // 'portal' => [
    //     'apps' => [
    //         ['name' => 'Gmail',        'url' => 'https://mail.google.com', 'emoji' => '📧', 'bg' => 'from-red-500 to-rose-700'],
    //         ['name' => 'Google Drive', 'url' => 'https://drive.google.com','emoji' => '💾', 'bg' => 'from-yellow-400 to-orange-500'],
    //     ],
    // ],

    // Sites surveillés sur la page /status.php
    // 'status_sites' => [
    //     ['name' => 'Portail', 'url' => 'https://portail.groupe-speed.cloud'],
    //     ['name' => 'SSO', 'url' => 'https://sign.groupe-speed.cloud'],
    // ],

];
