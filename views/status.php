<?php
$user    = $_SESSION['user'];
$config  = require __DIR__ . '/../config.php';
$isAdmin = in_array($user['email'], $config['admins'] ?? []);

$currentPage = 'status';
$sites = $config['status_sites'] ?? [
    ['name' => 'Portail', 'url' => 'https://portail.groupe-speed.cloud'],
    ['name' => 'SSO', 'url' => 'https://sign.groupe-speed.cloud'],
    ['name' => 'Site Groupe', 'url' => 'https://groupe-speed.cloud'],
];

function pingSite(string $url): array {
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return ['ok' => false, 'code' => 0, 'ms' => 0, 'error' => 'URL invalide'];
    }

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_NOBODY => true,
            CURLOPT_USERAGENT => 'GroupeSpeedCloudStatus/1.0',
        ]);

        $start = microtime(true);
        $result = curl_exec($ch);
        $ms = (int)round((microtime(true) - $start) * 1000);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        return [
            'ok' => $result !== false && $code >= 200 && $code < 400,
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
    return ['ok' => $code >= 200 && $code < 400, 'code' => $code, 'ms' => $ms, 'error' => ''];
}

$results = [];
foreach ($sites as $site) {
    $name = trim((string)($site['name'] ?? 'Site'));
    $url = trim((string)($site['url'] ?? ''));
    $ping = pingSite($url);
    $results[] = ['name' => $name, 'url' => $url, 'ping' => $ping];
}

$upCount = count(array_filter($results, fn($r) => $r['ping']['ok']));
$totalCount = count($results);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statuts - Groupe Speed Cloud</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="/assets/images/cloudy.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { font-family:'Inter',sans-serif; background:#06080f; }
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
    <header class="glass rounded-3xl p-6">
        <h1 class="text-2xl font-bold mb-1">📡 Statuts des sites</h1>
        <p class="text-white/45 text-sm">Verification en direct des services web du Groupe Speed Cloud.</p>
        <div class="mt-3 inline-flex items-center gap-2 text-xs px-3 py-1.5 rounded-full border border-white/15 bg-white/5">
            <span><?= $upCount === $totalCount ? '✅' : '⚠️' ?></span>
            <span><?= $upCount ?> / <?= $totalCount ?> services operationnels</span>
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
</main>
</body>
</html>
