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
    <title>Statuts — Groupe Speed Cloud</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="/assets/images/cloudy.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php include __DIR__ . '/_ui-tokens.php'; ?>
    <style>
        body { font-family:'Inter',sans-serif; background:var(--bg); }
        .bg-ambient {
            position:fixed; inset:0; pointer-events:none; z-index:0;
            background:
                radial-gradient(ellipse 65% 50% at 10%  5%,  rgba(124,58,237,.25) 0%, transparent 58%),
                radial-gradient(ellipse 50% 40% at 92% 95%,  rgba(8,145,178,.18)  0%, transparent 56%);
        }
        .panel { background:var(--surface); border:1px solid var(--border); border-radius:18px; }
        .sec-title { font-size:.65rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.38); }
        .status-row {
            background:rgba(255,255,255,.03);
            border:1px solid var(--border);
            border-radius:12px;
            transition:border-color .13s;
        }
        .status-row:hover { border-color:rgba(167,139,250,.25); }
        .status-dot-ok  { width:8px;height:8px;border-radius:50%;background:#34d399;box-shadow:0 0 6px rgba(52,211,153,.6); }
        .status-dot-err { width:8px;height:8px;border-radius:50%;background:#f87171;box-shadow:0 0 6px rgba(248,113,113,.6); }
    </style>
</head>
<body class="min-h-screen text-white relative">
<div class="bg-ambient"></div>

<?php include __DIR__ . '/_nav.php'; ?>

<main class="page-stack relative z-10 w-full max-w-4xl mx-auto px-4 sm:px-6 py-8">

    <?php if ($activeBanner):
        $tone = $activeBanner['style'] ?? 'danger';
        $bannerStyles = [
            'danger'  => ['bg'=>'rgba(220,38,38,.14)',  'border'=>'rgba(220,38,38,.35)',  'text'=>'#fca5a5'],
            'warning' => ['bg'=>'rgba(217,119,6,.14)',  'border'=>'rgba(217,119,6,.35)',  'text'=>'#fcd34d'],
            'success' => ['bg'=>'rgba(5,150,105,.14)',  'border'=>'rgba(5,150,105,.35)',  'text'=>'#6ee7b7'],
            'info'    => ['bg'=>'rgba(8,145,178,.14)',  'border'=>'rgba(8,145,178,.35)',  'text'=>'#7dd3fc'],
        ][$tone] ?? ['bg'=>'rgba(220,38,38,.14)', 'border'=>'rgba(220,38,38,.35)', 'text'=>'#fca5a5'];
        $toneIcon = ['danger'=>'🚨','warning'=>'⚠️','success'=>'✅','info'=>'ℹ️'][$tone] ?? '🚨';
    ?>
    <div class="rounded-2xl border px-4 py-3"
         style="background:<?= $bannerStyles['bg'] ?>;border-color:<?= $bannerStyles['border'] ?>;color:<?= $bannerStyles['text'] ?>;">
        <p class="font-semibold text-sm"><?= $toneIcon ?> <?= htmlspecialchars($activeBanner['title'] ?? 'Annonce') ?></p>
        <p class="text-sm opacity-85 mt-0.5"><?= htmlspecialchars($activeBanner['message'] ?? '') ?></p>
    </div>
    <?php endif; ?>

    <!-- Header -->
    <header class="panel p-5 sm:p-6">
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                     style="background:rgba(5,150,105,.18);border:1px solid rgba(5,150,105,.35);">
                    <span class="text-xl">📡</span>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-white">Statuts des services</h1>
                    <p class="text-white/40 text-xs mt-0.5">Mesure serveur en temps réel.</p>
                </div>
            </div>
            <div class="flex flex-col items-end gap-1.5 flex-shrink-0">
                <span class="text-xs px-2.5 py-1 rounded-full font-semibold"
                      style="<?= $upCount === $totalCount ? 'background:rgba(5,150,105,.18);color:#34d399;border:1px solid rgba(5,150,105,.35);' : 'background:rgba(220,38,38,.14);color:#f87171;border:1px solid rgba(220,38,38,.3);' ?>">
                    <?= $upCount === $totalCount ? '✓' : '!' ?> Sites <?= $upCount ?>/<?= $totalCount ?>
                </span>
                <span class="text-xs px-2.5 py-1 rounded-full font-semibold"
                      style="<?= $upApps === $totalApps ? 'background:rgba(5,150,105,.18);color:#34d399;border:1px solid rgba(5,150,105,.35);' : 'background:rgba(220,38,38,.14);color:#f87171;border:1px solid rgba(220,38,38,.3);' ?>">
                    <?= $upApps === $totalApps ? '✓' : '!' ?> Apps <?= $upApps ?>/<?= $totalApps ?>
                </span>
            </div>
        </div>
    </header>

    <!-- Sites -->
    <section>
        <p class="sec-title mb-3">Sites surveillés</p>
        <div class="space-y-2">
            <?php foreach ($results as $item):
                $ok   = $item['ping']['ok'];
                $code = (int)$item['ping']['code'];
                $ms   = (int)$item['ping']['ms'];
                $err  = $item['ping']['error'];
            ?>
            <div class="status-row px-4 py-3">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="<?= $ok ? 'status-dot-ok' : 'status-dot-err' ?> flex-shrink-0"></div>
                        <div class="min-w-0">
                            <p class="text-white font-semibold text-sm"><?= htmlspecialchars($item['name']) ?></p>
                            <a href="<?= htmlspecialchars($item['url']) ?>" target="_blank" rel="noopener noreferrer"
                               class="text-xs text-white/38 hover:text-white/65 truncate block">
                                <?= htmlspecialchars($item['url']) ?>
                            </a>
                        </div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-sm font-semibold <?= $ok ? 'text-emerald-300' : 'text-red-300' ?>">
                            <?= $ok ? 'En ligne' : 'Hors ligne' ?>
                        </p>
                        <p class="text-xs text-white/35 mt-0.5">
                            <?= $code ? 'HTTP '.$code : '—' ?> · <?= $ms ?> ms
                        </p>
                    </div>
                </div>
                <?php if (!$ok && $err !== ''): ?>
                <p class="text-xs mt-2 pl-5" style="color:#fca5a5;">Erreur : <?= htmlspecialchars($err) ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Apps -->
    <?php if (!empty($appResults)): ?>
    <section class="panel p-5">
        <h2 class="font-bold mb-4">Applications</h2>
        <div class="space-y-2">
            <?php foreach ($appResults as $item):
                $ok   = $item['ping']['ok'];
                $code = (int)$item['ping']['code'];
                $ms   = (int)$item['ping']['ms'];
                $err  = $item['ping']['error'];
            ?>
            <div class="status-row px-4 py-3">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="<?= $ok ? 'status-dot-ok' : 'status-dot-err' ?> flex-shrink-0"></div>
                        <div class="min-w-0">
                            <p class="text-white font-semibold text-sm">
                                <?= ($item['emoji'] ?? '') !== '' ? htmlspecialchars((string)$item['emoji']) : appEmoji((string)$item['icon']) ?>
                                <?= htmlspecialchars($item['name']) ?>
                            </p>
                            <a href="<?= htmlspecialchars($item['url']) ?>" target="_blank" rel="noopener noreferrer"
                               class="text-xs text-white/38 hover:text-white/65 truncate block">
                                <?= htmlspecialchars($item['url']) ?>
                            </a>
                        </div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-sm font-semibold <?= $ok ? 'text-emerald-300' : 'text-red-300' ?>">
                            <?= $ok ? 'En ligne' : 'Hors ligne' ?>
                        </p>
                        <p class="text-xs text-white/35 mt-0.5">
                            <?= $code ? 'HTTP '.$code : '—' ?> · <?= $ms ?> ms
                        </p>
                    </div>
                </div>
                <?php if (!$ok && $err !== ''): ?>
                <p class="text-xs mt-2 pl-5" style="color:#fca5a5;">Erreur : <?= htmlspecialchars($err) ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

</main>
</body>
</html>
