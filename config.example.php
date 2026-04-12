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

];
