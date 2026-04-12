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

function singlePing(string $url): array {
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return ['ok' => false, 'code' => 0, 'ms' => 0, 'error' => 'URL invalide'];
    }

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_NOBODY => false,
            CURLOPT_HTTPGET => true,
            CURLOPT_RANGE => '0-0',
            CURLOPT_USERAGENT => 'GroupeSpeedCloudStatus/1.0',
        ]);

        $result = curl_exec($ch);
        $ms = (int)round(((float)curl_getinfo($ch, CURLINFO_TOTAL_TIME)) * 1000);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        return [
            'ok' => $result !== false && $code >= 200 && $code < 500,
            'code' => $code,
            'ms' => $ms,
            'error' => $result === false ? ($err ?: 'Echec ping') : '',
        ];
    }

    $start = microtime(true);
    $headers = @get_headers($url);
    $ms = (int)round((microtime(true) - $start) * 1000);
    if (!is_array($headers) || !isset($headers[0])) {
        return ['ok' => false, 'code' => 0, 'ms' => $ms, 'error' => 'Pas de reponse'];
    }

    preg_match('/\s(\d{3})\s/', $headers[0], $m);
    $code = isset($m[1]) ? (int)$m[1] : 0;
    return ['ok' => $code >= 200 && $code < 500, 'code' => $code, 'ms' => $ms, 'error' => ''];
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
            $results[$idx] = ['ok' => false, 'code' => 0, 'ms' => 0, 'error' => 'URL invalide'];
            continue;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_NOBODY => false,
            CURLOPT_HTTPGET => true,
            CURLOPT_RANGE => '0-0',
            CURLOPT_USERAGENT => 'GroupeSpeedCloudStatus/1.0',
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
        $raw = curl_multi_getcontent($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $ms = (int)round(((float)curl_getinfo($ch, CURLINFO_TOTAL_TIME)) * 1000);
        $err = curl_error($ch);

        $results[$idx] = [
            'ok' => $raw !== false && $code >= 200 && $code < 500,
            'code' => $code,
            'ms' => $ms,
            'error' => $raw === false ? ($err ?: 'Echec ping') : '',
        ];

        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
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

$apps = array_values(array_filter($apps, function ($app) use ($workspaceIcons) {
    $icon = strtolower(trim((string)($app['icon'] ?? 'link')));
    return !in_array($icon, $workspaceIcons, true);
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
$useCache = isset($cache['signature'], $cache['created_at'], $cache['data'])
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
foreach ($apps as $i => $app) {
    $name = trim((string)($app['name'] ?? 'Application'));
    $url = trim((string)($app['url'] ?? ''));
    $icon = trim((string)($app['icon'] ?? 'link'));
    $emoji = trim((string)($app['emoji'] ?? ''));
    $ping = $pingData['app:' . $i] ?? singlePing($url);
    $appResults[] = ['name' => $name, 'url' => $url, 'icon' => $icon, 'emoji' => $emoji, 'ping' => $ping];
}

$upCount = count(array_filter($results, fn($r) => $r['ping']['ok']));
$totalCount = count($results);
$upApps = count(array_filter($appResults, fn($r) => $r['ping']['ok']));
$totalApps = count($appResults);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statuts - Groupe Speed Cloud</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="/assets/images/cloudy.png">
    <link href="https://fonts.googleapis.com/css2?family=Titillium+Web:wght;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { font-family:'Titillium Web',sans-serif; background:#06080f; }
        .bg-ambient { position:fixed; inset:0; pointer-events:none; z-index:0;
            background: radial-gradient(ellipse 70% 55% at 15% 0%, rgba(52,84,209,.28) 0%, transparent 65%),
                        radial-gradient(ellipse 50% 40% at 88% 100%, rgba(14,165,233,.18) 0%, transparent 60%); }
        .glass { background:rgba(255,255,255,.055); backdrop-filter:blur(16px) saturate(160%);
                 -webkit-backdrop-filter:blur(16px) saturate(160%); border:1px solid rgba(255,255,255,.10); }
        .status-card { transition:transform .15s,border-color .15s; }
        .status-card:hover { transform:translateY(-2px); border-color:rgba(255,255,255,.2); }
    </style>
</head>
<body class="min-h-screen text-white relative">
<div class="bg-ambient"></div>

<?php include __DIR__ . '/_nav.php'; ?>

<main class="relative z-10 w-full max-w-4xl mx-auto px-4 sm:px-6 py-8 space-y-6">
    <?php if ($activeBanner):
        $tone = $activeBanner['style'] ?? 'danger';
        $toneCls = [
            'danger' => 'bg-red-500/20 border-red-500/35 text-red-100',
            'warning' => 'bg-amber-500/20 border-amber-500/35 text-amber-100',
            'success' => 'bg-emerald-500/20 border-emerald-500/35 text-emerald-100',
            'info' => 'bg-cyan-500/20 border-cyan-500/35 text-cyan-100',
        ][$tone] ?? 'bg-red-500/20 border-red-500/35 text-red-100';
        $toneIcon = ['danger'=>'🚨','warning'=>'⚠️','success'=>'✅','info'=>'ℹ️'][$tone] ?? '🚨';
    ?>
    <section class="rounded-2xl border px-4 py-3 <?= $toneCls ?>">
        <p class="font-semibold text-sm"><?= $toneIcon ?> <?= htmlspecialchars($activeBanner['title'] ?? 'Annonce importante') ?></p>
        <p class="text-sm opacity-90 mt-0.5"><?= htmlspecialchars($activeBanner['message'] ?? '') ?></p>
    </section>
    <?php endif; ?>

    <header class="glass rounded-3xl p-6">
        <h1 class="text-2xl font-bold mb-1">📡 Statuts des sites</h1>
        <p class="text-white/45 text-sm">Ping HTTP effectué depuis le serveur pour des mesures réelles côté hébergement.</p>
        <div class="mt-3 inline-flex items-center gap-2 text-xs px-3 py-1.5 rounded-full border border-white/15 bg-white/5">
            <span><?= $upCount === $totalCount ? '✅' : '⚠️' ?></span>
            <span><?= $upCount ?> / <?= $totalCount ?> services operationnels</span>
        </div>
        <div class="mt-2 inline-flex items-center gap-2 text-xs px-3 py-1.5 rounded-full border border-white/15 bg-white/5">
            <span><?= $upApps === $totalApps ? '✅' : '⚠️' ?></span>
            <span><?= $upApps ?> / <?= $totalApps ?> applications operationnelles</span>
        </div>
    </header>

    <section class="space-y-3">
        <?php foreach ($results as $item):
            $ok = $item['ping']['ok'];
            $code = (int)$item['ping']['code'];
            $ms = (int)$item['ping']['ms'];
            $err = $item['ping']['error'];
        ?>
        <article class="status-card glass rounded-2xl p-4">
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-white font-semibold"><?= htmlspecialchars($item['name']) ?></p>
                    <a href="<?= htmlspecialchars($item['url']) ?>" class="text-xs text-white/50 hover:text-white/80" rel="noopener noreferrer">
                        <?= htmlspecialchars($item['url']) ?>
                    </a>
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="text-sm font-semibold <?= $ok ? 'text-emerald-300' : 'text-red-300' ?>">
                        <?= $ok ? '🟢 En ligne' : '🔴 Hors ligne' ?>
                    </p>
                    <p class="text-xs text-white/45 mt-0.5">
                        <?= $code ? ('HTTP ' . $code) : 'Aucun code' ?> · <?= $ms ?> ms
                    </p>
                </div>
            </div>
            <?php if (!$ok && $err !== ''): ?>
            <p class="text-xs text-red-300/90 mt-2">Erreur: <?= htmlspecialchars($err) ?></p>
            <?php endif; ?>
        </article>
        <?php endforeach; ?>
    </section>

    <section class="glass rounded-3xl p-5 space-y-3">
        <h2 class="text-lg font-bold">🧩 Statut des applications</h2>
        <?php foreach ($appResults as $item):
            $ok = $item['ping']['ok'];
            $code = (int)$item['ping']['code'];
            $ms = (int)$item['ping']['ms'];
            $err = $item['ping']['error'];
        ?>
        <article class="status-card rounded-2xl p-4 border border-white/10 bg-white/[0.03]">
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-white font-semibold"><?= ($item['emoji'] ?? '') !== '' ? htmlspecialchars((string)$item['emoji']) : appEmoji((string)$item['icon']) ?> <?= htmlspecialchars($item['name']) ?></p>
                    <a href="<?= htmlspecialchars($item['url']) ?>" class="text-xs text-white/50 hover:text-white/80" rel="noopener noreferrer">
                        <?= htmlspecialchars($item['url']) ?>
                    </a>
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="text-sm font-semibold <?= $ok ? 'text-emerald-300' : 'text-red-300' ?>">
                        <?= $ok ? '🟢 En ligne' : '🔴 Hors ligne' ?>
                    </p>
                    <p class="text-xs text-white/45 mt-0.5">
                        <?= $code ? ('HTTP ' . $code) : 'Aucun code' ?> · <?= $ms ?> ms
                    </p>
                </div>
            </div>
            <?php if (!$ok && $err !== ''): ?>
            <p class="text-xs text-red-300/90 mt-2">Erreur: <?= htmlspecialchars($err) ?></p>
            <?php endif; ?>
        </article>
        <?php endforeach; ?>
    </section>

</main>
</body>
</html>
