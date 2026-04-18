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
    <link rel="icon" type="image/png" href="/assets/images/cloudy.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <?php include __DIR__ . '/_ui-tokens.php'; ?>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            overflow-x: hidden;
            color: var(--on-surface);
        }

        /* ── Hero Card ──────────────────────────────────────────────── */
        .hero-card {
            background: linear-gradient(145deg, rgba(79,55,139,.28) 0%, var(--surface-1) 60%);
            border-radius: var(--shape-xl);
            box-shadow: var(--elev-2);
            transition: box-shadow .2s;
        }

        /* ── App Card ───────────────────────────────────────────────── */
        .app-card {
            background: var(--surface-1);
            border-radius: var(--shape-lg);
            box-shadow: var(--elev-1);
            transition: transform .2s cubic-bezier(.34,1.56,.64,1),
                        box-shadow .2s ease,
                        background .14s ease;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .app-card:hover {
            transform: translateY(-4px);
            background: var(--surface-2);
            box-shadow: var(--elev-3);
        }
        .app-card:active { transform: translateY(-1px); box-shadow: var(--elev-1); }

        /* ── App Card — workspace (teal accent) ─────────────────────── */
        .app-card.ws:hover { box-shadow: 0 8px 24px rgba(0,79,80,.35), var(--elev-2); }

        /* ── Emoji wrap ─────────────────────────────────────────────── */
        .emoji-wrap {
            width: 52px; height: 52px;
            border-radius: var(--shape-md);
            display: flex; align-items: center; justify-content: center;
            background: rgba(208,188,255,.08);
            transition: transform .2s cubic-bezier(.34,1.56,.64,1), background .14s;
        }
        .app-card:hover .emoji-wrap {
            background: rgba(208,188,255,.14);
            transform: scale(1.08);
        }

        /* ── Search ─────────────────────────────────────────────────── */
        .search-wrap {
            background: var(--surface-2);
            border: 1px solid var(--outline-var);
            border-radius: var(--shape-full);
            transition: border-color .14s, box-shadow .14s, background .14s;
            overflow: hidden;
        }
        .search-wrap:focus-within {
            border-color: var(--primary);
            box-shadow: var(--focus-ring);
        }
        .search-wrap input:focus { outline: none !important; box-shadow: none !important; border: none !important; }

        /* ── Google coloured logo ───────────────────────────────────── */
        .g-logo { font-size: 1rem; font-weight: 800; line-height: 1; user-select: none; letter-spacing: -.01em; }
        .g-logo .gb { color:#4285F4; } .g-logo .gr { color:#EA4335; }
        .g-logo .gy { color:#FBBC05; } .g-logo .gg { color:#34A853; }

        /* ── Clock ──────────────────────────────────────────────────── */
        #clock {
            font-variant-numeric: tabular-nums;
            letter-spacing: -.04em;
            background: linear-gradient(135deg, var(--on-surface) 30%, var(--primary) 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* ── Section title ──────────────────────────────────────────── */
        .sec-title {
            font-size: .6875rem; font-weight: 500;
            letter-spacing: .06em; text-transform: uppercase;
            color: var(--on-surface-var);
        }
        .sec-dot {
            display: inline-block; width: 7px; height: 7px; border-radius: 50%;
            margin-right: .5rem; vertical-align: middle; position: relative; top: -1px;
        }

        /* ── Featured card ──────────────────────────────────────────── */
        .feat-card {
            background: var(--surface-1);
            border-radius: var(--shape-lg);
            box-shadow: var(--elev-1);
            transition: box-shadow .2s, transform .18s ease, background .14s;
        }
        .feat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--elev-3);
            background: var(--surface-2);
        }

        /* ── Workspace quick-link ───────────────────────────────────── */
        .ws-link {
            background: rgba(208,188,255,.04);
            border: 1px solid var(--outline-var);
            border-radius: var(--shape-md);
            transition: background .12s, border-color .12s;
            text-decoration: none;
        }
        .ws-link:hover {
            background: rgba(158,236,235,.08);
            border-color: rgba(158,236,235,.3);
        }

        /* ── Avatar status dot ──────────────────────────────────────── */
        .avatar-ring { box-shadow: 0 0 0 2px var(--bg), 0 0 0 3.5px var(--success); }
        @keyframes pulse-ok {
            0%,100% { box-shadow: 0 0 0 2px var(--bg), 0 0 0 3.5px var(--success); }
            50%     { box-shadow: 0 0 0 2px var(--bg), 0 0 0 5px rgba(109,213,140,0); }
        }
        .status-dot {
            background: var(--success);
            border: 2px solid var(--bg);
            animation: pulse-ok 2.8s ease infinite;
        }

        /* ── Search submit btn ──────────────────────────────────────── */
        .search-btn {
            background: var(--primary-cnt);
            color: var(--primary-cnt-on);
            border-radius: var(--shape-full);
            transition: background .14s, transform .1s, box-shadow .14s;
        }
        .search-btn:hover {
            box-shadow: var(--elev-2);
            transform: scale(1.02);
        }
        .search-btn:active { transform: scale(.96); }

        /* ── Page grid ──────────────────────────────────────────────── */
        .portal-shell { max-width: 1280px; }
        .hero-grid  { display: grid; grid-template-columns: 1fr; gap: 1rem; }
        .app-grid   { grid-template-columns: repeat(2, minmax(0,1fr)); }
        .feat-grid  { grid-template-columns: 1fr; }

        @media (min-width: 640px) {
            .app-grid { grid-template-columns: repeat(3, minmax(0,1fr)); }
        }
        @media (min-width: 768px) {
            .hero-grid  { grid-template-columns: 1.5fr 1fr; }
            .feat-grid  { grid-template-columns: repeat(2, minmax(0,1fr)); }
            .app-grid   { grid-template-columns: repeat(4, minmax(0,1fr)); }
        }
        @media (min-width: 1024px) {
            .feat-grid { grid-template-columns: repeat(3, minmax(0,1fr)); }
            .app-grid  { grid-template-columns: repeat(6, minmax(0,1fr)); }
        }

        /* ── Entry animations ───────────────────────────────────────── */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: none; }
        }
        main > * { animation: fadeUp .4s cubic-bezier(.22,1,.36,1) both; }
        main > *:nth-child(1) { animation-delay: .04s; }
        main > *:nth-child(2) { animation-delay: .10s; }
        main > *:nth-child(3) { animation-delay: .16s; }
        main > *:nth-child(4) { animation-delay: .22s; }
        main > *:nth-child(5) { animation-delay: .28s; }

        /* ── Banner ─────────────────────────────────────────────────── */
        .md-banner {
            border-radius: var(--shape-lg);
            border-left: 4px solid;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 16px;
        }
    </style>
</head>
<body class="min-h-screen relative">

<div class="bg-ambient" aria-hidden="true"></div>

<?php include __DIR__ . '/_nav.php'; ?>

<main class="page-stack portal-shell relative z-10 w-full mx-auto px-4 sm:px-6 py-7 sm:py-10">

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
            <p class="text-sm opacity-80 mt-0.5"><?= htmlspecialchars($activeBanner['message'] ?? '') ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- ══ HERO GRID ══════════════════════════════════════════════════ -->
    <section class="hero-grid items-stretch">

        <!-- Greeting + Search -->
        <article class="hero-card p-6 sm:p-7 flex flex-col gap-5">

            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-5">

                <!-- Avatar + name -->
                <div class="flex items-center gap-4">
                    <?php if (!empty($user['picture'])): ?>
                    <div class="relative flex-shrink-0">
                        <img src="<?= htmlspecialchars($user['picture']) ?>"
                             alt="Photo de profil"
                             class="w-14 h-14 rounded-2xl object-cover avatar-ring">
                        <span class="status-dot absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full"
                              aria-hidden="true"></span>
                    </div>
                    <?php endif; ?>
                    <div>
                        <p class="sec-title mb-1">Bonjour,</p>
                        <h1 class="text-2xl sm:text-3xl font-extrabold leading-tight tracking-tight" style="color:var(--on-surface);">
                            <?= htmlspecialchars($firstName) ?>&thinsp;<span aria-hidden="true">👋</span>
                        </h1>
                        <p id="date-display" class="text-xs mt-1.5 capitalize font-medium" style="color:var(--on-surface-var);"></p>
                    </div>
                </div>

                <!-- Clock -->
                <div class="text-right flex-shrink-0">
                    <p id="clock" class="text-4xl sm:text-5xl font-black" aria-live="polite" aria-label="Heure actuelle">--:--</p>
                    <p class="sec-title mt-1.5">Heure locale</p>
                </div>
            </div>

            <!-- Divider -->
            <hr class="md-divider">

            <!-- Google search -->
            <form action="https://www.google.com/search" method="get" target="_blank" rel="noopener noreferrer" role="search" aria-label="Recherche Google">
                <input type="hidden" name="hl" value="fr">
                <div class="search-wrap flex items-center gap-3 px-4 py-2.5">
                    <span class="g-logo flex-shrink-0" aria-label="Google">
                        <span class="gb">G</span><span class="gr">o</span><span class="gy">o</span><span class="gb">g</span><span class="gg">l</span><span class="gr">e</span>
                    </span>
                    <input type="text" name="q" placeholder="Rechercher sur Google…" autocomplete="off"
                           aria-label="Terme de recherche"
                           class="flex-1 bg-transparent text-sm"
                           style="color:var(--on-surface);border:none!important;box-shadow:none!important;min-height:unset;height:auto;border-radius:0;">
                    <button type="submit"
                            class="search-btn flex-shrink-0 px-4 py-2 text-xs font-medium"
                            style="min-height:unset;">
                        Rechercher
                    </button>
                </div>
            </form>

        </article>

        <!-- Side panel -->
        <?php if (!empty($unavailableApps)): ?>
        <aside class="panel p-5 self-start">
            <p class="sec-title mb-4"><span class="sec-dot" style="background:var(--warning);"></span>Services indisponibles</p>
            <ul class="space-y-1" role="list">
                <?php foreach ($unavailableApps as $ua):
                    $uaName   = htmlspecialchars($ua['name'] ?? '');
                    $uaIcon   = strtolower(trim((string)($ua['icon'] ?? 'link')));
                    $uaEmoji  = trim((string)($ua['emoji'] ?? ''));
                    $uaStatus = $ua['status'] ?? 'disabled';
                ?>
                <li class="md-list-item" style="border-bottom:1px solid var(--outline-var);">
                    <span class="text-xl leading-none select-none flex-shrink-0" aria-hidden="true"><?= $uaEmoji !== '' ? htmlspecialchars($uaEmoji) : appEmoji($uaIcon) ?></span>
                    <span class="flex-1 text-sm truncate font-medium" style="color:var(--on-surface);"><?= $uaName ?></span>
                    <span class="flex-shrink-0 text-[10px] font-medium px-2.5 py-1 rounded-full"
                          style="<?= $uaStatus === 'maintenance'
                                    ? 'background:var(--warning-cnt);color:var(--warning);'
                                    : 'background:rgba(255,255,255,.07);color:var(--on-surface-var);' ?>">
                        <?= $uaStatus === 'maintenance' ? '🔧 Maintenance' : 'Désactivé' ?>
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
        </aside>
        <?php elseif (!empty($workspaceApps)): ?>
        <aside class="panel p-5 self-start flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <p class="sec-title"><span class="sec-dot" style="background:var(--tertiary);"></span>Workspace</p>
                <span class="text-[10px] font-medium px-2.5 py-1 rounded-full" style="background:rgba(208,188,255,.08);color:var(--on-surface-var);"><?= count($workspaceApps) ?> apps</span>
            </div>
            <div class="space-y-1" role="list">
                <?php foreach (array_slice($workspaceApps, 0, 5) as $wa):
                    $wIcon  = $wa['icon'] ?? 'default';
                    $wEmoji = trim((string)($wa['emoji'] ?? ''));
                    $wName  = htmlspecialchars($wa['name'] ?? '');
                    $wUrl   = htmlspecialchars($wa['url'] ?? '#');
                ?>
                <a href="<?= $wUrl ?>" class="ws-link md-list-item block" role="listitem">
                    <div class="flex items-center gap-3">
                        <span class="text-xl leading-none" aria-hidden="true"><?= $wEmoji !== '' ? htmlspecialchars($wEmoji) : appEmoji($wIcon) ?></span>
                        <span class="text-sm font-medium flex-1" style="color:var(--on-surface);"><?= $wName ?></span>
                        <span class="text-xs" style="color:var(--on-surface-var);" aria-hidden="true">↗</span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php if ($portalCount): ?>
            <div class="pt-2" style="border-top:1px solid var(--outline-var);">
                <p class="text-[11px] font-medium" style="color:var(--on-surface-var);"><?= $portalCount ?> outil<?= $portalCount > 1 ? 's' : '' ?> configuré<?= $portalCount > 1 ? 's' : '' ?></p>
            </div>
            <?php endif; ?>
        </aside>
        <?php endif; ?>

    </section>

    <!-- ══ FEATURED ANNOUNCEMENTS ═════════════════════════════════════ -->
    <?php if (!empty($featured)): ?>
    <section aria-labelledby="featured-heading">
        <p id="featured-heading" class="sec-title mb-4"><span class="sec-dot" style="background:var(--primary-lt);"></span>À la une</p>
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
                    <span class="text-xl select-none mt-0.5 flex-shrink-0" aria-hidden="true"><?= $annEmoji ?></span>
                    <div class="min-w-0 flex-1">
                        <?php if ($annTitle): ?>
                        <p class="font-semibold text-sm mb-1.5 leading-snug" style="color:var(--on-surface);"><?= $annTitle ?></p>
                        <?php endif; ?>
                        <p class="text-xs leading-relaxed line-clamp-2" style="color:var(--on-surface-var);"><?= strip_tags($annHtml) ?></p>
                        <div class="flex items-center justify-between mt-3">
                            <?php if ($annDate): ?><span class="text-[11px]" style="color:var(--outline);"><?= $annDate ?></span><?php endif; ?>
                            <a href="/article.php?id=<?= urlencode((string)($ann['id'] ?? '')) ?>"
                               class="text-xs font-medium ml-auto transition-opacity hover:opacity-75"
                               style="color:var(--primary);">
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
    <section aria-labelledby="workspace-heading">
        <p id="workspace-heading" class="sec-title mb-4"><span class="sec-dot" style="background:var(--tertiary);"></span>Suite Google Workspace</p>
        <div class="app-grid grid gap-3">
            <?php foreach ($workspaceApps as $app):
                $appName       = htmlspecialchars($app['name'] ?? '');
                $appUrl        = htmlspecialchars($app['url'] ?? '#');
                $appIcon       = $app['icon'] ?? 'default';
                $appEmojiValue = trim((string)($app['emoji'] ?? ''));
            ?>
            <a href="<?= $appUrl ?>"
               class="app-card ws gap-2.5 p-3.5 pt-5 text-center"
               aria-label="<?= $appName ?>">
                <div class="emoji-wrap" aria-hidden="true">
                    <span class="text-2xl leading-none select-none"><?= $appEmojiValue !== '' ? htmlspecialchars($appEmojiValue) : appEmoji($appIcon) ?></span>
                </div>
                <span class="text-xs font-medium leading-tight pb-1" style="color:var(--on-surface-var);"><?= $appName ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ══ APPLICATIONS ═══════════════════════════════════════════════ -->
    <section aria-labelledby="apps-heading">
        <p id="apps-heading" class="sec-title mb-4">
            <span class="sec-dot" style="background:var(--primary);"></span>
            Applications<?php if ($portalCount): ?> <span class="normal-case tracking-normal font-medium" style="color:var(--outline);">(<?= $portalCount ?>)</span><?php endif; ?>
        </p>
        <?php if (empty($portalApps)): ?>
        <div class="panel p-8 text-sm text-center" style="color:var(--on-surface-var);">Aucune application configurée.</div>
        <?php else: ?>
        <div class="app-grid grid gap-3">
            <?php foreach ($portalApps as $app):
                $appName       = htmlspecialchars($app['name'] ?? '');
                $appUrl        = htmlspecialchars($app['url'] ?? '#');
                $appIcon       = $app['icon'] ?? 'default';
                $appEmojiValue = trim((string)($app['emoji'] ?? ''));
            ?>
            <a href="<?= $appUrl ?>"
               class="app-card gap-2.5 p-3.5 pt-5 text-center"
               aria-label="<?= $appName ?>">
                <div class="emoji-wrap" aria-hidden="true">
                    <span class="text-2xl leading-none select-none"><?= $appEmojiValue !== '' ? htmlspecialchars($appEmojiValue) : appEmoji($appIcon) ?></span>
                </div>
                <span class="text-xs font-medium leading-tight pb-1" style="color:var(--on-surface-var);"><?= $appName ?></span>
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
