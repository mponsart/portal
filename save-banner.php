<?php
session_start();
header('Content-Type: application/json');

function jsonError(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

function jsonOk(array $payload): void {
    http_response_code(200);
    echo json_encode($payload);
    exit;
}

if (!isset($_SESSION['user'])) jsonError('Non authentifié.', 401);

require_once __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/config.php';
$user = $_SESSION['user'];

if (!in_array($user['email'], $config['admins'] ?? [], true)) {
    jsonError('Accès non autorisé.', 403);
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) jsonError('Corps de requête invalide.');

$clientToken = $body['csrf_token'] ?? '';
$serverToken = $_SESSION['csrf_token'] ?? '';
if (!$serverToken || !hash_equals($serverToken, $clientToken)) {
    jsonError('Token CSRF invalide.', 403);
}

$bannerFile = __DIR__ . '/uploads/banners.json';
$banners = [];
if (file_exists($bannerFile)) {
    $decoded = json_decode(file_get_contents($bannerFile), true);
    $banners = is_array($decoded) ? $decoded : [];
}

function saveBannerFile(string $path, array $data): void {
    if (file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) === false) {
        jsonError('Impossible d\'écrire le fichier de bannières.', 500);
    }
}

$action = trim((string)($body['action'] ?? ''));

if ($action === 'add') {
    $title = mb_substr(trim((string)($body['title'] ?? '')), 0, 150);
    $message = mb_substr(trim((string)($body['message'] ?? '')), 0, 600);
    $style = trim((string)($body['style'] ?? 'danger'));
    $active = (bool)($body['active'] ?? true);

    if ($title === '' || $message === '') {
        jsonError('Titre et message obligatoires.');
    }

    $allowed = ['danger', 'info', 'success', 'warning'];
    if (!in_array($style, $allowed, true)) $style = 'danger';

    $now = (new DateTimeImmutable('now', new DateTimeZone('Europe/Paris')))->format('d/m/Y à H:i');

    $banner = [
        'id' => bin2hex(random_bytes(8)),
        'title' => $title,
        'message' => $message,
        'style' => $style,
        'active' => $active,
        'created_at' => $now,
        'updated_at' => $now,
        'created_by' => $user['email'] ?? '',
    ];

    $banners[] = $banner;
    saveBannerFile($bannerFile, $banners);
    jsonOk(['banner' => $banner]);
}

if ($action === 'update') {
    $id = trim((string)($body['id'] ?? ''));
    if (!preg_match('/^[0-9a-f]{16}$/', $id)) jsonError('Identifiant invalide.');

    $idx = null;
    foreach ($banners as $i => $b) {
        if (($b['id'] ?? '') === $id) { $idx = $i; break; }
    }
    if ($idx === null) jsonError('Bannière introuvable.', 404);

    $title = mb_substr(trim((string)($body['title'] ?? $banners[$idx]['title'] ?? '')), 0, 150);
    $message = mb_substr(trim((string)($body['message'] ?? $banners[$idx]['message'] ?? '')), 0, 600);
    $style = trim((string)($body['style'] ?? $banners[$idx]['style'] ?? 'danger'));
    $allowed = ['danger', 'info', 'success', 'warning'];
    if (!in_array($style, $allowed, true)) $style = 'danger';

    if ($title === '' || $message === '') {
        jsonError('Titre et message obligatoires.');
    }

    $now = (new DateTimeImmutable('now', new DateTimeZone('Europe/Paris')))->format('d/m/Y à H:i');

    $banners[$idx] = array_merge($banners[$idx], [
        'title' => $title,
        'message' => $message,
        'style' => $style,
        'active' => (bool)($body['active'] ?? $banners[$idx]['active'] ?? false),
        'updated_at' => $now,
    ]);

    saveBannerFile($bannerFile, $banners);
    jsonOk(['banner' => $banners[$idx]]);
}

if ($action === 'toggle') {
    $id = trim((string)($body['id'] ?? ''));
    if (!preg_match('/^[0-9a-f]{16}$/', $id)) jsonError('Identifiant invalide.');

    foreach ($banners as $i => $b) {
        if (($b['id'] ?? '') === $id) {
            $banners[$i]['active'] = !($b['active'] ?? false);
            $banners[$i]['updated_at'] = (new DateTimeImmutable('now', new DateTimeZone('Europe/Paris')))->format('d/m/Y à H:i');
            saveBannerFile($bannerFile, $banners);
            jsonOk(['banner' => $banners[$i]]);
        }
    }

    jsonError('Bannière introuvable.', 404);
}

if ($action === 'delete') {
    $id = trim((string)($body['id'] ?? ''));
    if (!preg_match('/^[0-9a-f]{16}$/', $id)) jsonError('Identifiant invalide.');

    $before = count($banners);
    $banners = array_values(array_filter($banners, fn($b) => ($b['id'] ?? '') !== $id));
    if (count($banners) === $before) jsonError('Bannière introuvable.', 404);

    saveBannerFile($bannerFile, $banners);
    jsonOk(['deleted' => $id]);
}

jsonError('Action inconnue.');
