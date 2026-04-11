<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

$config = require __DIR__ . '/config.php';
$rawBody = file_get_contents('php://input');
$input = json_decode($rawBody, true);

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'Corps JSON invalide']);
    exit;
}

$channelKey        = trim((string) ($input['channel'] ?? ''));
$title             = trim((string) ($input['title'] ?? ''));
$content           = trim((string) ($input['content'] ?? ''));
$mention           = trim((string) ($input['mention'] ?? 'none'));
$colorRaw          = trim((string) ($input['color'] ?? '#3454d1'));
$useEmbed          = isset($input['useEmbed']) ? (bool) $input['useEmbed'] : true;
$customAuthorName  = trim((string) ($input['authorName'] ?? ''));
$customAuthorIcon  = trim((string) ($input['authorIcon'] ?? ''));
$customThumbUrl    = trim((string) ($input['thumbUrl'] ?? ''));
$customImageUrl    = trim((string) ($input['imageUrl'] ?? ''));
$customFooterText  = trim((string) ($input['footerText'] ?? ''));
$customFields      = isset($input['fields']) && is_array($input['fields']) ? $input['fields'] : [];

if ($channelKey === '' || $title === '' || $content === '') {
    http_response_code(422);
    echo json_encode(['error' => 'Canal, titre et message sont obligatoires']);
    exit;
}

if (mb_strlen($title) > 120) {
    http_response_code(422);
    echo json_encode(['error' => 'Le titre dépasse 120 caractères']);
    exit;
}

if ($useEmbed) {
    if (mb_strlen($content) > 2000) {
        http_response_code(422);
        echo json_encode(['error' => 'Le message dépasse 2000 caractères']);
        exit;
    }
}

$channels = $config['discord']['channels'] ?? [];
if (!isset($channels[$channelKey]) || !is_array($channels[$channelKey])) {
    http_response_code(422);
    echo json_encode(['error' => 'Canal Discord inconnu']);
    exit;
}

$webhookUrl = trim((string) ($channels[$channelKey]['webhook_url'] ?? ''));
if ($webhookUrl === '') {
    http_response_code(500);
    echo json_encode(['error' => 'Webhook Discord manquant en configuration']);
    exit;
}

$mentionPrefix = '';
if ($mention === 'everyone') {
    $mentionPrefix = '@everyone';
} elseif ($mention === 'here') {
    $mentionPrefix = '@here';
}


if ($useEmbed) {
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $colorRaw)) {
        $colorRaw = '#3454d1';
    }
    $colorDecimal = hexdec(ltrim($colorRaw, '#'));

    $embed = [
        'title'       => $title,
        'description' => $content,
        'color'       => $colorDecimal,
        'timestamp'   => gmdate('c'),
    ];

    // Author — only when explicitly provided
    if ($customAuthorName !== '') {
        $embedAuthor = ['name' => $customAuthorName];
        if ($customAuthorIcon !== '' && filter_var($customAuthorIcon, FILTER_VALIDATE_URL)) {
            $embedAuthor['icon_url'] = $customAuthorIcon;
        }
        $embed['author'] = $embedAuthor;
    }

    // Footer — only when explicitly provided
    if ($customFooterText !== '') {
        $embed['footer'] = ['text' => $customFooterText];
    }

    // Thumbnail
    if ($customThumbUrl !== '' && filter_var($customThumbUrl, FILTER_VALIDATE_URL)) {
        $embed['thumbnail'] = ['url' => $customThumbUrl];
    }

    // Large image
    if ($customImageUrl !== '' && filter_var($customImageUrl, FILTER_VALIDATE_URL)) {
        $embed['image'] = ['url' => $customImageUrl];
    }

    // Fields
    $embedFields = [];
    foreach ($customFields as $field) {
        if (!is_array($field)) {
            continue;
        }
        $fieldName  = trim((string) ($field['name'] ?? ''));
        $fieldValue = trim((string) ($field['value'] ?? ''));
        if ($fieldName === '' || $fieldValue === '') {
            continue;
        }
        $embedFields[] = [
            'name'   => mb_substr($fieldName, 0, 256),
            'value'  => mb_substr($fieldValue, 0, 1024),
            'inline' => isset($field['inline']) ? (bool) $field['inline'] : false,
        ];
        if (count($embedFields) >= 25) {
            break;
        }
    }
    if (!empty($embedFields)) {
        $embed['fields'] = $embedFields;
    }

    $payload = [
        'content' => $mentionPrefix,
        'embeds'  => [$embed],
        'allowed_mentions' => [
            'parse' => $mentionPrefix === '' ? [] : ['everyone'],
        ],
    ];
} else {
    $messageParts = [];
    if ($mentionPrefix !== '') {
        $messageParts[] = $mentionPrefix;
    }
    $messageParts[] = '**' . $title . '**';
    $messageParts[] = $content;
    $messageText = implode("\n\n", $messageParts);

    if (mb_strlen($messageText) > 2000) {
        http_response_code(422);
        echo json_encode(['error' => 'Le message combiné (mention + titre + contenu) dépasse 2000 caractères']);
        exit;
    }

    $payload = [
        'content' => $messageText,
        'allowed_mentions' => [
            'parse' => $mentionPrefix === '' ? [] : ['everyone'],
        ],
    ];
}

$ch = curl_init($webhookUrl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
]);

$responseBody = curl_exec($ch);
$curlError = curl_error($ch);
$statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($responseBody === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Erreur réseau Discord: ' . $curlError]);
    exit;
}

if ($statusCode < 200 || $statusCode >= 300) {
    http_response_code(502);
    echo json_encode(['error' => 'Discord a refusé la requête']);
    exit;
}

echo json_encode(['success' => true]);
