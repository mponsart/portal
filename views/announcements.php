<?php
$user        = $_SESSION['user'];
$config      = require __DIR__ . '/../config.php';
$isAdmin     = in_array($user['email'], $config['admins'] ?? []);
$currentPage = 'annonces';
$channels = $config['discord']['channels'] ?? [];

// Préparer uniquement des données d'affichage (pas de webhook côté client)
$channelOptions = [];
foreach ($channels as $key => $channelConfig) {
    if (!is_array($channelConfig)) {
        continue;
    }

    $label = trim((string) ($channelConfig['label'] ?? ''));
    if ($label === '') {
        $label = $key;
    }

    $channelOptions[] = [
        'key' => $key,
        'label' => $label,
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Annonces Discord - Groupe Speed Cloud</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        '#7c3aed': '#7c3aed',
                        'brand-cyan': '#0ea5e9',
                        'brand-ink': '#0b132b',
                    },
                },
            },
        };
    </script>
    <link rel="icon" type="image/png" href="https://sign.groupe-speed.cloud/assets/images/cloudy.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php include __DIR__ . '/_ui-tokens.php'; ?>
</head>
<body class="min-h-screen text-slate-100" style="font-family: 'Inter', sans-serif; background: radial-gradient(circle at 10% 10%, #1d4ed8 0%, #0b132b 45%, #020617 100%);">
    <?php include __DIR__ . '/_nav.php'; ?>

    <main class="container mx-auto px-4 py-8">
        <section class="max-w-4xl mx-auto mb-8">
            <h1 class="text-3xl sm:text-4xl font-bold mb-2">Annonces Discord</h1>
            <p class="text-slate-200/90">Publiez des annonces dans les salons de l'association depuis une interface sécurisée.</p>
        </section>

        <section class="max-w-4xl mx-auto grid lg:grid-cols-5 gap-6">
            <div class="lg:col-span-3 bg-white/10 border border-white/15 backdrop-blur-xl rounded-2xl p-6 shadow-2xl">
                <h2 class="text-xl font-semibold mb-5">Nouveau message</h2>

                <form id="announcementForm" class="space-y-4">
                    <div>
                        <label for="channel" class="block text-sm font-semibold mb-2">Canal Discord</label>
                        <select id="channel" name="channel" required class="w-full px-4 py-3 rounded-lg bg-slate-900/60 border border-white/20 focus:outline-none focus:ring-2 focus:ring-brand-cyan">
                            <option value="">-- Choisir un canal --</option>
                            <?php foreach ($channelOptions as $channel): ?>
                            <option value="<?= htmlspecialchars($channel['key']) ?>"><?= htmlspecialchars($channel['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="title" class="block text-sm font-semibold mb-2">Titre</label>
                        <input id="title" name="title" type="text" maxlength="120" required class="w-full px-4 py-3 rounded-lg bg-slate-900/60 border border-white/20 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-cyan" placeholder="Ex: Maintenance du serveur ce soir">
                    </div>

                    <div>
                        <label for="content" class="block text-sm font-semibold mb-2">Message</label>
                        <textarea id="content" name="content" rows="6" maxlength="2000" required class="w-full px-4 py-3 rounded-lg bg-slate-900/60 border border-white/20 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-cyan" placeholder="Contenu de l'annonce..."></textarea>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label for="mention" class="block text-sm font-semibold mb-2">Mention</label>
                            <select id="mention" name="mention" class="w-full px-4 py-3 rounded-lg bg-slate-900/60 border border-white/20 focus:outline-none focus:ring-2 focus:ring-brand-cyan">
                                <option value="none">Aucune</option>
                                <option value="everyone">@everyone</option>
                                <option value="here">@here</option>
                            </select>
                        </div>
                        <div id="colorField">
                            <label for="color" class="block text-sm font-semibold mb-2">Couleur</label>
                            <input id="color" name="color" type="color" value="#7c3aed" class="w-full h-12 p-1 rounded-lg bg-slate-900/60 border border-white/20 cursor-pointer">
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input id="useEmbed" name="useEmbed" type="checkbox" checked class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-700 peer-focus:ring-2 peer-focus:ring-brand-cyan rounded-full peer peer-checked:bg-#7c3aed after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                        </label>
                        <span id="embedToggleLabel" class="text-sm font-semibold">Mode embed</span>
                        <span id="embedToggleHint" class="text-xs text-slate-400">(activé — titre, couleur et encadré Discord)</span>
                    </div>

                    <!-- Advanced embed builder (visible only in embed mode) -->
                    <div id="embedAdvanced" class="space-y-3 border-t border-white/10 pt-4">
                        <button type="button" id="toggleAdvanced" class="flex items-center gap-2 text-sm text-slate-300 hover:text-white transition">
                            <svg id="toggleAdvancedIcon" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                            Options avancées de l'embed
                        </button>
                        <div id="advancedContent" class="hidden space-y-3">
                            <div class="grid sm:grid-cols-2 gap-3">
                                <div>
                                    <label for="authorName" class="block text-xs font-semibold mb-1.5">Auteur — Nom</label>
                                    <input id="authorName" name="authorName" type="text" maxlength="256"
                                        class="w-full px-3 py-2 rounded-lg bg-slate-900/60 border border-white/20 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-cyan text-sm"
                                        placeholder="Votre nom (par défaut)">
                                </div>
                                <div>
                                    <label for="authorIcon" class="block text-xs font-semibold mb-1.5">Auteur — Icône (URL)</label>
                                    <input id="authorIcon" name="authorIcon" type="url"
                                        class="w-full px-3 py-2 rounded-lg bg-slate-900/60 border border-white/20 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-cyan text-sm"
                                        placeholder="Votre avatar (par défaut)">
                                </div>
                            </div>
                            <div class="grid sm:grid-cols-2 gap-3">
                                <div>
                                    <label for="thumbUrl" class="block text-xs font-semibold mb-1.5">Miniature (URL)</label>
                                    <input id="thumbUrl" name="thumbUrl" type="url"
                                        class="w-full px-3 py-2 rounded-lg bg-slate-900/60 border border-white/20 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-cyan text-sm"
                                        placeholder="https://...">
                                </div>
                                <div>
                                    <label for="imageUrl" class="block text-xs font-semibold mb-1.5">Image principale (URL)</label>
                                    <input id="imageUrl" name="imageUrl" type="url"
                                        class="w-full px-3 py-2 rounded-lg bg-slate-900/60 border border-white/20 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-cyan text-sm"
                                        placeholder="https://...">
                                </div>
                            </div>
                            <div>
                                <label for="footerText" class="block text-xs font-semibold mb-1.5">Pied de page</label>
                                <input id="footerText" name="footerText" type="text" maxlength="2048"
                                    class="w-full px-3 py-2 rounded-lg bg-slate-900/60 border border-white/20 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-cyan text-sm"
                                    placeholder="Publié par votre nom (par défaut)">
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <label class="text-xs font-semibold">Champs (Fields)</label>
                                    <button type="button" id="addFieldBtn"
                                        class="text-xs px-2 py-1 rounded bg-slate-700 hover:bg-slate-600 transition">
                                        + Ajouter un champ
                                    </button>
                                </div>
                                <div id="fieldsList" class="space-y-2"></div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button id="sendBtn" type="submit" class="px-5 py-3 rounded-lg bg-#7c3aed hover:bg-violet-700 transition font-semibold">
                            Envoyer l'annonce
                        </button>
                        <span id="status" class="text-sm text-slate-200"></span>
                    </div>
                </form>
            </div>

            <aside class="lg:col-span-2 bg-white/10 border border-white/15 backdrop-blur-xl rounded-2xl p-6 shadow-2xl flex flex-col gap-4">
                <h2 class="text-xl font-semibold">Aperçu</h2>

                <!-- Discord embed preview -->
                <div id="previewEmbed" class="rounded-lg overflow-hidden text-sm">
                    <div class="flex">
                        <div id="embedColorBar" class="w-1 flex-shrink-0" style="background:#7c3aed"></div>
                        <div class="bg-[#2f3136] flex-1 p-3 relative min-w-0 space-y-1">
                            <img id="previewThumb" src="" alt="" class="hidden absolute top-3 right-3 w-14 h-14 rounded object-cover">
                            <div id="previewAuthorRow" class="hidden flex items-center gap-1.5 mb-1">
                                <img id="previewAuthorIcon" src="" alt="" class="hidden w-5 h-5 rounded-full object-cover">
                                <span id="previewAuthorName" class="text-xs font-semibold text-slate-200"></span>
                            </div>
                            <p id="previewMention" class="text-xs text-[#5865f2] font-semibold"></p>
                            <p id="previewTitle" class="font-semibold text-white pr-16">Titre de l'annonce</p>
                            <div id="previewContent" class="text-[#dcddde] leading-relaxed text-xs preview-md pr-16">Le contenu apparaîtra ici.</div>
                            <div id="previewFields" class="grid gap-x-4 gap-y-2 mt-2" style="display:none"></div>
                            <img id="previewImage" src="" alt="" class="hidden w-full rounded mt-2 max-h-48 object-cover">
                            <p id="previewFooter" class="text-[10px] text-[#a3a6aa] mt-2"></p>
                        </div>
                    </div>
                </div>

                <!-- Plain-text preview -->
                <div id="previewPlain" class="hidden rounded-lg bg-[#36393f] p-3 text-sm text-[#dcddde] space-y-1">
                    <p id="previewMentionPlain" class="text-[#5865f2] font-semibold text-xs"></p>
                    <div id="previewContentPlain" class="leading-relaxed preview-md"></div>
                </div>

                <div class="text-xs text-slate-300 bg-black/20 rounded-lg p-3 border border-white/10">
                    Les webhooks Discord restent côté serveur et ne sont jamais exposés dans le navigateur.
                </div>
            </aside>
        </section>
    </main>

    <style>
        .preview-md strong { font-weight: 700; }
        .preview-md em { font-style: italic; }
        .preview-md u { text-decoration: underline; }
        .preview-md s { text-decoration: line-through; }
        .preview-md code { background: rgba(0,0,0,.4); border-radius: 3px; padding: 0 3px; font-family: monospace; font-size: .85em; }
        .preview-md pre { background: rgba(0,0,0,.4); border-radius: 4px; padding: 8px; margin: 4px 0; overflow-x: auto; }
        .preview-md pre code { background: none; padding: 0; font-size: .8em; }
        .preview-md .bq { border-left: 3px solid #4f545c; padding-left: 8px; color: #8e9297; display: block; margin: 2px 0; }
        .preview-md .spoiler { background: #202225; color: #202225; border-radius: 3px; padding: 0 2px; cursor: pointer; transition: color .15s; }
        .preview-md .spoiler:hover, .preview-md .spoiler.open { color: inherit; }
    </style>

    <script>
        // ── Discord markdown renderer ─────────────────────────────────────────
        function renderDiscordMarkdown(raw) {
            if (!raw) return '';

            const ph = [];
            const stash = html => { const id = '\x02' + ph.length + '\x03'; ph.push(html); return id; };
            const esc   = s => s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

            function applyInline(s) {
                s = s.replace(/\*\*\*(.+?)\*\*\*/gs, '<strong><em>$1</em></strong>');
                s = s.replace(/\*\*(.+?)\*\*/gs,     '<strong>$1</strong>');
                s = s.replace(/\*([^*\n]+?)\*/g,     '<em>$1</em>');
                s = s.replace(/__(.+?)__/gs,          '<u>$1</u>');
                s = s.replace(/_([^_\n]+?)_/g,       '<em>$1</em>');
                s = s.replace(/~~(.+?)~~/gs,          '<s>$1</s>');
                s = s.replace(/\|\|(.+?)\|\|/gs,     m => stash(`<span class="spoiler" onclick="this.classList.toggle('open')">${m.slice(2,-2)}</span>`));
                return s;
            }

            let t = raw;

            // Stash code blocks
            t = t.replace(/```(?:[^\n`]*)?\n?([\s\S]*?)```/g, (_, c) =>
                stash(`<pre><code>${esc(c)}</code></pre>`)
            );
            // Stash inline code
            t = t.replace(/`([^`\n]+?)`/g, (_, c) =>
                stash(`<code>${esc(c)}</code>`)
            );
            // Stash Discord custom/animated emojis so their underscores don't trigger markdown
            t = t.replace(/<a?:[\w-]+:\d+>/g, m => stash(esc(m)));
            // Stash emoji shortcodes (:name:) so underscores inside are not italicised
            t = t.replace(/:[\w-]+:/g, m => stash(esc(m)));

            // Process line by line for block elements
            t = t.split('\n').map(line => {
                let m;
                if ((m = line.match(/^### (.*)$/))) return `<strong>${applyInline(esc(m[1]))}</strong>`;
                if ((m = line.match(/^## (.*)$/)))  return `<strong style="font-size:1.05em">${applyInline(esc(m[1]))}</strong>`;
                if ((m = line.match(/^# (.*)$/)))   return `<strong style="font-size:1.15em">${applyInline(esc(m[1]))}</strong>`;
                if ((m = line.match(/^> (.*)$/)))   return `<span class="bq">${applyInline(esc(m[1]))}</span>`;
                if ((m = line.match(/^[-*] (.+)$/))) return `<span>• ${applyInline(esc(m[1]))}</span>`;
                if ((m = line.match(/^(\d+)\. (.+)$/))) return `<span>${esc(m[1])}. ${applyInline(esc(m[2]))}</span>`;
                return applyInline(esc(line));
            }).join('<br>');

            // Clean up extra <br> around block wrappers
            t = t.replace(/<br>(<(?:span class="bq"|pre)[^>]*>)/g, '$1');
            t = t.replace(/(<\/(?:span|pre)>)<br>/g, '$1');

            // Unstash
            t = t.replace(/\x02(\d+)\x03/g, (_, i) => ph[+i]);
            return t;
        }

        // ── Helpers ───────────────────────────────────────────────────────────
        const escHtml = s => s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

        // ── DOM refs ──────────────────────────────────────────────────────────
        const form             = document.getElementById('announcementForm');
        const sendBtn          = document.getElementById('sendBtn');
        const statusNode       = document.getElementById('status');
        const colorField       = document.getElementById('colorField');
        const useEmbedToggle   = document.getElementById('useEmbed');
        const embedToggleHint  = document.getElementById('embedToggleHint');
        const embedAdvanced    = document.getElementById('embedAdvanced');

        const previewEmbed     = document.getElementById('previewEmbed');
        const previewPlain     = document.getElementById('previewPlain');
        const embedColorBar    = document.getElementById('embedColorBar');

        // ── Fields state ─────────────────────────────────────────────────────
        let embedFields = [];

        function renderFieldsUI() {
            const list = document.getElementById('fieldsList');
            list.innerHTML = '';
            embedFields.forEach((f, i) => {
                const el = document.createElement('div');
                el.className = 'flex gap-2 items-start bg-black/20 rounded-lg p-2';
                el.innerHTML = `
                    <div class="flex-1 grid sm:grid-cols-2 gap-2">
                        <input type="text" placeholder="Nom du champ" maxlength="256"
                            class="w-full px-2 py-1.5 rounded bg-slate-900/60 border border-white/20 text-xs focus:outline-none focus:ring-1 focus:ring-brand-cyan"
                            data-fi="${i}" data-fk="name" value="${escHtml(f.name)}">
                        <input type="text" placeholder="Valeur" maxlength="1024"
                            class="w-full px-2 py-1.5 rounded bg-slate-900/60 border border-white/20 text-xs focus:outline-none focus:ring-1 focus:ring-brand-cyan"
                            data-fi="${i}" data-fk="value" value="${escHtml(f.value)}">
                    </div>
                    <label class="flex flex-col items-center gap-0.5 cursor-pointer select-none">
                        <span class="text-[10px] text-slate-400">Inline</span>
                        <input type="checkbox" class="cursor-pointer mt-1" data-fi="${i}" data-fk="inline" ${f.inline ? 'checked' : ''}>
                    </label>
                    <button type="button" class="text-slate-400 hover:text-red-400 transition mt-1" data-rm="${i}" title="Supprimer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>`;
                list.appendChild(el);
            });
            list.querySelectorAll('[data-fi]').forEach(inp => {
                const idx = +inp.dataset.fi;
                const key = inp.dataset.fk;
                inp.addEventListener(inp.type === 'checkbox' ? 'change' : 'input', () => {
                    embedFields[idx][key] = inp.type === 'checkbox' ? inp.checked : inp.value;
                    updatePreview();
                });
            });
            list.querySelectorAll('[data-rm]').forEach(btn => {
                btn.addEventListener('click', () => {
                    embedFields.splice(+btn.dataset.rm, 1);
                    renderFieldsUI();
                    updatePreview();
                });
            });
        }

        document.getElementById('addFieldBtn').addEventListener('click', () => {
            if (embedFields.length >= 25) {
                alert('Maximum 25 champs Discord atteint.');
                return;
            }
            embedFields.push({ name: '', value: '', inline: false });
            renderFieldsUI();
            updatePreview();
        });

        // ── Advanced toggle ───────────────────────────────────────────────────
        document.getElementById('toggleAdvanced').addEventListener('click', () => {
            const content = document.getElementById('advancedContent');
            const icon    = document.getElementById('toggleAdvancedIcon');
            const opening = content.classList.contains('hidden');
            content.classList.toggle('hidden', !opening);
            icon.style.transform = opening ? 'rotate(90deg)' : '';
        });

        // ── Preview updater ───────────────────────────────────────────────────
        function getVal(id) { const el = document.getElementById(id); return el ? el.value.trim() : ''; }
        function setHidden(el, hidden) { el.classList.toggle('hidden', hidden); }

        function updatePreview() {
            const isEmbed     = useEmbedToggle.checked;
            const mention     = form.mention.value;
            const title       = form.title.value.trim();
            const content     = form.content.value.trim();
            const mentionText = mention === 'none' ? '' : '@' + mention;

            colorField.classList.toggle('hidden', !isEmbed);
            embedAdvanced.classList.toggle('hidden', !isEmbed);
            embedToggleHint.textContent = isEmbed
                ? '(activé — titre, couleur et encadré Discord)'
                : '(désactivé — message texte avec markdown Discord)';

            if (isEmbed) {
                setHidden(previewEmbed, false);
                setHidden(previewPlain,  true);

                embedColorBar.style.background = form.color.value;

                // Mention
                document.getElementById('previewMention').textContent = mentionText;

                // Author — only shown when explicitly provided
                const authorName = getVal('authorName');
                const authorIcon = getVal('authorIcon');
                const authorRow  = document.getElementById('previewAuthorRow');
                const authorNameEl = document.getElementById('previewAuthorName');
                const authorIconEl = document.getElementById('previewAuthorIcon');
                if (authorName) {
                    authorRow.classList.remove('hidden');
                    authorNameEl.textContent = authorName;
                    if (authorIcon) {
                        authorIconEl.src = authorIcon;
                        setHidden(authorIconEl, false);
                    } else {
                        setHidden(authorIconEl, true);
                    }
                } else {
                    authorRow.classList.add('hidden');
                }

                // Title
                document.getElementById('previewTitle').textContent = title || 'Titre de l\'annonce';

                // Content (markdown)
                const contentEl = document.getElementById('previewContent');
                contentEl.innerHTML = content
                    ? renderDiscordMarkdown(content)
                    : '<span style="color:#4f545c">Le contenu apparaîtra ici.</span>';

                // Thumbnail
                const thumbUrl = getVal('thumbUrl');
                const thumbEl  = document.getElementById('previewThumb');
                if (thumbUrl) {
                    thumbEl.src = thumbUrl;
                    setHidden(thumbEl, false);
                } else {
                    setHidden(thumbEl, true);
                }

                // Fields
                const fieldsEl = document.getElementById('previewFields');
                fieldsEl.innerHTML = '';
                const visibleFields = embedFields.filter(f => f.name || f.value);
                if (visibleFields.length > 0) {
                    fieldsEl.style.display  = 'grid';
                    fieldsEl.style.gridTemplateColumns = 'repeat(3, 1fr)';
                    visibleFields.forEach(f => {
                        const div = document.createElement('div');
                        div.style.gridColumn = f.inline ? 'span 1' : '1 / -1';
                        div.innerHTML = `<p style="font-size:.75rem;font-weight:700;color:#fff;margin-bottom:1px">${escHtml(f.name)}</p>`
                            + `<div style="font-size:.75rem;color:#dcddde" class="preview-md">${renderDiscordMarkdown(f.value)}</div>`;
                        fieldsEl.appendChild(div);
                    });
                } else {
                    fieldsEl.style.display = 'none';
                }

                // Image
                const imageUrl = getVal('imageUrl');
                const imageEl  = document.getElementById('previewImage');
                if (imageUrl) {
                    imageEl.src = imageUrl;
                    setHidden(imageEl, false);
                } else {
                    setHidden(imageEl, true);
                }

                // Footer — only shown when explicitly provided
                const footerText = getVal('footerText');
                const footerEl   = document.getElementById('previewFooter');
                footerEl.textContent = footerText;
                footerEl.style.display = footerText ? '' : 'none';

            } else {
                setHidden(previewEmbed,  true);
                setHidden(previewPlain, false);

                document.getElementById('previewMentionPlain').textContent = mentionText;
                const plainEl = document.getElementById('previewContentPlain');
                const titlePart = title ? `<strong>${escHtml(title)}</strong><br><br>` : '';
                plainEl.innerHTML = titlePart + (content
                    ? renderDiscordMarkdown(content)
                    : '<span style="color:#4f545c">Le contenu apparaîtra ici.</span>');
            }
        }

        // ── Event listeners ───────────────────────────────────────────────────
        form.title.addEventListener('input', updatePreview);
        form.content.addEventListener('input', updatePreview);
        form.mention.addEventListener('change', updatePreview);
        form.color.addEventListener('input', updatePreview);
        useEmbedToggle.addEventListener('change', updatePreview);
        ['authorName','authorIcon','thumbUrl','imageUrl','footerText'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('input', updatePreview);
        });

        // ── Form submission ───────────────────────────────────────────────────
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            statusNode.textContent = '';
            sendBtn.disabled = true;
            sendBtn.textContent = 'Envoi...';

            const payload = {
                channel:    form.channel.value,
                title:      form.title.value.trim(),
                content:    form.content.value.trim(),
                mention:    form.mention.value,
                color:      form.color.value,
                useEmbed:   useEmbedToggle.checked,
                authorName: getVal('authorName'),
                authorIcon: getVal('authorIcon'),
                thumbUrl:   getVal('thumbUrl'),
                imageUrl:   getVal('imageUrl'),
                footerText: getVal('footerText'),
                fields:     embedFields.filter(f => f.name && f.value),
            };

            try {
                const response = await fetch('/send-announcement.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                });

                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.error || 'Erreur lors de l\'envoi.');
                }

                statusNode.textContent = 'Annonce envoyée avec succès.';
                statusNode.className = 'text-sm text-emerald-300';
                form.reset();
                form.color.value = '#7c3aed';
                useEmbedToggle.checked = true;
                embedFields = [];
                renderFieldsUI();
                updatePreview();
            } catch (error) {
                statusNode.textContent = error.message;
                statusNode.className = 'text-sm text-red-300';
            } finally {
                sendBtn.disabled = false;
                sendBtn.textContent = 'Envoyer l\'annonce';
            }
        });

        updatePreview();
    </script>
</body>
</html>
