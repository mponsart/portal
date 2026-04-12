<?php
require_once __DIR__ . '/../db.php';

$user = $_SESSION['user'];
$config = require __DIR__ . '/../config.php';
$isAdmin = in_array($user['email'], $config['admins'] ?? [], true);
if (!$isAdmin) { http_response_code(403); include __DIR__ . '/unauthorized.php'; exit; }

$currentPage = 'admin';
$pdo = db_connect();
$version = current_charter_version($config);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = (string)($_POST['csrf_token'] ?? '');
    $serverToken = (string)($_SESSION['csrf_token'] ?? '');

    if ($serverToken === '' || !hash_equals($serverToken, $postedToken)) {
        $_SESSION['admin_users_flash'] = ['type' => 'error', 'message' => 'Token CSRF invalide.'];
        header('Location: /admin-users.php');
        exit;
    }

    $action = trim((string)($_POST['action'] ?? ''));
    if ($action === 'reset_charter') {
        $email = trim((string)($_POST['email'] ?? ''));
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            db_reset_charter_acceptance($pdo, $email, $version);
            $_SESSION['admin_users_flash'] = [
                'type' => 'success',
                'message' => 'Validation réinitialisée pour ' . $email . '. La charte sera redemandée à la prochaine ouverture.',
            ];
        } else {
            $_SESSION['admin_users_flash'] = ['type' => 'error', 'message' => 'Email utilisateur invalide.'];
        }
    }

    header('Location: /admin-users.php');
    exit;
}

$flash = $_SESSION['admin_users_flash'] ?? null;
unset($_SESSION['admin_users_flash']);

$stmt = $pdo->prepare(
    'SELECT
        u.email,
        u.full_name,
        u.first_name,
        u.last_name,
        u.picture,
        u.is_admin,
        u.last_login_at,
        ca.accepted_at AS charter_accepted_at
     FROM users u
     LEFT JOIN charter_acceptances ca
        ON ca.email = u.email AND ca.charter_version = :version
     ORDER BY
        CASE WHEN ca.accepted_at IS NULL THEN 0 ELSE 1 END ASC,
        u.last_login_at DESC'
);
$stmt->execute([':version' => $version]);
$users = $stmt->fetchAll();

$totalUsers = count($users);
$acceptedUsers = count(array_filter($users, fn($u) => !empty($u['charter_accepted_at'])));
$pendingUsers = $totalUsers - $acceptedUsers;

function formatIsoDate(?string $iso): string {
    if (!$iso) return '—';
    $ts = strtotime($iso);
    if ($ts === false) return '—';
    return date('d/m/Y H:i', $ts);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Utilisateurs - Groupe Speed Cloud</title>
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
        .admin-tab { border:1px solid rgba(255,255,255,.12); background:rgba(255,255,255,.05); }
        .admin-tab.active { background:rgba(245,158,11,.18); border-color:rgba(245,158,11,.35); color:#fcd34d; }
    </style>
</head>
<body class="min-h-screen text-white relative">
<div class="bg-ambient"></div>
<?php include __DIR__ . '/_nav.php'; ?>

<main class="page-stack relative z-10 w-full max-w-6xl mx-auto px-4 sm:px-6 py-8">
    <section class="glass rounded-3xl p-4 sm:p-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold">👥 Administration des utilisateurs</h1>
            <p class="text-white/45 text-sm">Suivi des connexions et validation de la charte informatique.</p>
            <p class="text-white/35 text-xs mt-1">Version de charte suivie : <?= htmlspecialchars($version) ?></p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="/admin.php" class="admin-tab px-3 py-1.5 rounded-lg text-xs font-semibold">🏠 Accueil Admin</a>
            <a href="/admin-news.php" class="admin-tab px-3 py-1.5 rounded-lg text-xs font-semibold">📰 Actualités</a>
            <a href="/admin-banners.php" class="admin-tab px-3 py-1.5 rounded-lg text-xs font-semibold">📣 Bannières</a>
            <a href="/admin-status.php" class="admin-tab px-3 py-1.5 rounded-lg text-xs font-semibold">📡 Sites</a>
            <a href="/admin-apps.php" class="admin-tab px-3 py-1.5 rounded-lg text-xs font-semibold">🧩 Applications</a>
            <a href="/admin-users.php" class="admin-tab active px-3 py-1.5 rounded-lg text-xs font-semibold">👥 Utilisateurs</a>
        </div>
    </section>

    <section class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="glass rounded-2xl p-4"><p class="text-blue-300 text-xs uppercase tracking-wider">Utilisateurs</p><p class="text-white text-2xl font-bold mt-1"><?= $totalUsers ?></p></div>
        <div class="glass rounded-2xl p-4"><p class="text-emerald-300 text-xs uppercase tracking-wider">Charte validée</p><p class="text-white text-2xl font-bold mt-1"><?= $acceptedUsers ?></p></div>
        <div class="glass rounded-2xl p-4"><p class="text-amber-300 text-xs uppercase tracking-wider">En attente</p><p class="text-white text-2xl font-bold mt-1"><?= $pendingUsers ?></p></div>
        <div class="glass rounded-2xl p-4"><p class="text-cyan-300 text-xs uppercase tracking-wider">Version suivie</p><p class="text-white text-sm font-semibold mt-2"><?= htmlspecialchars($version) ?></p></div>
    </section>

    <?php if ($flash && is_array($flash)): ?>
    <section class="rounded-2xl border px-4 py-2.5 text-sm <?= ($flash['type'] ?? '') === 'success' ? 'bg-emerald-500/20 border-emerald-500/35 text-emerald-100' : 'bg-red-500/20 border-red-500/35 text-red-100' ?>">
        <?= htmlspecialchars((string)($flash['message'] ?? '')) ?>
    </section>
    <?php endif; ?>

    <section class="glass rounded-3xl p-4 sm:p-5 overflow-x-auto">
        <table class="w-full min-w-[840px] text-sm">
            <thead>
                <tr class="text-left text-white/50 border-b border-white/10">
                    <th class="py-2 pr-3">Utilisateur</th>
                    <th class="py-2 pr-3">Email</th>
                    <th class="py-2 pr-3">Rôle</th>
                    <th class="py-2 pr-3">Dernière connexion</th>
                    <th class="py-2 pr-3">Validation charte</th>
                    <th class="py-2 pr-3">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <?php $accepted = !empty($u['charter_accepted_at']); ?>
                <tr class="border-b border-white/5 align-middle">
                    <td class="py-3 pr-3">
                        <div class="flex items-center gap-2">
                            <?php if (!empty($u['picture'])): ?>
                            <img src="<?= htmlspecialchars((string)$u['picture']) ?>" alt="" class="w-7 h-7 rounded-full border border-white/15">
                            <?php endif; ?>
                            <span class="text-white font-semibold"><?= htmlspecialchars((string)($u['full_name'] ?: $u['email'])) ?></span>
                        </div>
                    </td>
                    <td class="py-3 pr-3 text-white/70"><?= htmlspecialchars((string)$u['email']) ?></td>
                    <td class="py-3 pr-3">
                        <?php if ((int)$u['is_admin'] === 1): ?>
                        <span class="px-2 py-1 rounded-lg text-xs bg-amber-500/20 text-amber-300 border border-amber-500/30">Admin</span>
                        <?php else: ?>
                        <span class="px-2 py-1 rounded-lg text-xs bg-white/5 text-white/70 border border-white/10">Membre</span>
                        <?php endif; ?>
                    </td>
                    <td class="py-3 pr-3 text-white/70"><?= htmlspecialchars(formatIsoDate($u['last_login_at'] ?? null)) ?></td>
                    <td class="py-3 pr-3">
                        <?php if ($accepted): ?>
                        <span class="px-2 py-1 rounded-lg text-xs bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">✅ Validée le <?= htmlspecialchars(formatIsoDate($u['charter_accepted_at'])) ?></span>
                        <?php else: ?>
                        <span class="px-2 py-1 rounded-lg text-xs bg-red-500/20 text-red-300 border border-red-500/30">⏳ En attente</span>
                        <?php endif; ?>
                    </td>
                    <td class="py-3 pr-3">
                        <?php if ($accepted): ?>
                        <form method="post" class="inline-flex" onsubmit="return confirm('Réinitialiser la validation de charte pour cet utilisateur ?');">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($_SESSION['csrf_token'] ?? '')) ?>">
                            <input type="hidden" name="action" value="reset_charter">
                            <input type="hidden" name="email" value="<?= htmlspecialchars((string)$u['email']) ?>">
                            <button class="px-2 py-1 rounded-lg text-xs bg-amber-500/20 text-amber-300 border border-amber-500/30 hover:bg-amber-500/30">Réinitialiser</button>
                        </form>
                        <?php else: ?>
                        <span class="text-xs text-white/35">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>
</body>
</html>
