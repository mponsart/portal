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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
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
                radial-gradient(ellipse 70% 55% at 8%    0%,  rgba(124,58,237,.32) 0%, transparent 55%),
                radial-gradient(ellipse 55% 45% at 94%  100%, rgba(8,145,178,.25)  0%, transparent 55%),
                radial-gradient(ellipse 40% 32% at 52%   52%, rgba(109,40,217,.08) 0%, transparent 68%);
        }

        /* ── panel ──────────────────────────────────────────────────── */
        .panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            transition: border-color .14s;
        }
        .panel:hover { border-color: var(--border-hov); }

        /* ── hero panel ─────────────────────────────────────────────── */
        .hero-panel {
            background: linear-gradient(145deg, rgba(124,58,237,.13) 0%, var(--surface) 55%);
            border: 1px solid var(--border);
            border-radius: 20px;
            transition: border-color .14s;
        }
        .hero-panel:hover { border-color: rgba(124,58,237,.25); }

        /* ── app card ───────────────────────────────────────────────── */
        .app-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            transition: transform .17s cubic-bezier(.34,1.56,.64,1),
                        background .13s ease,
                        border-color .13s ease,
                        box-shadow .17s ease;
            cursor: pointer;
        }
        .app-card:hover {
            transform: translateY(-4px);
            background: var(--surface-hov);
            border-color: rgba(167,139,250,.38);
            box-shadow: 0 14px 36px rgba(124,58,237,.2), 0 0 0 1px rgba(167,139,250,.1);
        }
        .app-card:active { transform: translateY(-1px); }

        /* ── workspace app card (cyan accent) ───────────────────────── */
        .app-card.ws:hover {
            border-color: rgba(56,189,248,.38);
            box-shadow: 0 14px 36px rgba(8,145,178,.2), 0 0 0 1px rgba(56,189,248,.1);
        }

        /* ── emoji container ────────────────────────────────────────── */
        .emoji-wrap {
            width: 48px; height: 48px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            background: rgba(255,255,255,.07);
            border: 1px solid rgba(255,255,255,.09);
            transition: transform .17s ease, background .13s ease;
        }
        .app-card:hover .emoji-wrap {
            background: rgba(255,255,255,.12);
            transform: scale(1.1);
        }

        /* ── search ─────────────────────────────────────────────────── */
        .search-wrap {
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.11);
            border-radius: 16px;
            transition: border-color .14s, box-shadow .14s, background .14s;
        }
        .search-wrap:focus-within {
            border-color: rgba(167,139,250,.55);
            background: rgba(255,255,255,.08);
            box-shadow: 0 0 0 3px rgba(124,58,237,.2), 0 4px 24px rgba(124,58,237,.12);
        }
        .search-wrap input:focus { outline: none; }

        /* ── google coloured logo ───────────────────────────────────── */
        .g-logo { font-size: 1.1rem; font-weight: 800; line-height: 1; user-select: none; }
        .g-logo .gb { color:#4285F4; } .g-logo .gr { color:#EA4335; }
        .g-logo .gy { color:#FBBC05; } .g-logo .gg { color:#34A853; }

        /* ── clock ───────────────────────────────────────────────────── */
        #clock {
            font-variant-numeric: tabular-nums; letter-spacing: -.045em;
            background: linear-gradient(135deg, #fff 35%, rgba(167,139,250,.85) 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* ── section title ───────────────────────────────────────────── */
        .sec-title {
            font-size: .68rem; font-weight: 700;
            letter-spacing: .1em; text-transform: uppercase;
            color: rgba(255,255,255,.40);
        }
        .sec-dot {
            display: inline-block; width: 6px; height: 6px; border-radius: 50%;
            margin-right: .45rem; vertical-align: middle; position: relative; top: -1px;
        }

        /* ── featured card ───────────────────────────────────────────── */
        .feat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            transition: border-color .14s, background .14s, transform .15s ease, box-shadow .15s ease;
        }
        .feat-card:hover {
            background: var(--surface-hov);
            border-color: rgba(167,139,250,.32);
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(0,0,0,.22);
        }

        /* ── workspace quick-link ───────────────────────────────────── */
        .ws-link {
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.07);
            border-radius: 12px;
            transition: background .12s, border-color .12s;
        }
        .ws-link:hover {
            background: rgba(8,145,178,.12);
            border-color: rgba(56,189,248,.28);
        }

        /* ── avatar online ring + status dot ───────────────────────── */
        .avatar-ring { box-shadow: 0 0 0 2px var(--bg), 0 0 0 3.5px rgba(52,211,153,.65); }
        @keyframes pulse-green {
            0%,100% { box-shadow: 0 0 0 0   rgba(52,211,153,.55); }
            50%     { box-shadow: 0 0 0 4px  rgba(52,211,153,0);   }
        }
        .status-dot { animation: pulse-green 2.6s ease infinite; }

        /* ── search submit btn ───────────────────────────────────────── */
        .search-btn {
            background: var(--primary);
            transition: background .14s, transform .1s, box-shadow .14s;
        }
        .search-btn:hover {
            background: var(--primary-dk);
            box-shadow: 0 4px 14px rgba(124,58,237,.4);
        }
        .search-btn:active { transform: scale(.96); }

        /* ── page grid ───────────────────────────────────────────────── */
        .portal-shell { max-width: 1280px; }
        .hero-grid { display: grid; grid-template-columns: 1fr; gap: 1rem; }
        .app-grid  { grid-template-columns: repeat(2, minmax(0,1fr)); }
        .feat-grid { grid-template-columns: 1fr; }

        @media (min-width: 640px) {
            .app-grid { grid-template-columns: repeat(3, minmax(0,1fr)); }
        }
        @media (min-width: 768px) {
            .hero-grid { grid-template-columns: 1.5fr 1fr; }
            .feat-grid { grid-template-columns: repeat(2, minmax(0,1fr)); }
            .app-grid  { grid-template-columns: repeat(4, minmax(0,1fr)); }
        }
        @media (min-width: 1024px) {
            .feat-grid { grid-template-columns: repeat(3, minmax(0,1fr)); }
            .app-grid  { grid-template-columns: repeat(6, minmax(0,1fr)); }
        }

        /* ── entry animations ────────────────────────────────────────── */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: none; }
        }
        main > * { animation: fadeUp .38s ease both; }
        main > *:nth-child(1) { animation-delay: .04s; }
        main > *:nth-child(2) { animation-delay: .09s; }
        main > *:nth-child(3) { animation-delay: .14s; }
        main > *:nth-child(4) { animation-delay: .19s; }
        main > *:nth-child(5) { animation-delay: .24s; }
    </style>
</head>
<body class="min-h-screen text-white relative">

<div class="bg-ambient"></div>

<?php include __DIR__ . '/_nav.php'; ?>

<main class="page-stack portal-shell relative z-10 w-full mx-auto px-4 sm:px-6 py-7 sm:py-10">

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
    <div class="rounded-2xl border px-5 py-3.5 flex items-start gap-3"
         style="background:<?= $bannerStyles['bg'] ?>;border-color:<?= $bannerStyles['border'] ?>;color:<?= $bannerStyles['text'] ?>;">
        <span class="text-lg flex-shrink-0 mt-px"><?= $toneIcon ?></span>
        <div>
            <p class="font-semibold text-sm"><?= htmlspecialchars($activeBanner['title'] ?? 'Annonce') ?></p>
            <p class="text-sm opacity-80 mt-0.5"><?= htmlspecialchars($activeBanner['message'] ?? '') ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- ══ HERO GRID ══════════════════════════════════════════════════ -->
    <section class="hero-grid items-stretch">

        <!-- Greeting + Search -->
        <article class="hero-panel p-6 sm:p-7 flex flex-col gap-5">

            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-5">

                <!-- Avatar + name -->
                <div class="flex items-center gap-4">
                    <?php if (!empty($user['picture'])): ?>
                    <div class="relative flex-shrink-0">
                        <img src="<?= htmlspecialchars($user['picture']) ?>"
                             alt="Photo"
                             class="w-14 h-14 rounded-2xl object-cover avatar-ring">
                        <span class="status-dot absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full border-2"
                              style="background:#34d399;border-color:var(--bg);"></span>
                    </div>
                    <?php endif; ?>
                    <div>
                        <p class="text-white/40 text-xs font-semibold mb-1 tracking-widest uppercase">Bonjour,</p>
                        <h1 class="text-2xl sm:text-3xl font-extrabold leading-tight tracking-tight">
                            <?= htmlspecialchars($firstName) ?>&thinsp;<span style="opacity:.75">👋</span>
                        </h1>
                        <p id="date-display" class="text-white/35 text-xs mt-1.5 capitalize font-medium"></p>
                    </div>
                </div>

                <!-- Clock -->
                <div class="text-right flex-shrink-0">
                    <p id="clock" class="text-4xl sm:text-5xl font-black">--:--</p>
                    <p class="text-white/28 text-[11px] mt-1.5 font-semibold tracking-widest uppercase">Heure locale</p>
                </div>
            </div>

            <!-- Divider -->
            <div style="border-top:1px solid var(--border);"></div>

            <!-- Google search -->
            <form action="https://www.google.com/search" method="get" target="_blank" rel="noopener">
                <input type="hidden" name="hl" value="fr">
                <div class="search-wrap flex items-center gap-3 px-4 py-3">
                    <span class="g-logo flex-shrink-0">
                        <span class="gb">G</span><span class="gr">o</span><span class="gy">o</span><span class="gb">g</span><span class="gg">l</span><span class="gr">e</span>
                    </span>
                    <input type="text" name="q" placeholder="Rechercher sur Google…" autocomplete="off"
                           class="flex-1 bg-transparent text-white placeholder-white/30 text-sm border-none shadow-none"
                           style="height:auto;min-height:unset;border:none!important;box-shadow:none!important;">
                    <button type="submit"
                            class="search-btn flex-shrink-0 px-4 py-2 rounded-xl text-white text-xs font-semibold"
                            style="min-height:unset;">
                        Rechercher
                    </button>
                </div>
            </form>

        </article>

        <!-- Unavailable apps widget (only when needed) -->
        <?php if (!empty($unavailableApps)): ?>
        <aside class="panel p-5 self-start">
            <p class="sec-title mb-3.5"><span class="sec-dot" style="background:#fbbf24;"></span>Services indisponibles</p>
            <ul class="space-y-1.5">
                <?php foreach ($unavailableApps as $ua):
                    $uaName   = htmlspecialchars($ua['name'] ?? '');
                    $uaIcon   = strtolower(trim((string)($ua['icon'] ?? 'link')));
                    $uaEmoji  = trim((string)($ua['emoji'] ?? ''));
                    $uaStatus = $ua['status'] ?? 'disabled';
                ?>
                <li class="flex items-center gap-3 py-2" style="border-bottom:1px solid var(--border);">
                    <span class="text-xl leading-none select-none flex-shrink-0"><?= $uaEmoji !== '' ? htmlspecialchars($uaEmoji) : appEmoji($uaIcon) ?></span>
                    <span class="flex-1 text-sm text-white/75 truncate font-medium"><?= $uaName ?></span>
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
        <!-- Workspace quick-access side panel -->
        <aside class="panel p-5 self-start flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <p class="sec-title"><span class="sec-dot" style="background:#38bdf8;"></span>Workspace</p>
                <span class="text-[10px] font-semibold text-white/30 bg-white/[.055] px-2.5 py-0.5 rounded-full"><?= count($workspaceApps) ?> apps</span>
            </div>
            <div class="space-y-1.5">
                <?php foreach (array_slice($workspaceApps, 0, 5) as $wa):
                    $wIcon  = $wa['icon'] ?? 'default';
                    $wEmoji = trim((string)($wa['emoji'] ?? ''));
                    $wName  = htmlspecialchars($wa['name'] ?? '');
                    $wUrl   = htmlspecialchars($wa['url'] ?? '#');
                ?>
                <a href="<?= $wUrl ?>" class="ws-link flex items-center gap-3 px-3 py-2.5 rounded-xl">
                    <span class="text-xl leading-none"><?= $wEmoji !== '' ? htmlspecialchars($wEmoji) : appEmoji($wIcon) ?></span>
                    <span class="text-sm font-medium text-white/80"><?= $wName ?></span>
                    <span class="ml-auto text-white/22 text-xs">↗</span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php if ($portalCount): ?>
            <div class="pt-1" style="border-top:1px solid var(--border);">
                <p class="text-[11px] text-white/30 font-medium"><?= $portalCount ?> outil<?= $portalCount > 1 ? 's' : '' ?> configuré<?= $portalCount > 1 ? 's' : '' ?></p>
            </div>
            <?php endif; ?>
        </aside>
        <?php endif; ?>

    </section>

    <!-- ══ FEATURED ANNOUNCEMENTS ═════════════════════════════════════ -->
    <?php if (!empty($featured)): ?>
    <section>
        <p class="sec-title mb-3.5"><span class="sec-dot" style="background:var(--primary-lt);"></span>À la une</p>
        <div class="feat-grid grid gap-3">
            <?php foreach ($featured as $ann):
                $accentColor = htmlspecialchars($ann['color'] ?? '#7c3aed');
                $annTitle    = htmlspecialchars($ann['title'] ?? '');
                $annHtml     = $ann['html_content'] ?? nl2br(htmlspecialchars($ann['content'] ?? ''));
                $annEmoji    = htmlspecialchars($ann['emoji'] ?? '📢');
                $annDate     = htmlspecialchars($ann['created_at'] ?? ($ann['pinned_at'] ?? ''));
            ?>
            <div class="feat-card p-4 sm:p-5" style="border-left:3px solid <?= $accentColor ?>;">
                <div class="flex items-start gap-3.5">
                    <span class="text-xl select-none mt-0.5 flex-shrink-0"><?= $annEmoji ?></span>
                    <div class="min-w-0 flex-1">
                        <?php if ($annTitle): ?>
                        <p class="font-semibold text-sm text-white mb-1.5 leading-snug"><?= $annTitle ?></p>
                        <?php endif; ?>
                        <p class="text-white/50 text-xs leading-relaxed line-clamp-2"><?= strip_tags($annHtml) ?></p>
                        <div class="flex items-center justify-between mt-3">
                            <?php if ($annDate): ?><span class="text-white/25 text-[11px]"><?= $annDate ?></span><?php endif; ?>
                            <a href="/article.php?id=<?= urlencode((string)($ann['id'] ?? '')) ?>"
                               class="text-xs font-semibold ml-auto transition-opacity hover:opacity-75"
                               style="color:var(--primary-lt);">
                                Lire l'article →
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
        <p class="sec-title mb-3.5"><span class="sec-dot" style="background:#38bdf8;"></span>Suite Google Workspace</p>
        <div class="app-grid grid gap-3">
            <?php foreach ($workspaceApps as $app):
                $appName       = htmlspecialchars($app['name'] ?? '');
                $appUrl        = htmlspecialchars($app['url'] ?? '#');
                $appIcon       = $app['icon'] ?? 'default';
                $appEmojiValue = trim((string)($app['emoji'] ?? ''));
            ?>
            <a href="<?= $appUrl ?>"
               class="app-card ws flex flex-col items-center gap-2.5 p-3.5 pt-5 text-center">
                <div class="emoji-wrap">
                    <span class="text-2xl leading-none select-none"><?= $appEmojiValue !== '' ? htmlspecialchars($appEmojiValue) : appEmoji($appIcon) ?></span>
                </div>
                <span class="text-xs font-semibold text-white/65 leading-tight pb-1"><?= $appName ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ══ APPLICATIONS ═══════════════════════════════════════════════ -->
    <section>
        <p class="sec-title mb-3.5"><span class="sec-dot" style="background:var(--primary-lt);"></span>Applications<?php if ($portalCount): ?> <span class="normal-case tracking-normal opacity-55 font-semibold">(<?= $portalCount ?>)</span><?php endif; ?></p>
        <?php if (empty($portalApps)): ?>
        <div class="panel p-6 text-sm text-white/40 text-center">Aucune application configurée.</div>
        <?php else: ?>
        <div class="app-grid grid gap-3">
            <?php foreach ($portalApps as $app):
                $appName       = htmlspecialchars($app['name'] ?? '');
                $appUrl        = htmlspecialchars($app['url'] ?? '#');
                $appIcon       = $app['icon'] ?? 'default';
                $appEmojiValue = trim((string)($app['emoji'] ?? ''));
            ?>
            <a href="<?= $appUrl ?>"
               class="app-card flex flex-col items-center gap-2.5 p-3.5 pt-5 text-center">
                <div class="emoji-wrap">
                    <span class="text-2xl leading-none select-none"><?= $appEmojiValue !== '' ? htmlspecialchars($appEmojiValue) : appEmoji($appIcon) ?></span>
                </div>
                <span class="text-xs font-semibold text-white/65 leading-tight pb-1"><?= $appName ?></span>
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
    setInterval(tick, 60000);
</script>

</body>
</html>
