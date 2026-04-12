<?php
$user    = $_SESSION['user'];
$config  = require __DIR__ . '/../config.php';
$isAdmin = in_array($user['email'], $config['admins'] ?? [], true);
if (!$isAdmin) { http_response_code(403); exit('Accès non autorisé.'); }

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
        .panel { background:rgba(255,255,255,.055); border:1px solid rgba(255,255,255,.10); border-radius:1rem; }
        .btn-primary { background:#3454d1; color:#fff; border:1px solid rgba(255,255,255,.10); }
        .btn-primary:hover { background:#2440a8; }
        .btn-ghost { background:rgba(255,255,255,.10); border:1px solid rgba(255,255,255,.14); color:#e5e7eb; }
        .btn-ghost:hover { background:rgba(255,255,255,.18); }
        .crumb { color:rgba(229,231,235,.55); font-size:.75rem; }
        .input-dark { background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.12); color:#e5e7eb; }
        .input-dark:focus { outline:none; border-color:rgba(107,143,255,.6); box-shadow:0 0 0 2px rgba(52,84,209,.35); }
        .md-editor { min-height:220px; resize:vertical; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,Liberation Mono,Courier New,monospace; }
        .md-toolbar { border:1px solid rgba(255,255,255,.12); background:rgba(255,255,255,.06); }
        .md-btn { border:1px solid rgba(255,255,255,.14); background:rgba(255,255,255,.08); color:#e2e8f0; }
        .md-btn:hover { background:rgba(255,255,255,.15); }
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
        <div class="md-toolbar rounded-xl p-2 flex flex-wrap items-center gap-1.5">
            <button type="button" class="md-btn px-2.5 py-1 rounded text-xs" onclick="editWrapSelection('**', '**')"><strong>Gras</strong></button>
            <button type="button" class="md-btn px-2.5 py-1 rounded text-xs" onclick="editWrapSelection('*', '*')"><em>Italique</em></button>
            <button type="button" class="md-btn px-2.5 py-1 rounded text-xs" onclick="editPrefixLines('- ')">Liste</button>
            <button type="button" class="md-btn px-2.5 py-1 rounded text-xs" onclick="editPrefixLines('> ')">Citation</button>
            <button type="button" class="md-btn px-2.5 py-1 rounded text-xs" onclick="editInsertLink()">Lien</button>
            <button type="button" class="md-btn px-2.5 py-1 rounded text-xs" onclick="editInsertCodeBlock()">Code</button>
        </div>
        <textarea id="editMarkdown" class="input-dark md-editor w-full px-4 py-3 rounded-xl text-sm" placeholder="Modifiez le contenu en Markdown..."></textarea>
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

<script>
const CSRF = <?= json_encode($csrfToken) ?>;
const ANN_DATA = <?= json_encode(array_values($featured), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>;
const MAX_EDITOR_CHARS = 20000;
const editInput = document.getElementById('editMarkdown');

function escHtml(s) {
    return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function renderMarkdown(md) {
    const lines = String(md || '').replace(/\r\n/g, '\n').split('\n');
    const out = [];
    let inUl = false;
    let inOl = false;
    let inCode = false;

    const closeLists = () => {
        if (inUl) { out.push('</ul>'); inUl = false; }
        if (inOl) { out.push('</ol>'); inOl = false; }
    };

    const inline = (text) => {
        let t = escHtml(text);
        t = t.replace(/`([^`]+)`/g, '<code>$1</code>');
        t = t.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        t = t.replace(/\*([^*]+)\*/g, '<em>$1</em>');
        t = t.replace(/\[([^\]]+)\]\(([^)]+)\)/g, (_, label, href) => {
            const url = String(href || '').trim();
            if (!/^(https?:\/\/|mailto:|\/|#)/i.test(url)) return label;
            return `<a href="${url}" target="_blank" rel="noopener noreferrer" class="text-sky-300 underline">${label}</a>`;
        });
        return t;
    };

    for (const line of lines) {
        if (line.startsWith('```')) {
            closeLists();
            out.push(inCode ? '</code></pre>' : '<pre><code>');
            inCode = !inCode;
            continue;
        }
        if (inCode) {
            out.push(`${escHtml(line)}\n`);
            continue;
        }
        if (/^\s*$/.test(line)) {
            closeLists();
            continue;
        }
        const h = line.match(/^(#{1,3})\s+(.+)$/);
        if (h) {
            closeLists();
            const level = h[1].length;
            out.push(`<h${level} class="font-bold mt-2 mb-1">${inline(h[2])}</h${level}>`);
            continue;
        }
        const ul = line.match(/^[-*]\s+(.+)$/);
        if (ul) {
            if (!inUl) { closeLists(); out.push('<ul class="list-disc pl-5">'); inUl = true; }
            out.push(`<li>${inline(ul[1])}</li>`);
            continue;
        }
        const ol = line.match(/^\d+\.\s+(.+)$/);
        if (ol) {
            if (!inOl) { closeLists(); out.push('<ol class="list-decimal pl-5">'); inOl = true; }
            out.push(`<li>${inline(ol[1])}</li>`);
            continue;
        }
        const bq = line.match(/^>\s+(.+)$/);
        if (bq) {
            closeLists();
            out.push(`<blockquote>${inline(bq[1])}</blockquote>`);
            continue;
        }
        closeLists();
        out.push(`<p>${inline(line)}</p>`);
    }
    closeLists();
    if (inCode) out.push('</code></pre>');
    return out.join('');
}

function htmlToText(html) {
    const tmp = document.createElement('div');
    tmp.innerHTML = html || '';
    return (tmp.textContent || tmp.innerText || '').trim();
}

function getEditorHtml() {
    return renderMarkdown(editInput.value.trim());
}

function getEditorStats() {
    const text = editInput.value.replace(/\s+/g, ' ').trim();
    const chars = text.length;
    const words = text ? text.split(' ').length : 0;
    return { chars, words };
}

function updateEditMeta() {
    const meta = document.getElementById('editEditorMeta');
    const preview = document.getElementById('editPreviewBody');
    const stats = getEditorStats();
    meta.textContent = `${stats.words} mot${stats.words > 1 ? 's' : ''} • ${stats.chars}/${MAX_EDITOR_CHARS} caractères`;
    meta.classList.toggle('warn', stats.chars > MAX_EDITOR_CHARS);
    preview.innerHTML = getEditorHtml() || '<span class="text-white/35 italic">Aucun contenu</span>';
}

function editReplaceSelection(transformer) {
    const start = editInput.selectionStart;
    const end = editInput.selectionEnd;
    const before = editInput.value.slice(0, start);
    const selected = editInput.value.slice(start, end);
    const after = editInput.value.slice(end);
    const { text, cursorStart, cursorEnd } = transformer(selected);
    editInput.value = before + text + after;
    editInput.focus();
    editInput.setSelectionRange(before.length + cursorStart, before.length + cursorEnd);
    updateEditMeta();
}

function editWrapSelection(prefix, suffix) {
    editReplaceSelection((selected) => {
        const core = selected || 'texte';
        return {
            text: `${prefix}${core}${suffix}`,
            cursorStart: prefix.length,
            cursorEnd: prefix.length + core.length,
        };
    });
}

function editPrefixLines(prefix) {
    editReplaceSelection((selected) => {
        const core = selected || 'élément';
        const lines = core.split('\n').map((l) => `${prefix}${l}`);
        const text = lines.join('\n');
        return { text, cursorStart: 0, cursorEnd: text.length };
    });
}

function editInsertLink() {
    editReplaceSelection((selected) => {
        const label = selected || 'texte du lien';
        const text = `[${label}](https://)`;
        return { text, cursorStart: 1, cursorEnd: 1 + label.length };
    });
}

function editInsertCodeBlock() {
    editReplaceSelection((selected) => {
        const core = selected || 'code ici';
        const text = `\n\`\`\`\n${core}\n\`\`\`\n`;
        return { text, cursorStart: 5, cursorEnd: 5 + core.length };
    });
}

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
        const catCls = a.category === 'urgent' ? 'bg-red-500/20 text-red-300' : (a.category === 'event' ? 'bg-violet-500/20 text-violet-300' : (a.category === 'info' ? 'bg-cyan-500/20 text-cyan-300' : 'bg-blue-500/20 text-blue-300'));
        const catLabel = a.category === 'urgent' ? 'Urgent' : (a.category === 'event' ? 'Événement' : (a.category === 'info' ? 'Info' : 'Général'));
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

function editAnn(id) {
    const ann = ANN_DATA.find(a => a.id === id);
    if (!ann) return;
    document.getElementById('editId').value = id;
    document.getElementById('editEmoji').value = ann.emoji || '📢';
    document.getElementById('editTitle').value = ann.title || '';
    document.getElementById('editCategory').value = ann.category || 'general';
    document.getElementById('editStatusType').value = ann.status || 'published';
    document.getElementById('editColor').value = ann.color || '#3454d1';
    editInput.value = ann.markdown_content || htmlToText(ann.html_content || '');
    editInput.focus();
    updateEditMeta();
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
    const markdownContent = editInput.value.trim();
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

document.getElementById('sortMode').addEventListener('change', renderAnnouncements);
document.getElementById('filterStatus').addEventListener('change', renderAnnouncements);
document.getElementById('filterCategory').addEventListener('change', renderAnnouncements);
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeEdit(); });
editInput.addEventListener('input', updateEditMeta);
editInput.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        e.preventDefault();
        submitEdit();
    }
});

renderAnnouncements();
</script>
</body>
</html>
