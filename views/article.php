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
    <title>Article - Groupe Speed Cloud</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="/assets/images/cloudy.png">
    <link href="https://fonts.googleapis.com/css2?family=Titillium+Web:wght;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { font-family:'Titillium Web',sans-serif; background:#06080f; }
        .bg-ambient { position:fixed; inset:0; pointer-events:none; z-index:0;
            background: radial-gradient(ellipse 70% 55% at 15% 0%, rgba(52,84,209,.28) 0%, transparent 65%),
                        radial-gradient(ellipse 50% 40% at 88% 100%, rgba(14,165,233,.18) 0%, transparent 60%); }
        .glass { background:rgba(255,255,255,.055); backdrop-filter:blur(16px) saturate(160%);
                 -webkit-backdrop-filter:blur(16px) saturate(160%); border:1px solid rgba(255,255,255,.10); }
        .article-body h2 { font-size:1.5rem; font-weight:700; color:#fff; margin:1.2rem 0 .5rem; }
        .article-body h3 { font-size:1.15rem; font-weight:600; color:#e2e8f0; margin:1rem 0 .4rem; }
        .article-body p  { color:rgba(255,255,255,.75); font-size:.98rem; line-height:1.9; margin:.45rem 0; }
        .article-body strong { color:#fff; font-weight:600; }
        .article-body em { font-style:italic; color:rgba(255,255,255,.8); }
        .article-body ul { list-style:disc; padding-left:1.3rem; color:rgba(255,255,255,.75); margin:.4rem 0; }
        .article-body ol { list-style:decimal; padding-left:1.3rem; color:rgba(255,255,255,.75); margin:.4rem 0; }
        .article-body li { margin:.25rem 0; }
        .article-body blockquote { border-left:3px solid #3454d1; padding:.6rem 1rem; margin:.8rem 0;
                                   background:rgba(52,84,209,.10); border-radius:0 10px 10px 0; color:rgba(255,255,255,.7); }
        .article-body a { color:#6b8fff; text-decoration:underline; }
    </style>
</head>
<body class="min-h-screen text-white relative">
<div class="bg-ambient"></div>

<?php include __DIR__ . '/_nav.php'; ?>

<main class="relative z-10 w-full max-w-4xl mx-auto px-4 sm:px-6 py-8">
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
    <section class="rounded-2xl border px-4 py-3 <?= $toneCls ?> mb-4">
        <p class="font-semibold text-sm"><?= $toneIcon ?> <?= htmlspecialchars($activeBanner['title'] ?? 'Annonce importante') ?></p>
        <p class="text-sm opacity-90 mt-0.5"><?= htmlspecialchars($activeBanner['message'] ?? '') ?></p>
    </section>
    <?php endif; ?>

    <?php if (!$article): ?>
    <section class="glass rounded-3xl p-8 text-center">
        <p class="text-5xl mb-3">🕳️</p>
        <h1 class="text-xl font-semibold mb-2">Article introuvable</h1>
        <p class="text-white/45 text-sm mb-5">Le lien est invalide ou l'article a été supprimé.</p>
        <a href="/news.php" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white/10 hover:bg-white/15 border border-white/15 text-sm">⬅️ Retour aux actualités</a>
    </section>
    <?php else:
        $annEmoji = htmlspecialchars($article['emoji'] ?? '📢');
        $annTitle = htmlspecialchars($article['title'] ?? 'Actualite');
        $annCat   = $article['category'] ?? 'general';
        $annDate  = htmlspecialchars($article['created_at'] ?? ($article['pinned_at'] ?? ''));
        $annHtml  = $article['html_content'] ?? nl2br(htmlspecialchars($article['content'] ?? ''));
        $badge    = $catBadge[$annCat] ?? $catBadge['general'];
        $catLabel = $catLabels[$annCat] ?? 'Général';
    ?>
    <article class="glass rounded-3xl p-6 sm:p-8">
        <a href="/news.php" class="inline-flex items-center gap-2 text-sm text-white/60 hover:text-white mb-4 px-3 py-1.5 rounded-lg bg-white/5 border border-white/10">⬅️ Retour aux actualités</a>
        <div class="flex items-start gap-4 mb-5">
            <span class="text-4xl leading-none select-none"><?= $annEmoji ?></span>
            <div class="min-w-0">
                <h1 class="text-2xl sm:text-3xl font-bold leading-tight text-white"><?= $annTitle !== '' ? $annTitle : 'Actualite' ?></h1>
                <div class="flex items-center gap-2 mt-2">
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                          style="background:<?= $badge['bg'] ?>; color:<?= $badge['color'] ?>;">
                        <?= htmlspecialchars($catLabel) ?>
                    </span>
                    <?php if ($annDate): ?>
                    <span class="text-xs text-white/40">🕒 <?= $annDate ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="article-body"><?= $annHtml ?></div>
    </article>
    <?php endif; ?>
</main>
</body>
</html>
