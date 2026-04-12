<?php
/**
 * Endpoint AJAX — gestion des annonces mises en avant (featured)
 * Actions : add | delete
 * Protégé par : session utilisateur + rôle admin + token CSRF
 */

session_start();
header('Content-Type: application/json');

// ── Helpers ───────────────────────────────────────────────────────────────────
function jsonError(string $msg, int $code = 400): never {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

function jsonOk(array $payload): never {
    http_response_code(200);
    echo json_encode($payload);
    exit;
}

// ── Auth ──────────────────────────────────────────────────────────────────────
if (!isset($_SESSION['user'])) {
    jsonError('Non authentifié.', 401);
}

require_once __DIR__ . '/vendor/autoload.php';
$config  = require __DIR__ . '/config.php';
$user    = $_SESSION['user'];
$admins  = $config['admins'] ?? [];

if (!in_array($user['email'], $admins)) {
    jsonError('Accès non autorisé.', 403);
}

// ── Lecture du body JSON ──────────────────────────────────────────────────────
$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!is_array($body)) {
    jsonError('Corps de requête invalide.');
}

// ── CSRF ──────────────────────────────────────────────────────────────────────
$clientToken = $body['csrf_token'] ?? '';
$serverToken = $_SESSION['csrf_token'] ?? '';

if (!$serverToken || !hash_equals($serverToken, $clientToken)) {
    jsonError('Token CSRF invalide.', 403);
}

// ── Fichier de stockage ───────────────────────────────────────────────────────
$featuredFile = __DIR__ . '/uploads/featured.json';

$featured = [];
if (file_exists($featuredFile)) {
    $decoded  = json_decode(file_get_contents($featuredFile), true);
    $featured = is_array($decoded) ? $decoded : [];
}

$action = $body['action'] ?? '';

// ── Action : add ──────────────────────────────────────────────────────────────
if ($action === 'add') {
    $content = trim((string) ($body['content'] ?? ''));
    if ($content === '') {
        jsonError('Le contenu est obligatoire.');
    }

    $title = mb_substr(trim((string) ($body['title'] ?? '')), 0, 200);
    $emoji = mb_substr(trim((string) ($body['emoji'] ?? '📢')), 0, 4);
    $color = trim((string) ($body['color'] ?? '#3454d1'));

    // Valider la couleur CSS hex
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
        $color = '#3454d1';
    }

    $now = (new DateTimeImmutable('now', new DateTimeZone('Europe/Paris')))->format('d/m/Y à H:i');

    $announcement = [
        'id'        => bin2hex(random_bytes(8)),
        'emoji'     => $emoji ?: '📢',
        'title'     => $title,
        'content'   => mb_substr($content, 0, 2000),
        'color'     => $color,
        'pinned_at' => $now,
        'pinned_by' => $user['email'],
    ];

    $featured[] = $announcement;

    if (file_put_contents($featuredFile, json_encode($featured, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) === false) {
        jsonError('Impossible d\'écrire le fichier de données.', 500);
    }

    jsonOk(['announcement' => $announcement]);
}

// ── Action : delete ───────────────────────────────────────────────────────────
if ($action === 'delete') {
    $id = trim((string) ($body['id'] ?? ''));

    if ($id === '' || !preg_match('/^[0-9a-f]{16}$/', $id)) {
        jsonError('Identifiant invalide.');
    }

    $before   = count($featured);
    $featured = array_values(array_filter($featured, fn($a) => ($a['id'] ?? '') !== $id));

    if (count($featured) === $before) {
        jsonError('Annonce introuvable.', 404);
    }

    if (file_put_contents($featuredFile, json_encode($featured, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) === false) {
        jsonError('Impossible d\'écrire le fichier de données.', 500);
    }

    jsonOk(['deleted' => $id]);
}

jsonError('Action inconnue.');
