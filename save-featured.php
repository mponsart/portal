<?php
/**
 * Endpoint AJAX — gestion des actualités/annonces épinglées
 * Actions : add | update | delete | reorder
 */

session_start();
header('Content-Type: application/json');

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

if (!in_array($user['email'], $config['admins'] ?? [])) {
    jsonError('Accès non autorisé.', 403);
}

// ── Body JSON ──────────────────────────────────────────────────────────────────
$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) jsonError('Corps de requête invalide.');

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

function saveFile(string $path, array $data): void {
    if (file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) === false) {
        jsonError('Impossible d\'écrire le fichier de données.', 500);
    }
}

function sanitizeHtml(string $html): string {
    // Balises autorisées pour le texte riche (Quill)
    $allowed = '<p><br><strong><em><u><s><ul><ol><li><h1><h2><h3><blockquote><a><pre><code><span>';
    $clean = strip_tags($html, $allowed);

    // Supprimer les attributs inline dangereux
    $clean = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean);
    $clean = preg_replace('/\s+style\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean);

    // Sécuriser les href des liens
    $clean = preg_replace_callback(
        '/<a\b([^>]*)>/i',
        static function (array $matches): string {
            $attrs = $matches[1] ?? '';
            if (!preg_match('/href\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $attrs, $hrefMatch)) {
                return '<a>';
            }

            $href = $hrefMatch[2] ?: ($hrefMatch[3] ?: ($hrefMatch[4] ?? ''));
            $href = trim($href);
            if ($href === '') {
                return '<a>';
            }

            $lowerHref = strtolower($href);
            $allowedSchemes = ['http://', 'https://', 'mailto:', '/', '#'];
            $isAllowed = false;
            foreach ($allowedSchemes as $scheme) {
                if (str_starts_with($lowerHref, $scheme)) {
                    $isAllowed = true;
                    break;
                }
            }

            if (!$isAllowed) {
                return '<a>';
            }

            $safeHref = htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            return '<a href="' . $safeHref . '" target="_blank" rel="noopener noreferrer">';
        },
        $clean
    );

    return $clean;
}

$action = $body['action'] ?? '';

// ── ADD ───────────────────────────────────────────────────────────────────────
if ($action === 'add') {
    $htmlContent = trim((string)($body['html_content'] ?? ''));
    $plainText   = trim(strip_tags($htmlContent));
    if ($plainText === '') jsonError('Le contenu est obligatoire.');

    $title    = mb_substr(trim((string)($body['title']    ?? '')), 0, 200);
    $emoji    = mb_substr(trim((string)($body['emoji']    ?? '📢')), 0, 4);
    $color    = trim((string)($body['color']   ?? '#3454d1'));
    $category = trim((string)($body['category'] ?? 'general'));
    $status   = trim((string)($body['status'] ?? 'published'));
    $pinned   = (bool)($body['pinned'] ?? true);

    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) $color = '#3454d1';
    $allowedCats = ['general', 'event', 'urgent', 'info'];
    if (!in_array($category, $allowedCats)) $category = 'general';
    $allowedStatus = ['published', 'draft'];
    if (!in_array($status, $allowedStatus, true)) $status = 'published';

    $now = (new DateTimeImmutable('now', new DateTimeZone('Europe/Paris')))->format('d/m/Y à H:i');

    $announcement = [
        'id'           => bin2hex(random_bytes(8)),
        'emoji'        => $emoji ?: '📢',
        'title'        => $title,
        'html_content' => sanitizeHtml($htmlContent),
        'color'        => $color,
        'category'     => $category,
        'status'       => $status,
        'pinned'       => $pinned,
        'created_at'   => $now,
        'updated_at'   => $now,
        'pinned_by'    => $user['email'],
    ];

    $featured[] = $announcement;
    saveFile($featuredFile, $featured);
    jsonOk(['announcement' => $announcement]);
}

// ── UPDATE ────────────────────────────────────────────────────────────────────
if ($action === 'update') {
    $id = trim((string)($body['id'] ?? ''));
    if (!preg_match('/^[0-9a-f]{16}$/', $id)) jsonError('Identifiant invalide.');

    $idx = null;
    foreach ($featured as $i => $a) {
        if (($a['id'] ?? '') === $id) { $idx = $i; break; }
    }
    if ($idx === null) jsonError('Annonce introuvable.', 404);

    $htmlContent = trim((string)($body['html_content'] ?? ''));
    $plainText   = trim(strip_tags($htmlContent));
    if ($plainText === '') jsonError('Le contenu est obligatoire.');

    $now = (new DateTimeImmutable('now', new DateTimeZone('Europe/Paris')))->format('d/m/Y à H:i');
    $color = trim((string)($body['color'] ?? $featured[$idx]['color'] ?? '#3454d1'));
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) $color = '#3454d1';
    $category = trim((string)($body['category'] ?? $featured[$idx]['category'] ?? 'general'));
    $allowedCats = ['general', 'event', 'urgent', 'info'];
    if (!in_array($category, $allowedCats)) $category = 'general';
    $status = trim((string)($body['status'] ?? $featured[$idx]['status'] ?? 'published'));
    $allowedStatus = ['published', 'draft'];
    if (!in_array($status, $allowedStatus, true)) $status = 'published';

    $featured[$idx] = array_merge($featured[$idx], [
        'emoji'        => mb_substr(trim((string)($body['emoji'] ?? $featured[$idx]['emoji'] ?? '📢')), 0, 4),
        'title'        => mb_substr(trim((string)($body['title'] ?? $featured[$idx]['title'] ?? '')), 0, 200),
        'html_content' => sanitizeHtml($htmlContent),
        'color'        => $color,
        'category'     => $category,
        'status'       => $status,
        'pinned'       => (bool)($body['pinned'] ?? $featured[$idx]['pinned'] ?? true),
        'updated_at'   => $now,
    ]);

    saveFile($featuredFile, $featured);
    jsonOk(['announcement' => $featured[$idx]]);
}

// ── DELETE ────────────────────────────────────────────────────────────────────
if ($action === 'delete') {
    $id = trim((string)($body['id'] ?? ''));
    if (!preg_match('/^[0-9a-f]{16}$/', $id)) jsonError('Identifiant invalide.');

    $before   = count($featured);
    $featured = array_values(array_filter($featured, fn($a) => ($a['id'] ?? '') !== $id));
    if (count($featured) === $before) jsonError('Annonce introuvable.', 404);

    saveFile($featuredFile, $featured);
    jsonOk(['deleted' => $id]);
}

jsonError('Action inconnue.');
