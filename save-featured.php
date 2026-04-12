<?php
/**
 * Endpoint AJAX — gestion des actualités/annonces épinglées
 * Actions : add | update | delete | reorder
 */

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

function markdownInlineToSafeHtml(string $text): string {
    $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    $stash = [];
    $escaped = preg_replace_callback('/`([^`\n]+)`/', static function (array $m) use (&$stash): string {
        $idx = count($stash);
        $stash[] = '<code>' . $m[1] . '</code>';
        return "\x02{$idx}\x03";
    }, $escaped) ?? $escaped;

    $escaped = preg_replace_callback('/\[([^\]]+)\]\(([^)]+)\)/', static function (array $m): string {
        $label = $m[1];
        $href = trim(html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if (!preg_match('#^(https?://|mailto:|/|#)#i', $href)) {
            return $label;
        }
        $safeHref = htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return '<a href="' . $safeHref . '" target="_blank" rel="noopener noreferrer">' . $label . '</a>';
    }, $escaped) ?? $escaped;

    $escaped = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $escaped) ?? $escaped;
    $escaped = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $escaped) ?? $escaped;

    $escaped = preg_replace_callback('/\x02(\d+)\x03/', static function (array $m) use ($stash): string {
        $i = (int)$m[1];
        return $stash[$i] ?? '';
    }, $escaped) ?? $escaped;

    return $escaped;
}

function markdownToSafeHtml(string $markdown): string {
    $lines = preg_split('/\r\n|\r|\n/', $markdown) ?: [];
    $html = [];
    $inUl = false;
    $inOl = false;
    $inCode = false;

    $closeLists = static function () use (&$html, &$inUl, &$inOl): void {
        if ($inUl) {
            $html[] = '</ul>';
            $inUl = false;
        }
        if ($inOl) {
            $html[] = '</ol>';
            $inOl = false;
        }
    };

    foreach ($lines as $line) {
        $line = rtrim($line, "\t ");

        if (str_starts_with($line, '```')) {
            $closeLists();
            $html[] = $inCode ? '</code></pre>' : '<pre><code>';
            $inCode = !$inCode;
            continue;
        }

        if ($inCode) {
            $html[] = htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\n";
            continue;
        }

        if (trim($line) === '') {
            $closeLists();
            continue;
        }

        if (preg_match('/^(#{1,3})\s+(.+)$/u', $line, $m) === 1) {
            $closeLists();
            $level = strlen($m[1]);
            $html[] = '<h' . $level . '>' . markdownInlineToSafeHtml($m[2]) . '</h' . $level . '>';
            continue;
        }

        if (preg_match('/^[-*]\s+(.+)$/u', $line, $m) === 1) {
            if (!$inUl) {
                $closeLists();
                $html[] = '<ul>';
                $inUl = true;
            }
            $html[] = '<li>' . markdownInlineToSafeHtml($m[1]) . '</li>';
            continue;
        }

        if (preg_match('/^\d+\.\s+(.+)$/u', $line, $m) === 1) {
            if (!$inOl) {
                $closeLists();
                $html[] = '<ol>';
                $inOl = true;
            }
            $html[] = '<li>' . markdownInlineToSafeHtml($m[1]) . '</li>';
            continue;
        }

        if (preg_match('/^>\s+(.+)$/u', $line, $m) === 1) {
            $closeLists();
            $html[] = '<blockquote>' . markdownInlineToSafeHtml($m[1]) . '</blockquote>';
            continue;
        }

        $closeLists();
        $html[] = '<p>' . markdownInlineToSafeHtml($line) . '</p>';
    }

    $closeLists();
    if ($inCode) {
        $html[] = '</code></pre>';
    }

    return implode('', $html);
}

function htmlToText(string $html): string {
    return trim(preg_replace('/\s+/u', ' ', strip_tags($html)) ?? '');
}

$action = $body['action'] ?? '';

// ── ADD ───────────────────────────────────────────────────────────────────────
if ($action === 'add') {
    $markdownContent = trim((string)($body['markdown_content'] ?? ''));
    if ($markdownContent === '' && isset($body['html_content'])) {
        $markdownContent = htmlToText((string)$body['html_content']);
    }
    $htmlContent = markdownToSafeHtml($markdownContent);
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
        'markdown_content' => $markdownContent,
        'html_content' => $htmlContent,
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

    $markdownContent = trim((string)($body['markdown_content'] ?? ($featured[$idx]['markdown_content'] ?? '')));
    if ($markdownContent === '' && isset($body['html_content'])) {
        $markdownContent = htmlToText((string)$body['html_content']);
    }
    $htmlContent = markdownToSafeHtml($markdownContent);
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
        'markdown_content' => $markdownContent,
        'html_content' => $htmlContent,
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
