<?php
$user    = $_SESSION['user'];
$config  = require __DIR__ . '/../config.php';
$isAdmin = in_array($user['email'], $config['admins'] ?? []);

$currentPage  = 'news';
$featuredFile = __DIR__ . '/../uploads/featured.json';
$articleId    = trim((string)($_GET['id'] ?? ''));

$all = [];
if (file_exists($featuredFile)) {
    $decoded = json_decode(file_get_contents($featuredFile), true);
    $all     = is_array($decoded) ? $decoded : [];
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

$article = null;
if (preg_match('/^[0-9a-f]{16}$/', $articleId)) {
    foreach ($all as $ann) {
        if (($ann['id'] ?? '') === $articleId) {
            $article = $ann;
            break;
        }
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
    <title><?= $article ? htmlspecialchars($article['title'] ?? 'Article') : 'Article introuvable' ?> — Groupe Speed Cloud</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="/assets/images/cloudy.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php include __DIR__ . '/_ui-tokens.php'; ?>
    <style>
        body { font-family:'Inter',sans-serif; background:var(--bg); color:var(--on-surface); }

        /* ── Article typography ── */
        .article-body h2 { font-size:1.375rem; font-weight:700; color:var(--on-surface); margin:1.5rem 0 .5rem; line-height:1.3; }
        .article-body h3 { font-size:1.1rem; font-weight:600; color:var(--on-surface); margin:1.2rem 0 .4rem; }
        .article-body p  { color:var(--on-surface-var); font-size:.9375rem; line-height:1.9; margin:.5rem 0; }
        .article-body strong { color:var(--on-surface); font-weight:600; }
        .article-body em { font-style:italic; color:var(--on-surface-var); }
        .article-body ul { list-style:disc; padding-left:1.5rem; color:var(--on-surface-var); margin:.5rem 0; }
        .article-body ol { list-style:decimal; padding-left:1.5rem; color:var(--on-surface-var); margin:.5rem 0; }
        .article-body li { margin:.3rem 0; font-size:.9375rem; line-height:1.75; }
        .article-body blockquote {
            border-left:3px solid var(--primary);
            padding:.75rem 1.25rem; margin:.75rem 0;
            background:rgba(208,188,255,.07);
            border-radius:0 var(--shape-md) var(--shape-md) 0;
            color:var(--on-surface-var);
        }
        .article-body a { color:var(--primary); text-decoration:underline; }
        .article-body a:hover { color:var(--primary-cnt-on); }

        /* ── Banner ── */
        .md-banner { border-radius:var(--shape-lg); border-left:4px solid; display:flex; align-items:flex-start; gap:12px; padding:14px 16px; }
    </style>
</head>
<body class="min-h-screen relative">
<div class="bg-ambient" aria-hidden="true"></div>

<?php include __DIR__ . '/_nav.php'; ?>

<main class="page-stack relative z-10 w-full max-w-3xl mx-auto px-4 sm:px-6 py-8">

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
            <p class="text-sm opacity-85 mt-0.5"><?= htmlspecialchars($activeBanner['message'] ?? '') ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Back -->
    <div>
        <a href="/news.php"
           class="inline-flex items-center gap-2 text-sm font-medium px-4 py-2 rounded-full transition"
           style="background:var(--surface-1);color:var(--on-surface-var);box-shadow:var(--elev-1);"
           onmouseover="this.style.background='var(--surface-2)'"
           onmouseout="this.style.background='var(--surface-1)'">
            ← Retour aux actualités
        </a>
    </div>

    <?php if (!$article): ?>
    <div class="panel p-12 text-center">
        <span class="text-5xl block mb-4" aria-hidden="true">🕳️</span>
        <h1 class="text-xl font-semibold mb-2" style="color:var(--on-surface);">Article introuvable</h1>
        <p class="text-sm" style="color:var(--on-surface-var);">Le lien est invalide ou l'article a été supprimé.</p>
    </div>
    <?php else:
        $annEmoji = htmlspecialchars($article['emoji'] ?? '📢');
        $annTitle = htmlspecialchars($article['title'] ?? 'Actualité');
        $annCat   = $article['category'] ?? 'general';
        $annDate  = htmlspecialchars($article['created_at'] ?? ($article['pinned_at'] ?? ''));
        $annHtml  = $article['html_content'] ?? nl2br(htmlspecialchars($article['content'] ?? ''));
        $badge    = $catBadge[$annCat] ?? $catBadge['general'];
        $catLabel = $catLabels[$annCat] ?? 'Général';
        $annColor = htmlspecialchars($article['color'] ?? '#7c3aed');
    ?>
    <article class="panel p-6 sm:p-8" style="border-left:4px solid <?= $annColor ?>;">
        <div class="flex items-start gap-4 mb-6">
            <span class="text-4xl leading-none select-none flex-shrink-0 mt-1" aria-hidden="true"><?= $annEmoji ?></span>
            <div class="min-w-0">
                <h1 class="text-2xl sm:text-3xl font-extrabold leading-tight" style="color:var(--on-surface);">
                    <?= $annTitle !== '' ? $annTitle : 'Actualité' ?>
                </h1>
                <div class="flex flex-wrap items-center gap-2 mt-2">
                    <span class="text-[11px] px-2.5 py-1 rounded-full font-medium"
                          style="background:<?= $badge['bg'] ?>;color:<?= $badge['color'] ?>;">
                        <?= htmlspecialchars($catLabel) ?>
                    </span>
                    <?php if ($annDate): ?>
                    <span class="text-xs" style="color:var(--outline);"><?= $annDate ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <hr class="md-divider mb-6">
        <div class="article-body"><?= $annHtml ?></div>
    </article>
    <?php endif; ?>

</main>
</body>
</html>
