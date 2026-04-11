<?php
$user = $_SESSION['user'];
$config = require __DIR__ . '/../config.php';
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
                        'brand-indigo': '#3454d1',
                        'brand-cyan': '#0ea5e9',
                        'brand-ink': '#0b132b',
                    },
                },
            },
        };
    </script>
    <link rel="icon" type="image/png" href="https://sign.groupe-speed.cloud/assets/images/cloudy.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Titillium+Web:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen text-slate-100" style="font-family: 'Titillium Web', sans-serif; background: radial-gradient(circle at 10% 10%, #1d4ed8 0%, #0b132b 45%, #020617 100%);">
    <nav class="bg-black/30 backdrop-blur-md border-b border-white/10 sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <a href="/" class="flex items-center gap-3 hover:opacity-80 transition">
                    <img src="/assets/images/cloudy.png" alt="" class="w-10 h-10 rounded-lg">
                    <span class="text-white font-bold text-lg hidden sm:block">Groupe Speed Cloud</span>
                </a>

                <div class="flex items-center gap-3">
                    <?php if (!empty($user['picture'])): ?>
                    <img src="<?= htmlspecialchars($user['picture']) ?>" alt="" class="w-8 h-8 rounded-full border-2 border-white/20">
                    <?php endif; ?>
                    <div class="hidden md:block text-right">
                        <p class="text-white text-sm font-medium leading-tight"><?= htmlspecialchars($user['name']) ?></p>
                        <p class="text-gray-300 text-xs"><?= htmlspecialchars($user['email']) ?></p>
                    </div>
                    <a href="/logout.php" class="p-2 text-gray-300 hover:text-white hover:bg-white/10 rounded-lg transition" title="Déconnexion">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </nav>

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
                        <div>
                            <label for="color" class="block text-sm font-semibold mb-2">Couleur</label>
                            <input id="color" name="color" type="color" value="#3454d1" class="w-full h-12 p-1 rounded-lg bg-slate-900/60 border border-white/20 cursor-pointer">
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button id="sendBtn" type="submit" class="px-5 py-3 rounded-lg bg-brand-indigo hover:bg-blue-700 transition font-semibold">
                            Envoyer l'annonce
                        </button>
                        <span id="status" class="text-sm text-slate-200"></span>
                    </div>
                </form>
            </div>

            <aside class="lg:col-span-2 bg-white/10 border border-white/15 backdrop-blur-xl rounded-2xl p-6 shadow-2xl">
                <h2 class="text-xl font-semibold mb-5">Aperçu</h2>
                <div class="rounded-xl bg-slate-950/70 border border-white/10 p-4 space-y-3">
                    <p id="previewMention" class="text-xs text-cyan-300"></p>
                    <p id="previewTitle" class="font-semibold text-lg">Titre de l'annonce</p>
                    <p id="previewContent" class="text-sm text-slate-300 whitespace-pre-wrap">Le contenu apparaîtra ici.</p>
                    <p class="text-xs text-slate-400">Envoyé par <?= htmlspecialchars($user['name']) ?></p>
                </div>

                <div class="mt-5 text-xs text-slate-300 bg-black/20 rounded-lg p-3 border border-white/10">
                    Les webhooks Discord restent côté serveur et ne sont jamais exposés dans le navigateur.
                </div>
            </aside>
        </section>
    </main>

    <script>
        const form = document.getElementById('announcementForm');
        const sendBtn = document.getElementById('sendBtn');
        const statusNode = document.getElementById('status');
        const previewMention = document.getElementById('previewMention');
        const previewTitle = document.getElementById('previewTitle');
        const previewContent = document.getElementById('previewContent');

        function updatePreview() {
            const mention = form.mention.value;
            const title = form.title.value.trim();
            const content = form.content.value.trim();

            previewMention.textContent = mention === 'none' ? '' : '@' + mention;
            previewTitle.textContent = title || 'Titre de l\'annonce';
            previewContent.textContent = content || 'Le contenu apparaîtra ici.';
        }

        form.title.addEventListener('input', updatePreview);
        form.content.addEventListener('input', updatePreview);
        form.mention.addEventListener('change', updatePreview);

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            statusNode.textContent = '';
            sendBtn.disabled = true;
            sendBtn.textContent = 'Envoi...';

            const payload = {
                channel: form.channel.value,
                title: form.title.value.trim(),
                content: form.content.value.trim(),
                mention: form.mention.value,
                color: form.color.value,
            };

            try {
                const response = await fetch('/send-announcement.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.error || 'Erreur lors de l\'envoi.');
                }

                statusNode.textContent = 'Annonce envoyée avec succès.';
                statusNode.className = 'text-sm text-emerald-300';
                form.reset();
                form.color.value = '#3454d1';
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
