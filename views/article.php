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
        body { font-family:'Inter',sans-serif; background:var(--bg); }
        .bg-ambient {
            position:fixed; inset:0; pointer-events:none; z-index:0;
            background:
                radial-gradient(ellipse 65% 50% at 10%   5%,  rgba(124,58,237,.25) 0%, transparent 58%),
                radial-gradient(ellipse 50% 40% at 92%  95%,  rgba(8,145,178,.18)  0%, transparent 56%);
        }
        .panel { background:var(--surface); border:1px solid var(--border); border-radius:18px; }

        /* Article typography */
        .article-body h2 { font-size:1.4rem; font-weight:700; color:#fff; margin:1.2rem 0 .5rem; line-height:1.3; }
        .article-body h3 { font-size:1.1rem; font-weight:600; color:#e2e8f0; margin:1rem 0 .4rem; }
        .article-body p  { color:rgba(255,255,255,.72); font-size:.97rem; line-height:1.85; margin:.4rem 0; }
        .article-body strong { color:#fff; font-weight:600; }
        .article-body em { font-style:italic; color:rgba(255,255,255,.78); }
        .article-body ul { list-style:disc; padding-left:1.4rem; color:rgba(255,255,255,.72); margin:.4rem 0; }
        .article-body ol { list-style:decimal; padding-left:1.4rem; color:rgba(255,255,255,.72); margin:.4rem 0; }
        .article-body li { margin:.25rem 0; font-size:.97rem; line-height:1.7; }
        .article-body blockquote {
            border-left:3px solid var(--primary);
            padding:.6rem 1rem; margin:.75rem 0;
            background:rgba(124,58,237,.10);
            border-radius:0 10px 10px 0;
            color:rgba(255,255,255,.68);
        }
        .article-body a { color:var(--primary-lt); text-decoration:underline; }
        .article-body a:hover { color:#c4b5fd; }
    </style>
</head>
<body class="min-h-screen text-white relative">
<div class="bg-ambient"></div>

<?php include __DIR__ . '/_nav.php'; ?>

<main class="page-stack relative z-10 w-full max-w-3xl mx-auto px-4 sm:px-6 py-8">

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
    <div class="rounded-2xl border px-4 py-3"
         style="background:<?= $bannerStyles['bg'] ?>;border-color:<?= $bannerStyles['border'] ?>;color:<?= $bannerStyles['text'] ?>;">
        <p class="font-semibold text-sm"><?= $toneIcon ?> <?= htmlspecialchars($activeBanner['title'] ?? 'Annonce') ?></p>
        <p class="text-sm opacity-85 mt-0.5"><?= htmlspecialchars($activeBanner['message'] ?? '') ?></p>
    </div>
    <?php endif; ?>

    <!-- Back -->
    <div>
        <a href="/news.php"
           class="inline-flex items-center gap-2 text-sm text-white/50 hover:text-white/85 transition px-3 py-1.5 rounded-lg"
           style="background:var(--surface);border:1px solid var(--border);">
            ← Retour aux actualités
        </a>
    </div>

    <?php if (!$article): ?>
    <div class="panel p-10 text-center">
        <span class="text-5xl block mb-3">🕳️</span>
        <h1 class="text-xl font-semibold mb-2">Article introuvable</h1>
        <p class="text-white/40 text-sm">Le lien est invalide ou l'article a été supprimé.</p>
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
            <span class="text-4xl leading-none select-none flex-shrink-0 mt-1"><?= $annEmoji ?></span>
            <div class="min-w-0">
                <h1 class="text-2xl sm:text-3xl font-extrabold leading-tight text-white">
                    <?= $annTitle !== '' ? $annTitle : 'Actualité' ?>
                </h1>
                <div class="flex flex-wrap items-center gap-2 mt-2">
                    <span class="text-[11px] px-2 py-0.5 rounded-full font-medium"
                          style="background:<?= $badge['bg'] ?>;color:<?= $badge['color'] ?>;">
                        <?= htmlspecialchars($catLabel) ?>
                    </span>
                    <?php if ($annDate): ?>
                    <span class="text-xs text-white/35"><?= $annDate ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div style="border-top:1px solid var(--border);margin-bottom:1.5rem;"></div>
        <div class="article-body"><?= $annHtml ?></div>
    </article>
    <?php endif; ?>

</main>
</body>
</html>
