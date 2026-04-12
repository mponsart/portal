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

// SVG inline des icônes de marque
function appIcon(string $icon): string {
    return match($icon) {
        'gmail' => '<svg viewBox="0 0 48 48" class="w-8 h-8"><path fill="#EA4335" d="M6 40h6V22.5L4 17v20a3 3 0 003 3z"/><path fill="#34A853" d="M36 40h6a3 3 0 003-3V17l-9 5.5V40z"/><path fill="#FBBC05" d="M36 8l-12 9L12 8H6v9l18 11L42 17V8h-6z"/><path fill="#4285F4" d="M6 8a3 3 0 00-3 3v6l9 5.5V8H6z"/><path fill="#C5221F" d="M42 8h-6v14.5L45 17v-6a3 3 0 00-3-3z"/></svg>',
        'drive' => '<svg viewBox="0 0 48 48" class="w-8 h-8"><path fill="#4285F4" d="M14.4 38l9-15.6H42l-9 15.6z"/><path fill="#34A853" d="M6 38l9-15.6L24 38H6z"/><path fill="#FBBC05" d="M15 10l9 15.6H6L15 10z"/><path fill="#EA4335" d="M33 10l9 15.6H24L33 10z"/><path fill="#1967D2" d="M24 25.4l9.6-15.6H14.4L24 25.4z"/></svg>',
        'calendar' => '<svg viewBox="0 0 48 48" class="w-8 h-8"><rect width="40" height="38" x="4" y="6" rx="4" fill="#fff"/><rect width="40" height="12" x="4" y="6" rx="4" fill="#1A73E8"/><rect width="40" height="2" x="4" y="16" fill="#1A73E8"/><circle cx="17" cy="31" r="3" fill="#EA4335"/></svg>',
        'meet' => '<svg viewBox="0 0 48 48" class="w-8 h-8"><path fill="#00832D" d="M6 14a4 4 0 014-4h18v12H6V14z"/><path fill="#0066DA" d="M28 10l14 9-14 9V10z"/><path fill="#00AC47" d="M6 22h22v12H10a4 4 0 01-4-4V22z"/></svg>',
        'docs' => '<svg viewBox="0 0 48 48" class="w-8 h-8"><rect width="28" height="38" x="10" y="5" rx="3" fill="#4285F4"/><rect width="28" height="38" x="10" y="5" rx="3" fill="#fff"/><path fill="#4285F4" d="M10 5h18l10 10H10z" opacity=".1"/><path fill="#1A73E8" d="M28 5l10 10H28V5z"/><rect width="18" height="2" x="14" y="22" rx="1" fill="#4285F4"/><rect width="18" height="2" x="14" y="27" rx="1" fill="#4285F4"/><rect width="12" height="2" x="14" y="32" rx="1" fill="#4285F4"/></svg>',
        'sheets' => '<svg viewBox="0 0 48 48" class="w-8 h-8"><rect width="28" height="38" x="10" y="5" rx="3" fill="#fff"/><path fill="#188038" d="M28 5l10 10H28V5z"/><rect width="28" height="38" x="10" y="5" rx="3" fill="none" stroke="#34A853" stroke-width="1"/><path fill="#34A853" d="M28 5l10 10H28V5z"/><rect width="6" height="2" x="14" y="21" rx="1" fill="#34A853"/><rect width="6" height="2" x="22" y="21" rx="1" fill="#34A853"/><rect width="6" height="2" x="30" y="21" rx="1" fill="#34A853"/><rect width="6" height="2" x="14" y="26" rx="1" fill="#34A853"/><rect width="6" height="2" x="22" y="26" rx="1" fill="#34A853"/><rect width="6" height="2" x="30" y="26" rx="1" fill="#34A853"/><rect width="6" height="2" x="14" y="31" rx="1" fill="#34A853"/><rect width="6" height="2" x="22" y="31" rx="1" fill="#34A853"/><rect width="6" height="2" x="30" y="31" rx="1" fill="#34A853"/></svg>',
        'slides' => '<svg viewBox="0 0 48 48" class="w-8 h-8"><rect width="36" height="28" x="6" y="10" rx="3" fill="#fff"/><rect width="36" height="28" x="6" y="10" rx="3" fill="none" stroke="#FBBC05" stroke-width="1"/><rect width="36" height="6" x="6" y="10" rx="3" fill="#FBBC05"/><rect width="16" height="2" x="14" y="24" rx="1" fill="#FBBC05"/><rect width="20" height="2" x="14" y="29" rx="1" fill="#FBBC05"/><rect width="10" height="6" x="19" y="36" rx="1" fill="#FBBC05"/></svg>',
        'youtube' => '<svg viewBox="0 0 48 48" class="w-8 h-8"><path fill="#FF0000" d="M43.2 13.6a5.4 5.4 0 00-3.8-3.8C36 9 24 9 24 9s-12 0-15.4.8a5.4 5.4 0 00-3.8 3.8C4 17 4 24 4 24s0 7 .8 10.4a5.4 5.4 0 003.8 3.8C12 39 24 39 24 39s12 0 15.4-.8a5.4 5.4 0 003.8-3.8C44 31 44 24 44 24s0-7-.8-10.4z"/><polygon fill="#fff" points="20,30 30,24 20,18"/></svg>',
        'discord' => '<svg viewBox="0 0 48 48" class="w-8 h-8"><path fill="#5865F2" d="M40.6 8.4A39.6 39.6 0 0031 5.6a27.7 27.7 0 00-1.2 2.5 36.6 36.6 0 00-11.5 0 27.7 27.7 0 00-1.2-2.5 39.5 39.5 0 00-9.6 2.8C2.5 18 1.2 27.3 1.9 36.5A40 40 0 0014 42.3a30.1 30.1 0 002.6-4.2 25.9 25.9 0 01-4.1-2 20.6 20.6 0 00.9-.7 28.5 28.5 0 0024.4 0 19.4 19.4 0 00.9.7 26 26 0 01-4.1 2 30.1 30.1 0 002.6 4.2A39.8 39.8 0 0046 36.5c.8-10.6-1.4-19.8-5.4-28.1zM16.4 31.2c-2.5 0-4.5-2.3-4.5-5.2s2-5.2 4.5-5.2 4.5 2.3 4.5 5.2-2 5.2-4.5 5.2zm15.2 0c-2.5 0-4.5-2.3-4.5-5.2s2-5.2 4.5-5.2 4.5 2.3 4.5 5.2-2 5.2-4.5 5.2z"/></svg>',
        'github' => '<svg viewBox="0 0 48 48" class="w-8 h-8"><path fill="#fff" d="M24 4C12.95 4 4 13.07 4 24.25c0 8.96 5.7 16.56 13.6 19.24.99.18 1.35-.44 1.35-.97v-3.4c-5.52 1.22-6.68-2.7-6.68-2.7-.9-2.33-2.2-2.95-2.2-2.95-1.8-1.25.14-1.22.14-1.22 2 .14 3.04 2.08 3.04 2.08 1.77 3.08 4.64 2.19 5.77 1.67.18-1.3.69-2.19 1.26-2.69-4.41-.51-9.04-2.24-9.04-9.97 0-2.2.77-4 2.04-5.41-.2-.51-.88-2.56.2-5.33 0 0 1.66-.54 5.44 2.07A18.67 18.67 0 0124 14.7c1.68.01 3.37.23 4.95.67 3.78-2.61 5.44-2.07 5.44-2.07 1.08 2.77.4 4.82.2 5.33 1.27 1.41 2.04 3.21 2.04 5.41 0 7.75-4.64 9.45-9.06 9.95.71.62 1.34 1.84 1.34 3.71v5.5c0 .53.36 1.16 1.37.96C38.3 40.8 44 33.2 44 24.25 44 13.07 35.05 4 24 4z"/></svg>',
        'notion' => '<svg viewBox="0 0 48 48" class="w-8 h-8"><rect width="40" height="40" x="4" y="4" rx="6" fill="#fff"/><path fill="#000" d="M13 12h15.5l8.5 8.5V36a2 2 0 01-2 2H13a2 2 0 01-2-2V14a2 2 0 012-2zm14 0v8h7"/><path fill="#000" d="M16 20h16M16 25h12M16 30h10" stroke="#000" stroke-width="1.5" stroke-linecap="round"/></svg>',
        'figma' => '<svg viewBox="0 0 48 48" class="w-8 h-8"><circle cx="30" cy="24" r="7" fill="#1ABCFE"/><path d="M16 37a7 7 0 007-7v-7h-7a7 7 0 000 14z" fill="#0ACF83"/><path d="M16 17h7V10h-7a7 7 0 000 7z" fill="#FF7262"/><path d="M23 10h7a7 7 0 010 14h-7V10z" fill="#F24E1E"/><path d="M23 24h7a7 7 0 010 14h-7V24z" fill="#A259FF"/></svg>',
        default  => '<span class="text-3xl">🔗</span>',
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
        .search-input:focus { box-shadow: 0 0 0 3px rgba(52,84,209,.45); }

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
        <form action="https://www.google.com/search" method="get" class="flex gap-2.5">
            <div class="relative flex-1">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-white/35" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                </svg>
                <input type="text" name="q" placeholder="Rechercher sur Google…" autocomplete="off"
                       class="search-input w-full pl-10 pr-4 py-2.5 rounded-xl bg-white/8 border border-white/12
                              text-white placeholder-white/30 text-sm focus:outline-none transition">
            </div>
            <button type="submit"
                    class="px-5 py-2.5 bg-brand hover:bg-brand-dk text-white text-sm font-semibold rounded-xl transition shadow-lg shadow-brand/20 whitespace-nowrap">
                Rechercher
            </button>
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
                            <a href="/news.php" class="text-xs text-brand-lt hover:underline ml-auto">Lire &rarr;</a>
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
                    <?= appIcon($appIcon) ?>
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
