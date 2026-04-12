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
        .news-card { transition: border-color .15s, transform .15s; }
        .news-card:hover { border-color:rgba(255,255,255,.2);transform:translateY(-2px); }
    </style>
</head>
<body class="min-h-screen text-white relative">
<div class="bg-ambient"></div>

<?php include __DIR__ . '/_nav.php'; ?>

<main class="relative z-10 w-full max-w-4xl mx-auto px-4 sm:px-6 py-8 space-y-6">

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
            <!-- Header cliquable -->
            <button onclick="toggleNews('<?= $annId ?>')"
                    class="w-full text-left p-5 flex items-start gap-4">
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
                </div>
                <!-- Chevron -->
                <svg id="chev-<?= $annId ?>" class="w-5 h-5 text-white/30 flex-shrink-0 mt-1 transition-transform duration-200"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <!-- Corps dépliable -->
            <div id="body-<?= $annId ?>" class="hidden px-5 pb-5 pl-16">
                <div class="news-body"><?= $annHtml ?></div>
            </div>
        </article>
        <?php endforeach; endif; ?>
    </div>

</main>

<script>
// ── Accordéon ────────────────────────────────────────────────────────────────
function toggleNews(id) {
    const body = document.getElementById('body-' + id);
    const chev = document.getElementById('chev-' + id);
    const open = !body.classList.contains('hidden');
    body.classList.toggle('hidden', open);
    chev.style.transform = open ? '' : 'rotate(180deg)';
}

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
