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
$apps = $config['portal']['apps'] ?? [
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

$firstName = $user['firstName'] ?? explode(' ', $user['name'])[0];

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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            font-family: 'Inter', sans-serif;
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
        }
        .app-card:hover {
            transform: translateY(-6px) scale(1.03);
            background: var(--surface-hov);
            border-color: var(--border-hov);
            box-shadow: 0 16px 40px rgba(0,0,0,.45), 0 0 0 1px rgba(255,255,255,.08);
        }
        .app-card:active {
            transform: translateY(-2px) scale(1.01);
        }

        /* ── search ─────────────────────────────────────────────────── */
        .search-shell {
            border: 1px solid rgba(255,255,255,.12);
            background: rgba(255,255,255,.04);
            border-radius: 14px;
            transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
        }
        .search-shell:focus-within {
            border-color: rgba(107,143,255,.55);
            background: rgba(255,255,255,.07);
            box-shadow: 0 0 0 3px rgba(52,84,209,.35);
        }
        .search-input:focus { outline: none; }
        .quick-chip {
            border: 1px solid rgba(255,255,255,.12);
            background: rgba(255,255,255,.05);
            border-radius: 10px;
            padding: 6px 9px;
            color: rgba(255,255,255,.65);
            font-size: .72rem;
            font-weight: 600;
            transition: all .15s ease;
        }
        .quick-chip:hover {
            color: #fff;
            background: rgba(255,255,255,.12);
            transform: translateY(-1px);
        }

        /* ── horloge ────────────────────────────────────────────────── */
        #clock { font-variant-numeric: tabular-nums; letter-spacing: -.02em; }

        /* ── section label ──────────────────────────────────────────── */
        .section-label {
            font-size: .7rem;
            font-weight: 600;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: rgba(255,255,255,.35);
        }

        /* ── divider ────────────────────────────────────────────────── */
        .divider { border-color: var(--border); }

        /* ── fade in cascade ────────────────────────────────────────── */
        @keyframes fadeUp { from { opacity:0; transform:translateY(14px); } to { opacity:1; transform:none; } }
        .fade-up { animation: fadeUp .45s ease both; }
        .d1 { animation-delay: .05s } .d2 { animation-delay:.12s } .d3 { animation-delay:.20s }
        .d4 { animation-delay: .28s } .d5 { animation-delay:.36s } .d6 { animation-delay:.44s }
        .d7 { animation-delay: .52s } .d8 { animation-delay:.60s } .d9 { animation-delay:.68s }
        .d10{ animation-delay: .76s } .d11{ animation-delay:.84s } .d12{ animation-delay:.92s }
    </style>
</head>
<body class="min-h-screen text-white relative">

<div class="bg-ambient"></div>

<?php include __DIR__ . '/_nav.php'; ?>

<main class="relative z-10 w-full max-w-6xl mx-auto px-4 sm:px-6 py-8 space-y-6">

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
    <section class="rounded-2xl border px-4 py-3 <?= $toneCls ?> fade-up d1">
        <p class="font-semibold text-sm"><?= $toneIcon ?> <?= htmlspecialchars($activeBanner['title'] ?? 'Annonce importante') ?></p>
        <p class="text-sm opacity-90 mt-0.5"><?= htmlspecialchars($activeBanner['message'] ?? '') ?></p>
    </section>
    <?php endif; ?>

    <!-- ══ HERO ═════════════════════════════════════════════════════════ -->
    <section class="glass rounded-3xl p-6 sm:p-8 fade-up d1">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">

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
                <div>
                    <p class="text-white/40 text-xs font-medium mb-0.5">Bonjour,</p>
                    <h1 class="text-xl sm:text-2xl font-bold text-white leading-tight"><?= htmlspecialchars($firstName) ?> 👋</h1>
                    <p class="text-white/40 text-xs mt-0.5"><?= htmlspecialchars($user['email']) ?></p>
                </div>
            </div>

            <!-- Horloge -->
            <div class="flex flex-col items-start sm:items-end gap-0.5 flex-shrink-0">
                <p id="clock" class="text-3xl sm:text-4xl font-bold tracking-tight text-white">--:--:--</p>
                <p id="date-display" class="text-white/40 text-xs capitalize"></p>
            </div>
        </div>

        <hr class="divider my-5">

        <!-- Recherche -->
        <form action="https://www.google.com/search" method="get" class="space-y-2.5">
            <div class="search-shell flex items-center gap-2 pl-3 pr-2 py-2">
                <span class="text-sm text-white/35 select-none">🔎</span>
                <input type="text" name="q" placeholder="Recherche rapide web, docs, erreurs, tickets..." autocomplete="off"
                       class="search-input flex-1 bg-transparent text-white placeholder-white/30 text-sm">
                <button type="button" onclick="this.closest('form').q.value=''; this.closest('form').q.focus();"
                        class="px-2.5 py-1.5 rounded-lg text-xs text-white/45 hover:text-white hover:bg-white/10 transition">
                    Effacer
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-brand hover:bg-brand-dk text-white text-sm font-semibold rounded-lg transition shadow-lg shadow-brand/20 whitespace-nowrap">
                    Rechercher
                </button>
            </div>
            <div class="flex flex-wrap gap-1.5">
                <button type="button" class="quick-chip" onclick="const f=this.closest('form');f.q.value='groupe speed cloud';f.submit();">🏠 Groupe</button>
                <button type="button" class="quick-chip" onclick="const f=this.closest('form');f.q.value='incident status';f.submit();">📡 Incident</button>
                <button type="button" class="quick-chip" onclick="const f=this.closest('form');f.q.value='documentation interne';f.submit();">📚 Docs</button>
                <button type="button" class="quick-chip" onclick="const f=this.closest('form');f.q.value='oauth error';f.submit();">🛠️ Support</button>
            </div>
        </form>
    </section>

    <!-- ══ ANNONCES À LA UNE ═════════════════════════════════════════════ -->
    <?php if (!empty($featured)): ?>
    <section class="fade-up d2">
        <p class="section-label mb-3">📌 &nbsp;À la une</p>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
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
            <div class="glass rounded-2xl p-4 hover:border-white/20 transition"
                 style="border-left: 3px solid <?= $accentColor ?>;">
                <div class="flex items-start gap-3">
                    <span class="text-lg select-none mt-0.5 flex-shrink-0"><?= $annEmoji ?></span>
                    <div class="min-w-0 flex-1">
                        <?php if ($annTitle): ?>
                        <p class="font-semibold text-sm text-white mb-1 leading-snug"><?= $annTitle ?></p>
                        <?php endif; ?>
                        <div class="text-white/55 text-xs leading-relaxed line-clamp-3"><?= strip_tags($annHtml) ?></div>
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

    <!-- ══ APPLICATIONS ═════════════════════════════════════════════════ -->
    <section class="fade-up d3">
        <p class="section-label mb-3">Applications</p>
        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-3">
            <?php foreach ($apps as $i => $app):
                $appName = htmlspecialchars($app['name'] ?? '');
                $appUrl  = htmlspecialchars($app['url']  ?? '#');
                $appIcon = $app['icon'] ?? 'default';
                $delay   = 'd' . min($i + 1, 12);
            ?>
            <a href="<?= $appUrl ?>"
               class="app-card glass fade-up <?= $delay ?> rounded-2xl p-4 flex flex-col items-center gap-2.5">
                <div class="w-10 h-10 flex items-center justify-center">
                    <span class="text-3xl leading-none select-none"><?= appEmoji($appIcon) ?></span>
                </div>
                <span class="text-xs font-medium text-white/65 text-center leading-tight"><?= $appName ?></span>
            </a>
            <?php endforeach; ?>
        </div>
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
            String(n.getSeconds()).padStart(2,'0'),
        ].join(':');
        dateEl.textContent = `${JOURS[n.getDay()]} ${n.getDate()} ${MOIS[n.getMonth()]} ${n.getFullYear()}`;
    }
    tick();
    setInterval(tick, 1000);
</script>

</body>
</html>
