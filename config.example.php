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
        'client_id' => 'VOTRE_CLIENT_ID.apps.googleusercontent.com',
        'client_secret' => 'VOTRE_CLIENT_SECRET',
        'redirect_uri' => 'https://sign.groupe-speed.cloud/callback.php',
        'hosted_domain' => 'groupe-speed.cloud',
    ],
    // Webhooks Discord. Ne jamais exposer ces URLs côté client.
    'discord' => [
        'channels' => [
            'annonces' => [
                'label' => 'Annonces générales',
                'webhook_url' => 'https://discord.com/api/webhooks/ID/TOKEN',
            ],
            'events' => [
                'label' => 'Events',
                'webhook_url' => 'https://discord.com/api/webhooks/ID/TOKEN',
            ],
            'staff' => [
                'label' => 'Staff',
                'webhook_url' => 'https://discord.com/api/webhooks/ID/TOKEN',
            ],
        ],
    ],
];
