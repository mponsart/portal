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
        body { font-family:'Inter',sans-serif; background:var(--bg); color:var(--on-surface); }

        /* ── Article card ── */
        .news-card {
            background: var(--surface-1);
            border-radius: var(--shape-xl);
            box-shadow: var(--elev-1);
            transition: box-shadow .2s, transform .18s cubic-bezier(.22,1,.36,1), background .14s;
        }
        .news-card:hover {
            box-shadow: var(--elev-3);
            transform: translateY(-2px);
            background: var(--surface-2);
        }

        /* ── Rich text ── */
        .news-body p { color: var(--on-surface-var); font-size: .875rem; line-height: 1.7; }

        /* ── Filter chips row ── */
        .chips-row { display: flex; flex-wrap: wrap; gap: 8px; }

        @keyframes fadeUp { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:none; } }
        .fade-up { animation: fadeUp .38s cubic-bezier(.22,1,.36,1) both; }

        /* ── Banner ── */
        .md-banner { border-radius:var(--shape-lg); border-left:4px solid; display:flex; align-items:flex-start; gap:12px; padding:14px 16px; }

        /* ── Sec title ── */
        .sec-title { font-size:.6875rem; font-weight:500; letter-spacing:.06em; text-transform:uppercase; color:var(--on-surface-var); }
    </style>
</head>
<body class="min-h-screen relative">
<div class="bg-ambient" aria-hidden="true"></div>

<?php include __DIR__ . '/_nav.php'; ?>

<main class="page-stack relative z-10 w-full max-w-4xl mx-auto px-4 sm:px-6 py-8">

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
    <div class="md-banner fade-up"
         style="background:<?= $bannerStyles['bg'] ?>;border-color:<?= $bannerStyles['border'] ?>;color:<?= $bannerStyles['text'] ?>;" role="alert">
        <span class="text-lg flex-shrink-0 mt-px" aria-hidden="true"><?= $toneIcon ?></span>
        <div>
            <p class="font-semibold text-sm"><?= htmlspecialchars($activeBanner['title'] ?? 'Annonce') ?></p>
            <p class="text-sm opacity-85 mt-0.5"><?= htmlspecialchars($activeBanner['message'] ?? '') ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Header -->
    <header class="panel p-5 sm:p-6 fade-up" style="animation-delay:.04s">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl flex items-center justify-center flex-shrink-0"
                 style="background:var(--primary-cnt);border:1px solid rgba(208,188,255,.2);">
                <span class="text-xl" aria-hidden="true">📰</span>
            </div>
            <div>
                <h1 class="text-xl font-bold" style="color:var(--on-surface);">Actualités</h1>
                <p class="text-xs mt-0.5" style="color:var(--on-surface-var);">Annonces et informations de l'association.</p>
            </div>
        </div>
    </header>

    <!-- Filter chips -->
    <?php if (!empty($all)): ?>
    <div class="chips-row fade-up" style="animation-delay:.09s" role="group" aria-label="Filtrer par catégorie">
        <button onclick="filterCat('all')" data-cat="all" class="md-chip active" aria-pressed="true">
            Tout (<?= count($all) ?>)
        </button>
        <?php foreach ($catLabels as $key => $label):
            $cnt = count(array_filter($all, fn($a) => ($a['category'] ?? 'general') === $key));
            if (!$cnt) continue;
            $badge = $catBadge[$key] ?? $catBadge['general'];
        ?>
        <button onclick="filterCat('<?= $key ?>')" data-cat="<?= $key ?>" class="md-chip" aria-pressed="false">
            <?= $label ?> (<?= $cnt ?>)
        </button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Article list -->
    <div id="newsList" class="space-y-3" role="list">
        <?php if (empty($all)): ?>
        <div class="panel p-12 text-center fade-up" style="animation-delay:.13s" role="listitem">
            <span class="text-4xl block mb-3" aria-hidden="true">📭</span>
            <p style="color:var(--on-surface-var);">Aucune actualité pour le moment.</p>
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
                 style="border-left:3px solid <?= $annColor ?>;animation-delay:<?= $delay ?>s;"
                 role="listitem">
            <div class="p-5 flex items-start gap-4">
                <span class="text-2xl select-none flex-shrink-0 mt-0.5" aria-hidden="true"><?= $annEmoji ?></span>
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        <?php if ($annTitle): ?>
                        <p class="font-semibold leading-snug" style="color:var(--on-surface);"><?= $annTitle ?></p>
                        <?php endif; ?>
                        <span class="text-[11px] px-2.5 py-0.5 rounded-full font-medium"
                              style="background:<?= $badge['bg'] ?>;color:<?= $badge['color'] ?>;">
                            <?= $catLabel ?>
                        </span>
                    </div>
                    <?php if ($annDate): ?>
                    <p class="text-xs mb-2" style="color:var(--outline);"><?= $annDate ?></p>
                    <?php endif; ?>
                    <?php if ($annExcerpt !== ''): ?>
                    <p class="text-sm leading-relaxed" style="color:var(--on-surface-var);"><?= htmlspecialchars($annExcerpt) ?><?= mb_strlen(trim(strip_tags($annHtml))) > 220 ? '…' : '' ?></p>
                    <?php endif; ?>
                    <div class="mt-4">
                        <a href="/article.php?id=<?= urlencode((string)($ann['id'] ?? '')) ?>"
                           class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs font-medium transition"
                           style="background:var(--primary-cnt);color:var(--primary-cnt-on);">
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
    document.querySelectorAll('.md-chip').forEach(b => {
        b.classList.remove('active');
        b.setAttribute('aria-pressed', 'false');
    });
    const activeBtn = document.querySelector(`[data-cat="${cat}"]`);
    if (activeBtn) { activeBtn.classList.add('active'); activeBtn.setAttribute('aria-pressed', 'true'); }
    document.querySelectorAll('#newsList article').forEach(art => {
        art.style.display = (cat === 'all' || art.dataset.cat === cat) ? '' : 'none';
    });
}
</script>
</body>
</html>
