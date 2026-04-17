<?php
$user    = $_SESSION['user'];
$config  = require __DIR__ . '/../config.php';
$isAdmin = in_array($user['email'], $config['admins'] ?? [], true);
if (!$isAdmin) { http_response_code(403); include __DIR__ . '/unauthorized.php'; exit; }

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
    <link rel="icon" type="image/png" href="/assets/images/cloudy.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php include __DIR__ . '/_ui-tokens.php'; ?>
    <style>
        body { font-family:'Inter',sans-serif; background:var(--bg); color-scheme:dark; }
        .bg-ambient { position:fixed; inset:0; pointer-events:none; z-index:0;
            background: radial-gradient(ellipse 70% 55% at 15% 0%, rgba(124,58,237,.26) 0%, transparent 65%),
                        radial-gradient(ellipse 50% 40% at 88% 100%, rgba(8,145,178,.18) 0%, transparent 60%); }
        .glass { background:rgba(255,255,255,.055); border:1px solid rgba(255,255,255,.09); border-radius:16px; }
        .admin-tab { border:1px solid rgba(255,255,255,.12); background:rgba(255,255,255,.05); }
        .admin-tab.active { background:rgba(124,58,237,.2); border-color:rgba(124,58,237,.45); color:#a78bfa; }
        .panel { background:rgba(255,255,255,.055); border:1px solid rgba(255,255,255,.10); border-radius:1rem; }
        .btn-primary { background:var(--primary); color:#fff; border:none; }
        .btn-primary:hover { background:var(--primary-dk); }
        .btn-ghost { background:rgba(255,255,255,.10); border:1px solid rgba(255,255,255,.14); color:#e5e7eb; }
        .btn-ghost:hover { background:rgba(255,255,255,.18); }
        .crumb { color:rgba(229,231,235,.55); font-size:.75rem; }
        .input-dark { background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.12); color:#e5e7eb; }
        .input-dark:focus { outline:none; border-color:rgba(167,139,250,.55); box-shadow:0 0 0 2px rgba(124,58,237,.25); }
        .tiptap-shell { border:1px solid rgba(255,255,255,.12); background:rgba(255,255,255,.04); }
        .tiptap-toolbar { border-bottom:1px solid rgba(255,255,255,.12); background:rgba(255,255,255,.05); }
        .tiptap-btn { border:1px solid rgba(255,255,255,.14); background:rgba(255,255,255,.08); color:#e2e8f0; }
        .tiptap-btn:hover { background:rgba(255,255,255,.15); }
        .tiptap-btn.active { background:rgba(124,58,237,.25); border-color:rgba(167,139,250,.8); }
        .tiptap-editor { min-height:220px; padding:.9rem 1rem; }
        .tiptap-editor:focus { outline:none; }
        .tiptap-editor .ProseMirror { min-height:220px; }
        .tiptap-editor .ProseMirror:focus { outline:none; }
        .tiptap-editor p { margin:.45rem 0; }
        .tiptap-editor ul, .tiptap-editor ol { margin:.45rem 0 .45rem 1.1rem; }
        .tiptap-editor blockquote { border-left:3px solid rgba(148,163,184,.45); padding-left:.75rem; color:#cbd5e1; margin:.45rem 0; }
        .tiptap-editor pre { background:rgba(2,6,23,.8); color:#e2e8f0; border:1px solid rgba(255,255,255,.12); border-radius:.5rem; padding:.6rem .75rem; overflow:auto; }
        .tiptap-editor code { background:rgba(255,255,255,.08); padding:0 .25rem; border-radius:.25rem; }
        .news-card { transition:transform .15s,border-color .15s; }
        .news-card:hover { transform:translateY(-2px); border-color:rgba(255,255,255,.24); }
        .editor-meta { color:rgba(226,232,240,.65); font-size:.75rem; }
        .editor-meta.warn { color:#fda4af; }
        .editor-preview { border:1px dashed rgba(255,255,255,.18); background:rgba(255,255,255,.04); }
        .md-preview p { margin:.4rem 0; }
        .md-preview ul, .md-preview ol { margin:.4rem 0 .4rem 1.2rem; }
        .md-preview blockquote { border-left:3px solid rgba(148,163,184,.45); padding-left:.75rem; color:#cbd5e1; margin:.4rem 0; }
        .md-preview pre { background:rgba(2,6,23,.8); color:#e2e8f0; border:1px solid rgba(255,255,255,.12); border-radius:.5rem; padding:.6rem .75rem; overflow:auto; }
        .md-preview code { background:rgba(255,255,255,.08); padding:0 .25rem; border-radius:.25rem; }
    </style>
</head>
<body class="min-h-screen text-white relative">
<div class="bg-ambient"></div>
<?php include __DIR__ . '/_nav.php'; ?>

<main class="page-stack relative z-10 w-full max-w-6xl mx-auto px-4 sm:px-6 py-8">
    <section class="glass rounded-3xl p-4 sm:p-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold">📰 Administration des actualités</h1>
            <p class="text-white/45 text-sm">Page dédiée à l'édition et à la publication.</p>
            <p class="crumb mt-1">Admin / Actualités</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="/admin.php" class="admin-tab px-3 py-1.5 rounded-lg text-xs font-semibold">🏠 Accueil Admin</a>
            <a href="/admin-news.php" class="admin-tab active px-3 py-1.5 rounded-lg text-xs font-semibold">📰 Actualités</a>
            <a href="/admin-news-new.php" class="admin-tab px-3 py-1.5 rounded-lg text-xs font-semibold">➕ Nouvelle actualité</a>
            <a href="/admin-banners.php" class="admin-tab px-3 py-1.5 rounded-lg text-xs font-semibold">📣 Bannières</a>
            <a href="/admin-status.php" class="admin-tab px-3 py-1.5 rounded-lg text-xs font-semibold">📡 Sites</a>
            <a href="/admin-apps.php" class="admin-tab px-3 py-1.5 rounded-lg text-xs font-semibold">🧩 Applications</a>
            <a href="/admin-users.php" class="admin-tab px-3 py-1.5 rounded-lg text-xs font-semibold">👥 Utilisateurs</a>
        </div>
    </section>

    <div class="space-y-3">
        <div class="panel p-4 flex items-center justify-between gap-3">
            <p class="text-sm text-white/65">La création d'une actualité se fait désormais dans une sous-page dédiée.</p>
            <a href="/admin-news-new.php" class="btn-primary px-3 py-2 rounded-lg text-xs font-semibold whitespace-nowrap">➕ Nouvelle actualité</a>
        </div>
        <section class="space-y-3">
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
        <div class="tiptap-shell rounded-xl overflow-hidden">
            <div class="tiptap-toolbar p-2 flex flex-wrap items-center gap-1.5">
                <button type="button" data-edit-action="bold" class="tiptap-btn px-2.5 py-1 rounded text-xs"><strong>Gras</strong></button>
                <button type="button" data-edit-action="italic" class="tiptap-btn px-2.5 py-1 rounded text-xs"><em>Italique</em></button>
                <button type="button" data-edit-action="h2" class="tiptap-btn px-2.5 py-1 rounded text-xs">Titre</button>
                <button type="button" data-edit-action="bullet" class="tiptap-btn px-2.5 py-1 rounded text-xs">Liste</button>
                <button type="button" data-edit-action="quote" class="tiptap-btn px-2.5 py-1 rounded text-xs">Citation</button>
                <button type="button" data-edit-action="code" class="tiptap-btn px-2.5 py-1 rounded text-xs">Code</button>
                <button type="button" data-edit-action="link" class="tiptap-btn px-2.5 py-1 rounded text-xs">Lien</button>
            </div>
            <div id="editEditor" class="tiptap-editor text-sm"></div>
        </div>
        <div class="flex items-center justify-between gap-2">
            <p class="text-white/40 text-xs">Astuce: Ctrl/Cmd + Entrée pour enregistrer.</p>
            <p id="editEditorMeta" class="editor-meta">0 mot • 0 caractère</p>
        </div>
        <div class="editor-preview rounded-xl p-3">
            <p class="text-white/45 text-[11px] uppercase tracking-[.14em] mb-2">Aperçu du contenu</p>
            <div id="editPreviewBody" class="text-white/75 text-sm md-preview"></div>
        </div>
        <button id="editSubmitBtn" onclick="submitEdit()" class="w-full py-2.5 rounded-xl text-sm font-semibold btn-primary">Enregistrer</button>
    </div>
</div>

<script type="module">
import { Editor } from 'https://esm.sh/@tiptap/core@2.11.5';
import StarterKit from 'https://esm.sh/@tiptap/starter-kit@2.11.5';
import Link from 'https://esm.sh/@tiptap/extension-link@2.11.5';

const CSRF = <?= json_encode($csrfToken) ?>;
const ANN_DATA = <?= json_encode(array_values($featured), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>;
const MAX_EDITOR_CHARS = 20000;
const editEditorRoot = document.getElementById('editEditor');

const editEditor = new Editor({
    element: editEditorRoot,
    extensions: [
        StarterKit.configure({
            heading: { levels: [1, 2, 3] },
        }),
        Link.configure({
            openOnClick: true,
            autolink: true,
            linkOnPaste: true,
            protocols: ['http', 'https', 'mailto'],
        }),
    ],
    content: '<p></p>',
    editorProps: {
        attributes: { class: 'focus:outline-none' },
    },
    onUpdate: () => {
        updateEditMeta();
        refreshEditToolbarState();
    },
    onSelectionUpdate: () => {
        refreshEditToolbarState();
    },
});

function escHtml(s) {
    return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function htmlToText(html) {
    const tmp = document.createElement('div');
    tmp.innerHTML = html || '';
    return (tmp.textContent || tmp.innerText || '').trim();
}

function getEditorHtml() {
    return editEditor.getHTML();
}

function getEditorPlainText() {
    return editEditor.getText({ blockSeparator: '\n' }).trim();
}

function getEditorStats() {
    const text = getEditorPlainText().replace(/\s+/g, ' ').trim();
    const chars = text.length;
    const words = text ? text.split(' ').length : 0;
    return { chars, words };
}

function refreshEditToolbarState() {
    document.querySelectorAll('[data-edit-action]').forEach((btn) => btn.classList.remove('active'));
    if (editEditor.isActive('bold')) document.querySelector('[data-edit-action="bold"]')?.classList.add('active');
    if (editEditor.isActive('italic')) document.querySelector('[data-edit-action="italic"]')?.classList.add('active');
    if (editEditor.isActive('heading', { level: 2 })) document.querySelector('[data-edit-action="h2"]')?.classList.add('active');
    if (editEditor.isActive('bulletList')) document.querySelector('[data-edit-action="bullet"]')?.classList.add('active');
    if (editEditor.isActive('blockquote')) document.querySelector('[data-edit-action="quote"]')?.classList.add('active');
    if (editEditor.isActive('codeBlock')) document.querySelector('[data-edit-action="code"]')?.classList.add('active');
    if (editEditor.isActive('link')) document.querySelector('[data-edit-action="link"]')?.classList.add('active');
}

function updateEditMeta() {
    const meta = document.getElementById('editEditorMeta');
    const preview = document.getElementById('editPreviewBody');
    const stats = getEditorStats();
    meta.textContent = `${stats.words} mot${stats.words > 1 ? 's' : ''} • ${stats.chars}/${MAX_EDITOR_CHARS} caractères`;
    meta.classList.toggle('warn', stats.chars > MAX_EDITOR_CHARS);
    preview.innerHTML = getEditorHtml() || '<span class="text-white/35 italic">Aucun contenu</span>';
}

document.querySelector('[data-edit-action="bold"]')?.addEventListener('click', () => editEditor.chain().focus().toggleBold().run());
document.querySelector('[data-edit-action="italic"]')?.addEventListener('click', () => editEditor.chain().focus().toggleItalic().run());
document.querySelector('[data-edit-action="h2"]')?.addEventListener('click', () => editEditor.chain().focus().toggleHeading({ level: 2 }).run());
document.querySelector('[data-edit-action="bullet"]')?.addEventListener('click', () => editEditor.chain().focus().toggleBulletList().run());
document.querySelector('[data-edit-action="quote"]')?.addEventListener('click', () => editEditor.chain().focus().toggleBlockquote().run());
document.querySelector('[data-edit-action="code"]')?.addEventListener('click', () => editEditor.chain().focus().toggleCodeBlock().run());
document.querySelector('[data-edit-action="link"]')?.addEventListener('click', () => {
    const previousUrl = editEditor.getAttributes('link').href || '';
    const url = window.prompt('URL du lien', previousUrl || 'https://');
    if (url === null) return;
    const trimmed = String(url).trim();
    if (trimmed === '') {
        editEditor.chain().focus().unsetLink().run();
        return;
    }
    editEditor.chain().focus().setLink({ href: trimmed }).run();
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
        const status = (a.status || 'published') === 'draft' ? 'Brouillon' : 'Publié';
        const statusCls = (a.status || 'published') === 'draft' ? 'bg-amber-500/20 text-amber-300' : 'bg-emerald-500/20 text-emerald-300';
        const catCls = a.category === 'urgent' ? 'bg-red-500/20 text-red-300' : (a.category === 'event' ? 'bg-violet-500/20 text-violet-300' : (a.category === 'info' ? 'bg-cyan-500/20 text-cyan-300' : 'bg-violet-500/20 text-blue-300'));
        const catLabel = a.category === 'urgent' ? 'Urgent' : (a.category === 'event' ? 'Événement' : (a.category === 'info' ? 'Info' : 'Général'));
        const title = a.title ? `<p class="font-semibold text-sm text-white leading-snug">${esc(a.title)}</p>` : '';
        return `<div class="news-card glass rounded-2xl p-4" style="border-left:3px solid ${esc(a.color || '#7c3aed')}">
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
                    <button onclick="editAnn('${esc(a.id)}')" class="p-1.5 text-blue-400 hover:bg-violet-500/20 rounded-lg">✏️</button>
                    <button onclick="deleteAnn('${esc(a.id)}')" class="p-1.5 text-red-400 hover:bg-red-500/20 rounded-lg">🗑️</button>
                </div>
            </div>
        </div>`;
    }).join('');
}

async function apiFeatured(payload) {
    const res = await fetch('/save-featured.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });
    const raw = await res.text();
    let data;
    try {
        data = JSON.parse(raw);
    } catch {
        throw new Error('Réponse serveur invalide.');
    }
    if (!res.ok) throw new Error(data.error || 'Erreur serveur');
    return data;
}

function editAnn(id) {
    const ann = ANN_DATA.find(a => a.id === id);
    if (!ann) return;
    document.getElementById('editId').value = id;
    document.getElementById('editEmoji').value = ann.emoji || '📢';
    document.getElementById('editTitle').value = ann.title || '';
    document.getElementById('editCategory').value = ann.category || 'general';
    document.getElementById('editStatusType').value = ann.status || 'published';
    document.getElementById('editColor').value = ann.color || '#7c3aed';
    const htmlContent = String(ann.html_content || '').trim();
    const fallbackText = ann.markdown_content || htmlToText(ann.html_content || '');
    const fallbackHtml = `<p>${escHtml(fallbackText).replace(/\n/g, '<br>')}</p>`;
    editEditor.commands.setContent(htmlContent !== '' ? htmlContent : fallbackHtml);
    editEditor.commands.focus('end');
    updateEditMeta();
    refreshEditToolbarState();
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
    const markdownContent = getEditorPlainText();
    const htmlContent = getEditorHtml();
    if (markdownContent === '') return showStatus(statusEl, 'Le contenu est obligatoire.', 'error');
    const stats = getEditorStats();
    if (stats.chars > MAX_EDITOR_CHARS) return showStatus(statusEl, `Le contenu dépasse ${MAX_EDITOR_CHARS} caractères.`, 'error');

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
            markdown_content:markdownContent,
            html_content:htmlContent,
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

window.editAnn = editAnn;
window.deleteAnn = deleteAnn;
window.submitEdit = submitEdit;
window.closeEdit = closeEdit;

document.getElementById('sortMode').addEventListener('change', renderAnnouncements);
document.getElementById('filterStatus').addEventListener('change', renderAnnouncements);
document.getElementById('filterCategory').addEventListener('change', renderAnnouncements);
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeEdit(); });
editEditorRoot.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        e.preventDefault();
        submitEdit();
    }
});

renderAnnouncements();
updateEditMeta();
refreshEditToolbarState();
</script>
</body>
</html>
