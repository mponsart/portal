<?php
$user    = $_SESSION['user'];
$config  = require __DIR__ . '/../config.php';
$isAdmin = in_array($user['email'], $config['admins'] ?? [], true);
if (!$isAdmin) { http_response_code(403); exit('Acces non autorise.'); }

$currentPage = 'admin';
$featuredFile = __DIR__ . '/../uploads/featured.json';
$bannerFile = __DIR__ . '/../uploads/banners.json';

$featured = [];
if (file_exists($featuredFile)) {
    $decoded = json_decode(file_get_contents($featuredFile), true);
    $featured = is_array($decoded) ? $decoded : [];
}

$banners = [];
if (file_exists($bannerFile)) {
    $decoded = json_decode(file_get_contents($bannerFile), true);
    $banners = is_array($decoded) ? $decoded : [];
}

$total = count($featured);
$published = count(array_filter($featured, fn($a) => ($a['status'] ?? 'published') === 'published'));
$drafts = count(array_filter($featured, fn($a) => ($a['status'] ?? 'published') === 'draft'));
$activeBanners = count(array_filter($banners, fn($b) => !empty($b['active'])));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Groupe Speed Cloud</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="/assets/images/cloudy.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family:'Inter',sans-serif; background:#06080f; color-scheme:dark; }
        .bg-ambient { position:fixed; inset:0; pointer-events:none; z-index:0;
            background: radial-gradient(ellipse 70% 55% at 15% 0%, rgba(52,84,209,.28) 0%, transparent 65%),
                        radial-gradient(ellipse 50% 40% at 88% 100%, rgba(14,165,233,.18) 0%, transparent 60%); }
        .glass { background:rgba(255,255,255,.055); backdrop-filter:blur(16px) saturate(160%); border:1px solid rgba(255,255,255,.10); }
        .admin-card { transition:transform .16s,border-color .16s,background .16s; }
        .admin-card:hover { transform:translateY(-2px); border-color:rgba(255,255,255,.24); background:rgba(255,255,255,.08); }
    </style>
</head>
<body class="min-h-screen text-white relative">
<div class="bg-ambient"></div>
<?php include __DIR__ . '/_nav.php'; ?>

<main class="relative z-10 w-full max-w-6xl mx-auto px-4 sm:px-6 py-8 space-y-6">
    <section class="glass rounded-3xl p-6 sm:p-7">
        <h1 class="text-2xl font-bold">⚙️ Administration</h1>
        <p class="text-white/45 text-sm mt-1">Espace admin réorganisé en plusieurs pages pour une interface plus propre.</p>
    </section>

    <section class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="glass rounded-2xl p-4"><p class="text-blue-300 text-xl font-bold"><?= $total ?></p><p class="text-white/45 text-xs">Total actualités</p></div>
        <div class="glass rounded-2xl p-4"><p class="text-emerald-300 text-xl font-bold"><?= $published ?></p><p class="text-white/45 text-xs">Publiées</p></div>
        <div class="glass rounded-2xl p-4"><p class="text-amber-300 text-xl font-bold"><?= $drafts ?></p><p class="text-white/45 text-xs">Brouillons</p></div>
        <div class="glass rounded-2xl p-4"><p class="text-red-300 text-xl font-bold"><?= $activeBanners ?></p><p class="text-white/45 text-xs">Bannières actives</p></div>
    </section>

    <section class="grid md:grid-cols-2 gap-4">
        <a href="/admin-news.php" class="admin-card glass rounded-3xl p-6 block border border-white/10">
            <p class="text-lg font-semibold">📰 Gérer les actualités</p>
            <p class="text-white/45 text-sm mt-1">Création, brouillons, publication, édition, tri et filtres.</p>
            <p class="text-xs text-brand-lt mt-4">Ouvrir la page →</p>
        </a>
        <a href="/admin-banners.php" class="admin-card glass rounded-3xl p-6 block border border-white/10">
            <p class="text-lg font-semibold">📣 Gérer les bannières</p>
            <p class="text-white/45 text-sm mt-1">Messages critiques, activation/désactivation et nettoyage.</p>
            <p class="text-xs text-brand-lt mt-4">Ouvrir la page →</p>
        </a>
    </section>
</main>
</body>
</html>
