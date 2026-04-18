<?php
$user    = $_SESSION['user'];
$config  = require __DIR__ . '/../config.php';
$isAdmin = in_array($user['email'], $config['admins'] ?? [], true);
if (!$isAdmin) { http_response_code(403); include __DIR__ . '/unauthorized.php'; exit; }

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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php include __DIR__ . '/_ui-tokens.php'; ?>
    <style>
        body { font-family:'Inter',sans-serif; background:var(--bg); color-scheme:dark; color:var(--on-surface); }
        .bg-ambient { position:fixed; inset:0; pointer-events:none; z-index:0;
            background:
                radial-gradient(ellipse 70% 55% at 8% 0%, rgba(79,55,139,.35) 0%, transparent 55%),
                radial-gradient(ellipse 55% 45% at 94% 100%, rgba(0,79,80,.25) 0%, transparent 55%); }
        .glass { background:var(--surface-1); border-radius:var(--shape-xl); box-shadow:var(--elev-1); }
        .admin-card { transition:transform .16s,box-shadow .2s,background .14s; min-height:clamp(108px, 10vw, 132px); }
        .admin-card:hover { transform:translateY(-3px); box-shadow:var(--elev-3); background:var(--surface-2); }
        .stat-card { min-height:78px; }
        .kpi-value { font-size:clamp(1.15rem, 2.3vw, 1.7rem); line-height:1.05; }
        .dash-shell { max-width:1200px; }
        .section-title { letter-spacing:.06em; font-size:.6875rem; text-transform:uppercase; color:var(--on-surface-var); font-weight:500; }
        .hero-grid { display:grid; gap:1rem; grid-template-columns:1fr; }
        .quick-btn { background:var(--surface-2); border-radius:var(--shape-md); transition:background .14s; text-decoration:none; display:block; }
        .quick-btn:hover { background:var(--surface-3); }
        @media (min-width: 1024px) {
            .hero-grid { grid-template-columns:1.35fr .95fr; }
            .no-scroll-desktop { height:calc(100vh - 64px); overflow:hidden; }
            .tight-desktop { padding-top:.85rem; padding-bottom:.85rem; }
        }
    </style>
</head>
<body class="min-h-screen text-white relative">
<div class="bg-ambient"></div>
<?php include __DIR__ . '/_nav.php'; ?>

<main class="page-stack dash-shell no-scroll-desktop tight-desktop relative z-10 w-full mx-auto px-4 sm:px-6 py-5 sm:py-6">
    <section class="hero-grid">
        <article class="glass rounded-3xl p-4 sm:p-5">
            <p class="section-title">Tableau de bord</p>
            <h1 class="text-xl sm:text-2xl font-bold mt-1.5">⚙️ Administration</h1>
            <p class="text-white/55 text-sm mt-1.5">Centre de pilotage rapide du portail.</p>
            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
                <a href="/admin-news-new.php" class="quick-btn px-3 py-2 rounded-xl text-sm font-semibold">➕ Nouvelle actualité</a>
                <a href="/admin-banners.php" class="quick-btn px-3 py-2 rounded-xl text-sm font-semibold">📣 Nouvelle bannière</a>
            </div>
        </article>

        <article class="glass rounded-3xl p-4 sm:p-5">
            <p class="section-title">Vue rapide</p>
            <div class="mt-2.5 grid grid-cols-2 gap-2">
                <div class="stat-card rounded-2xl border border-white/10 bg-white/5 p-3">
                    <p class="text-violet-300 text-[11px] uppercase tracking-wider">Actualités</p>
                    <p class="kpi-value font-bold mt-1"><?= $total ?></p>
                </div>
                <div class="stat-card rounded-2xl border border-white/10 bg-white/5 p-3">
                    <p class="text-emerald-300 text-[11px] uppercase tracking-wider">Publiées</p>
                    <p class="kpi-value font-bold mt-1"><?= $published ?></p>
                </div>
                <div class="stat-card rounded-2xl border border-white/10 bg-white/5 p-3">
                    <p class="text-amber-300 text-[11px] uppercase tracking-wider">Brouillons</p>
                    <p class="kpi-value font-bold mt-1"><?= $drafts ?></p>
                </div>
                <div class="stat-card rounded-2xl border border-white/10 bg-white/5 p-3">
                    <p class="text-red-300 text-[11px] uppercase tracking-wider">Bannières actives</p>
                    <p class="kpi-value font-bold mt-1"><?= $activeBanners ?></p>
                </div>
            </div>
        </article>
    </section>

    <section class="min-h-0">
        <p class="section-title mb-2.5">Modules</p>
        <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-3 h-full">
            <a href="/admin-news.php" class="admin-card glass rounded-3xl p-4 block border border-white/10">
                <p class="text-base font-semibold">📰 Actualités</p>
                 <p class="text-sm mt-1" style="color:var(--on-surface-var);">Créer, publier, modifier.</p>
                 <p class="text-xs mt-3" style="color:var(--primary);">Ouvrir →</p>
            </a>
            <a href="/admin-banners.php" class="admin-card glass rounded-3xl p-4 block border border-white/10">
                <p class="text-base font-semibold">📣 Bannières</p>
                <p class="text-white/50 text-xs mt-1">Urgences et messages globaux.</p>
                <p class="text-xs text-violet-400 mt-3">Ouvrir →</p>
            </a>
            <a href="/admin-status.php" class="admin-card glass rounded-3xl p-4 block border border-white/10">
                <p class="text-base font-semibold">📡 Statuts</p>
                <p class="text-white/50 text-xs mt-1">Sites surveillés.</p>
                <p class="text-xs text-violet-400 mt-3">Ouvrir →</p>
            </a>
            <a href="/admin-apps.php" class="admin-card glass rounded-3xl p-4 block border border-white/10">
                <p class="text-base font-semibold">🧩 Applications</p>
                <p class="text-white/50 text-xs mt-1">Apps visibles dans le portail.</p>
                <p class="text-xs text-violet-400 mt-3">Ouvrir →</p>
            </a>
            <a href="/admin-users.php" class="admin-card glass rounded-3xl p-4 block border border-white/10">
                <p class="text-base font-semibold">👥 Utilisateurs</p>
                <p class="text-white/50 text-xs mt-1">Accès et validations charte.</p>
                <p class="text-xs text-violet-400 mt-3">Ouvrir →</p>
            </a>
            <a href="/admin-workspace.php" class="admin-card glass rounded-3xl p-4 block border border-white/10">
                <p class="text-base font-semibold">🏢 Workspace</p>
                <p class="text-white/50 text-xs mt-1">Suite Google et tri.</p>
                <p class="text-xs text-violet-400 mt-3">Ouvrir →</p>
            </a>
        </div>
    </section>
</main>
</body>
</html>
