<?php
$user    = $_SESSION['user'];
$config  = require __DIR__ . '/../config.php';
$isAdmin = in_array($user['email'], $config['admins'] ?? [], true);
if (!$isAdmin) { http_response_code(403); include __DIR__ . '/unauthorized.php'; exit; }

$currentPage = 'admin';
$sitesFile = __DIR__ . '/../uploads/status-sites.json';
$appsFile = __DIR__ . '/../uploads/apps.json';

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

function saveJsonArray(string $path, array $data): bool {
    $dir = dirname($path);
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    return file_put_contents($path, json_encode(array_values($data), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) !== false;
}

function normalizeName(string $name): string {
    return mb_substr(trim($name), 0, 80);
}

function normalizeUrl(string $url): string {
    $url = trim($url);
    if ($url !== '' && !preg_match('~^https?://~i', $url)) {
        $url = 'https://' . $url;
    }
    return $url;
}

function normalizeIcon(string $icon): string {
    $icon = trim($icon);
    $allowed = ['gmail','drive','calendar','meet','docs','sheets','slides','youtube','discord','github','notion','figma','link'];
    return in_array($icon, $allowed, true) ? $icon : 'link';
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


$sites = readJsonArray($sitesFile, $defaultSites);
$apps = readJsonArray($appsFile, $defaultApps);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], (string)$postedToken)) {
        $_SESSION['admin_status_flash'] = ['type' => 'error', 'message' => 'Token CSRF invalide.'];
        header('Location: /admin-status.php');
        exit;
    }

    $action = trim((string)($_POST['action'] ?? ''));
    $ok = false;

    if ($action === 'add_site') {
        $name = normalizeName((string)($_POST['name'] ?? ''));
        $url = normalizeUrl((string)($_POST['url'] ?? ''));
        if ($name !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
            $sites[] = ['name' => $name, 'url' => $url];
            $ok = saveJsonArray($sitesFile, $sites);
        }
    }

    if ($action === 'update_site') {
        $idx = (int)($_POST['index'] ?? -1);
        $name = normalizeName((string)($_POST['name'] ?? ''));
        $url = normalizeUrl((string)($_POST['url'] ?? ''));
        if (isset($sites[$idx]) && $name !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
            $sites[$idx] = ['name' => $name, 'url' => $url];
            $ok = saveJsonArray($sitesFile, $sites);
        }
    }

    if ($action === 'delete_site') {
        $idx = (int)($_POST['index'] ?? -1);
        if (isset($sites[$idx])) {
            unset($sites[$idx]);
            $ok = saveJsonArray($sitesFile, $sites);
        }
    }

    if ($action === 'add_app') {
        $name = normalizeName((string)($_POST['name'] ?? ''));
        $url = normalizeUrl((string)($_POST['url'] ?? ''));
        $icon = normalizeIcon((string)($_POST['icon'] ?? 'link'));

        if ($name !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
            $apps[] = ['name' => $name, 'url' => $url, 'icon' => $icon];
            $ok = saveJsonArray($appsFile, $apps);
        }
    }

    if ($action === 'update_app') {
        $idx = (int)($_POST['index'] ?? -1);
        $name = normalizeName((string)($_POST['name'] ?? ''));
        $url = normalizeUrl((string)($_POST['url'] ?? ''));
        $icon = normalizeIcon((string)($_POST['icon'] ?? 'link'));
        if (isset($apps[$idx]) && $name !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
            $apps[$idx] = ['name' => $name, 'url' => $url, 'icon' => $icon];
            $ok = saveJsonArray($appsFile, $apps);
        }
    }

    if ($action === 'delete_app') {
        $idx = (int)($_POST['index'] ?? -1);
        if (isset($apps[$idx])) {
            unset($apps[$idx]);
            $ok = saveJsonArray($appsFile, $apps);
        }
    }

    $_SESSION['admin_status_flash'] = [
        'type' => $ok ? 'success' : 'error',
        'message' => $ok ? 'Mise à jour enregistrée.' : 'Action invalide ou enregistrement impossible.',
    ];
    header('Location: /admin-status.php');
    exit;
}

$flash = $_SESSION['admin_status_flash'] ?? null;
unset($_SESSION['admin_status_flash']);

$totalSites = count($sites);
$totalApps = count($apps);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Statuts - Groupe Speed Cloud</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="/assets/images/cloudy.png">
    <link href="https://fonts.googleapis.com/css2?family=Titillium+Web:wght@400;600;700&display=swap" rel="stylesheet">
    <?php include __DIR__ . '/_ui-tokens.php'; ?>
    <style>
        body { font-family:'Titillium Web',sans-serif; background:#06080f; color-scheme:dark; }
        .bg-ambient { position:fixed; inset:0; pointer-events:none; z-index:0;
            background: radial-gradient(ellipse 70% 55% at 15% 0%, rgba(52,84,209,.28) 0%, transparent 65%),
                        radial-gradient(ellipse 50% 40% at 88% 100%, rgba(14,165,233,.18) 0%, transparent 60%); }
        .glass { background:rgba(255,255,255,.055); backdrop-filter:blur(16px) saturate(160%); border:1px solid rgba(255,255,255,.10); }
        .admin-tab { border:1px solid rgba(255,255,255,.12); background:rgba(255,255,255,.05); }
        .admin-tab.active { background:rgba(245,158,11,.18); border-color:rgba(245,158,11,.35); color:#fcd34d; }
        .panel { background:rgba(255,255,255,.055); border:1px solid rgba(255,255,255,.10); border-radius:1rem; }
        .crumb { color:rgba(229,231,235,.55); font-size:.75rem; }
        .status-card { transition:transform .15s,border-color .15s; }
        .status-card:hover { transform:translateY(-2px); border-color:rgba(255,255,255,.2); }
    </style>
</head>
<body class="min-h-screen text-white relative">
<div class="bg-ambient"></div>
<?php include __DIR__ . '/_nav.php'; ?>

<main class="page-stack relative z-10 w-full max-w-6xl mx-auto px-4 sm:px-6 py-8">
    <section class="glass rounded-3xl p-4 sm:p-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold">📡 Administration des sites</h1>
            <p class="text-white/45 text-sm">Gestion des sites monitorés (enregistrement uniquement, sans ping).</p>
            <p class="crumb mt-1">Admin / Sites</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="/admin.php" class="admin-tab px-3 py-1.5 rounded-lg text-xs font-semibold">🏠 Accueil Admin</a>
            <a href="/admin-news.php" class="admin-tab px-3 py-1.5 rounded-lg text-xs font-semibold">📰 Actualités</a>
            <a href="/admin-banners.php" class="admin-tab px-3 py-1.5 rounded-lg text-xs font-semibold">📣 Bannières</a>
            <a href="/admin-status.php" class="admin-tab active px-3 py-1.5 rounded-lg text-xs font-semibold">📡 Sites</a>
            <a href="/admin-apps.php" class="admin-tab px-3 py-1.5 rounded-lg text-xs font-semibold">🧩 Applications</a>
            <a href="/admin-users.php" class="admin-tab px-3 py-1.5 rounded-lg text-xs font-semibold">👥 Utilisateurs</a>
        </div>
    </section>

    <section class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="glass rounded-2xl p-4"><p class="text-blue-300 text-xl font-bold"><?= $totalSites ?></p><p class="text-white/45 text-xs">Total sites</p></div>
        <div class="glass rounded-2xl p-4"><p class="text-white text-xl font-bold">—</p><p class="text-white/45 text-xs">Sites en ligne</p></div>
        <div class="glass rounded-2xl p-4"><p class="text-cyan-300 text-xl font-bold"><?= $totalApps ?></p><p class="text-white/45 text-xs">Total applications</p></div>
        <div class="glass rounded-2xl p-4"><p class="text-white text-xl font-bold">—</p><p class="text-white/45 text-xs">Apps en ligne</p></div>
    </section>

    <?php if ($flash && is_array($flash)): ?>
    <section class="rounded-2xl border px-4 py-2.5 text-sm <?= ($flash['type'] ?? '') === 'success' ? 'bg-emerald-500/20 border-emerald-500/35 text-emerald-100' : 'bg-red-500/20 border-red-500/35 text-red-100' ?>">
        <?= htmlspecialchars((string)($flash['message'] ?? '')) ?>
    </section>
    <?php endif; ?>

    <div class="grid lg:grid-cols-2 gap-4">
        <section class="panel p-4 space-y-3">
            <h2 class="font-semibold">🌐 Sites monitorés</h2>
            <form method="post" class="grid gap-2 rounded-2xl border border-white/10 bg-white/[0.03] p-3">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" value="add_site">
                <input type="text" name="name" maxlength="80" required placeholder="Nom du site" class="px-3 py-2 rounded-xl bg-white/10 border border-white/15 text-sm">
                <input type="text" name="url" maxlength="220" required placeholder="https://..." class="px-3 py-2 rounded-xl bg-white/10 border border-white/15 text-sm">
                <button class="px-3 py-2 rounded-xl text-sm font-semibold bg-blue-600 hover:bg-blue-700">Ajouter le site</button>
            </form>

            <?php foreach ($sites as $idx => $site):
                $name = trim((string)($site['name'] ?? 'Site'));
                $url = trim((string)($site['url'] ?? ''));
            ?>
            <div class="status-card rounded-lg bg-white/[0.03] border border-white/10 p-2">
                <form method="post" class="grid gap-2">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="update_site">
                    <input type="hidden" name="index" value="<?= (int)$idx ?>">
                    <input type="text" name="name" maxlength="80" required value="<?= htmlspecialchars($name) ?>" class="px-3 py-2 rounded-xl bg-white/10 border border-white/15 text-sm">
                    <input type="text" name="url" maxlength="220" required value="<?= htmlspecialchars($url) ?>" class="px-3 py-2 rounded-xl bg-white/10 border border-white/15 text-sm">
                    <div class="flex items-center justify-between text-xs text-white/60">
                        <span>Entrée de configuration</span>
                        <button class="px-2.5 py-1.5 rounded-lg text-xs bg-blue-600 hover:bg-blue-700">Modifier</button>
                    </div>
                </form>
                <form method="post" class="mt-1 flex justify-end">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="delete_site">
                    <input type="hidden" name="index" value="<?= (int)$idx ?>">
                    <button class="px-2 py-1 rounded-lg text-xs bg-red-500/20 text-red-200 hover:bg-red-500/30">Suppr.</button>
                </form>
            </div>
            <?php endforeach; ?>
        </section>

        <section class="panel p-4 space-y-3">
            <h2 class="font-semibold">🧩 Applications monitorées</h2>
            <form method="post" class="grid gap-2 rounded-2xl border border-white/10 bg-white/[0.03] p-3">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" value="add_app">
                <input type="text" name="name" maxlength="80" required placeholder="Nom de l'app" class="px-3 py-2 rounded-xl bg-white/10 border border-white/15 text-sm">
                <input type="text" name="url" maxlength="220" required placeholder="https://..." class="px-3 py-2 rounded-xl bg-white/10 border border-white/15 text-sm">
                <select name="icon" class="px-3 py-2 rounded-xl bg-white/10 border border-white/15 text-sm">
                    <option value="link">🔗 Lien</option>
                    <option value="gmail">📧 Gmail</option>
                    <option value="drive">💾 Drive</option>
                    <option value="calendar">📅 Agenda</option>
                    <option value="meet">🎥 Meet</option>
                    <option value="docs">📄 Docs</option>
                    <option value="sheets">📊 Sheets</option>
                    <option value="slides">🖼️ Slides</option>
                    <option value="youtube">▶️ YouTube</option>
                    <option value="discord">💬 Discord</option>
                    <option value="github">🐙 GitHub</option>
                    <option value="notion">🗂️ Notion</option>
                    <option value="figma">🎨 Figma</option>
                </select>
                <button class="px-3 py-2 rounded-xl text-sm font-semibold bg-blue-600 hover:bg-blue-700">Ajouter l'application</button>
            </form>

            <?php foreach ($apps as $idx => $app):
                $name = trim((string)($app['name'] ?? 'Application'));
                $url = trim((string)($app['url'] ?? ''));
                $icon = normalizeIcon((string)($app['icon'] ?? 'link'));
            ?>
            <div class="status-card rounded-lg bg-white/[0.03] border border-white/10 p-2">
                <form method="post" class="grid gap-2">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="update_app">
                    <input type="hidden" name="index" value="<?= (int)$idx ?>">
                    <input type="text" name="name" maxlength="80" required value="<?= htmlspecialchars($name) ?>" class="px-3 py-2 rounded-xl bg-white/10 border border-white/15 text-sm">
                    <input type="text" name="url" maxlength="220" required value="<?= htmlspecialchars($url) ?>" class="px-3 py-2 rounded-xl bg-white/10 border border-white/15 text-sm">
                    <div class="grid grid-cols-2 gap-2">
                        <select name="icon" class="px-3 py-2 rounded-xl bg-white/10 border border-white/15 text-sm">
                            <option value="link" <?= $icon === 'link' ? 'selected' : '' ?>>🔗 Lien</option>
                            <option value="gmail" <?= $icon === 'gmail' ? 'selected' : '' ?>>📧 Gmail</option>
                            <option value="drive" <?= $icon === 'drive' ? 'selected' : '' ?>>💾 Drive</option>
                            <option value="calendar" <?= $icon === 'calendar' ? 'selected' : '' ?>>📅 Agenda</option>
                            <option value="meet" <?= $icon === 'meet' ? 'selected' : '' ?>>🎥 Meet</option>
                            <option value="docs" <?= $icon === 'docs' ? 'selected' : '' ?>>📄 Docs</option>
                            <option value="sheets" <?= $icon === 'sheets' ? 'selected' : '' ?>>📊 Sheets</option>
                            <option value="slides" <?= $icon === 'slides' ? 'selected' : '' ?>>🖼️ Slides</option>
                            <option value="youtube" <?= $icon === 'youtube' ? 'selected' : '' ?>>▶️ YouTube</option>
                            <option value="discord" <?= $icon === 'discord' ? 'selected' : '' ?>>💬 Discord</option>
                            <option value="github" <?= $icon === 'github' ? 'selected' : '' ?>>🐙 GitHub</option>
                            <option value="notion" <?= $icon === 'notion' ? 'selected' : '' ?>>🗂️ Notion</option>
                            <option value="figma" <?= $icon === 'figma' ? 'selected' : '' ?>>🎨 Figma</option>
                        </select>
                        <div class="text-xs text-white/65 flex items-center justify-center rounded-xl bg-white/5 border border-white/10">
                            Icône: <?= appEmoji($icon) ?>
                        </div>
                    </div>
                    <div class="flex items-center justify-between text-xs text-white/60">
                        <span>Entrée de configuration</span>
                        <button class="px-2.5 py-1.5 rounded-lg text-xs bg-blue-600 hover:bg-blue-700">Modifier</button>
                    </div>
                </form>
                <form method="post" class="mt-1 flex justify-end">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="delete_app">
                    <input type="hidden" name="index" value="<?= (int)$idx ?>">
                    <button class="px-2 py-1 rounded-lg text-xs bg-red-500/20 text-red-200 hover:bg-red-500/30">Suppr.</button>
                </form>
            </div>
            <?php endforeach; ?>
        </section>
    </div>
</main>
</body>
</html>
