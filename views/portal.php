<?php
$user    = $_SESSION['user'];
$config  = require __DIR__ . '/../config.php';
$isAdmin = in_array($user['email'], $config['admins'] ?? []);

$currentPage = 'portail';

// Annonces mises en avant
$featuredFile = __DIR__ . '/../uploads/featured.json';
$featured = [];
if (file_exists($featuredFile)) {
    $raw      = file_get_contents($featuredFile);
    $decoded  = json_decode($raw, true);
    $featured = is_array($decoded) ? $decoded : [];
}

// Applications du portail (surchargeable via config.php > portal.apps)
$apps = $config['portal']['apps'] ?? [
    ['name' => 'Gmail',          'url' => 'https://mail.google.com',              'emoji' => '📧', 'bg' => 'from-red-500 to-rose-700'],
    ['name' => 'Google Drive',   'url' => 'https://drive.google.com',             'emoji' => '💾', 'bg' => 'from-yellow-400 to-orange-500'],
    ['name' => 'Google Agenda',  'url' => 'https://calendar.google.com',          'emoji' => '📅', 'bg' => 'from-blue-400 to-blue-700'],
    ['name' => 'Google Meet',    'url' => 'https://meet.google.com',              'emoji' => '🎥', 'bg' => 'from-green-400 to-emerald-700'],
    ['name' => 'Google Docs',    'url' => 'https://docs.google.com',              'emoji' => '📝', 'bg' => 'from-sky-400 to-cyan-700'],
    ['name' => 'Google Sheets',  'url' => 'https://sheets.google.com',            'emoji' => '📊', 'bg' => 'from-emerald-400 to-teal-700'],
    ['name' => 'Google Slides',  'url' => 'https://slides.google.com',            'emoji' => '📽️', 'bg' => 'from-amber-400 to-yellow-600'],
    ['name' => 'YouTube',        'url' => 'https://youtube.com',                  'emoji' => '▶️',  'bg' => 'from-red-600 to-red-900'],
    ['name' => 'Discord',        'url' => 'https://discord.com',                  'emoji' => '💬', 'bg' => 'from-indigo-500 to-violet-700'],
    ['name' => 'GitHub',         'url' => 'https://github.com',                   'emoji' => '🐙', 'bg' => 'from-gray-600 to-gray-900'],
    ['name' => 'Notion',         'url' => 'https://notion.so',                    'emoji' => '📒', 'bg' => 'from-slate-500 to-slate-800'],
    ['name' => 'Figma',          'url' => 'https://figma.com',                    'emoji' => '🎨', 'bg' => 'from-pink-500 to-fuchsia-700'],
];

$firstName = $user['firstName'] ?? explode(' ', $user['name'])[0];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portail — Groupe Speed Cloud</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'brand-indigo': '#3454d1',
                        'brand-cyan':   '#0ea5e9',
                        'brand-ink':    '#0b132b',
                    },
                },
            },
        };
    </script>
    <link rel="icon" type="image/png" href="https://sign.groupe-speed.cloud/assets/images/cloudy.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Titillium+Web:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Titillium Web', sans-serif; }
        .app-card:hover .app-emoji { transform: scale(1.15); }
    </style>
</head>
<body class="min-h-screen text-slate-100"
      style="background: radial-gradient(circle at 10% 10%, #1d4ed8 0%, #0b132b 45%, #020617 100%);">

    <?php include __DIR__ . '/_nav.php'; ?>

    <main class="container mx-auto px-4 py-10 space-y-12">

        <!-- ── Profil utilisateur ─────────────────────────────────────── -->
        <section class="flex flex-col sm:flex-row items-center sm:items-start gap-6">
            <?php if (!empty($user['picture'])): ?>
            <img src="<?= htmlspecialchars($user['picture']) ?>"
                 alt="Photo de profil"
                 class="w-20 h-20 rounded-2xl border-4 border-white/20 shadow-xl flex-shrink-0">
            <?php endif; ?>
            <div>
                <p class="text-gray-400 text-sm mb-1">Bonjour,</p>
                <h1 class="text-3xl sm:text-4xl font-bold text-white leading-tight">
                    <?= htmlspecialchars($firstName) ?> 👋
                </h1>
                <p class="text-gray-400 mt-1"><?= htmlspecialchars($user['email']) ?></p>
            </div>
        </section>

        <!-- ── Annonces à la une ────────────────────────────────────────── -->
        <?php if (!empty($featured)): ?>
        <section>
            <h2 class="text-xl font-bold text-white mb-4">📌 À la une</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($featured as $ann): ?>
                <?php
                    $accentColor = htmlspecialchars($ann['color'] ?? '#3454d1');
                    $title   = htmlspecialchars($ann['title']   ?? '');
                    $content = htmlspecialchars($ann['content'] ?? '');
                    $emoji   = htmlspecialchars($ann['emoji']   ?? '📢');
                    $date    = htmlspecialchars($ann['pinned_at'] ?? '');
                ?>
                <div class="rounded-2xl p-5 bg-white/10 backdrop-blur border border-white/10 shadow-lg"
                     style="border-left: 4px solid <?= $accentColor ?>;">
                    <div class="flex items-start gap-3">
                        <span class="text-2xl select-none"><?= $emoji ?></span>
                        <div class="min-w-0">
                            <?php if ($title): ?>
                            <p class="font-bold text-white leading-snug mb-1"><?= $title ?></p>
                            <?php endif; ?>
                            <p class="text-gray-300 text-sm leading-relaxed"><?= nl2br($content) ?></p>
                            <?php if ($date): ?>
                            <p class="text-gray-500 text-xs mt-3"><?= $date ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- ── Moteur de recherche ──────────────────────────────────────── -->
        <section>
            <h2 class="text-xl font-bold text-white mb-4">🔍 Recherche</h2>
            <form action="https://www.google.com/search" method="get" target="_blank"
                  class="flex gap-3 max-w-2xl">
                <input type="text" name="q" placeholder="Rechercher sur Google…"
                       autocomplete="off"
                       class="flex-1 px-5 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder-gray-400
                              focus:outline-none focus:ring-2 focus:ring-brand-cyan transition">
                <button type="submit"
                        class="px-6 py-3 bg-brand-indigo hover:bg-blue-700 text-white font-semibold rounded-xl transition shadow">
                    Rechercher
                </button>
            </form>
        </section>

        <!-- ── Applications ─────────────────────────────────────────────── -->
        <section>
            <h2 class="text-xl font-bold text-white mb-4">📦 Applications</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <?php foreach ($apps as $app): ?>
                <?php
                    $appName  = htmlspecialchars($app['name']  ?? '');
                    $appUrl   = htmlspecialchars($app['url']   ?? '#');
                    $appEmoji = htmlspecialchars($app['emoji'] ?? '🔗');
                    $appBg    = $app['bg'] ?? 'from-gray-600 to-gray-900';
                ?>
                <a href="<?= $appUrl ?>" target="_blank" rel="noopener noreferrer"
                   class="app-card group flex flex-col items-center gap-3 rounded-2xl p-5 bg-white/5 border border-white/10
                          hover:bg-white/15 hover:border-white/30 hover:scale-105 transition-all duration-200 shadow">
                    <span class="app-emoji text-4xl transition-transform duration-200 select-none">
                        <?= $appEmoji ?>
                    </span>
                    <span class="text-sm font-semibold text-gray-200 text-center leading-tight">
                        <?= $appName ?>
                    </span>
                </a>
                <?php endforeach; ?>
            </div>
        </section>

    </main>

</body>
</html>
