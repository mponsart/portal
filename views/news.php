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
    <link rel="icon" type="image/png" href="/assets/images/cloudy.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php include __DIR__ . '/_ui-tokens.php'; ?>
    <style>
        body { font-family:'Inter',sans-serif; background:var(--bg); }
        .bg-ambient {
            position:fixed; inset:0; pointer-events:none; z-index:0;
            background:
                radial-gradient(ellipse 65% 50% at 10%   5%,  rgba(124,58,237,.26) 0%, transparent 60%),
                radial-gradient(ellipse 50% 40% at 92%  95%,  rgba(8,145,178,.20)  0%, transparent 58%);
        }
        .panel {
            background:var(--surface);
            border:1px solid var(--border);
            border-radius:18px;
        }
        .sec-title { font-size:.65rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.38); }

        /* ── Article card ── */
        .news-card {
            background:var(--surface);
            border:1px solid var(--border);
            border-radius:16px;
            transition:border-color .14s,transform .14s,background .14s;
        }
        .news-card:hover {
            border-color:rgba(167,139,250,.3);
            background:var(--surface-hov);
            transform:translateY(-2px);
        }

        /* ── Rich text (excerpt) ── */
        .news-body p  { color:rgba(255,255,255,.62); font-size:.875rem; line-height:1.7; }

        /* ── Filters ── */
        .filter-btn { transition:all .14s; border-radius:999px; }
        .filter-btn.active { color:#a78bfa!important; border-color:rgba(124,58,237,.5)!important; background:rgba(124,58,237,.18)!important; }

        @keyframes fadeUp { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:none} }
        .fade-up { animation:fadeUp .35s ease both; }
    </style>
</head>
<body class="min-h-screen text-white relative">
<div class="bg-ambient"></div>

<?php include __DIR__ . '/_nav.php'; ?>

<main class="page-stack relative z-10 w-full max-w-4xl mx-auto px-4 sm:px-6 py-8">

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
    <div class="rounded-2xl border px-4 py-3 fade-up"
         style="background:<?= $bannerStyles['bg'] ?>;border-color:<?= $bannerStyles['border'] ?>;color:<?= $bannerStyles['text'] ?>;">
        <p class="font-semibold text-sm"><?= $toneIcon ?> <?= htmlspecialchars($activeBanner['title'] ?? 'Annonce') ?></p>
        <p class="text-sm opacity-85 mt-0.5"><?= htmlspecialchars($activeBanner['message'] ?? '') ?></p>
    </div>
    <?php endif; ?>

    <!-- Header -->
    <header class="panel p-5 sm:p-6 fade-up" style="animation-delay:.04s">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                 style="background:rgba(124,58,237,.2);border:1px solid rgba(124,58,237,.35);">
                <span class="text-xl">📰</span>
            </div>
            <div>
                <h1 class="text-xl font-bold text-white">Actualités</h1>
                <p class="text-white/40 text-xs mt-0.5">Annonces et informations de l'association.</p>
            </div>
        </div>
    </header>

    <!-- Filters -->
    <?php if (!empty($all)): ?>
    <div class="flex flex-wrap gap-2 fade-up" style="animation-delay:.09s">
        <button onclick="filterCat('all')" data-cat="all"
                class="filter-btn active px-3 py-1.5 text-xs font-semibold border text-white/60"
                style="background:rgba(255,255,255,.05);border-color:rgba(255,255,255,.10);">
            Tout (<?= count($all) ?>)
        </button>
        <?php foreach ($catLabels as $key => $label):
            $cnt = count(array_filter($all, fn($a) => ($a['category'] ?? 'general') === $key));
            if (!$cnt) continue;
            $badge = $catBadge[$key] ?? $catBadge['general'];
        ?>
        <button onclick="filterCat('<?= $key ?>')" data-cat="<?= $key ?>"
                class="filter-btn px-3 py-1.5 text-xs font-semibold border"
                style="background:<?= $badge['bg'] ?>;color:<?= $badge['color'] ?>;border-color:<?= $badge['color'] ?>40;">
            <?= $label ?> (<?= $cnt ?>)
        </button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- List -->
    <div id="newsList" class="space-y-3">
        <?php if (empty($all)): ?>
        <div class="panel p-10 text-center fade-up" style="animation-delay:.13s">
            <span class="text-4xl block mb-3">📭</span>
            <p class="text-white/38">Aucune actualité pour le moment.</p>
        </div>
        <?php else:
            foreach ($all as $i => $ann):
                $annId      = htmlspecialchars($ann['id'] ?? '');
                $annEmoji   = htmlspecialchars($ann['emoji'] ?? '📢');
                $annTitle   = htmlspecialchars($ann['title'] ?? '');
                $annHtml    = $ann['html_content'] ?? nl2br(htmlspecialchars($ann['content'] ?? ''));
                $annExcerpt = mb_substr(trim(strip_tags($annHtml)), 0, 220);
                $annColor   = htmlspecialchars($ann['color'] ?? '#7c3aed');
                $annCat     = $ann['category'] ?? 'general';
                $annDate    = htmlspecialchars($ann['created_at'] ?? ($ann['pinned_at'] ?? ''));
                $badge      = $catBadge[$annCat] ?? $catBadge['general'];
                $catLabel   = $catLabels[$annCat] ?? 'Général';
                $delay      = min($i * 0.05 + 0.13, 0.85);
        ?>
        <article id="art-<?= $annId ?>" data-cat="<?= htmlspecialchars($annCat) ?>"
                 class="news-card overflow-hidden fade-up"
                 style="border-left:3px solid <?= $annColor ?>;animation-delay:<?= $delay ?>s;">
            <div class="p-5 flex items-start gap-4">
                <span class="text-2xl select-none flex-shrink-0 mt-0.5"><?= $annEmoji ?></span>
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        <?php if ($annTitle): ?>
                        <p class="font-semibold text-white leading-snug"><?= $annTitle ?></p>
                        <?php endif; ?>
                        <span class="text-[11px] px-2 py-0.5 rounded-full font-medium"
                              style="background:<?= $badge['bg'] ?>;color:<?= $badge['color'] ?>;">
                            <?= $catLabel ?>
                        </span>
                    </div>
                    <?php if ($annDate): ?>
                    <p class="text-white/35 text-xs mb-2"><?= $annDate ?></p>
                    <?php endif; ?>
                    <?php if ($annExcerpt !== ''): ?>
                    <p class="text-white/55 text-sm leading-relaxed"><?= htmlspecialchars($annExcerpt) ?><?= mb_strlen(trim(strip_tags($annHtml))) > 220 ? '…' : '' ?></p>
                    <?php endif; ?>
                    <div class="mt-3">
                        <a href="/article.php?id=<?= urlencode((string)($ann['id'] ?? '')) ?>"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition"
                           style="background:rgba(124,58,237,.2);color:#a78bfa;border:1px solid rgba(124,58,237,.35);">
                            Lire l'article →
                        </a>
                    </div>
                </div>
            </div>
        </article>
        <?php endforeach; endif; ?>
    </div>

</main>

<script>
function filterCat(cat) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    document.querySelector(`[data-cat="${cat}"]`).classList.add('active');
    document.querySelectorAll('#newsList article').forEach(art => {
        art.style.display = (cat === 'all' || art.dataset.cat === cat) ? '' : 'none';
    });
}
</script>
</body>
</html>
