<?php
$user    = $_SESSION['user'];
$config  = require __DIR__ . '/../config.php';
$isAdmin = in_array($user['email'], $config['admins'] ?? []);

$currentPage = 'portail';

// Annonces mises en avant
$featuredFile = __DIR__ . '/../uploads/featured.json';
$featured = [];
if (file_exists($featuredFile)) {
    $raw     = file_get_contents($featuredFile);
    $decoded = json_decode($raw, true);
    $featured = is_array($decoded) ? $decoded : [];
}
$featured = array_values(array_filter($featured, fn($a) => ($a['status'] ?? 'published') === 'published'));

$bannerFile = __DIR__ . '/../uploads/banners.json';
$activeBanner = null;
if (file_exists($bannerFile)) {
    $decoded = json_decode(file_get_contents($bannerFile), true);
    $banners = is_array($decoded) ? $decoded : [];
    foreach (array_reverse($banners) as $b) {
        if (!empty($b['active'])) { $activeBanner = $b; break; }
    }
}

// Applications
$appsFile = __DIR__ . '/../uploads/apps.json';
$workspaceDefaults = [
    ['name' => 'Gmail',       'url' => 'https://mail.google.com',     'icon' => 'gmail'],
    ['name' => 'Drive',       'url' => 'https://drive.google.com',    'icon' => 'drive'],
    ['name' => 'Agenda',      'url' => 'https://calendar.google.com', 'icon' => 'calendar'],
    ['name' => 'Meet',        'url' => 'https://meet.google.com',     'icon' => 'meet'],
    ['name' => 'Docs',        'url' => 'https://docs.google.com',     'icon' => 'docs'],
    ['name' => 'Sheets',      'url' => 'https://sheets.google.com',   'icon' => 'sheets'],
    ['name' => 'Slides',      'url' => 'https://slides.google.com',   'icon' => 'slides'],
];

$portalDefaults = $config['portal']['apps'] ?? [
    ['name' => 'YouTube',     'url' => 'https://youtube.com',         'icon' => 'youtube'],
    ['name' => 'Discord',     'url' => 'https://discord.com',         'icon' => 'discord'],
    ['name' => 'GitHub',      'url' => 'https://github.com',          'icon' => 'github'],
    ['name' => 'Notion',      'url' => 'https://notion.so',           'icon' => 'notion'],
    ['name' => 'Figma',       'url' => 'https://figma.com',           'icon' => 'figma'],
];

$apps = array_merge($workspaceDefaults, $portalDefaults);
if (file_exists($appsFile)) {
    $decoded = json_decode((string)file_get_contents($appsFile), true);
    if (is_array($decoded)) {
        $apps = $decoded;
    }
}

$googleWorkspaceIcons = ['gmail', 'drive', 'calendar', 'meet', 'docs', 'sheets', 'slides'];
$workspaceApps = [];
$portalApps = [];
$unavailableApps = [];
foreach ($apps as $app) {
    $icon = strtolower(trim((string)($app['icon'] ?? 'link')));
    $adminOnly = !empty($app['admin_only']);
    if ($adminOnly && !$isAdmin) {
        continue;
    }
    $appStatus = $app['status'] ?? 'active';
    if (in_array($appStatus, ['maintenance', 'disabled'], true)) {
        $unavailableApps[] = $app;
        continue;
    }
    if (in_array($icon, $googleWorkspaceIcons, true)) {
        $workspaceApps[] = $app;
    } else {
        $portalApps[] = $app;
    }
}

if (empty($workspaceApps)) {
    $workspaceApps = $workspaceDefaults;
}

$firstName = $user['firstName'] ?? explode(' ', $user['name'])[0];
$workspaceCount = count($workspaceApps);
$portalCount = count($portalApps);
$hasActiveBanner = $activeBanner !== null;

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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portail — Groupe Speed Cloud</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'violet': { 400:'#a78bfa', 500:'#8b5cf6', 600:'#7c3aed', 700:'#6d28d9' },
                        'cyan':   { 400:'#38bdf8', 500:'#0ea5e9', 600:'#0891b2' },
                    }
                }
            }
        };
    </script>
    <link rel="icon" type="image/png" href="/assets/images/cloudy.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php include __DIR__ . '/_ui-tokens.php'; ?>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            overflow-x: hidden;
            color: #f1f5f9;
        }

        /* ── ambient background ─────────────────────────────────────── */
        .bg-ambient {
            position: fixed; inset: 0; pointer-events: none; z-index: 0;
            background:
                radial-gradient(ellipse 65% 50% at 12%   5%,  rgba(124,58,237,.28) 0%, transparent 60%),
                radial-gradient(ellipse 50% 42% at 90%  95%,  rgba(8,145,178,.22)  0%, transparent 58%),
                radial-gradient(ellipse 38% 30% at 55%  50%,  rgba(109,40,217,.07) 0%, transparent 75%);
        }

        /* ── panel ──────────────────────────────────────────────────── */
        .panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
        }
        .panel:hover { border-color: var(--border-hov); }

        /* ── app card ───────────────────────────────────────────────── */
        .app-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            transition: transform .17s cubic-bezier(.34,1.56,.64,1),
                        background .13s ease,
                        border-color .13s ease;
            cursor: pointer;
        }
        .app-card:hover {
            transform: translateY(-3px);
            background: var(--surface-hov);
            border-color: rgba(167,139,250,.35);
        }
        .app-card:active { transform: translateY(-1px); }

        /* ── emoji container ────────────────────────────────────────── */
        .emoji-wrap {
            width: 44px; height: 44px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.09);
        }

        /* ── search ─────────────────────────────────────────────────── */
        .search-wrap {
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.10);
            border-radius: 14px;
            transition: border-color .14s, box-shadow .14s, background .14s;
        }
        .search-wrap:focus-within {
            border-color: rgba(167,139,250,.5);
            background: rgba(255,255,255,.07);
            box-shadow: 0 0 0 3px rgba(124,58,237,.22);
        }
        .search-wrap input:focus { outline: none; }

        /* ── google coloured logo ───────────────────────────────────── */
        .g-logo { font-size: 1.05rem; font-weight: 800; line-height: 1; user-select: none; }
        .g-logo .gb { color:#4285F4; } .g-logo .gr { color:#EA4335; }
        .g-logo .gy { color:#FBBC05; } .g-logo .gg { color:#34A853; }

        /* ── clock ───────────────────────────────────────────────────── */
        #clock { font-variant-numeric: tabular-nums; letter-spacing: -.03em; }

        /* ── section title ───────────────────────────────────────────── */
        .sec-title {
            font-size: .65rem; font-weight: 700;
            letter-spacing: .1em; text-transform: uppercase;
            color: rgba(255,255,255,.38);
        }

        /* ── featured card ───────────────────────────────────────────── */
        .feat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            transition: border-color .14s, background .14s;
        }
        .feat-card:hover {
            background: var(--surface-hov);
            border-color: rgba(167,139,250,.3);
        }

        /* ── grid ────────────────────────────────────────────────────── */
        .portal-shell { max-width: 1280px; }
        .hero-grid { display: grid; grid-template-columns: 1fr; gap: .9rem; }
        .app-grid  { grid-template-columns: repeat(2, minmax(0,1fr)); }
        .feat-grid { grid-template-columns: 1fr; }

        @media (min-width: 640px) {
            .app-grid { grid-template-columns: repeat(3, minmax(0,1fr)); }
        }
        @media (min-width: 768px) {
            .hero-grid { grid-template-columns: 1.4fr 1fr; }
            .feat-grid { grid-template-columns: repeat(2, minmax(0,1fr)); }
            .app-grid  { grid-template-columns: repeat(4, minmax(0,1fr)); }
        }
        @media (min-width: 1024px) {
            .feat-grid { grid-template-columns: repeat(3, minmax(0,1fr)); }
            .app-grid  { grid-template-columns: repeat(6, minmax(0,1fr)); }
        }
    </style>
</head>
<body class="min-h-screen text-white relative">

<div class="bg-ambient"></div>

<?php include __DIR__ . '/_nav.php'; ?>

<main class="page-stack portal-shell relative z-10 w-full mx-auto px-4 sm:px-6 py-6 sm:py-8">

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

    <!-- ══ HERO GRID ══════════════════════════════════════════════════ -->
    <section class="hero-grid items-start">

        <!-- Greeting + Search -->
        <article class="panel p-5 sm:p-6">

            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-5">

                <!-- Avatar + name -->
                <div class="flex items-center gap-4">
                    <?php if (!empty($user['picture'])): ?>
                    <div class="relative flex-shrink-0">
                        <img src="<?= htmlspecialchars($user['picture']) ?>"
                             alt="Photo"
                             class="w-12 h-12 rounded-xl border border-white/15 shadow-lg">
                        <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 border-[#07080e]"
                              style="background:#34d399;"></span>
                    </div>
                    <?php endif; ?>
                    <div>
                        <p class="text-white/38 text-xs font-medium mb-0.5">Bonjour,</p>
                        <h1 class="text-xl sm:text-2xl font-bold leading-tight">
                            <?= htmlspecialchars($firstName) ?> <span style="opacity:.7">👋</span>
                        </h1>
                    </div>
                </div>

                <!-- Clock -->
                <div class="text-right flex-shrink-0">
                    <p id="clock" class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white">--:--</p>
                    <p id="date-display" class="text-white/38 text-xs mt-0.5 capitalize"></p>
                </div>
            </div>

            <!-- Divider -->
            <div class="mb-5" style="border-top:1px solid var(--border);"></div>

            <!-- Google search -->
            <form action="https://www.google.com/search" method="get" target="_blank" rel="noopener">
                <input type="hidden" name="hl" value="fr">
                <div class="search-wrap flex items-center gap-2.5 px-3 py-2.5">
                    <span class="g-logo flex-shrink-0">
                        <span class="gb">G</span><span class="gr">o</span><span class="gy">o</span><span class="gb">g</span><span class="gg">l</span><span class="gr">e</span>
                    </span>
                    <input type="text" name="q" placeholder="Rechercher…" autocomplete="off"
                           class="flex-1 bg-transparent text-white placeholder-white/28 text-sm border-none shadow-none"
                           style="height:auto;min-height:unset;border:none!important;box-shadow:none!important;">
                    <button type="submit"
                            class="flex-shrink-0 px-4 py-2 rounded-xl text-white text-xs font-semibold transition"
                            style="background:var(--primary);min-height:unset;">
                        Rechercher
                    </button>
                </div>
            </form>

        </article>

        <!-- Unavailable apps widget (only when needed) -->
        <?php if (!empty($unavailableApps)): ?>
        <aside class="panel p-4 self-start">
            <p class="sec-title mb-3">⚠ Services indisponibles</p>
            <ul class="space-y-2">
                <?php foreach ($unavailableApps as $ua):
                    $uaName   = htmlspecialchars($ua['name'] ?? '');
                    $uaIcon   = strtolower(trim((string)($ua['icon'] ?? 'link')));
                    $uaEmoji  = trim((string)($ua['emoji'] ?? ''));
                    $uaStatus = $ua['status'] ?? 'disabled';
                ?>
                <li class="flex items-center gap-2.5 py-1.5" style="border-bottom:1px solid var(--border);">
                    <span class="text-xl leading-none select-none flex-shrink-0"><?= $uaEmoji !== '' ? htmlspecialchars($uaEmoji) : appEmoji($uaIcon) ?></span>
                    <span class="flex-1 text-sm text-white/70 truncate font-medium"><?= $uaName ?></span>
                    <span class="flex-shrink-0 text-[10px] font-semibold px-2 py-0.5 rounded-full border"
                          style="<?= $uaStatus === 'maintenance'
                                    ? 'background:rgba(217,119,6,.18);color:#fbbf24;border-color:rgba(217,119,6,.35);'
                                    : 'background:rgba(255,255,255,.06);color:rgba(255,255,255,.35);border-color:rgba(255,255,255,.10);' ?>">
                        <?= $uaStatus === 'maintenance' ? '🔧 Maintenance' : 'Désactivé' ?>
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
        </aside>
        <?php elseif (!empty($workspaceApps)): ?>
        <!-- Clock / quick stats side panel -->
        <aside class="panel p-5 self-start flex flex-col gap-4">
            <div>
                <p class="sec-title mb-2">Workspace</p>
                <p class="text-white/45 text-xs"><?= count($workspaceApps) ?> apps · <?= count($portalApps) ?> outils</p>
            </div>
            <div class="space-y-1">
                <?php foreach (array_slice($workspaceApps, 0, 4) as $wa):
                    $wIcon  = $wa['icon'] ?? 'default';
                    $wEmoji = trim((string)($wa['emoji'] ?? ''));
                    $wName  = htmlspecialchars($wa['name'] ?? '');
                    $wUrl   = htmlspecialchars($wa['url'] ?? '#');
                ?>
                <a href="<?= $wUrl ?>" class="flex items-center gap-2.5 px-3 py-2 rounded-xl transition"
                   style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);">
                    <span class="text-lg leading-none"><?= $wEmoji !== '' ? htmlspecialchars($wEmoji) : appEmoji($wIcon) ?></span>
                    <span class="text-sm font-medium text-white/75"><?= $wName ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </aside>
        <?php endif; ?>

    </section>

    <!-- ══ FEATURED ANNOUNCEMENTS ═════════════════════════════════════ -->
    <?php if (!empty($featured)): ?>
    <section>
        <p class="sec-title mb-3">À la une</p>
        <div class="feat-grid grid gap-3">
            <?php foreach ($featured as $ann):
                $accentColor = htmlspecialchars($ann['color'] ?? '#7c3aed');
                $annTitle    = htmlspecialchars($ann['title'] ?? '');
                $annHtml     = $ann['html_content'] ?? nl2br(htmlspecialchars($ann['content'] ?? ''));
                $annEmoji    = htmlspecialchars($ann['emoji'] ?? '📢');
                $annDate     = htmlspecialchars($ann['created_at'] ?? ($ann['pinned_at'] ?? ''));
            ?>
            <div class="feat-card p-4" style="border-left:3px solid <?= $accentColor ?>;">
                <div class="flex items-start gap-3">
                    <span class="text-lg select-none mt-0.5 flex-shrink-0"><?= $annEmoji ?></span>
                    <div class="min-w-0 flex-1">
                        <?php if ($annTitle): ?>
                        <p class="font-semibold text-sm text-white mb-1 leading-snug"><?= $annTitle ?></p>
                        <?php endif; ?>
                        <p class="text-white/50 text-xs leading-relaxed line-clamp-2"><?= strip_tags($annHtml) ?></p>
                        <div class="flex items-center justify-between mt-2.5">
                            <?php if ($annDate): ?><span class="text-white/25 text-[11px]"><?= $annDate ?></span><?php endif; ?>
                            <a href="/article.php?id=<?= urlencode((string)($ann['id'] ?? '')) ?>"
                               class="text-xs font-semibold ml-auto"
                               style="color:var(--primary-lt);">
                                Lire →
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ══ GOOGLE WORKSPACE ═══════════════════════════════════════════ -->
    <?php if (!empty($workspaceApps)): ?>
    <section>
        <p class="sec-title mb-3">Suite Google Workspace</p>
        <div class="app-grid grid gap-3">
            <?php foreach ($workspaceApps as $app):
                $appName       = htmlspecialchars($app['name'] ?? '');
                $appUrl        = htmlspecialchars($app['url'] ?? '#');
                $appIcon       = $app['icon'] ?? 'default';
                $appEmojiValue = trim((string)($app['emoji'] ?? ''));
            ?>
            <a href="<?= $appUrl ?>"
               class="app-card flex flex-col items-center gap-2 p-3 pt-4 text-center">
                <div class="emoji-wrap">
                    <span class="text-2xl leading-none select-none"><?= $appEmojiValue !== '' ? htmlspecialchars($appEmojiValue) : appEmoji($appIcon) ?></span>
                </div>
                <span class="text-xs font-medium text-white/60 leading-tight"><?= $appName ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ══ APPLICATIONS ═══════════════════════════════════════════════ -->
    <section>
        <p class="sec-title mb-3">Applications <?php if ($portalCount): ?>(<?= $portalCount ?>)<?php endif; ?></p>
        <?php if (empty($portalApps)): ?>
        <div class="panel p-5 text-sm text-white/40 text-center">Aucune application configurée.</div>
        <?php else: ?>
        <div class="app-grid grid gap-3">
            <?php foreach ($portalApps as $app):
                $appName       = htmlspecialchars($app['name'] ?? '');
                $appUrl        = htmlspecialchars($app['url'] ?? '#');
                $appIcon       = $app['icon'] ?? 'default';
                $appEmojiValue = trim((string)($app['emoji'] ?? ''));
            ?>
            <a href="<?= $appUrl ?>"
               class="app-card flex flex-col items-center gap-2 p-3 pt-4 text-center">
                <div class="emoji-wrap">
                    <span class="text-2xl leading-none select-none"><?= $appEmojiValue !== '' ? htmlspecialchars($appEmojiValue) : appEmoji($appIcon) ?></span>
                </div>
                <span class="text-xs font-medium text-white/60 leading-tight"><?= $appName ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>

</main>

<script>
    const clockEl = document.getElementById('clock');
    const dateEl  = document.getElementById('date-display');
    const JOURS = ['dimanche','lundi','mardi','mercredi','jeudi','vendredi','samedi'];
    const MOIS  = ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
    function tick() {
        const n = new Date();
        clockEl.textContent = [
            String(n.getHours()).padStart(2,'0'),
            String(n.getMinutes()).padStart(2,'0'),
        ].join(':');
        dateEl.textContent = `${JOURS[n.getDay()]} ${n.getDate()} ${MOIS[n.getMonth()]} ${n.getFullYear()}`;
    }
    tick();
    setInterval(tick, 30000);
</script>

</body>
</html>
