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
    <link href="https://fonts.googleapis.com/css2?family=Titillium+Web:wght@400;600;700&display=swap" rel="stylesheet">
    <?php include __DIR__ . '/_ui-tokens.php'; ?>
    <style>
        body { font-family:'Titillium Web',sans-serif; background:#06080f; color-scheme:dark; }
        .bg-ambient { position:fixed; inset:0; pointer-events:none; z-index:0;
            background: radial-gradient(ellipse 70% 55% at 15% 0%, rgba(52,84,209,.28) 0%, transparent 65%),
                        radial-gradient(ellipse 50% 40% at 88% 100%, rgba(14,165,233,.18) 0%, transparent 60%); }
        .glass { background:rgba(255,255,255,.055); backdrop-filter:blur(16px) saturate(160%); border:1px solid rgba(255,255,255,.10); }
        .admin-card { transition:transform .16s,border-color .16s,background .16s,box-shadow .16s; min-height:clamp(150px, 17vw, 210px); }
        .admin-card:hover { transform:translateY(-3px); border-color:rgba(255,255,255,.24); background:rgba(255,255,255,.08); box-shadow:0 12px 28px rgba(0,0,0,.28); }
        .stat-card { min-height:96px; }
        .kpi-value { font-size:clamp(1.3rem, 3vw, 2rem); line-height:1.05; }
        .dash-shell { max-width:1200px; }
        .section-title { letter-spacing:.08em; font-size:.7rem; text-transform:uppercase; color:rgba(226,232,240,.6); }
        .hero-grid { display:grid; gap:1rem; grid-template-columns:1fr; }
        @media (min-width: 1024px) {
            .hero-grid { grid-template-columns:1.35fr .95fr; }
        }
    </style>
</head>
<body class="min-h-screen text-white relative">
<div class="bg-ambient"></div>
<?php include __DIR__ . '/_nav.php'; ?>

<main class="page-stack dash-shell relative z-10 w-full mx-auto px-4 sm:px-6 py-7 sm:py-8">
    <section class="hero-grid">
        <article class="glass rounded-3xl p-5 sm:p-7">
            <p class="section-title">Tableau de bord</p>
            <h1 class="text-2xl sm:text-3xl font-bold mt-2">⚙️ Administration</h1>
            <p class="text-white/55 text-sm sm:text-base mt-2 max-w-2xl">Espace centralisé pour piloter le contenu, la diffusion et les accès. Mise en page optimisée pour mobile, laptop et grand écran.</p>
            <div class="mt-5 flex flex-wrap items-center gap-2.5">
                <a href="/admin-news-new.php" class="px-3.5 py-2 rounded-xl text-sm font-semibold bg-blue-600 hover:bg-blue-700 border border-blue-400/35">➕ Nouvelle actualité</a>
                <a href="/admin-banners.php" class="px-3.5 py-2 rounded-xl text-sm font-semibold bg-white/10 hover:bg-white/15 border border-white/15">📣 Ajouter une bannière</a>
            </div>
        </article>

        <article class="glass rounded-3xl p-5 sm:p-6">
            <p class="section-title">Vue rapide</p>
            <div class="mt-3 grid grid-cols-2 gap-2.5">
                <div class="stat-card rounded-2xl border border-white/10 bg-white/5 p-3.5">
                    <p class="text-blue-300 text-[11px] uppercase tracking-wider">Actualités</p>
                    <p class="kpi-value font-bold mt-1"><?= $total ?></p>
                </div>
                <div class="stat-card rounded-2xl border border-white/10 bg-white/5 p-3.5">
                    <p class="text-emerald-300 text-[11px] uppercase tracking-wider">Publiées</p>
                    <p class="kpi-value font-bold mt-1"><?= $published ?></p>
                </div>
                <div class="stat-card rounded-2xl border border-white/10 bg-white/5 p-3.5">
                    <p class="text-amber-300 text-[11px] uppercase tracking-wider">Brouillons</p>
                    <p class="kpi-value font-bold mt-1"><?= $drafts ?></p>
                </div>
                <div class="stat-card rounded-2xl border border-white/10 bg-white/5 p-3.5">
                    <p class="text-red-300 text-[11px] uppercase tracking-wider">Bannières actives</p>
                    <p class="kpi-value font-bold mt-1"><?= $activeBanners ?></p>
                </div>
            </div>
        </article>
    </section>

    <section>
        <p class="section-title mb-3">Modules</p>
        <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">
            <a href="/admin-news.php" class="admin-card glass rounded-3xl p-5 sm:p-6 block border border-white/10">
                <p class="text-lg font-semibold">📰 Gérer les actualités</p>
                <p class="text-white/50 text-sm mt-1.5">Création, brouillons, publication, édition, tri et filtres.</p>
                <p class="text-xs text-brand-lt mt-5">Ouvrir la page →</p>
            </a>
            <a href="/admin-banners.php" class="admin-card glass rounded-3xl p-5 sm:p-6 block border border-white/10">
                <p class="text-lg font-semibold">📣 Gérer les bannières</p>
                <p class="text-white/50 text-sm mt-1.5">Messages prioritaires, activation/désactivation et modification.</p>
                <p class="text-xs text-brand-lt mt-5">Ouvrir la page →</p>
            </a>
            <a href="/admin-status.php" class="admin-card glass rounded-3xl p-5 sm:p-6 block border border-white/10">
                <p class="text-lg font-semibold">📡 Gérer les statuts</p>
                <p class="text-white/50 text-sm mt-1.5">Configuration des sites monitorés et de leur visibilité.</p>
                <p class="text-xs text-brand-lt mt-5">Ouvrir la page →</p>
            </a>
            <a href="/admin-apps.php" class="admin-card glass rounded-3xl p-5 sm:p-6 block border border-white/10">
                <p class="text-lg font-semibold">🧩 Gérer les applications</p>
                <p class="text-white/50 text-sm mt-1.5">Organisation des applications affichées dans le portail.</p>
                <p class="text-xs text-brand-lt mt-5">Ouvrir la page →</p>
            </a>
            <a href="/admin-users.php" class="admin-card glass rounded-3xl p-5 sm:p-6 block border border-white/10">
                <p class="text-lg font-semibold">👥 Gérer les utilisateurs</p>
                <p class="text-white/50 text-sm mt-1.5">Suivi des validations de charte et gestion des accès.</p>
                <p class="text-xs text-brand-lt mt-5">Ouvrir la page →</p>
            </a>
            <a href="/admin-workspace.php" class="admin-card glass rounded-3xl p-5 sm:p-6 block border border-white/10">
                <p class="text-lg font-semibold">🏢 Gérer Workspace</p>
                <p class="text-white/50 text-sm mt-1.5">Applications Google Workspace et ordre d'affichage.</p>
                <p class="text-xs text-brand-lt mt-5">Ouvrir la page →</p>
            </a>
        </div>
    </section>
</main>
</body>
</html>
