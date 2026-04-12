<?php
$user    = $_SESSION['user'];
$config  = require __DIR__ . '/../config.php';
$isAdmin = in_array($user['email'], $config['admins'] ?? [], true);
if (!$isAdmin) { http_response_code(403); exit('Acces non autorise.'); }

$currentPage = 'admin';
$bannerFile = __DIR__ . '/../uploads/banners.json';
$banners = [];
if (file_exists($bannerFile)) {
    $decoded = json_decode(file_get_contents($bannerFile), true);
    $banners = is_array($decoded) ? $decoded : [];
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Bannières - Groupe Speed Cloud</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="/assets/images/cloudy.png">
    <link href="https://fonts.googleapis.com/css2?family=Titillium+Web:wght;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family:'Titillium Web',sans-serif; background:#06080f; color-scheme:dark; }
        .bg-ambient { position:fixed; inset:0; pointer-events:none; z-index:0;
            background: radial-gradient(ellipse 70% 55% at 15% 0%, rgba(52,84,209,.28) 0%, transparent 65%),
                        radial-gradient(ellipse 50% 40% at 88% 100%, rgba(14,165,233,.18) 0%, transparent 60%); }
        .glass { background:rgba(255,255,255,.055); backdrop-filter:blur(16px) saturate(160%); border:1px solid rgba(255,255,255,.10); }
        .admin-tab { border:1px solid rgba(255,255,255,.12); background:rgba(255,255,255,.05); }
        .admin-tab.active { background:rgba(245,158,11,.18); border-color:rgba(245,158,11,.35); color:#fcd34d; }
        .panel { background:rgba(255,255,255,.055); border:1px solid rgba(255,255,255,.10); border-radius:1rem; }
        .btn-primary { background:#3454d1; color:#fff; border:1px solid rgba(255,255,255,.10); }
        .btn-primary:hover { background:#2440a8; }
        .btn-ghost { background:rgba(255,255,255,.10); border:1px solid rgba(255,255,255,.14); color:#e5e7eb; }
        .btn-ghost:hover { background:rgba(255,255,255,.18); }
        .crumb { color:rgba(229,231,235,.55); font-size:.75rem; }
        .input-dark { background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.12); color:#e5e7eb; }
        .input-dark:focus { outline:none; border-color:rgba(107,143,255,.6); box-shadow:0 0 0 2px rgba(52,84,209,.35); }
    </style>
</head>
<body class="min-h-screen text-white relative">
<div class="bg-ambient"></div>
<?php include __DIR__ . '/_nav.php'; ?>

<main class="relative z-10 w-full max-w-6xl mx-auto px-4 sm:px-6 py-8 space-y-5">
    <section class="glass rounded-3xl p-4 sm:p-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold">📣 Administration des bannières</h1>
            <p class="text-white/45 text-sm">Messages prioritaires affichés sur tout le portail.</p>
            <p class="crumb mt-1">Admin / Bannières</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="/admin.php" class="admin-tab px-3 py-1.5 rounded-lg text-xs font-semibold">🏠 Accueil Admin</a>
            <a href="/admin-news.php" class="admin-tab px-3 py-1.5 rounded-lg text-xs font-semibold">📰 Actualités</a>
            <a href="/admin-banners.php" class="admin-tab active px-3 py-1.5 rounded-lg text-xs font-semibold">📣 Bannières</a>
            <a href="/admin-status.php" class="admin-tab px-3 py-1.5 rounded-lg text-xs font-semibold">📡 Sites</a>
            <a href="/admin-apps.php" class="admin-tab px-3 py-1.5 rounded-lg text-xs font-semibold">🧩 Applications</a>
        </div>
    </section>

    <section class="panel p-5 space-y-4">
        <div id="bannerStatus" class="hidden text-sm rounded-xl px-4 py-2.5"></div>
        <form id="bannerForm" class="grid lg:grid-cols-5 gap-2.5">
            <input id="bannerTitle" type="text" maxlength="150" placeholder="Titre bannière" class="input-dark lg:col-span-1 px-3 py-2.5 rounded-xl text-sm">
            <input id="bannerMessage" type="text" maxlength="600" placeholder="Message important" class="input-dark lg:col-span-2 px-3 py-2.5 rounded-xl text-sm">
            <select id="bannerStyle" class="input-dark lg:col-span-1 px-3 py-2.5 rounded-xl text-sm">
                <option value="danger">🚨 Critique</option>
                <option value="warning">⚠️ Alerte</option>
                <option value="info">ℹ️ Info</option>
                <option value="success">✅ Succès</option>
            </select>
            <button class="lg:col-span-1 py-2.5 rounded-xl text-sm font-semibold btn-primary">Ajouter</button>
        </form>
        <div id="bannerList" class="space-y-2"></div>
    </section>
</main>

<script>
const CSRF = <?= json_encode($csrfToken) ?>;
const BANNER_DATA = <?= json_encode(array_values($banners), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>;

function esc(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function showStatus(el, msg, type) {
    el.textContent = msg;
    el.className = 'text-sm rounded-xl px-4 py-2.5 ' + (type === 'success' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-red-500/20 text-red-300');
    el.classList.remove('hidden');
    if (type === 'success') setTimeout(() => el.classList.add('hidden'), 3200);
}

function tone(style) {
    if (style === 'danger') return { cls: 'bg-red-500/20 text-red-200 border-red-500/35', icon: '🚨' };
    if (style === 'warning') return { cls: 'bg-amber-500/20 text-amber-200 border-amber-500/35', icon: '⚠️' };
    if (style === 'success') return { cls: 'bg-emerald-500/20 text-emerald-200 border-emerald-500/35', icon: '✅' };
    return { cls: 'bg-cyan-500/20 text-cyan-200 border-cyan-500/35', icon: 'ℹ️' };
}

function renderBanners() {
    const list = document.getElementById('bannerList');
    if (!BANNER_DATA.length) {
        list.innerHTML = '<p class="text-white/35 text-sm italic">Aucune bannière pour le moment.</p>';
        return;
    }

    const ordered = BANNER_DATA.slice().sort((a, b) => String(b.updated_at || b.created_at || '').localeCompare(String(a.updated_at || a.created_at || '')));
    list.innerHTML = ordered.map(b => {
        const t = tone(b.style || 'danger');
        return `<div class="glass rounded-xl p-3 border ${t.cls}">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-semibold text-sm">${t.icon} ${esc(b.title || '')}</p>
                    <p class="text-sm opacity-90 mt-0.5">${esc(b.message || '')}</p>
                    <p class="text-xs opacity-70 mt-1">${b.active ? 'Active' : 'Inactive'} · ${esc(b.updated_at || b.created_at || '')}</p>
                    <p class="text-xs opacity-70 mt-0.5">Auteur: ${esc(b.created_by || 'Inconnu')}</p>
                </div>
                <div class="flex gap-1.5">
                    <button onclick="toggleBanner('${esc(b.id)}')" class="px-2 py-1 text-xs rounded-lg btn-ghost">${b.active ? 'Désactiver' : 'Activer'}</button>
                    <button onclick="deleteBanner('${esc(b.id)}')" class="px-2 py-1 text-xs rounded-lg bg-red-500/20 text-red-200 hover:bg-red-500/30">Suppr.</button>
                </div>
            </div>
        </div>`;
    }).join('');
}

async function api(payload) {
    const res = await fetch('/save-banner.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Erreur serveur');
    return data;
}

document.getElementById('bannerForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const statusEl = document.getElementById('bannerStatus');
    const title = document.getElementById('bannerTitle').value.trim();
    const message = document.getElementById('bannerMessage').value.trim();
    const style = document.getElementById('bannerStyle').value;
    if (!title || !message) return showStatus(statusEl, 'Titre et message obligatoires.', 'error');

    try {
        const data = await api({ action:'add', csrf_token:CSRF, title, message, style, active:true });
        BANNER_DATA.push(data.banner);
        showStatus(statusEl, 'Bannière ajoutée.', 'success');
        e.target.reset();
        document.getElementById('bannerStyle').value = 'danger';
        renderBanners();
    } catch (err) {
        showStatus(statusEl, err.message, 'error');
    }
});

async function toggleBanner(id) {
    const statusEl = document.getElementById('bannerStatus');
    try {
        const data = await api({ action:'toggle', csrf_token:CSRF, id });
        const idx = BANNER_DATA.findIndex(b => b.id === id);
        if (idx >= 0) BANNER_DATA[idx] = data.banner;
        renderBanners();
        showStatus(statusEl, data.banner.active ? 'Bannière activée.' : 'Bannière désactivée.', 'success');
    } catch (err) {
        showStatus(statusEl, err.message, 'error');
    }
}

async function deleteBanner(id) {
    if (!confirm('Supprimer cette bannière ?')) return;
    const statusEl = document.getElementById('bannerStatus');
    try {
        await api({ action:'delete', csrf_token:CSRF, id });
        const idx = BANNER_DATA.findIndex(b => b.id === id);
        if (idx >= 0) BANNER_DATA.splice(idx, 1);
        renderBanners();
        showStatus(statusEl, 'Bannière supprimée.', 'success');
    } catch (err) {
        showStatus(statusEl, err.message, 'error');
    }
}

renderBanners();
</script>
</body>
</html>
