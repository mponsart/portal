<?php
$user    = $_SESSION['user'];
$config  = require __DIR__ . '/../config.php';
$isAdmin = in_array($user['email'], $config['admins'] ?? [], true);
if (!$isAdmin) { http_response_code(403); exit('Acces non autorise.'); }

$currentPage  = 'admin';
$featuredFile = __DIR__ . '/../uploads/featured.json';

$featured = [];
if (file_exists($featuredFile)) {
    $decoded  = json_decode(file_get_contents($featuredFile), true);
    $featured = is_array($decoded) ? $decoded : [];
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
    <title>Admin Actualités - Groupe Speed Cloud</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
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
        .ql-toolbar { background:rgba(255,255,255,.08)!important;border:1px solid rgba(255,255,255,.12)!important;border-radius:12px 12px 0 0!important; }
        .ql-container { background:rgba(255,255,255,.05)!important;border:1px solid rgba(255,255,255,.12)!important;border-top:none!important;border-radius:0 0 12px 12px!important;min-height:140px; }
        .ql-editor { color:#e2e8f0!important;min-height:120px; }
        .ql-editor.ql-blank::before { color:rgba(255,255,255,.25)!important;font-style:normal!important; }
        .news-card { transition:transform .15s,border-color .15s; }
        .news-card:hover { transform:translateY(-2px); border-color:rgba(255,255,255,.24); }
        .preview-box { border:1px dashed rgba(255,255,255,.18); background:rgba(255,255,255,.04); }
    </style>
</head>
<body class="min-h-screen text-white relative">
<div class="bg-ambient"></div>
<?php include __DIR__ . '/_nav.php'; ?>

<main class="relative z-10 w-full max-w-6xl mx-auto px-4 sm:px-6 py-8 space-y-5">
    <section class="glass rounded-3xl p-4 sm:p-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold">📰 Administration des actualités</h1>
            <p class="text-white/45 text-sm">Page dédiée à la création, l'édition et la publication.</p>
            <p class="crumb mt-1">Admin / Actualités</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="/admin.php" class="admin-tab px-3 py-1.5 rounded-lg text-xs font-semibold">🏠 Accueil Admin</a>
            <a href="/admin-news.php" class="admin-tab active px-3 py-1.5 rounded-lg text-xs font-semibold">📰 Actualités</a>
            <a href="/admin-banners.php" class="admin-tab px-3 py-1.5 rounded-lg text-xs font-semibold">📣 Bannières</a>
        </div>
    </section>

    <div class="grid lg:grid-cols-5 gap-5">
        <section class="lg:col-span-2 panel p-5 space-y-3">
            <h2 class="font-semibold">➕ Nouvelle actualité</h2>
            <div id="addStatus" class="hidden text-sm rounded-xl px-4 py-2.5"></div>

            <form id="addForm" class="space-y-3" novalidate>
                <div class="flex gap-2">
                    <input id="addEmoji" type="text" maxlength="4" value="📢" class="input-dark w-14 px-2 py-2.5 rounded-xl text-center text-xl">
                    <input id="addTitle" type="text" placeholder="Titre (optionnel)" class="input-dark flex-1 px-4 py-2.5 rounded-xl text-sm">
                </div>

                <div class="grid sm:grid-cols-2 gap-2">
                    <select id="addCategory" class="input-dark w-full px-3 py-2.5 rounded-xl text-sm">
                        <option value="general">Général</option>
                        <option value="info">Info</option>
                        <option value="event">Événement</option>
                        <option value="urgent">Urgent</option>
                    </select>
                    <select id="addStatusType" class="input-dark w-full px-3 py-2.5 rounded-xl text-sm">
                        <option value="published">Publier maintenant</option>
                        <option value="draft">Enregistrer brouillon</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <label class="text-white/50 text-xs">Couleur</label>
                    <input id="addColor" type="color" value="#3454d1" class="w-8 h-8 rounded border border-white/20 bg-transparent">
                    <div class="flex gap-1.5">
                        <?php foreach (['#3454d1','#ef4444','#8b5cf6','#0ea5e9','#10b981','#f59e0b'] as $c): ?>
                        <button type="button" onclick="document.getElementById('addColor').value='<?= $c ?>'" class="w-5 h-5 rounded-full border border-white/20" style="background:<?= $c ?>"></button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div id="addEditor"></div>

                <div class="preview-box rounded-2xl p-3">
                    <p class="text-white/45 text-[11px] uppercase tracking-[.14em] mb-2">Aperçu article</p>
                    <p class="text-sm font-semibold"><span id="addPrevEmoji">📢</span> <span id="addPrevTitle">Titre</span></p>
                    <p class="text-white/35 text-xs" id="addPrevMeta">Publie</p>
                    <div id="addPrevBody" class="text-white/70 text-sm mt-2"></div>
                </div>

                <button class="w-full py-2.5 rounded-xl text-sm font-semibold btn-primary">Enregistrer</button>
            </form>
        </section>

        <section class="lg:col-span-3 space-y-3">
            <div class="panel p-2.5 flex flex-wrap items-center gap-2">
                <span class="text-white/55 text-xs">Tri</span>
                <select id="sortMode" class="input-dark px-2.5 py-1.5 rounded-lg text-xs">
                    <option value="recent">Récent</option>
                    <option value="urgent">Urgent d'abord</option>
                    <option value="category">Catégorie</option>
                </select>
                <span class="text-white/55 text-xs">Filtre</span>
                <select id="filterStatus" class="input-dark px-2.5 py-1.5 rounded-lg text-xs">
                    <option value="all">Tous statuts</option>
                    <option value="published">Publiés</option>
                    <option value="draft">Brouillons</option>
                </select>
                <select id="filterCategory" class="input-dark px-2.5 py-1.5 rounded-lg text-xs">
                    <option value="all">Toutes catégories</option>
                    <option value="urgent">Urgent</option>
                    <option value="event">Événement</option>
                    <option value="info">Info</option>
                    <option value="general">Général</option>
                </select>
                <span id="annCount" class="ml-auto text-white/45 text-xs"></span>
            </div>
            <div id="annList" class="space-y-3"></div>
        </section>
    </div>
</main>

<div id="editModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeEdit()"></div>
    <div class="relative panel p-5 w-full max-w-xl space-y-3 z-10">
        <div class="flex items-center justify-between">
            <h3 class="font-semibold">✏️ Modifier</h3>
            <button onclick="closeEdit()" class="px-2 py-1 rounded-lg btn-ghost">✖️</button>
        </div>
        <div id="editStatus" class="hidden text-sm rounded-xl px-4 py-2.5"></div>
        <input id="editId" type="hidden">
        <div class="flex gap-2">
            <input id="editEmoji" type="text" maxlength="4" class="input-dark w-14 px-2 py-2.5 rounded-xl text-center text-xl">
            <input id="editTitle" type="text" class="input-dark flex-1 px-4 py-2.5 rounded-xl text-sm" placeholder="Titre">
        </div>
        <div class="grid sm:grid-cols-2 gap-2">
            <select id="editCategory" class="input-dark w-full px-3 py-2.5 rounded-xl text-sm">
                <option value="general">Général</option>
                <option value="info">Info</option>
                <option value="event">Événement</option>
                <option value="urgent">Urgent</option>
            </select>
            <select id="editStatusType" class="input-dark w-full px-3 py-2.5 rounded-xl text-sm">
                <option value="published">Publié</option>
                <option value="draft">Brouillon</option>
            </select>
        </div>
        <div class="flex items-center gap-2">
            <label class="text-white/50 text-xs">Couleur</label>
            <input id="editColor" type="color" class="w-8 h-8 rounded border border-white/20 bg-transparent">
        </div>
        <div id="editEditor"></div>
        <button id="editSubmitBtn" onclick="submitEdit()" class="w-full py-2.5 rounded-xl text-sm font-semibold btn-primary">Enregistrer</button>
    </div>
</div>

<script>
const CSRF = <?= json_encode($csrfToken) ?>;
const ANN_DATA = <?= json_encode(array_values($featured), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>;

const quillAdd = new Quill('#addEditor', {
    theme: 'snow',
    placeholder: 'Rédigez votre actualité...',
    modules: { toolbar: [[{ header: [2,3,false] }], ['bold','italic','underline','strike'], [{ list:'ordered' },{ list:'bullet' }], ['blockquote'], ['clean']] }
});
const quillEdit = new Quill('#editEditor', {
    theme: 'snow',
    placeholder: 'Modifiez le contenu...',
    modules: { toolbar: [[{ header: [2,3,false] }], ['bold','italic','underline','strike'], [{ list:'ordered' },{ list:'bullet' }], ['blockquote'], ['clean']] }
});

function esc(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function parseFrDate(value) {
    const m = String(value || '').match(/(\d{2})\/(\d{2})\/(\d{4})\s+à\s+(\d{2}):(\d{2})/);
    if (!m) return 0;
    return Date.UTC(Number(m[3]), Number(m[2]) - 1, Number(m[1]), Number(m[4]), Number(m[5]));
}

function showStatus(el, msg, type) {
    el.textContent = msg;
    el.className = 'text-sm rounded-xl px-4 py-2.5 ' + (type === 'success' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-red-500/20 text-red-300');
    el.classList.remove('hidden');
    if (type === 'success') setTimeout(() => el.classList.add('hidden'), 3200);
}

function updateAddPreview() {
    const emoji = document.getElementById('addEmoji').value.trim() || '📢';
    const title = document.getElementById('addTitle').value.trim() || 'Titre';
    const status = document.getElementById('addStatusType').value === 'draft' ? 'Brouillon' : 'Publie';
    document.getElementById('addPrevEmoji').textContent = emoji;
    document.getElementById('addPrevTitle').textContent = title;
    document.getElementById('addPrevMeta').textContent = status;
    document.getElementById('addPrevBody').innerHTML = quillAdd.root.innerHTML;
}

function filteredSortedAnnouncements() {
    const mode = document.getElementById('sortMode').value;
    const fs = document.getElementById('filterStatus').value;
    const fc = document.getElementById('filterCategory').value;

    let items = ANN_DATA.slice();
    if (fs !== 'all') items = items.filter(a => (a.status || 'published') === fs);
    if (fc !== 'all') items = items.filter(a => (a.category || 'general') === fc);

    items.sort((a, b) => {
        if (mode === 'urgent') {
            const av = (a.category === 'urgent') ? 0 : 1;
            const bv = (b.category === 'urgent') ? 0 : 1;
            if (av !== bv) return av - bv;
        }
        if (mode === 'category') return String(a.category || '').localeCompare(String(b.category || ''));
        return parseFrDate(b.updated_at || b.created_at || '') - parseFrDate(a.updated_at || a.created_at || '');
    });

    return items;
}

function renderAnnouncements() {
    const list = document.getElementById('annList');
    const items = filteredSortedAnnouncements();
    document.getElementById('annCount').textContent = `${items.length} entrée${items.length > 1 ? 's' : ''}`;

    if (!items.length) {
        list.innerHTML = '<p class="text-white/35 text-sm italic">Aucune actualité pour ce filtre.</p>';
        return;
    }

    list.innerHTML = items.map(a => {
        const status = (a.status || 'published') === 'draft' ? 'Brouillon' : 'Publie';
        const statusCls = (a.status || 'published') === 'draft' ? 'bg-amber-500/20 text-amber-300' : 'bg-emerald-500/20 text-emerald-300';
        const catCls = a.category === 'urgent' ? 'bg-red-500/20 text-red-300' : (a.category === 'event' ? 'bg-violet-500/20 text-violet-300' : (a.category === 'info' ? 'bg-cyan-500/20 text-cyan-300' : 'bg-blue-500/20 text-blue-300'));
        const catLabel = a.category === 'urgent' ? 'Urgent' : (a.category === 'event' ? 'Evenement' : (a.category === 'info' ? 'Info' : 'General'));
        const title = a.title ? `<p class="font-semibold text-sm text-white leading-snug">${esc(a.title)}</p>` : '';
        return `<div class="news-card glass rounded-2xl p-4" style="border-left:3px solid ${esc(a.color || '#3454d1')}">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-3 min-w-0 flex-1">
                    <span class="text-lg mt-0.5">${esc(a.emoji || '📢')}</span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap mb-1">${title}
                            <span class="text-xs px-2 py-0.5 rounded-full ${catCls}">${catLabel}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full ${statusCls}">${status}</span>
                        </div>
                        <div class="text-white/50 text-xs leading-relaxed line-clamp-2">${esc(String(a.html_content || '').replace(/<[^>]+>/g,''))}</div>
                        <p class="text-white/25 text-xs mt-2">Maj: ${esc(a.updated_at || a.created_at || '')}</p>
                    </div>
                </div>
                <div class="flex gap-1">
                    <button onclick="editAnn('${esc(a.id)}')" class="p-1.5 text-blue-400 hover:bg-blue-500/20 rounded-lg">✏️</button>
                    <button onclick="deleteAnn('${esc(a.id)}')" class="p-1.5 text-red-400 hover:bg-red-500/20 rounded-lg">🗑️</button>
                </div>
            </div>
        </div>`;
    }).join('');
}

async function apiFeatured(payload) {
    const res = await fetch('/save-featured.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Erreur serveur');
    return data;
}

document.getElementById('addForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const statusEl = document.getElementById('addStatus');
    const btn = e.target.querySelector('button[type="submit"]');
    if (quillAdd.getText().trim() === '') return showStatus(statusEl, 'Le contenu est obligatoire.', 'error');

    btn.disabled = true;
    btn.textContent = 'Enregistrement...';
    try {
        const data = await apiFeatured({
            action:'add', csrf_token:CSRF,
            emoji:document.getElementById('addEmoji').value.trim() || '📢',
            title:document.getElementById('addTitle').value.trim(),
            category:document.getElementById('addCategory').value,
            status:document.getElementById('addStatusType').value,
            color:document.getElementById('addColor').value,
            html_content:quillAdd.root.innerHTML,
        });
        ANN_DATA.unshift(data.announcement);
        showStatus(statusEl, data.announcement.status === 'draft' ? 'Brouillon enregistré.' : 'Actualité publiée.', 'success');
        e.target.reset();
        quillAdd.setContents([]);
        document.getElementById('addEmoji').value = '📢';
        document.getElementById('addColor').value = '#3454d1';
        document.getElementById('addStatusType').value = 'published';
        updateAddPreview();
        renderAnnouncements();
    } catch (err) {
        showStatus(statusEl, err.message, 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Enregistrer';
    }
});

function editAnn(id) {
    const ann = ANN_DATA.find(a => a.id === id);
    if (!ann) return;
    document.getElementById('editId').value = id;
    document.getElementById('editEmoji').value = ann.emoji || '📢';
    document.getElementById('editTitle').value = ann.title || '';
    document.getElementById('editCategory').value = ann.category || 'general';
    document.getElementById('editStatusType').value = ann.status || 'published';
    document.getElementById('editColor').value = ann.color || '#3454d1';
    quillEdit.root.innerHTML = ann.html_content || '';
    document.getElementById('editStatus').classList.add('hidden');
    document.getElementById('editModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeEdit() {
    document.getElementById('editModal').classList.add('hidden');
    document.body.style.overflow = '';
}

async function submitEdit() {
    const statusEl = document.getElementById('editStatus');
    const btn = document.getElementById('editSubmitBtn');
    const id = document.getElementById('editId').value;
    if (quillEdit.getText().trim() === '') return showStatus(statusEl, 'Le contenu est obligatoire.', 'error');

    btn.disabled = true;
    btn.textContent = 'Enregistrement...';
    try {
        const data = await apiFeatured({
            action:'update', csrf_token:CSRF, id,
            emoji:document.getElementById('editEmoji').value.trim() || '📢',
            title:document.getElementById('editTitle').value.trim(),
            category:document.getElementById('editCategory').value,
            status:document.getElementById('editStatusType').value,
            color:document.getElementById('editColor').value,
            html_content:quillEdit.root.innerHTML,
        });
        const idx = ANN_DATA.findIndex(a => a.id === id);
        if (idx >= 0) ANN_DATA[idx] = data.announcement;
        renderAnnouncements();
        showStatus(statusEl, 'Modifications enregistrées.', 'success');
        setTimeout(closeEdit, 800);
    } catch (err) {
        showStatus(statusEl, err.message, 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Enregistrer';
    }
}

async function deleteAnn(id) {
    if (!confirm('Supprimer cette actualité ?')) return;
    try {
        await apiFeatured({ action:'delete', csrf_token:CSRF, id });
        const idx = ANN_DATA.findIndex(a => a.id === id);
        if (idx >= 0) ANN_DATA.splice(idx, 1);
        renderAnnouncements();
    } catch (err) {
        alert(err.message);
    }
}

quillAdd.on('text-change', updateAddPreview);
document.getElementById('addEmoji').addEventListener('input', updateAddPreview);
document.getElementById('addTitle').addEventListener('input', updateAddPreview);
document.getElementById('addStatusType').addEventListener('change', updateAddPreview);
document.getElementById('sortMode').addEventListener('change', renderAnnouncements);
document.getElementById('filterStatus').addEventListener('change', renderAnnouncements);
document.getElementById('filterCategory').addEventListener('change', renderAnnouncements);
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeEdit(); });

updateAddPreview();
renderAnnouncements();
</script>
</body>
</html>
