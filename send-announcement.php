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

$channelKey = trim((string) ($input['channel'] ?? ''));
$title = trim((string) ($input['title'] ?? ''));
$content = trim((string) ($input['content'] ?? ''));
$mention = trim((string) ($input['mention'] ?? 'none'));
$colorRaw = trim((string) ($input['color'] ?? '#3454d1'));

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

if (mb_strlen($content) > 2000) {
    http_response_code(422);
    echo json_encode(['error' => 'Le message dépasse 2000 caractères']);
    exit;
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

if (!preg_match('/^#[0-9a-fA-F]{6}$/', $colorRaw)) {
    $colorRaw = '#3454d1';
}
$colorDecimal = hexdec(ltrim($colorRaw, '#'));

$mentionPrefix = '';
if ($mention === 'everyone') {
    $mentionPrefix = '@everyone';
} elseif ($mention === 'here') {
    $mentionPrefix = '@here';
}

$authorName = trim((string) ($_SESSION['user']['name'] ?? 'Utilisateur inconnu'));
$authorEmail = trim((string) ($_SESSION['user']['email'] ?? ''));
$authorPicture = trim((string) ($_SESSION['user']['picture'] ?? ''));

$embed = [
    'title' => $title,
    'description' => $content,
    'color' => $colorDecimal,
    'footer' => [
        'text' => $authorEmail !== '' ? 'Publié par ' . $authorName . ' (' . $authorEmail . ')' : 'Publié par ' . $authorName,
    ],
    'timestamp' => gmdate('c'),
];

if ($authorPicture !== '') {
    $embed['thumbnail'] = ['url' => $authorPicture];
}

$payload = [
    'content' => $mentionPrefix,
    'embeds' => [$embed],
    'allowed_mentions' => [
        'parse' => $mentionPrefix === '' ? [] : ['everyone'],
    ],
];

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
