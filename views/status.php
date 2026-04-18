<?php
$user    = $_SESSION['user'];
$config  = require __DIR__ . '/../config.php';
$isAdmin = in_array($user['email'], $config['admins'] ?? []);

$currentPage = 'status';
$sitesFile = __DIR__ . '/../uploads/status-sites.json';
$appsFile = __DIR__ . '/../uploads/apps.json';
$pingCacheFile = __DIR__ . '/../uploads/ping-cache-status.json';
$pingCacheTtl = 45;

$defaultSites = $config['status_sites'] ?? [
    ['name' => 'Portail', 'url' => 'https://portail.groupe-speed.cloud'],
    ['name' => 'SSO', 'url' => 'https://sign.groupe-speed.cloud'],
    ['name' => 'Site Groupe', 'url' => 'https://groupe-speed.cloud'],
];

$defaultApps = $config['portal']['apps'] ?? [
    ['name' => 'Gmail',       'url' => 'https://mail.google.com',     'icon' => 'gmail'],
    ['name' => 'Drive',       'url' => 'https://drive.google.com',    'icon' => 'drive'],
    ['name' => 'Agenda',      'url' => 'https://calendar.google.com', 'icon' => 'calendar'],
    ['name' => 'Meet',        'url' => 'https://meet.google.com',     'icon' => 'meet'],
    ['name' => 'Docs',        'url' => 'https://docs.google.com',     'icon' => 'docs'],
    ['name' => 'Sheets',      'url' => 'https://sheets.google.com',   'icon' => 'sheets'],
    ['name' => 'Slides',      'url' => 'https://slides.google.com',   'icon' => 'slides'],
    ['name' => 'YouTube',     'url' => 'https://youtube.com',         'icon' => 'youtube'],
    ['name' => 'Discord',     'url' => 'https://discord.com',         'icon' => 'discord'],
    ['name' => 'GitHub',      'url' => 'https://github.com',          'icon' => 'github'],
    ['name' => 'Notion',      'url' => 'https://notion.so',           'icon' => 'notion'],
    ['name' => 'Figma',       'url' => 'https://figma.com',           'icon' => 'figma'],
];

function readJsonArray(string $path, array $fallback): array {
    if (!file_exists($path)) return $fallback;
    $decoded = json_decode((string)file_get_contents($path), true);
    return is_array($decoded) ? $decoded : $fallback;
}

function appEmoji(string $icon): string {
    return match($icon) {
        'gmail' => '📧',
        'drive' => '💾',
        'calendar' => '📅',
        'meet' => '🎥',
        'docs' => '📄',
        'sheets' => '📊',
        'slides' => '🖼️',
        'youtube' => '▶️',
        'discord' => '💬',
        'github' => '🐙',
        'notion' => '🗂️',
        'figma' => '🎨',
        default  => '🔗',
    };
}

/** Network-error curl codes that indicate connectivity restriction, not server downtime */
const CURL_NETWORK_ERRORS = [
    CURLE_COULDNT_RESOLVE_HOST,   // 6  — DNS failure
    CURLE_COULDNT_CONNECT,        // 7  — Connection refused / unreachable
    CURLE_OPERATION_TIMEDOUT,     // 28 — Timeout
    CURLE_SSL_CONNECT_ERROR,      // 35 — SSL handshake failed
    CURLE_SEND_ERROR,             // 55 — Send failure
    CURLE_RECV_ERROR,             // 56 — Receive failure
];

function singlePing(string $url): array {
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return ['ok' => false, 'code' => 0, 'ms' => 0, 'error' => 'URL invalide', 'net_err' => false];
    }

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_NOBODY         => true,   // HEAD request — no body download
            CURLOPT_USERAGENT      => 'GroupeSpeedCloudStatus/1.0',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        curl_exec($ch);
        $ms    = (int)round(((float)curl_getinfo($ch, CURLINFO_TOTAL_TIME)) * 1000);
        $code  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errno = curl_errno($ch);
        $err   = curl_error($ch);
        curl_close($ch);

        // 405 = server responded but HEAD not allowed → still up
        if ($code === 405) {
            return ['ok' => true, 'code' => $code, 'ms' => $ms, 'error' => '', 'net_err' => false];
        }

        $isNetworkErr = $errno !== 0 && in_array($errno, CURL_NETWORK_ERRORS, true);

        return [
            'ok'      => $errno === 0 && $code >= 200 && $code < 500,
            'code'    => $code,
            'ms'      => $ms,
            'error'   => $errno !== 0 ? ($err ?: 'Échec ping') : '',
            'net_err' => $isNetworkErr,
        ];
    }

    $start = microtime(true);
    $headers = @get_headers($url);
    $ms = (int)round((microtime(true) - $start) * 1000);
    if (!is_array($headers) || !isset($headers[0])) {
        return ['ok' => false, 'code' => 0, 'ms' => $ms, 'error' => 'Pas de réponse', 'net_err' => true];
    }

    preg_match('/\s(\d{3})\s/', $headers[0], $m);
    $code = isset($m[1]) ? (int)$m[1] : 0;
    return ['ok' => $code >= 200 && $code < 500, 'code' => $code, 'ms' => $ms, 'error' => '', 'net_err' => false];
}

function batchPing(array $urls): array {
    $results = [];
    if (!function_exists('curl_multi_init') || !function_exists('curl_init')) {
        foreach ($urls as $idx => $url) {
            $results[$idx] = singlePing((string)$url);
        }
        return $results;
    }

    $mh = curl_multi_init();
    $handles = [];
    foreach ($urls as $idx => $url) {
        $url = (string)$url;
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $results[$idx] = ['ok' => false, 'code' => 0, 'ms' => 0, 'error' => 'URL invalide', 'net_err' => false];
            continue;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_NOBODY         => true,   // HEAD request — no body download
            CURLOPT_USERAGENT      => 'GroupeSpeedCloudStatus/1.0',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $handles[$idx] = $ch;
        curl_multi_add_handle($mh, $ch);
    }

    do {
        $status = curl_multi_exec($mh, $active);
        if ($active) {
            curl_multi_select($mh, 1.0);
        }
    } while ($active && $status === CURLM_OK);

    foreach ($handles as $idx => $ch) {
        $code  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $ms    = (int)round(((float)curl_getinfo($ch, CURLINFO_TOTAL_TIME)) * 1000);
        $errno = curl_errno($ch);
        $err   = curl_error($ch);

        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);

        // 405 = server responded but HEAD not allowed → still up
        if ($code === 405) {
            $results[$idx] = ['ok' => true, 'code' => $code, 'ms' => $ms, 'error' => '', 'net_err' => false];
            continue;
        }

        $isNetworkErr = $errno !== 0 && in_array($errno, CURL_NETWORK_ERRORS, true);

        $results[$idx] = [
            'ok'      => $errno === 0 && $code >= 200 && $code < 500,
            'code'    => $code,
            'ms'      => $ms,
            'error'   => $errno !== 0 ? ($err ?: 'Échec ping') : '',
            'net_err' => $isNetworkErr,
        ];
    }
    curl_multi_close($mh);

    ksort($results);
    return $results;
}

function readPingCache(string $path): array {
    if (!file_exists($path)) return [];
    $decoded = json_decode((string)file_get_contents($path), true);
    return is_array($decoded) ? $decoded : [];
}

function savePingCache(string $path, array $payload): void {
    $dir = dirname($path);
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    @file_put_contents($path, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

$sites = readJsonArray($sitesFile, $defaultSites);
$apps = readJsonArray($appsFile, $defaultApps);
$workspaceIcons = ['gmail','drive','calendar','meet','docs','sheets','slides'];

$apps = array_values(array_filter($apps, function ($app) use ($workspaceIcons, $isAdmin) {
    $icon = strtolower(trim((string)($app['icon'] ?? 'link')));
    if (in_array($icon, $workspaceIcons, true)) {
        return false;
    }
    if (!$isAdmin && !empty($app['admin_only'])) {
        return false;
    }
    return true;
}));

$bannerFile = __DIR__ . '/../uploads/banners.json';
$activeBanner = null;
if (file_exists($bannerFile)) {
    $decoded = json_decode(file_get_contents($bannerFile), true);
    $banners = is_array($decoded) ? $decoded : [];
    foreach (array_reverse($banners) as $b) {
        if (!empty($b['active'])) { $activeBanner = $b; break; }
    }
}

$targets = [];
foreach ($sites as $i => $site) {
    $targets[] = ['kind' => 'site', 'index' => $i, 'url' => trim((string)($site['url'] ?? ''))];
}
foreach ($apps as $i => $app) {
    $targets[] = ['kind' => 'app', 'index' => $i, 'url' => trim((string)($app['url'] ?? ''))];
}

$signature = hash('sha256', json_encode(array_column($targets, 'url')) ?: '');
$cache = readPingCache($pingCacheFile);
$forceRefresh = isset($_GET['refresh']);
$useCache = !$forceRefresh && isset($cache['signature'], $cache['created_at'], $cache['data'])
    && $cache['signature'] === $signature
    && (time() - (int)$cache['created_at']) <= $pingCacheTtl
    && is_array($cache['data']);

$pingData = [];
if ($useCache) {
    $pingData = $cache['data'];
} else {
    $urls = array_map(fn($t) => (string)$t['url'], $targets);
    $raw = batchPing($urls);
    foreach ($targets as $idx => $t) {
        $key = $t['kind'] . ':' . (int)$t['index'];
        $pingData[$key] = $raw[$idx] ?? ['ok' => false, 'code' => 0, 'ms' => 0, 'error' => 'Ping indisponible'];
    }
    savePingCache($pingCacheFile, [
        'created_at' => time(),
        'signature' => $signature,
        'data' => $pingData,
    ]);
}

$results = [];
foreach ($sites as $i => $site) {
    $name = trim((string)($site['name'] ?? 'Site'));
    $url = trim((string)($site['url'] ?? ''));
    $ping = $pingData['site:' . $i] ?? singlePing($url);
    $results[] = ['name' => $name, 'url' => $url, 'ping' => $ping];
}

$appResults = [];
$unavailableAppResults = [];
foreach ($apps as $i => $app) {
    $name = trim((string)($app['name'] ?? 'Application'));
    $url = trim((string)($app['url'] ?? ''));
    $icon = trim((string)($app['icon'] ?? 'link'));
    $emoji = trim((string)($app['emoji'] ?? ''));
    $appStatus = $app['status'] ?? 'active';
    $ping = $pingData['app:' . $i] ?? singlePing($url);
    $entry = ['name' => $name, 'url' => $url, 'icon' => $icon, 'emoji' => $emoji, 'status' => $appStatus, 'ping' => $ping];
    if (in_array($appStatus, ['maintenance', 'disabled'], true)) {
        $unavailableAppResults[] = $entry;
    } else {
        $appResults[] = $entry;
    }
}

$upCount = count(array_filter($results, fn($r) => $r['ping']['ok']));
$totalCount = count($results);
$upApps = count(array_filter($appResults, fn($r) => $r['ping']['ok']));
$totalApps = count($appResults);

// Detect "network limited" mode: when every non-cached check returned a network error
$allPings = array_merge(
    array_column($results, 'ping'),
    array_column($appResults, 'ping')
);
$failedPings = array_filter($allPings, fn($p) => !$p['ok']);
$failedCount = count($failedPings);
$networkLimited = !$useCache && $failedCount > 0
    && count(array_filter($failedPings, fn($p) => !empty($p['net_err']))) === $failedCount
    && $failedCount === count($allPings);

$lastCheckTs  = $useCache ? (int)$cache['created_at'] : time();
$lastCheckAgo = max(0, time() - $lastCheckTs);
$lastCheckLabel = $lastCheckAgo < 60
    ? "il y a {$lastCheckAgo}s"
    : 'il y a ' . floor($lastCheckAgo / 60) . 'min';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statuts — Groupe Speed Cloud</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="/assets/images/cloudy.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php include __DIR__ . '/_ui-tokens.php'; ?>
    <style>
        body { font-family:'Inter',sans-serif; background:var(--bg); color:var(--on-surface); }

        /* ── Status row ── */
        .status-row {
            background: var(--surface-1);
            border-radius: var(--shape-lg);
            box-shadow: var(--elev-1);
            transition: box-shadow .2s, background .14s;
        }
        .status-row:hover { box-shadow: var(--elev-2); background: var(--surface-2); }

        /* ── Status dot ── */
        .dot-ok  { width:8px;height:8px;border-radius:50%;background:var(--success);box-shadow:0 0 6px rgba(109,213,140,.65); flex-shrink:0; }
        .dot-err { width:8px;height:8px;border-radius:50%;background:var(--danger);box-shadow:0 0 6px rgba(242,184,184,.55); flex-shrink:0; }
        .dot-warn{ width:8px;height:8px;border-radius:50%;background:var(--warning);box-shadow:0 0 6px rgba(255,185,81,.55); flex-shrink:0; }
        .dot-unknown { width:8px;height:8px;border-radius:50%;background:var(--outline);flex-shrink:0; }

        /* ── Sec title ── */
        .sec-title { font-size:.6875rem;font-weight:500;letter-spacing:.06em;text-transform:uppercase;color:var(--on-surface-var); }

        /* ── Banner ── */
        .md-banner { border-radius:var(--shape-lg); border-left:4px solid; display:flex; align-items:flex-start; gap:12px; padding:14px 16px; }

        @keyframes fadeUp { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:none} }
        .fade-up { animation:fadeUp .38s cubic-bezier(.22,1,.36,1) both; }
    </style>
</head>
<body class="min-h-screen relative">
<div class="bg-ambient" aria-hidden="true"></div>

<?php include __DIR__ . '/_nav.php'; ?>

<main class="page-stack relative z-10 w-full max-w-4xl mx-auto px-4 sm:px-6 py-8">

    <?php if ($activeBanner):
        $tone = $activeBanner['style'] ?? 'danger';
        $bannerStyles = [
            'danger'  => ['bg'=>'var(--danger-cnt)',  'border'=>'var(--danger)',  'text'=>'var(--danger)'],
            'warning' => ['bg'=>'var(--warning-cnt)', 'border'=>'var(--warning)', 'text'=>'var(--warning)'],
            'success' => ['bg'=>'var(--success-cnt)', 'border'=>'var(--success)', 'text'=>'var(--success)'],
            'info'    => ['bg'=>'rgba(0,79,80,.5)',   'border'=>'var(--tertiary)','text'=>'var(--tertiary)'],
        ][$tone] ?? ['bg'=>'var(--danger-cnt)', 'border'=>'var(--danger)', 'text'=>'var(--danger)'];
        $toneIcon = ['danger'=>'🚨','warning'=>'⚠️','success'=>'✅','info'=>'ℹ️'][$tone] ?? '🚨';
    ?>
    <div class="md-banner" style="background:<?= $bannerStyles['bg'] ?>;border-color:<?= $bannerStyles['border'] ?>;color:<?= $bannerStyles['text'] ?>;" role="alert">
        <span class="text-lg flex-shrink-0 mt-px" aria-hidden="true"><?= $toneIcon ?></span>
        <div>
            <p class="font-semibold text-sm"><?= htmlspecialchars($activeBanner['title'] ?? 'Annonce') ?></p>
            <p class="text-sm opacity-85 mt-0.5"><?= htmlspecialchars($activeBanner['message'] ?? '') ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Network limited warning -->
    <?php if ($networkLimited): ?>
    <div class="md-banner fade-up" style="background:var(--warning-cnt);border-color:var(--warning);color:var(--warning);" role="alert">
        <span class="text-lg flex-shrink-0 mt-px" aria-hidden="true">⚠️</span>
        <div>
            <p class="font-semibold text-sm">Réseau serveur limité</p>
            <p class="text-sm opacity-85 mt-0.5">
                Le serveur ne peut pas atteindre les services externes. Les résultats ci-dessous peuvent
                refléter une restriction réseau et non un vrai problème de disponibilité.
                <a href="/status.php?refresh=1" class="underline font-medium">Rafraîchir</a>
            </p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Header -->
    <header class="panel p-5 sm:p-6 fade-up" style="animation-delay:.04s">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl flex items-center justify-center flex-shrink-0"
                     style="background:var(--success-cnt);border:1px solid rgba(109,213,140,.25);">
                    <span class="text-xl" aria-hidden="true">📡</span>
                </div>
                <div>
                    <h1 class="text-xl font-bold" style="color:var(--on-surface);">Statuts des services</h1>
                    <p class="text-xs mt-0.5" style="color:var(--on-surface-var);">
                        Mesure serveur · <?= htmlspecialchars($lastCheckLabel) ?>
                        <?php if ($useCache): ?><span style="color:var(--outline);">(cache)</span><?php endif; ?>
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs px-3 py-1.5 rounded-full font-medium"
                      style="<?= $upCount === $totalCount ? 'background:var(--success-cnt);color:var(--success);' : 'background:var(--danger-cnt);color:var(--danger);' ?>">
                    <?= $upCount === $totalCount ? '✓' : '!' ?> Sites <?= $upCount ?>/<?= $totalCount ?>
                </span>
                <?php if ($totalApps > 0): ?>
                <span class="text-xs px-3 py-1.5 rounded-full font-medium"
                      style="<?= $upApps === $totalApps ? 'background:var(--success-cnt);color:var(--success);' : 'background:var(--danger-cnt);color:var(--danger);' ?>">
                    <?= $upApps === $totalApps ? '✓' : '!' ?> Apps <?= $upApps ?>/<?= $totalApps ?>
                </span>
                <?php endif; ?>
                <a href="/status.php?refresh=1"
                   class="text-xs px-3 py-1.5 rounded-full font-medium transition"
                   style="background:var(--primary-cnt);color:var(--primary-cnt-on);"
                   onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                    ↺ Rafraîchir
                </a>
            </div>
        </div>
    </header>

    <!-- Sites -->
    <section aria-labelledby="sites-heading">
        <p id="sites-heading" class="sec-title mb-3">Sites surveillés</p>
        <div class="space-y-2" role="list">
            <?php foreach ($results as $item):
                $ok      = $item['ping']['ok'];
                $code    = (int)$item['ping']['code'];
                $ms      = (int)$item['ping']['ms'];
                $err     = $item['ping']['error'];
                $netErr  = !empty($item['ping']['net_err']);
                // Classify HTTP status
                if ($ok) {
                    $dotClass = ($code >= 400) ? 'dot-warn' : 'dot-ok';
                    $statusLabel = ($code >= 400) ? 'Accessible (HTTP '.$code.')' : 'En ligne';
                    $statusColor = ($code >= 400) ? 'var(--warning)' : 'var(--success)';
                } else {
                    $dotClass = $netErr ? 'dot-unknown' : 'dot-err';
                    $statusLabel = $netErr ? 'Inaccessible (réseau)' : 'Hors ligne';
                    $statusColor = $netErr ? 'var(--outline)' : 'var(--danger)';
                }
            ?>
            <div class="status-row px-4 py-3" role="listitem">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="<?= $dotClass ?>" aria-hidden="true"></div>
                        <div class="min-w-0">
                            <p class="font-semibold text-sm" style="color:var(--on-surface);"><?= htmlspecialchars($item['name']) ?></p>
                            <a href="<?= htmlspecialchars($item['url']) ?>" target="_blank" rel="noopener noreferrer"
                               class="text-xs truncate block transition-opacity hover:opacity-75"
                               style="color:var(--outline);">
                                <?= htmlspecialchars($item['url']) ?>
                            </a>
                        </div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-sm font-medium" style="color:<?= $statusColor ?>;">
                            <?= $statusLabel ?>
                        </p>
                        <p class="text-xs mt-0.5" style="color:var(--outline);">
                            <?= $code ? 'HTTP '.$code : '—' ?> · <?= $ms ?> ms
                        </p>
                    </div>
                </div>
                <?php if (!$ok && $err !== '' && !$netErr): ?>
                <p class="text-xs mt-2 pl-5" style="color:var(--danger);">Erreur : <?= htmlspecialchars($err) ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Apps -->
    <?php if (!empty($appResults)): ?>
    <section aria-labelledby="apps-heading">
        <p id="apps-heading" class="sec-title mb-3">Applications surveillées</p>
        <div class="space-y-2" role="list">
            <?php foreach ($appResults as $item):
                $ok      = $item['ping']['ok'];
                $code    = (int)$item['ping']['code'];
                $ms      = (int)$item['ping']['ms'];
                $err     = $item['ping']['error'];
                $netErr  = !empty($item['ping']['net_err']);
                if ($ok) {
                    $dotClass = ($code >= 400) ? 'dot-warn' : 'dot-ok';
                    $statusLabel = ($code >= 400) ? 'Accessible (HTTP '.$code.')' : 'En ligne';
                    $statusColor = ($code >= 400) ? 'var(--warning)' : 'var(--success)';
                } else {
                    $dotClass = $netErr ? 'dot-unknown' : 'dot-err';
                    $statusLabel = $netErr ? 'Inaccessible (réseau)' : 'Hors ligne';
                    $statusColor = $netErr ? 'var(--outline)' : 'var(--danger)';
                }
            ?>
            <div class="status-row px-4 py-3" role="listitem">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="<?= $dotClass ?>" aria-hidden="true"></div>
                        <div class="min-w-0">
                            <p class="font-semibold text-sm" style="color:var(--on-surface);">
                                <span aria-hidden="true"><?= ($item['emoji'] ?? '') !== '' ? htmlspecialchars((string)$item['emoji']) : appEmoji((string)$item['icon']) ?></span>
                                <?= htmlspecialchars($item['name']) ?>
                            </p>
                            <a href="<?= htmlspecialchars($item['url']) ?>" target="_blank" rel="noopener noreferrer"
                               class="text-xs truncate block transition-opacity hover:opacity-75"
                               style="color:var(--outline);">
                                <?= htmlspecialchars($item['url']) ?>
                            </a>
                        </div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-sm font-medium" style="color:<?= $statusColor ?>;">
                            <?= $statusLabel ?>
                        </p>
                        <p class="text-xs mt-0.5" style="color:var(--outline);">
                            <?= $code ? 'HTTP '.$code : '—' ?> · <?= $ms ?> ms
                        </p>
                    </div>
                </div>
                <?php if (!$ok && $err !== '' && !$netErr): ?>
                <p class="text-xs mt-2 pl-5" style="color:var(--danger);">Erreur : <?= htmlspecialchars($err) ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Unavailable Apps -->
    <?php if (!empty($unavailableAppResults)): ?>
    <section aria-labelledby="unavail-heading">
        <p id="unavail-heading" class="sec-title mb-3">Applications indisponibles</p>
        <div class="space-y-2" role="list">
            <?php foreach ($unavailableAppResults as $item):
                $appStatus = $item['status'] ?? 'disabled';
            ?>
            <div class="status-row px-4 py-3 opacity-70" role="listitem">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="dot-warn" aria-hidden="true"></div>
                        <div class="min-w-0">
                            <p class="font-semibold text-sm" style="color:var(--on-surface);">
                                <span aria-hidden="true"><?= ($item['emoji'] ?? '') !== '' ? htmlspecialchars((string)$item['emoji']) : appEmoji((string)$item['icon']) ?></span>
                                <?= htmlspecialchars($item['name']) ?>
                            </p>
                            <a href="<?= htmlspecialchars($item['url']) ?>" target="_blank" rel="noopener noreferrer"
                               class="text-xs truncate block transition-opacity hover:opacity-75"
                               style="color:var(--outline);">
                                <?= htmlspecialchars($item['url']) ?>
                            </a>
                        </div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-sm font-medium" style="color:var(--warning);">
                            <?= $appStatus === 'maintenance' ? '🔧 Maintenance' : '⛔ Désactivé' ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

</main>
</body>
</html>
