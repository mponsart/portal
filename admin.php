<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';

// ── Auth ──────────────────────────────────────────────────────────────────────
if (!isset($_SESSION['user'])) {
    header('Location: /');
    exit;
}

$config  = require __DIR__ . '/config.php';
$user    = $_SESSION['user'];
$admins  = $config['admins'] ?? [];
$isAdmin = in_array($user['email'], $admins);

if (!$isAdmin) {
    http_response_code(403);
    exit('Accès non autorisé.');
}

$currentPage  = 'admin';
$featuredFile = __DIR__ . '/uploads/featured.json';

// Charger les annonces existantes
$featured = [];
if (file_exists($featuredFile)) {
    $raw     = file_get_contents($featuredFile);
    $decoded = json_decode($raw, true);
    $featured = is_array($decoded) ? $decoded : [];
}

// CSRF token
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
    <title>Admin — Annonces à la une</title>
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
    <style>body { font-family: 'Titillium Web', sans-serif; }</style>
</head>
<body class="min-h-screen text-slate-100"
      style="background: radial-gradient(circle at 10% 10%, #1d4ed8 0%, #0b132b 45%, #020617 100%);">

    <?php include __DIR__ . '/views/_nav.php'; ?>

    <main class="container mx-auto px-4 py-10 max-w-4xl space-y-10">

        <div>
            <h1 class="text-3xl font-bold text-white mb-1">⚙️ Administration</h1>
            <p class="text-gray-400">Gérez les annonces mises en avant sur le portail.</p>
        </div>

        <!-- ── Formulaire d'ajout ─────────────────────────────────────── -->
        <section class="bg-white/10 backdrop-blur border border-white/15 rounded-2xl p-6 shadow-xl space-y-5">
            <h2 class="text-xl font-bold text-white">➕ Épingler une annonce</h2>

            <div id="addStatus" class="hidden text-sm rounded-lg px-4 py-2"></div>

            <form id="addForm" class="space-y-4" novalidate>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <!-- Emoji + Titre -->
                <div class="grid sm:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-200 mb-1">Emoji</label>
                        <input type="text" name="emoji" value="📢" maxlength="4"
                               class="w-full px-4 py-2.5 bg-white/10 border border-white/20 rounded-lg text-white text-center text-xl
                                      focus:outline-none focus:ring-2 focus:ring-brand-cyan transition">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-semibold text-gray-200 mb-1">Titre <span class="text-gray-500">(optionnel)</span></label>
                        <input type="text" name="title" placeholder="Titre de l'annonce…"
                               class="w-full px-4 py-2.5 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400
                                      focus:outline-none focus:ring-2 focus:ring-brand-cyan transition">
                    </div>
                </div>

                <!-- Contenu -->
                <div>
                    <label class="block text-sm font-semibold text-gray-200 mb-1">Contenu <span class="text-red-400">*</span></label>
                    <textarea name="content" rows="3" required
                              placeholder="Texte de l'annonce…"
                              class="w-full px-4 py-2.5 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400
                                     focus:outline-none focus:ring-2 focus:ring-brand-cyan transition resize-none"></textarea>
                </div>

                <!-- Couleur -->
                <div class="flex items-center gap-3">
                    <label class="text-sm font-semibold text-gray-200">Couleur d'accent</label>
                    <input type="color" name="color" value="#3454d1"
                           class="w-10 h-10 rounded-lg cursor-pointer bg-transparent border border-white/20">
                </div>

                <button type="submit"
                        class="px-6 py-2.5 bg-brand-indigo hover:bg-blue-700 text-white font-semibold rounded-xl transition shadow">
                    Épingler l'annonce
                </button>
            </form>
        </section>

        <!-- ── Liste des annonces épinglées ─────────────────────────────── -->
        <section>
            <h2 class="text-xl font-bold text-white mb-4">📌 Annonces épinglées</h2>

            <div id="featuredList" class="space-y-4">
                <?php if (empty($featured)): ?>
                <p class="text-gray-500 italic" id="emptyMsg">Aucune annonce épinglée pour le moment.</p>
                <?php else: ?>
                <?php foreach ($featured as $ann): ?>
                <?php
                    $annId      = htmlspecialchars($ann['id']         ?? '');
                    $annEmoji   = htmlspecialchars($ann['emoji']      ?? '📢');
                    $annTitle   = htmlspecialchars($ann['title']      ?? '');
                    $annContent = htmlspecialchars($ann['content']    ?? '');
                    $annColor   = htmlspecialchars($ann['color']      ?? '#3454d1');
                    $annDate    = htmlspecialchars($ann['pinned_at']  ?? '');
                    $annBy      = htmlspecialchars($ann['pinned_by']  ?? '');
                ?>
                <div id="ann-<?= $annId ?>"
                     class="flex items-start gap-4 rounded-2xl p-5 bg-white/10 border border-white/10 shadow"
                     style="border-left: 4px solid <?= $annColor ?>;">
                    <span class="text-2xl select-none mt-0.5"><?= $annEmoji ?></span>
                    <div class="flex-1 min-w-0">
                        <?php if ($annTitle): ?>
                        <p class="font-bold text-white mb-0.5"><?= $annTitle ?></p>
                        <?php endif; ?>
                        <p class="text-gray-300 text-sm"><?= nl2br($annContent) ?></p>
                        <p class="text-gray-500 text-xs mt-2">Épinglé le <?= $annDate ?> par <?= $annBy ?></p>
                    </div>
                    <button onclick="deleteAnn('<?= $annId ?>', '<?= htmlspecialchars($csrfToken) ?>')"
                            class="flex-shrink-0 p-2 text-red-400 hover:text-red-300 hover:bg-red-500/20 rounded-lg transition"
                            title="Supprimer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m-7 0a1 1 0 011-1h4a1 1 0 011 1m-7 0h8"/>
                        </svg>
                    </button>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

    </main>

    <script>
    const csrfToken = <?= json_encode($csrfToken) ?>;

    // ── Ajout ────────────────────────────────────────────────────────────────
    document.getElementById('addForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form     = e.target;
        const statusEl = document.getElementById('addStatus');
        const btn      = form.querySelector('button[type="submit"]');

        const content = form.content.value.trim();
        if (!content) {
            showStatus(statusEl, 'Le contenu est obligatoire.', 'error');
            return;
        }

        btn.disabled    = true;
        btn.textContent = 'Envoi…';

        try {
            const res  = await fetch('/save-featured.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({
                    action:     'add',
                    csrf_token: csrfToken,
                    emoji:      form.emoji.value.trim()   || '📢',
                    title:      form.title.value.trim(),
                    content:    content,
                    color:      form.color.value,
                }),
            });

            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Erreur serveur.');

            // Injecter la nouvelle card dans la liste
            const list    = document.getElementById('featuredList');
            const emptyMsg = document.getElementById('emptyMsg');
            if (emptyMsg) emptyMsg.remove();

            list.insertAdjacentHTML('beforeend', buildCard(data.announcement));
            showStatus(statusEl, 'Annonce épinglée avec succès.', 'success');
            form.reset();
            form.emoji.value = '📢';
            form.color.value = '#3454d1';

        } catch (err) {
            showStatus(statusEl, err.message, 'error');
        } finally {
            btn.disabled    = false;
            btn.textContent = 'Épingler l\'annonce';
        }
    });

    // ── Suppression ──────────────────────────────────────────────────────────
    async function deleteAnn(id, token) {
        if (!confirm('Retirer cette annonce du portail ?')) return;

        try {
            const res  = await fetch('/save-featured.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ action: 'delete', csrf_token: token, id }),
            });

            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Erreur serveur.');

            const el = document.getElementById('ann-' + id);
            if (el) el.remove();

            const list = document.getElementById('featuredList');
            if (!list.children.length) {
                list.innerHTML = '<p class="text-gray-500 italic" id="emptyMsg">Aucune annonce épinglée pour le moment.</p>';
            }
        } catch (err) {
            alert(err.message);
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────────
    function showStatus(el, msg, type) {
        el.textContent = msg;
        el.className   = 'text-sm rounded-lg px-4 py-2 ' + (type === 'success'
            ? 'bg-emerald-500/20 text-emerald-300'
            : 'bg-red-500/20 text-red-300');
        el.classList.remove('hidden');
        setTimeout(() => el.classList.add('hidden'), 5000);
    }

    function esc(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function buildCard(ann) {
        const titleHtml = ann.title
            ? `<p class="font-bold text-white mb-0.5">${esc(ann.title)}</p>`
            : '';
        return `
        <div id="ann-${esc(ann.id)}"
             class="flex items-start gap-4 rounded-2xl p-5 bg-white/10 border border-white/10 shadow"
             style="border-left: 4px solid ${esc(ann.color)};">
            <span class="text-2xl select-none mt-0.5">${esc(ann.emoji)}</span>
            <div class="flex-1 min-w-0">
                ${titleHtml}
                <p class="text-gray-300 text-sm">${esc(ann.content).replace(/\n/g, '<br>')}</p>
                <p class="text-gray-500 text-xs mt-2">Épinglé le ${esc(ann.pinned_at)} par ${esc(ann.pinned_by)}</p>
            </div>
            <button onclick="deleteAnn('${esc(ann.id)}', '${esc(csrfToken)}')"
                    class="flex-shrink-0 p-2 text-red-400 hover:text-red-300 hover:bg-red-500/20 rounded-lg transition"
                    title="Supprimer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m-7 0a1 1 0 011-1h4a1 1 0 011 1m-7 0h8"/>
                </svg>
            </button>
        </div>`;
    }
    </script>

</body>
</html>
