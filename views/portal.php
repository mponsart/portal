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
                        'brand':     '#3454d1',
                        'brand-dk':  '#2440a8',
                        'brand-lt':  '#6b8fff',
                        'accent':    '#0ea5e9',
                    },                },
            },
        };
    </script>
    <link rel="icon" type="image/png" href="/assets/images/cloudy.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Titillium+Web:wght@400;600;700&display=swap" rel="stylesheet">
    <?php include __DIR__ . '/_ui-tokens.php'; ?>
    <style>
        :root {
            --bg:          #06080f;
            --surface:     rgba(255,255,255,.055);
            --surface-hov: rgba(255,255,255,.10);
            --border:      rgba(255,255,255,.10);
            --border-hov:  rgba(255,255,255,.22);
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Titillium Web', sans-serif;
            background: var(--bg);
            overflow-x: hidden;
        }

        /* ── fond ambiant ───────────────────────────────────────────── */
        .bg-ambient {
            position: fixed; inset: 0; pointer-events: none; z-index: 0;
            background:
                radial-gradient(ellipse 70% 55% at 15%  0%,  rgba(52,84,209,.30) 0%, transparent 65%),
                radial-gradient(ellipse 55% 45% at 88% 100%, rgba(14,165,233,.20) 0%, transparent 60%),
                radial-gradient(ellipse 40% 35% at 50%  50%, rgba(52,84,209,.06) 0%, transparent 80%);
        }

        /* ── glassmorphism ──────────────────────────────────────────── */
        .glass {
            background: var(--surface);
            backdrop-filter: blur(16px) saturate(160%);
            -webkit-backdrop-filter: blur(16px) saturate(160%);
            border: 1px solid var(--border);
        }

        /* ── app cards ──────────────────────────────────────────────── */
        .app-card {
            transition: transform .16s cubic-bezier(.34,1.56,.64,1),
                        background .14s ease,
                        box-shadow .14s ease;
            cursor: pointer;
            min-height: 104px;
        }
        .app-card:hover {
            transform: translateY(-2px);
            background: var(--surface-hov);
            border-color: var(--border-hov);
            box-shadow: 0 8px 20px rgba(0,0,0,.22);
        }
        .app-card:active {
            transform: translateY(-1px);
        }

        /* ── search ─────────────────────────────────────────────────── */
        .search-shell {
            border: 1px solid rgba(255,255,255,.12);
            background: rgba(255,255,255,.03);
            border-radius: 14px;
            transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
        }
        .search-shell:focus-within {
            border-color: rgba(107,143,255,.55);
            background: rgba(255,255,255,.07);
            box-shadow: 0 0 0 3px rgba(52,84,209,.35);
        }
        .search-input:focus { outline: none; }
        .google-logo {
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: .01em;
            line-height: 1;
            user-select: none;
        }
        .google-logo .b { color:#4285F4; }
        .google-logo .r { color:#DB4437; }
        .google-logo .y { color:#F4B400; }
        .google-logo .g { color:#0F9D58; }

        /* ── horloge ────────────────────────────────────────────────── */
        #clock { font-variant-numeric: tabular-nums; letter-spacing: -.02em; }

        /* ── section label ──────────────────────────────────────────── */
        .section-label {
            font-size: .7rem;
            font-weight: 600;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: rgba(255,255,255,.45);
        }

        /* ── divider ────────────────────────────────────────────────── */
        .divider { border-color: var(--border); }

        .portal-shell { max-width: 1200px; }
        .hero-grid { display:grid; grid-template-columns:1fr; gap:.8rem; }
        .hero-panel { min-height: clamp(190px, 22vw, 260px); }
        .mini-kpi { border:1px solid rgba(255,255,255,.12); background:rgba(255,255,255,.04); }
        .featured-grid { grid-template-columns:1fr; }
        .app-grid { grid-template-columns:repeat(2, minmax(0,1fr)); }
        .panel-stack { display:flex; flex-direction:column; gap:.75rem; }
        @media (min-width: 640px) {
            .app-grid { grid-template-columns:repeat(3, minmax(0,1fr)); }
        }
        @media (min-width: 768px) {
            .hero-grid { grid-template-columns:1.35fr .95fr; }
            .featured-grid { grid-template-columns:repeat(2, minmax(0,1fr)); }
            .app-grid { grid-template-columns:repeat(4, minmax(0,1fr)); }
        }
        @media (min-width: 1024px) {
            .featured-grid { grid-template-columns:repeat(3, minmax(0,1fr)); }
            .app-grid { grid-template-columns:repeat(6, minmax(0,1fr)); }

        }

    </style>
</head>
<body class="min-h-screen text-white relative">

<div class="bg-ambient"></div>

<?php include __DIR__ . '/_nav.php'; ?>

<main class="page-stack portal-shell relative z-10 w-full mx-auto px-4 sm:px-6 py-6 sm:py-7">

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

    <!-- ══ HERO ═════════════════════════════════════════════════════════ -->
    <section class="hero-grid items-start">
        <article class="glass rounded-3xl p-4 sm:p-5">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-5">

            <!-- Profil -->
            <div class="flex items-center gap-4">
                <?php if (!empty($user['picture'])): ?>
                <div class="relative flex-shrink-0">
                    <img src="<?= htmlspecialchars($user['picture']) ?>"
                         alt="Photo de profil"
                         class="w-14 h-14 rounded-2xl border-2 border-white/15 shadow-lg">
                    <span class="absolute -bottom-1 -right-1 w-4 h-4 bg-emerald-400 rounded-full border-2 border-[#06080f]"></span>
                </div>
                <?php endif; ?>
                <div class="min-w-0">
                    <p class="text-white/40 text-xs font-medium mb-0.5">Bienvenue,</p>
                    <h1 class="text-lg sm:text-2xl font-bold text-white leading-tight"><?= htmlspecialchars($firstName) ?> 👋</h1>
                    <p class="text-white/40 text-xs mt-0.5">Accès rapide à vos applications.</p>
                </div>
            </div>

            <!-- Horloge -->
            <div class="flex flex-col items-start sm:items-end gap-0.5 flex-shrink-0">
                <p id="clock" class="text-3xl sm:text-4xl font-bold tracking-tight text-white">--:--:--</p>
                <p id="date-display" class="text-white/40 text-xs capitalize"></p>
            </div>
        </div>

        <hr class="divider my-3.5">

        <!-- Recherche -->
        <form action="https://www.google.com/search" method="get" target="_blank" rel="noopener" class="space-y-2.5">
            <input type="hidden" name="hl" value="fr">
            <input type="hidden" name="source" value="hp">
            <div class="search-shell p-2.5 sm:p-3">
                <div class="flex items-center gap-2.5">
                    <span class="google-logo" aria-hidden="true">
                        <span class="b">G</span><span class="r">o</span><span class="y">o</span><span class="b">g</span><span class="g">l</span><span class="r">e</span>
                    </span>
                    <input type="text" name="q" placeholder="Rechercher sur Google..." autocomplete="off"
                           class="search-input flex-1 bg-transparent text-white placeholder-white/30 text-sm">
                    <button type="submit"
                            class="px-4 py-2 bg-brand hover:bg-brand-dk text-white text-sm font-semibold rounded-lg transition shadow-lg shadow-brand/20 whitespace-nowrap">
                        Recherche Google
                    </button>
                </div>
            </div>
        </form>
        </article>

        <!-- ══ WIDGET INDISPONIBLES ════════════════════════════════════ -->
        <?php if (!empty($unavailableApps)): ?>
        <aside class="glass rounded-3xl p-4 self-start">
            <p class="section-label mb-3">⚠️ Services indisponibles</p>
            <ul class="space-y-2.5">
                <?php foreach ($unavailableApps as $ua):
                    $uaName   = htmlspecialchars($ua['name'] ?? '');
                    $uaIcon   = strtolower(trim((string)($ua['icon'] ?? 'link')));
                    $uaEmoji  = trim((string)($ua['emoji'] ?? ''));
                    $uaStatus = $ua['status'] ?? 'disabled';
                ?>
                <li class="flex items-center gap-2.5 py-1 border-b border-white/[0.06] last:border-0">
                    <span class="text-xl leading-none select-none flex-shrink-0"><?= $uaEmoji !== '' ? htmlspecialchars($uaEmoji) : appEmoji($uaIcon) ?></span>
                    <span class="flex-1 text-sm text-white/75 truncate font-medium"><?= $uaName ?></span>
                    <span class="flex-shrink-0 text-[10px] font-semibold px-2 py-0.5 rounded-full
                        <?= $uaStatus === 'maintenance' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-white/[0.07] text-white/35 border border-white/10' ?>">
                        <?= $uaStatus === 'maintenance' ? '🔧 Maintenance' : 'Désactivé' ?>
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
        </aside>
        <?php endif; ?>

    </section>

    <!-- ══ ANNONCES À LA UNE ═════════════════════════════════════════════ -->
    <div class="panel-stack">
    <?php if (!empty($featured)): ?>
    <section>
        <p class="section-label mb-2">A la une</p>
        <div class="featured-grid grid gap-3">
            <?php foreach ($featured as $i => $ann):
                $accentColor = htmlspecialchars($ann['color']    ?? '#3454d1');
                $annTitle    = htmlspecialchars($ann['title']    ?? '');
                $annHtml     = $ann['html_content'] ?? nl2br(htmlspecialchars($ann['content'] ?? ''));
                $annEmoji    = htmlspecialchars($ann['emoji']    ?? '📢');
                $annDate     = htmlspecialchars($ann['created_at'] ?? ($ann['pinned_at'] ?? ''));
                $annCat      = $ann['category'] ?? 'general';
                $catColors   = ['general'=>'#3454d1','urgent'=>'#ef4444','event'=>'#8b5cf6','info'=>'#0ea5e9'];
                $dotColor    = $catColors[$annCat] ?? '#3454d1';
            ?>
              <div class="glass rounded-2xl p-4 hover:border-white/20 transition min-h-[150px]"
                 style="border-left: 3px solid <?= $accentColor ?>;">
                <div class="flex items-start gap-3">
                    <span class="text-lg select-none mt-0.5 flex-shrink-0"><?= $annEmoji ?></span>
                    <div class="min-w-0 flex-1">
                        <?php if ($annTitle): ?>
                        <p class="font-semibold text-sm text-white mb-1 leading-snug"><?= $annTitle ?></p>
                        <?php endif; ?>
                        <div class="text-white/55 text-xs leading-relaxed line-clamp-2"><?= strip_tags($annHtml) ?></div>
                        <div class="flex items-center justify-between mt-2">
                            <?php if ($annDate): ?><p class="text-white/25 text-xs"><?= $annDate ?></p><?php endif; ?>
                            <a href="/article.php?id=<?= urlencode((string)($ann['id'] ?? '')) ?>" class="text-xs text-brand-lt hover:underline ml-auto">Lire l'article &rarr;</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

        <!-- ══ GOOGLE WORKSPACE ════════════════════════════════════════════════════════ -->
        <?php if (!empty($workspaceApps)): ?>
        <section>
            <p class="section-label mb-3">Suite Google Workspace</p>
            <div class="app-grid grid gap-3">
                <?php foreach ($workspaceApps as $i => $app):
                    $appName = htmlspecialchars($app['name'] ?? '');
                    $appUrl  = htmlspecialchars($app['url']  ?? '#');
                    $appIcon = $app['icon'] ?? 'default';
                    $appEmojiValue = trim((string)($app['emoji'] ?? ''));
                ?>
                <a href="<?= $appUrl ?>" class="app-card glass rounded-2xl p-3 flex flex-col items-center gap-1.5">
                    <div class="w-10 h-10 flex items-center justify-center">
                        <span class="text-3xl leading-none select-none"><?= $appEmojiValue !== '' ? htmlspecialchars($appEmojiValue) : appEmoji($appIcon) ?></span>
                    </div>
                    <span class="text-xs font-medium text-white/65 text-center leading-tight"><?= $appName ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- ══ APPLICATIONS ════════════════════════════════════════════ -->
        <section>
            <p class="section-label mb-3">Applications (<?= $portalCount ?>)</p>
            <?php if (empty($portalApps)): ?>
            <div class="glass rounded-2xl p-4 text-sm text-white/50">Aucune application hors Google Workspace.</div>
            <?php else: ?>
            <div class="app-grid grid gap-3">
                <?php foreach ($portalApps as $i => $app):
                    $appName = htmlspecialchars($app['name'] ?? '');
                    $appUrl  = htmlspecialchars($app['url']  ?? '#');
                    $appIcon = $app['icon'] ?? 'default';
                    $appEmojiValue = trim((string)($app['emoji'] ?? ''));
                ?>
                <a href="<?= $appUrl ?>" class="app-card glass rounded-2xl p-3 flex flex-col items-center gap-1.5">
                    <div class="w-10 h-10 flex items-center justify-center">
                        <span class="text-3xl leading-none select-none"><?= $appEmojiValue !== '' ? htmlspecialchars($appEmojiValue) : appEmoji($appIcon) ?></span>
                    </div>
                    <span class="text-xs font-medium text-white/65 text-center leading-tight"><?= $appName ?></span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>


    </div>

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
            String(n.getSeconds()).padStart(2,'0'),
        ].join(':');
        dateEl.textContent = `${JOURS[n.getDay()]} ${n.getDate()} ${MOIS[n.getMonth()]} ${n.getFullYear()}`;
    }
    tick();
    setInterval(tick, 1000);
</script>

</body>
</html>
