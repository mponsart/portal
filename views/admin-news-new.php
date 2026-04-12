<?php
$user    = $_SESSION['user'];
$config  = require __DIR__ . '/../config.php';
$isAdmin = in_array($user['email'], $config['admins'] ?? [], true);
if (!$isAdmin) { http_response_code(403); include __DIR__ . '/unauthorized.php'; exit; }

$currentPage = 'admin';

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
    <title>Nouvelle actualité - Groupe Speed Cloud</title>
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
        .preview-box { border:1px dashed rgba(255,255,255,.18); background:rgba(255,255,255,.04); }
        .editor-meta { color:rgba(226,232,240,.65); font-size:.75rem; }
        .editor-meta.warn { color:#fda4af; }
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

<main class="page-stack relative z-10 w-full max-w-4xl mx-auto px-4 sm:px-6 py-8">
    <section class="glass rounded-3xl p-4 sm:p-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold">➕ Nouvelle actualité</h1>
            <p class="text-white/45 text-sm">Création d'une actualité dans une page dédiée.</p>
            <p class="crumb mt-1">Admin / Actualités / Nouvelle</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="/admin-news.php" class="admin-tab px-3 py-1.5 rounded-lg text-xs font-semibold">📰 Retour aux actualités</a>
        </div>
    </section>

    <section class="panel p-5 space-y-3">
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

            <div class="md-toolbar rounded-xl p-2 flex flex-wrap items-center gap-1.5">
                <button type="button" class="md-btn px-2.5 py-1 rounded text-xs" onclick="wrapSelection('**', '**')"><strong>Gras</strong></button>
                <button type="button" class="md-btn px-2.5 py-1 rounded text-xs" onclick="wrapSelection('*', '*')"><em>Italique</em></button>
                <button type="button" class="md-btn px-2.5 py-1 rounded text-xs" onclick="prefixLines('- ')">Liste</button>
                <button type="button" class="md-btn px-2.5 py-1 rounded text-xs" onclick="prefixLines('> ')">Citation</button>
                <button type="button" class="md-btn px-2.5 py-1 rounded text-xs" onclick="insertLink()">Lien</button>
                <button type="button" class="md-btn px-2.5 py-1 rounded text-xs" onclick="insertCodeBlock()">Code</button>
            </div>
            <textarea id="addMarkdown" class="input-dark md-editor w-full px-4 py-3 rounded-xl text-sm" placeholder="Rédigez votre actualité en Markdown..."></textarea>
            <div class="flex items-center justify-between gap-2">
                <p class="text-white/40 text-xs">Astuce: Ctrl/Cmd + Entrée pour enregistrer rapidement.</p>
                <p id="addEditorMeta" class="editor-meta">0 mot • 0 caractère</p>
            </div>

            <div class="preview-box rounded-2xl p-3">
                <p class="text-white/45 text-[11px] uppercase tracking-[.14em] mb-2">Aperçu article</p>
                <p class="text-sm font-semibold"><span id="addPrevEmoji">📢</span> <span id="addPrevTitle">Titre</span></p>
                <p class="text-white/35 text-xs" id="addPrevMeta">Publié</p>
                <div id="addPrevBody" class="text-white/70 text-sm mt-2 md-preview"></div>
            </div>

            <button class="w-full py-2.5 rounded-xl text-sm font-semibold btn-primary">Enregistrer</button>
        </form>
    </section>
</main>

<script>
const CSRF = <?= json_encode($csrfToken) ?>;
const MAX_EDITOR_CHARS = 20000;
const mdInput = document.getElementById('addMarkdown');

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

function getEditorHtml() {
    return renderMarkdown(mdInput.value.trim());
}

function getEditorStats() {
    const text = mdInput.value.replace(/\s+/g, ' ').trim();
    const chars = text.length;
    const words = text ? text.split(' ').length : 0;
    return { chars, words };
}

function replaceSelection(transformer) {
    const start = mdInput.selectionStart;
    const end = mdInput.selectionEnd;
    const before = mdInput.value.slice(0, start);
    const selected = mdInput.value.slice(start, end);
    const after = mdInput.value.slice(end);
    const { text, cursorStart, cursorEnd } = transformer(selected);
    mdInput.value = before + text + after;
    mdInput.focus();
    mdInput.setSelectionRange(before.length + cursorStart, before.length + cursorEnd);
    updateAddPreview();
}

function wrapSelection(prefix, suffix) {
    replaceSelection((selected) => {
        const core = selected || 'texte';
        return {
            text: `${prefix}${core}${suffix}`,
            cursorStart: prefix.length,
            cursorEnd: prefix.length + core.length,
        };
    });
}

function prefixLines(prefix) {
    replaceSelection((selected) => {
        const core = selected || 'élément';
        const lines = core.split('\n').map((l) => `${prefix}${l}`);
        const text = lines.join('\n');
        return { text, cursorStart: 0, cursorEnd: text.length };
    });
}

function insertLink() {
    replaceSelection((selected) => {
        const label = selected || 'texte du lien';
        const text = `[${label}](https://)`;
        return { text, cursorStart: 1, cursorEnd: 1 + label.length };
    });
}

function insertCodeBlock() {
    replaceSelection((selected) => {
        const core = selected || 'code ici';
        const text = `\n\`\`\`\n${core}\n\`\`\`\n`;
        return { text, cursorStart: 5, cursorEnd: 5 + core.length };
    });
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
    const status = document.getElementById('addStatusType').value === 'draft' ? 'Brouillon' : 'Publié';
    document.getElementById('addPrevEmoji').textContent = emoji;
    document.getElementById('addPrevTitle').textContent = title;
    document.getElementById('addPrevMeta').textContent = status;
    document.getElementById('addPrevBody').innerHTML = getEditorHtml();

    const meta = document.getElementById('addEditorMeta');
    const stats = getEditorStats();
    meta.textContent = `${stats.words} mot${stats.words > 1 ? 's' : ''} • ${stats.chars}/${MAX_EDITOR_CHARS} caractères`;
    meta.classList.toggle('warn', stats.chars > MAX_EDITOR_CHARS);
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
    const markdownContent = mdInput.value.trim();
    if (markdownContent === '') return showStatus(statusEl, 'Le contenu est obligatoire.', 'error');
    const stats = getEditorStats();
    if (stats.chars > MAX_EDITOR_CHARS) return showStatus(statusEl, `Le contenu dépasse ${MAX_EDITOR_CHARS} caractères.`, 'error');

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
            markdown_content:markdownContent,
        });
        showStatus(statusEl, data.announcement.status === 'draft' ? 'Brouillon enregistré.' : 'Actualité publiée.', 'success');
        e.target.reset();
        mdInput.value = '';
        document.getElementById('addEmoji').value = '📢';
        document.getElementById('addColor').value = '#3454d1';
        document.getElementById('addStatusType').value = 'published';
        updateAddPreview();
    } catch (err) {
        showStatus(statusEl, err.message, 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Enregistrer';
    }
});

mdInput.addEventListener('input', updateAddPreview);
document.getElementById('addEmoji').addEventListener('input', updateAddPreview);
document.getElementById('addTitle').addEventListener('input', updateAddPreview);
document.getElementById('addStatusType').addEventListener('change', updateAddPreview);
mdInput.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('addForm').requestSubmit();
    }
});

updateAddPreview();
</script>
</body>
</html>
