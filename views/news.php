<?php
$user    = $_SESSION['user'];
$config  = require __DIR__ . '/../config.php';
$isAdmin = in_array($user['email'], $config['admins'] ?? []);

$currentPage  = 'news';
$featuredFile = __DIR__ . '/../uploads/featured.json';

$all = [];
if (file_exists($featuredFile)) {
    $decoded = json_decode(file_get_contents($featuredFile), true);
    $all     = is_array($decoded) ? array_reverse($decoded) : [];
}
$all = array_values(array_filter($all, fn($a) => ($a['status'] ?? 'published') === 'published'));

$bannerFile = __DIR__ . '/../uploads/banners.json';
$activeBanner = null;
if (file_exists($bannerFile)) {
    $decoded = json_decode(file_get_contents($bannerFile), true);
    $banners = is_array($decoded) ? $decoded : [];
    foreach (array_reverse($banners) as $b) {
        if (!empty($b['active'])) { $activeBanner = $b; break; }
    }
}

$catLabels = ['general' => 'Général', 'event' => 'Événement', 'urgent' => 'Urgent', 'info' => 'Info'];
$catBadge  = [
    'general' => ['bg' => 'rgba(52,84,209,.25)',  'color' => '#6b8fff'],
    'urgent'  => ['bg' => 'rgba(239,68,68,.25)',   'color' => '#f87171'],
    'event'   => ['bg' => 'rgba(139,92,246,.25)',  'color' => '#c4b5fd'],
    'info'    => ['bg' => 'rgba(14,165,233,.25)',  'color' => '#38bdf8'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualités — Groupe Speed Cloud</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { brand:'#3454d1','brand-dk':'#2440a8' } } }
        };
    </script>
    <link rel="icon" type="image/png" href="/assets/images/cloudy.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { font-family:'Inter',sans-serif; background:#06080f; }
        .bg-ambient { position:fixed;inset:0;pointer-events:none;z-index:0;
            background: radial-gradient(ellipse 70% 55% at 15% 0%,  rgba(52,84,209,.28) 0%,transparent 65%),
                        radial-gradient(ellipse 50% 40% at 88% 100%,rgba(14,165,233,.18) 0%,transparent 60%); }
        .glass { background:rgba(255,255,255,.055);backdrop-filter:blur(16px) saturate(160%);
                 -webkit-backdrop-filter:blur(16px) saturate(160%);border:1px solid rgba(255,255,255,.10); }
        .section-label { font-size:.7rem;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:rgba(255,255,255,.35); }
        @keyframes fadeUp { from{opacity:0;transform:translateY(12px)} to{opacity:1;transform:none} }
        .fade-up { animation:fadeUp .4s ease both; }

        /* Rendu du contenu riche */
        .news-body h2 { font-size:1.1rem;font-weight:700;color:#fff;margin:1rem 0 .4rem; }
        .news-body h3 { font-size:.95rem;font-weight:600;color:#e2e8f0;margin:.8rem 0 .3rem; }
        .news-body p  { color:rgba(255,255,255,.65);font-size:.875rem;line-height:1.7;margin:.35rem 0; }
        .news-body strong { color:#fff;font-weight:600; }
        .news-body em { font-style:italic;color:rgba(255,255,255,.7); }
        .news-body u  { text-decoration:underline; }
        .news-body s  { text-decoration:line-through;color:rgba(255,255,255,.4); }
        .news-body ul { list-style:disc;padding-left:1.2rem;color:rgba(255,255,255,.65);font-size:.875rem;margin:.35rem 0; }
        .news-body ol { list-style:decimal;padding-left:1.2rem;color:rgba(255,255,255,.65);font-size:.875rem;margin:.35rem 0; }
        .news-body li { margin:.2rem 0; }
        .news-body blockquote { border-left:3px solid #3454d1;padding:.4rem .8rem;margin:.6rem 0;background:rgba(52,84,209,.1);border-radius:0 8px 8px 0;color:rgba(255,255,255,.6);font-size:.875rem; }
        .news-body a  { color:#6b8fff;text-decoration:underline; }

        /* Filtres catégorie */
        .filter-btn { transition: all .15s; }
        .filter-btn.active { background:rgba(52,84,209,.35)!important;border-color:rgba(52,84,209,.7)!important;color:#fff!important; }

        /* Card */
        .news-card { transition: border-color .15s, transform .15s, background .15s; }
        .news-card:hover { border-color:rgba(255,255,255,.2);transform:translateY(-2px);background:rgba(255,255,255,.07); }
        .news-excerpt { color:rgba(255,255,255,.62);font-size:.875rem;line-height:1.7; }
    </style>
</head>
<body class="min-h-screen text-white relative">
<div class="bg-ambient"></div>

<?php include __DIR__ . '/_nav.php'; ?>

<main class="relative z-10 w-full max-w-4xl mx-auto px-4 sm:px-6 py-8 space-y-6">

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
    <section class="rounded-2xl border px-4 py-3 <?= $toneCls ?> fade-up" style="animation-delay:.03s;">
        <p class="font-semibold text-sm"><?= $toneIcon ?> <?= htmlspecialchars($activeBanner['title'] ?? 'Annonce importante') ?></p>
        <p class="text-sm opacity-90 mt-0.5"><?= htmlspecialchars($activeBanner['message'] ?? '') ?></p>
    </section>
    <?php endif; ?>

    <!-- En-tête -->
    <div class="fade-up" style="animation-delay:.05s">
        <h1 class="text-2xl font-bold text-white mb-1">📰 Actualités</h1>
        <p class="text-white/40 text-sm">Toutes les annonces et informations de l'association.</p>
    </div>

    <!-- Filtres -->
    <?php if (!empty($all)): ?>
    <div class="flex flex-wrap gap-2 fade-up" style="animation-delay:.10s">
        <button onclick="filterCat('all')" data-cat="all"
                class="filter-btn active px-3 py-1.5 rounded-xl text-xs font-medium text-white/70 glass border border-white/10">
            Tout (<?= count($all) ?>)
        </button>
        <?php foreach ($catLabels as $key => $label):
            $cnt = count(array_filter($all, fn($a) => ($a['category'] ?? 'general') === $key));
            if (!$cnt) continue;
            $badge = $catBadge[$key] ?? $catBadge['general'];
        ?>
        <button onclick="filterCat('<?= $key ?>')" data-cat="<?= $key ?>"
                class="filter-btn px-3 py-1.5 rounded-xl text-xs font-medium border"
                style="background:<?= $badge['bg'] ?>;color:<?= $badge['color'] ?>;border-color:<?= $badge['color'] ?>40;">
            <?= $label ?> (<?= $cnt ?>)
        </button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Liste des actualités -->
    <div id="newsList" class="space-y-4">
        <?php if (empty($all)): ?>
        <div class="glass rounded-2xl p-10 text-center fade-up" style="animation-delay:.15s">
            <span class="text-4xl">📭</span>
            <p class="text-white/40 mt-3">Aucune actualité pour le moment.</p>
        </div>
        <?php else:
            foreach ($all as $i => $ann):
                $annId      = htmlspecialchars($ann['id']             ?? '');
                $annEmoji   = htmlspecialchars($ann['emoji']          ?? '📢');
                $annTitle   = htmlspecialchars($ann['title']          ?? '');
                $annHtml    = $ann['html_content'] ?? nl2br(htmlspecialchars($ann['content'] ?? ''));
            $annExcerpt = mb_substr(trim(strip_tags($annHtml)), 0, 220);
                $annColor   = htmlspecialchars($ann['color']          ?? '#3454d1');
                $annCat     = $ann['category'] ?? 'general';
                $annDate    = htmlspecialchars($ann['created_at'] ?? ($ann['pinned_at'] ?? ''));
                $annUpdated = htmlspecialchars($ann['updated_at']     ?? '');
                $badge      = $catBadge[$annCat]  ?? $catBadge['general'];
                $catLabel   = $catLabels[$annCat] ?? 'Général';
                $delay      = min($i * 0.06 + 0.15, 0.9);
        ?>
        <article id="art-<?= $annId ?>" data-cat="<?= htmlspecialchars($annCat) ?>"
                 class="news-card glass rounded-2xl overflow-hidden fade-up"
                 style="border-left:3px solid <?= $annColor ?>;animation-delay:<?= $delay ?>s;">
            <div class="p-5 flex items-start gap-4">
                <span class="text-2xl select-none flex-shrink-0 mt-0.5"><?= $annEmoji ?></span>
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        <?php if ($annTitle): ?>
                        <p class="font-semibold text-white leading-snug"><?= $annTitle ?></p>
                        <?php endif; ?>
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                              style="background:<?= $badge['bg'] ?>;color:<?= $badge['color'] ?>;">
                            <?= $catLabel ?>
                        </span>
                    </div>
                    <p class="text-white/40 text-xs">
                        <?= $annDate ?>
                        <?= ($annUpdated && $annUpdated !== $annDate) ? ' · Modifié le ' . $annUpdated : '' ?>
                    </p>
                    <?php if ($annExcerpt !== ''): ?>
                    <p class="news-excerpt mt-3"><?= htmlspecialchars($annExcerpt) ?><?= mb_strlen(trim(strip_tags($annHtml))) > 220 ? '…' : '' ?></p>
                    <?php endif; ?>
                    <div class="mt-4">
                        <a href="/article.php?id=<?= urlencode((string)($ann['id'] ?? '')) ?>"
                           class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-brand/25 hover:bg-brand/35 text-brand-lt text-xs font-semibold border border-brand/35 transition">
                            <span>📖</span>
                            Lire l'article
                        </a>
                    </div>
                </div>
            </div>
        </article>
        <?php endforeach; endif; ?>
    </div>

</main>

<script>
// ── Filtre catégorie ─────────────────────────────────────────────────────────
function filterCat(cat) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    document.querySelector(`[data-cat="${cat}"]`).classList.add('active');

    document.querySelectorAll('#newsList article').forEach(art => {
        const show = cat === 'all' || art.dataset.cat === cat;
        art.style.display = show ? '' : 'none';
    });
}
</script>

</body>
</html>
