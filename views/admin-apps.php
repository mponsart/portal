<?php
$user    = $_SESSION['user'];
$config  = require __DIR__ . '/../config.php';
$isAdmin = in_array($user['email'], $config['admins'] ?? [], true);
if (!$isAdmin) { http_response_code(403); include __DIR__ . '/unauthorized.php'; exit; }

$currentPage = 'admin';
$appsFile = __DIR__ . '/../uploads/apps.json';

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
    $allowed = ['youtube','discord','github','notion','figma','link'];
    return in_array($icon, $allowed, true) ? $icon : 'link';
}

function normalizeEmoji(string $emoji): string {
    return mb_substr(trim($emoji), 0, 8);
}

function normalizeAdminOnly(mixed $value): bool {
    return $value === '1' || $value === 1 || $value === true || $value === 'on';
}

function isWorkspaceIcon(string $icon): bool {
    return in_array($icon, ['gmail','drive','calendar','meet','docs','sheets','slides'], true);
}

function moveWithinFiltered(array &$apps, int $idx, string $direction, callable $predicate): bool {
    if (!isset($apps[$idx])) return false;
    $positions = [];
    foreach ($apps as $i => $app) {
        if ($predicate($app)) $positions[] = $i;
    }

    $currentPos = array_search($idx, $positions, true);
    if ($currentPos === false) return false;

    $targetPos = $direction === 'up' ? $currentPos - 1 : $currentPos + 1;
    if (!isset($positions[$targetPos])) return false;

    $swapIdx = $positions[$targetPos];
    [$apps[$idx], $apps[$swapIdx]] = [$apps[$swapIdx], $apps[$idx]];
    return true;
}

function reorderFilteredByIndexes(array &$apps, array $orderedIndexes, callable $predicate): bool {
    $currentIndexes = [];
    foreach ($apps as $i => $app) {
        if ($predicate($app)) $currentIndexes[] = $i;
    }

    $orderedIndexes = array_values(array_unique(array_map('intval', $orderedIndexes)));

    $sortedCurrent = $currentIndexes;
    $sortedPosted = $orderedIndexes;
    sort($sortedCurrent);
    sort($sortedPosted);
    if ($sortedCurrent !== $sortedPosted) {
        return false;
    }

    $reorderedItems = [];
    foreach ($orderedIndexes as $idx) {
        if (!isset($apps[$idx])) return false;
        $reorderedItems[] = $apps[$idx];
    }

    $cursor = 0;
    foreach ($apps as $i => $app) {
        if ($predicate($app)) {
            $apps[$i] = $reorderedItems[$cursor] ?? $apps[$i];
            $cursor++;
        }
    }

    return true;
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

$apps = readJsonArray($appsFile, $defaultApps);
$workspaceIcons = ['gmail','drive','calendar','meet','docs','sheets','slides'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], (string)$postedToken)) {
        $_SESSION['admin_apps_flash'] = ['type' => 'error', 'message' => 'Token CSRF invalide.'];
        header('Location: /admin-apps.php');
        exit;
    }

    $action = trim((string)($_POST['action'] ?? ''));
    $ok = false;

    if ($action === 'add_app') {
        $name = normalizeName((string)($_POST['name'] ?? ''));
        $url = normalizeUrl((string)($_POST['url'] ?? ''));
        $icon = normalizeIcon((string)($_POST['icon'] ?? 'link'));
        $emoji = normalizeEmoji((string)($_POST['emoji'] ?? ''));
        $adminOnly = normalizeAdminOnly($_POST['admin_only'] ?? false);
        if ($name !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
            $apps[] = ['name' => $name, 'url' => $url, 'icon' => $icon, 'emoji' => $emoji, 'admin_only' => $adminOnly];
            $ok = saveJsonArray($appsFile, $apps);
        }
    }

    if ($action === 'update_app') {
        $idx = (int)($_POST['index'] ?? -1);
        $name = normalizeName((string)($_POST['name'] ?? ''));
        $url = normalizeUrl((string)($_POST['url'] ?? ''));
        $icon = normalizeIcon((string)($_POST['icon'] ?? 'link'));
        $emoji = normalizeEmoji((string)($_POST['emoji'] ?? ''));
        $adminOnly = normalizeAdminOnly($_POST['admin_only'] ?? false);
        if (isset($apps[$idx]) && $name !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
            $apps[$idx] = ['name' => $name, 'url' => $url, 'icon' => $icon, 'emoji' => $emoji, 'admin_only' => $adminOnly];
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

    if ($action === 'move_app') {
        $idx = (int)($_POST['index'] ?? -1);
        $direction = trim((string)($_POST['direction'] ?? ''));
        if (in_array($direction, ['up', 'down'], true)) {
            $ok = moveWithinFiltered(
                $apps,
                $idx,
                $direction,
                static fn(array $app): bool => !isWorkspaceIcon(strtolower(trim((string)($app['icon'] ?? 'link'))))
            );
            if ($ok) $ok = saveJsonArray($appsFile, $apps);
        }
    }

    if ($action === 'reorder_apps') {
        $rawOrder = trim((string)($_POST['order'] ?? ''));
        if ($rawOrder !== '') {
            $orderedIndexes = array_filter(
                array_map('intval', explode(',', $rawOrder)),
                static fn(int $v): bool => $v >= 0
            );
            $ok = reorderFilteredByIndexes(
                $apps,
                $orderedIndexes,
                static fn(array $app): bool => !isWorkspaceIcon(strtolower(trim((string)($app['icon'] ?? 'link'))))
            );
            if ($ok) $ok = saveJsonArray($appsFile, $apps);
        }
    }

    $_SESSION['admin_apps_flash'] = [
        'type' => $ok ? 'success' : 'error',
        'message' => $ok ? 'Applications mises à jour.' : 'Action invalide ou enregistrement impossible.',
    ];
    header('Location: /admin-apps.php');
    exit;
}

$flash = $_SESSION['admin_apps_flash'] ?? null;
unset($_SESSION['admin_apps_flash']);

$customApps = [];
foreach ($apps as $idx => $app) {
    if (!isWorkspaceIcon(strtolower(trim((string)($app['icon'] ?? 'link'))))) {
        $customApps[] = ['index' => $idx, 'app' => $app];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Applications - Groupe Speed Cloud</title>
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
        .card { transition:transform .15s,border-color .15s; }
        .card:hover { transform:translateY(-2px); border-color:rgba(255,255,255,.2); }
        .input-dark { background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.14); color:#e5e7eb; }
        .input-dark:focus { outline:none; border-color:rgba(107,143,255,.6); box-shadow:0 0 0 2px rgba(52,84,209,.3); }
        .btn-soft { border:1px solid rgba(255,255,255,.15); background:rgba(255,255,255,.08); }
        .btn-soft:hover { background:rgba(255,255,255,.15); }
    </style>
</head>
<body class="min-h-screen text-white relative">
<div class="bg-ambient"></div>
<?php include __DIR__ . '/_nav.php'; ?>

<main class="page-stack relative z-10 w-full max-w-6xl mx-auto px-4 sm:px-6 py-8">
    <section class="glass rounded-3xl p-4 sm:p-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold">🧩 Administration des applications</h1>
            <p class="text-white/45 text-sm">Gestion des applications personnalisées du portail.</p>
            <p class="crumb mt-1">Admin / Applications</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="/admin.php" class="admin-tab px-3 py-1.5 rounded-lg text-xs font-semibold">🏠 Accueil Admin</a>
            <a href="/admin-news.php" class="admin-tab px-3 py-1.5 rounded-lg text-xs font-semibold">📰 Actualités</a>
            <a href="/admin-banners.php" class="admin-tab px-3 py-1.5 rounded-lg text-xs font-semibold">📣 Bannières</a>
            <a href="/admin-status.php" class="admin-tab px-3 py-1.5 rounded-lg text-xs font-semibold">📡 Sites</a>
            <a href="/admin-apps.php" class="admin-tab active px-3 py-1.5 rounded-lg text-xs font-semibold">🧩 Applications</a>
            <a href="/admin-users.php" class="admin-tab px-3 py-1.5 rounded-lg text-xs font-semibold">👥 Utilisateurs</a>
        </div>
    </section>

    <?php if ($flash && is_array($flash)): ?>
    <section class="rounded-2xl border px-4 py-2.5 text-sm <?= ($flash['type'] ?? '') === 'success' ? 'bg-emerald-500/20 border-emerald-500/35 text-emerald-100' : 'bg-red-500/20 border-red-500/35 text-red-100' ?>">
        <?= htmlspecialchars((string)($flash['message'] ?? '')) ?>
    </section>
    <?php endif; ?>

    <section class="panel p-4 space-y-3">
        <h2 class="font-semibold">➕ Ajouter une application</h2>
        <form method="post" class="grid sm:grid-cols-6 gap-2 rounded-2xl border border-white/10 bg-white/[0.03] p-3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="action" value="add_app">
            <input type="text" name="name" maxlength="80" required placeholder="Nom de l'app" class="input-dark sm:col-span-1 px-3 py-2 rounded-xl text-sm">
            <input type="text" name="url" maxlength="220" required placeholder="https://..." class="input-dark sm:col-span-2 px-3 py-2 rounded-xl text-sm">
            <input type="text" name="emoji" maxlength="8" placeholder="Emoji (ex: 🚀)" class="input-dark sm:col-span-1 px-3 py-2 rounded-xl text-sm">
            <select name="icon" class="input-dark sm:col-span-1 px-3 py-2 rounded-xl text-sm">
                <option value="link">🔗 Lien</option>
                <option value="youtube">▶️ YouTube</option>
                <option value="discord">💬 Discord</option>
                <option value="github">🐙 GitHub</option>
                <option value="notion">🗂️ Notion</option>
                <option value="figma">🎨 Figma</option>
            </select>
            <label class="input-dark sm:col-span-1 px-3 py-2 rounded-xl text-sm flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="admin_only" value="1" class="accent-blue-500">
                <span>Admin uniquement</span>
            </label>
            <button class="sm:col-span-6 px-3 py-2 rounded-xl text-sm font-semibold bg-blue-600 hover:bg-blue-700">Ajouter l'application</button>
        </form>

        <div class="flex items-center justify-between gap-2">
            <p class="text-xs text-white/60">Glissez-déposez les cartes pour réorganiser l'ordre.</p>
            <form id="reorderAppsForm" method="post" class="flex items-center gap-2">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" value="reorder_apps">
                <input id="appsOrderInput" type="hidden" name="order" value="">
                <button id="saveAppsOrderBtn" type="submit" disabled class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-600/70 text-white disabled:opacity-40 disabled:cursor-not-allowed hover:bg-emerald-600">Enregistrer l'ordre</button>
            </form>
        </div>

        <div id="customAppsList" class="space-y-2">
            <?php foreach ($customApps as $row):
                $idx = (int)$row['index'];
                $app = $row['app'];
                $name = trim((string)($app['name'] ?? 'Application'));
                $url = trim((string)($app['url'] ?? ''));
                $icon = normalizeIcon((string)($app['icon'] ?? 'link'));
                $emoji = normalizeEmoji((string)($app['emoji'] ?? ''));
                $adminOnly = !empty($app['admin_only']);
            ?>
            <div class="card app-sort-item rounded-lg bg-white/[0.03] border border-white/10 p-2" draggable="true" data-index="<?= $idx ?>">
                <form method="post" class="grid sm:grid-cols-9 gap-2">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="update_app">
                    <input type="hidden" name="index" value="<?= (int)$idx ?>">
                    <input type="text" name="name" maxlength="80" required value="<?= htmlspecialchars($name) ?>" class="input-dark sm:col-span-1 px-3 py-2 rounded-xl text-sm">
                    <input type="text" name="url" maxlength="220" required value="<?= htmlspecialchars($url) ?>" class="input-dark sm:col-span-2 px-3 py-2 rounded-xl text-sm">
                    <input type="text" name="emoji" maxlength="8" value="<?= htmlspecialchars($emoji) ?>" placeholder="Emoji" class="input-dark sm:col-span-1 px-3 py-2 rounded-xl text-sm">
                    <select name="icon" class="input-dark sm:col-span-1 px-3 py-2 rounded-xl text-sm">
                        <option value="link" <?= $icon === 'link' ? 'selected' : '' ?>>🔗 Lien</option>
                        <option value="youtube" <?= $icon === 'youtube' ? 'selected' : '' ?>>▶️ YouTube</option>
                        <option value="discord" <?= $icon === 'discord' ? 'selected' : '' ?>>💬 Discord</option>
                        <option value="github" <?= $icon === 'github' ? 'selected' : '' ?>>🐙 GitHub</option>
                        <option value="notion" <?= $icon === 'notion' ? 'selected' : '' ?>>🗂️ Notion</option>
                        <option value="figma" <?= $icon === 'figma' ? 'selected' : '' ?>>🎨 Figma</option>
                    </select>
                    <label class="input-dark sm:col-span-1 px-3 py-2 rounded-xl text-xs flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="admin_only" value="1" <?= $adminOnly ? 'checked' : '' ?> class="accent-blue-500">
                        <span>Admin uniquement</span>
                    </label>
                    <div class="sm:col-span-1 text-xs text-white/65 flex items-center justify-center rounded-xl bg-white/5 border border-white/10">
                        Affichage: <?= $emoji !== '' ? htmlspecialchars($emoji) : appEmoji($icon) ?>
                    </div>
                    <div class="sm:col-span-2 flex items-center justify-end gap-1.5">
                        <span class="text-xs text-white/45 cursor-move select-none" title="Déplacer">↕</span>
                        <button class="px-2.5 py-1.5 rounded-lg text-xs bg-blue-600 hover:bg-blue-700">Modifier</button>
                    </div>
                </form>
                <div class="mt-1 flex items-center justify-end">
                <form method="post" class="inline-flex">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="delete_app">
                    <input type="hidden" name="index" value="<?= (int)$idx ?>">
                    <button class="px-2 py-1 rounded-lg text-xs bg-red-500/20 text-red-200 hover:bg-red-500/30">Suppr.</button>
                </form>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</main>
<script>
(() => {
    const list = document.getElementById('customAppsList');
    const saveBtn = document.getElementById('saveAppsOrderBtn');
    const orderInput = document.getElementById('appsOrderInput');
    if (!list || !saveBtn || !orderInput) return;

    let dragEl = null;
    let changed = false;

    function markChanged() {
        changed = true;
        saveBtn.disabled = false;
    }

    function currentOrder() {
        return Array.from(list.querySelectorAll('.app-sort-item'))
            .map((el) => el.getAttribute('data-index'))
            .filter(Boolean)
            .join(',');
    }

    function setupItem(item) {
        item.addEventListener('dragstart', () => {
            dragEl = item;
            item.classList.add('opacity-50');
        });

        item.addEventListener('dragend', () => {
            item.classList.remove('opacity-50');
            dragEl = null;
        });

        item.addEventListener('dragover', (e) => {
            e.preventDefault();
            if (!dragEl || dragEl === item) return;

            const rect = item.getBoundingClientRect();
            const isAfter = (e.clientY - rect.top) > (rect.height / 2);
            const anchor = isAfter ? item.nextElementSibling : item;
            if (anchor !== dragEl) {
                list.insertBefore(dragEl, anchor);
                markChanged();
            }
        });
    }

    Array.from(list.querySelectorAll('.app-sort-item')).forEach(setupItem);

    document.getElementById('reorderAppsForm')?.addEventListener('submit', () => {
        orderInput.value = currentOrder();
    });
})();
</script>
</body>
</html>
