# Discord Annonces - Groupe Speed Cloud

Application PHP pour publier des annonces sur Discord via webhook, avec authentification Google Workspace.

## Fonctionnalites

- Connexion Google OAuth (restriction par domaine)
- Interface web pour composer une annonce
- Envoi vers plusieurs canaux Discord configures
- Publication en embed Discord (titre, description, couleur, auteur, date)
- Mentions controlees: aucune, @everyone, @here
- Webhooks gardes cote serveur (jamais exposes au navigateur)

## Prerequis

- PHP 8.0+
- Extension PHP cURL activee
- Composer
- Un projet Google Cloud avec OAuth 2.0 Web
- Des webhooks Discord deja crees

## Installation

1. Installer les dependances:

```bash
composer install
```

2. Creer la configuration locale:

```bash
cp config.example.php config.php
```

3. Remplir `config.php`:
- `google.client_id`
- `google.client_secret`
- `google.redirect_uri`
- `google.hosted_domain`
- `discord.channels` (label + webhook_url)

4. Configurer le serveur web pour servir le dossier du projet.

## Format des canaux Discord

Exemple dans `config.php`:

```php
'discord' => [
    'channels' => [
        'annonces' => [
            'label' => 'Annonces generales',
            'webhook_url' => 'https://discord.com/api/webhooks/ID/TOKEN',
        ],
        'events' => [
            'label' => 'Events',
            'webhook_url' => 'https://discord.com/api/webhooks/ID/TOKEN',
        ],
    ],
],
```

## Utilisation

1. Ouvrir l'application.
2. Se connecter avec Google.
3. Choisir un canal, renseigner titre + message.
4. Choisir mention et couleur.
5. Envoyer l'annonce.

## Securite

- Les URLs de webhook ne sont pas envoyees au front.
- L'endpoint `send-announcement.php` exige une session authentifiee.
- Validation serveur sur les champs (taille, format couleur, canal existant).

## Endpoint principal

- `POST /send-announcement.php`
- Content-Type: `application/json`
- Corps attendu:

```json
{
  "channel": "annonces",
  "title": "Maintenance ce soir",
  "content": "Le serveur sera indisponible de 22h a 22h30.",
  "mention": "none",
  "color": "#3454d1"
}
```

Reponse succes:

```json
{
  "success": true
}
```

## Arborescence utile

- `index.php`: point d'entree + redirection login
- `auth.php`: verification de session
- `callback.php`: retour OAuth Google
- `views/announcements.php`: interface d'envoi
- `send-announcement.php`: envoi webhook Discord
- `config.example.php`: exemple de configuration

## Licence

Projet interne Groupe Speed Cloud.
