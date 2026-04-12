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
        .tiptap-shell { border:1px solid rgba(255,255,255,.12); background:rgba(255,255,255,.04); }
        .tiptap-toolbar { border-bottom:1px solid rgba(255,255,255,.12); background:rgba(255,255,255,.05); }
        .tiptap-btn { border:1px solid rgba(255,255,255,.14); background:rgba(255,255,255,.08); color:#e2e8f0; }
        .tiptap-btn:hover { background:rgba(255,255,255,.15); }
        .tiptap-btn.active { background:rgba(52,84,209,.35); border-color:rgba(107,143,255,.8); }
        .tiptap-editor { min-height:220px; padding:.9rem 1rem; }
        .tiptap-editor:focus { outline:none; }
        .tiptap-editor .ProseMirror { min-height:220px; }
        .tiptap-editor .ProseMirror:focus { outline:none; }
        .tiptap-editor p { margin:.45rem 0; }
        .tiptap-editor ul, .tiptap-editor ol { margin:.45rem 0 .45rem 1.1rem; }
        .tiptap-editor blockquote { border-left:3px solid rgba(148,163,184,.45); padding-left:.75rem; color:#cbd5e1; margin:.45rem 0; }
        .tiptap-editor pre { background:rgba(2,6,23,.8); color:#e2e8f0; border:1px solid rgba(255,255,255,.12); border-radius:.5rem; padding:.6rem .75rem; overflow:auto; }
        .tiptap-editor code { background:rgba(255,255,255,.08); padding:0 .25rem; border-radius:.25rem; }
        .preview-box { border:1px dashed rgba(255,255,255,.18); background:rgba(255,255,255,.04); }
        .editor-meta { color:rgba(226,232,240,.65); font-size:.75rem; }
        .editor-meta.warn { color:#fda4af; }
        .content-preview p { margin:.4rem 0; }
        .content-preview ul, .content-preview ol { margin:.4rem 0 .4rem 1.2rem; }
        .content-preview blockquote { border-left:3px solid rgba(148,163,184,.45); padding-left:.75rem; color:#cbd5e1; margin:.4rem 0; }
        .content-preview pre { background:rgba(2,6,23,.8); color:#e2e8f0; border:1px solid rgba(255,255,255,.12); border-radius:.5rem; padding:.6rem .75rem; overflow:auto; }
        .content-preview code { background:rgba(255,255,255,.08); padding:0 .25rem; border-radius:.25rem; }
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

            <div class="tiptap-shell rounded-xl overflow-hidden">
                <div class="tiptap-toolbar p-2 flex flex-wrap items-center gap-1.5">
                    <button type="button" data-action="bold" class="tiptap-btn px-2.5 py-1 rounded text-xs"><strong>Gras</strong></button>
                    <button type="button" data-action="italic" class="tiptap-btn px-2.5 py-1 rounded text-xs"><em>Italique</em></button>
                    <button type="button" data-action="h2" class="tiptap-btn px-2.5 py-1 rounded text-xs">Titre</button>
                    <button type="button" data-action="bullet" class="tiptap-btn px-2.5 py-1 rounded text-xs">Liste</button>
                    <button type="button" data-action="quote" class="tiptap-btn px-2.5 py-1 rounded text-xs">Citation</button>
                    <button type="button" data-action="code" class="tiptap-btn px-2.5 py-1 rounded text-xs">Code</button>
                    <button type="button" data-action="link" class="tiptap-btn px-2.5 py-1 rounded text-xs">Lien</button>
                </div>
                <div id="addEditor" class="tiptap-editor text-sm"></div>
            </div>
            <div class="flex items-center justify-between gap-2">
                <p class="text-white/40 text-xs">Astuce: Ctrl/Cmd + Entrée pour enregistrer rapidement.</p>
                <p id="addEditorMeta" class="editor-meta">0 mot • 0 caractère</p>
            </div>

            <div class="preview-box rounded-2xl p-3">
                <p class="text-white/45 text-[11px] uppercase tracking-[.14em] mb-2">Aperçu article</p>
                <p class="text-sm font-semibold"><span id="addPrevEmoji">📢</span> <span id="addPrevTitle">Titre</span></p>
                <p class="text-white/35 text-xs" id="addPrevMeta">Publié</p>
                <div id="addPrevBody" class="text-white/70 text-sm mt-2 content-preview"></div>
            </div>

            <button type="submit" class="w-full py-2.5 rounded-xl text-sm font-semibold btn-primary">Enregistrer</button>
        </form>
    </section>
</main>

<script type="module">
import { Editor } from 'https://esm.sh/@tiptap/core@2.11.5';
import StarterKit from 'https://esm.sh/@tiptap/starter-kit@2.11.5';
import Link from 'https://esm.sh/@tiptap/extension-link@2.11.5';

const CSRF = <?= json_encode($csrfToken) ?>;
const MAX_EDITOR_CHARS = 20000;
const editorRoot = document.getElementById('addEditor');

const editor = new Editor({
    element: editorRoot,
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
        attributes: {
            class: 'focus:outline-none',
        },
    },
    onUpdate: () => {
        updateAddPreview();
        refreshToolbarState();
    },
    onSelectionUpdate: () => {
        refreshToolbarState();
    },
});

function escHtml(s) {
    return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function getEditorHtml() {
    return editor.getHTML();
}

function getEditorPlainText() {
    return editor.getText({ blockSeparator: '\n' }).trim();
}

function getEditorStats() {
    const text = getEditorPlainText().replace(/\s+/g, ' ').trim();
    const chars = text.length;
    const words = text ? text.split(' ').length : 0;
    return { chars, words };
}

function refreshToolbarState() {
    document.querySelectorAll('[data-action]').forEach((btn) => btn.classList.remove('active'));
    if (editor.isActive('bold')) document.querySelector('[data-action="bold"]')?.classList.add('active');
    if (editor.isActive('italic')) document.querySelector('[data-action="italic"]')?.classList.add('active');
    if (editor.isActive('heading', { level: 2 })) document.querySelector('[data-action="h2"]')?.classList.add('active');
    if (editor.isActive('bulletList')) document.querySelector('[data-action="bullet"]')?.classList.add('active');
    if (editor.isActive('blockquote')) document.querySelector('[data-action="quote"]')?.classList.add('active');
    if (editor.isActive('codeBlock')) document.querySelector('[data-action="code"]')?.classList.add('active');
    if (editor.isActive('link')) document.querySelector('[data-action="link"]')?.classList.add('active');
}

document.querySelector('[data-action="bold"]')?.addEventListener('click', () => editor.chain().focus().toggleBold().run());
document.querySelector('[data-action="italic"]')?.addEventListener('click', () => editor.chain().focus().toggleItalic().run());
document.querySelector('[data-action="h2"]')?.addEventListener('click', () => editor.chain().focus().toggleHeading({ level: 2 }).run());
document.querySelector('[data-action="bullet"]')?.addEventListener('click', () => editor.chain().focus().toggleBulletList().run());
document.querySelector('[data-action="quote"]')?.addEventListener('click', () => editor.chain().focus().toggleBlockquote().run());
document.querySelector('[data-action="code"]')?.addEventListener('click', () => editor.chain().focus().toggleCodeBlock().run());
document.querySelector('[data-action="link"]')?.addEventListener('click', () => {
    const previousUrl = editor.getAttributes('link').href || '';
    const url = window.prompt('URL du lien', previousUrl || 'https://');
    if (url === null) return;
    const trimmed = String(url).trim();
    if (trimmed === '') {
        editor.chain().focus().unsetLink().run();
        return;
    }
    editor.chain().focus().setLink({ href: trimmed }).run();
});

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

document.getElementById('addForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const statusEl = document.getElementById('addStatus');
    const btn = e.target.querySelector('button[type="submit"]');
    const plainContent = getEditorPlainText();
    const htmlContent = getEditorHtml();
    if (plainContent === '') return showStatus(statusEl, 'Le contenu est obligatoire.', 'error');
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
            markdown_content:plainContent,
            html_content:htmlContent,
        });
        showStatus(statusEl, data.announcement.status === 'draft' ? 'Brouillon enregistré.' : 'Actualité publiée.', 'success');
        e.target.reset();
        editor.commands.setContent('<p></p>');
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

document.getElementById('addEmoji').addEventListener('input', updateAddPreview);
document.getElementById('addTitle').addEventListener('input', updateAddPreview);
document.getElementById('addStatusType').addEventListener('change', updateAddPreview);
editorRoot.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('addForm').requestSubmit();
    }
});

updateAddPreview();
refreshToolbarState();
</script>
</body>
</html>
