<?php
$user    = $_SESSION['user'];
$config  = require __DIR__ . '/../config.php';
$isAdmin = in_array($user['email'], $config['admins'] ?? []);

if (!$isAdmin) { http_response_code(403); exit('Accès non autorisé.'); }

$currentPage  = 'admin';
$featuredFile = __DIR__ . '/../uploads/featured.json';
$bannerFile   = __DIR__ . '/../uploads/banners.json';

$featured = [];
if (file_exists($featuredFile)) {
    $decoded  = json_decode(file_get_contents($featuredFile), true);
    $featured = is_array($decoded) ? $decoded : [];
}

$banners = [];
if (file_exists($bannerFile)) {
    $decoded = json_decode(file_get_contents($bannerFile), true);
    $banners = is_array($decoded) ? $decoded : [];
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

$catLabels = ['general' => 'Général', 'event' => 'Événement', 'urgent' => 'Urgent', 'info' => 'Info'];
$catColors = ['general' => '#3454d1', 'urgent' => '#ef4444', 'event' => '#8b5cf6', 'info' => '#0ea5e9'];
$catBadge  = ['general' => 'bg-blue-500/20 text-blue-300', 'urgent' => 'bg-red-500/20 text-red-300', 'event' => 'bg-violet-500/20 text-violet-300', 'info' => 'bg-cyan-500/20 text-cyan-300'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Groupe Speed Cloud</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Quill rich text editor -->
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { 'brand':'#3454d1','brand-dk':'#2440a8' } } }
        };
    </script>
    <link rel="icon" type="image/png" href="/assets/images/cloudy.png">
    <link href="https://fonts.googleapis.com/css2?family=Titillium+Web:wght@400;600;700&display=swap" rel="stylesheet">
    <?php include __DIR__ . '/_ui-tokens.php'; ?>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Titillium Web', sans-serif; background: #06080f; color-scheme: dark; }
        .bg-ambient { position:fixed;inset:0;pointer-events:none;z-index:0;
            background: radial-gradient(ellipse 70% 55% at 15% 0%, rgba(52,84,209,.28) 0%, transparent 65%),
                        radial-gradient(ellipse 50% 40% at 88% 100%, rgba(14,165,233,.18) 0%, transparent 60%); }
        .glass { background:rgba(255,255,255,.055);backdrop-filter:blur(16px) saturate(160%);
                 -webkit-backdrop-filter:blur(16px) saturate(160%);border:1px solid rgba(255,255,255,.10); }
        .section-label { font-size:.7rem;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:rgba(255,255,255,.35); }

        /* Quill override */
        .ql-toolbar { background:rgba(255,255,255,.08)!important;border:1px solid rgba(255,255,255,.12)!important;border-radius:12px 12px 0 0!important; }
        .ql-container { background:rgba(255,255,255,.05)!important;border:1px solid rgba(255,255,255,.12)!important;border-top:none!important;border-radius:0 0 12px 12px!important;min-height:140px; }
        .ql-editor { color:#e2e8f0!important;min-height:130px;font-size:.9rem;line-height:1.6; }
        .ql-editor.ql-blank::before { color:rgba(255,255,255,.25)!important;font-style:normal!important; }
        .ql-stroke { stroke:#94a3b8!important; }
        .ql-fill { fill:#94a3b8!important; }
        .ql-picker-label { color:#94a3b8!important; }
        .ql-picker-options { background:#1e2235!important;border:1px solid rgba(255,255,255,.12)!important; }
        .ql-picker-item { color:#e2e8f0!important; }
        .ql-picker-item:hover { color:#fff!important; }

        /* Annonce card */
        .ann-card { transition: border-color .15s; }
        .ann-card:hover { border-color: rgba(255,255,255,.2); }
        .form-box { border:1px solid rgba(255,255,255,.10); background:rgba(255,255,255,.03); border-radius:14px; padding:12px; }
        .emoji-chip { border:1px solid rgba(255,255,255,.12); background:rgba(255,255,255,.06); border-radius:10px; padding:5px 8px; font-size:16px; line-height:1; transition:all .15s; }
        .emoji-chip:hover { background:rgba(255,255,255,.14); transform:translateY(-1px); }
        .preview-card { border:1px dashed rgba(255,255,255,.18); background:rgba(255,255,255,.04); border-radius:14px; padding:12px; }
        .preview-title { color:#fff; font-size:.88rem; font-weight:600; line-height:1.4; }
        .preview-body { color:rgba(255,255,255,.52); font-size:.76rem; line-height:1.5; }
        .article-preview h2 { font-size:1.05rem;font-weight:700;color:#fff;margin:.75rem 0 .35rem; }
        .article-preview h3 { font-size:.92rem;font-weight:600;color:#e2e8f0;margin:.65rem 0 .3rem; }
        .article-preview p  { color:rgba(255,255,255,.72);font-size:.84rem;line-height:1.65;margin:.3rem 0; }
        .article-preview ul { list-style:disc;padding-left:1.2rem;color:rgba(255,255,255,.72);font-size:.84rem; }
        .article-preview ol { list-style:decimal;padding-left:1.2rem;color:rgba(255,255,255,.72);font-size:.84rem; }
        .article-preview blockquote { border-left:3px solid #3454d1;padding:.35rem .7rem;background:rgba(52,84,209,.10);border-radius:0 8px 8px 0;color:rgba(255,255,255,.66);margin:.4rem 0; }
        .article-preview a { color:#6b8fff;text-decoration:underline; }
        /* Forcer un style dark stable sur tous les navigateurs */
        input[type="text"], input[type="color"], select, textarea {
            background: rgba(255,255,255,.08) !important;
            border: 1px solid rgba(255,255,255,.12) !important;
            color: #e5e7eb !important;
            -webkit-text-fill-color: #e5e7eb !important;
            opacity: 1 !important;
        }
        input::placeholder, textarea::placeholder { color: rgba(229,231,235,.42) !important; }
        select {
            appearance: none;
            -webkit-appearance: none;
            background-image:
                linear-gradient(45deg, transparent 50%, rgba(229,231,235,.7) 50%),
                linear-gradient(135deg, rgba(229,231,235,.7) 50%, transparent 50%);
            background-position:
                calc(100% - 16px) calc(50% - 1px),
                calc(100% - 11px) calc(50% - 1px);
            background-size: 5px 5px, 5px 5px;
            background-repeat: no-repeat;
            padding-right: 2.1rem !important;
        }
        select option { background: #111827 !important; color: #e5e7eb !important; }
        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus,
        textarea:-webkit-autofill,
        select:-webkit-autofill {
            -webkit-text-fill-color: #e5e7eb;
            -webkit-box-shadow: 0 0 0px 1000px #1a1f2d inset;
            transition: background-color 5000s ease-in-out 0s;
        }
        .glass input:focus,
        .glass select:focus,
        .glass textarea:focus {
            outline: none !important;
            border-color: rgba(107,143,255,.65) !important;
            box-shadow: 0 0 0 2px rgba(52,84,209,.35) !important;
        }
        #editModal .glass { border-color: rgba(255,255,255,.18); }
        @keyframes fadeUp { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:none} }
        .fade-up { animation: fadeUp .35s ease both; }
    </style>
</head>
<body class="min-h-screen text-white relative">
<div class="bg-ambient"></div>

<?php include __DIR__ . '/_nav.php'; ?>

<main class="relative z-10 w-full max-w-6xl mx-auto px-4 sm:px-6 py-8 space-y-8">

    <!-- ── En-tête + stats ────────────────────────────────────────────────── -->
    <div class="fade-up" style="animation-delay:.05s">
        <h1 class="text-2xl font-bold text-white mb-1">⚙️ Administration</h1>
        <p class="text-white/40 text-sm">Gérez les actualités et annonces du portail.</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 fade-up" style="animation-delay:.10s">
        <?php
        $total   = count($featured);
        $urgent  = count(array_filter($featured, fn($a) => ($a['category'] ?? '') === 'urgent'));
        $events  = count(array_filter($featured, fn($a) => ($a['category'] ?? '') === 'event'));
        $drafts  = count(array_filter($featured, fn($a) => ($a['status'] ?? 'published') === 'draft'));
        $lastDate = !empty($featured) ? end($featured)['created_at'] ?? '—' : '—';
        $stats = [
            ['label' => 'Actualités', 'value' => $total,    'icon' => '📰', 'color' => 'text-blue-400'],
            ['label' => 'Urgentes',   'value' => $urgent,   'icon' => '🚨', 'color' => 'text-red-400'],
            ['label' => 'Brouillons', 'value' => $drafts,   'icon' => '📝', 'color' => 'text-amber-400'],
            ['label' => 'Dernière',   'value' => $lastDate, 'icon' => '🕐', 'color' => 'text-cyan-400'],
        ];
        foreach ($stats as $s): ?>
        <div class="glass rounded-2xl p-4 flex items-center gap-3">
            <span class="text-2xl select-none"><?= $s['icon'] ?></span>
            <div>
                <p class="<?= $s['color'] ?> font-bold text-lg leading-none"><?= htmlspecialchars((string)$s['value']) ?></p>
                <p class="text-white/40 text-xs mt-0.5"><?= $s['label'] ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="grid lg:grid-cols-5 gap-6">

        <!-- ── Formulaire d'ajout ─────────────────────────────────────────── -->
        <section class="lg:col-span-2 glass rounded-3xl p-6 space-y-4 fade-up" style="animation-delay:.15s">
            <h2 class="font-semibold text-white">➕ Nouvelle actualité</h2>

            <div id="addStatus" class="hidden text-sm rounded-xl px-4 py-2.5"></div>

            <form id="addForm" class="space-y-3" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <!-- Emoji + Titre -->
                <div class="form-box space-y-2.5">
                    <p class="text-white/55 text-xs font-medium">Titre & humeur</p>
                    <div class="flex gap-2">
                        <input type="text" id="addEmoji" value="📢" maxlength="4"
                               class="w-14 px-2 py-2.5 rounded-xl bg-white/8 border border-white/12 text-white text-center text-xl focus:outline-none focus:ring-2 focus:ring-brand transition flex-shrink-0">
                        <input type="text" id="addTitle" placeholder="Titre de l'article (optionnel)…"
                               class="flex-1 px-4 py-2.5 rounded-xl bg-white/8 border border-white/12 text-white placeholder-white/25 text-sm focus:outline-none focus:ring-2 focus:ring-brand transition">
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <?php foreach (['📢','📰','🚨','🎉','📣','⚠️','✅','🛠️'] as $e): ?>
                        <button type="button" onclick="document.getElementById('addEmoji').value='<?= $e ?>'; updateAddPreview();" class="emoji-chip" title="Choisir <?= $e ?>"><?= $e ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Catégorie -->
                <div class="form-box space-y-2.5">
                    <p class="text-white/55 text-xs font-medium">Classement</p>
                    <select id="addCategory"
                            class="w-full px-4 py-2.5 rounded-xl bg-white/8 border border-white/12 text-white text-sm focus:outline-none focus:ring-2 focus:ring-brand transition cursor-pointer">
                        <option value="general"  class="bg-gray-900">Général</option>
                        <option value="info"     class="bg-gray-900">Info</option>
                        <option value="event"    class="bg-gray-900">Événement</option>
                        <option value="urgent"   class="bg-gray-900">🚨 Urgent</option>
                    </select>
                    <select id="addStatusType"
                            class="w-full px-4 py-2.5 rounded-xl bg-white/8 border border-white/12 text-white text-sm focus:outline-none focus:ring-2 focus:ring-brand transition cursor-pointer">
                        <option value="published" class="bg-gray-900">✅ Publier maintenant</option>
                        <option value="draft" class="bg-gray-900">📝 Enregistrer en brouillon</option>
                    </select>
                </div>

                <!-- Couleur -->
                <div class="form-box">
                    <div class="flex items-center gap-3">
                        <label class="text-white/50 text-xs">Couleur d'accent</label>
                        <input type="color" id="addColor" value="#3454d1"
                               class="w-8 h-8 rounded-lg cursor-pointer bg-transparent border border-white/15">
                        <div class="flex gap-1.5">
                            <?php foreach (['#3454d1','#ef4444','#8b5cf6','#0ea5e9','#10b981','#f59e0b'] as $c): ?>
                            <button type="button" onclick="document.getElementById('addColor').value='<?= $c ?>'"
                                    class="w-5 h-5 rounded-full border border-white/20 hover:scale-110 transition"
                                    style="background:<?= $c ?>"></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Éditeur riche -->
                <div>
                    <label class="text-white/50 text-xs mb-1.5 block">Contenu <span class="text-red-400">*</span></label>
                    <div id="addEditor"></div>
                </div>

                <div class="preview-card space-y-2">
                    <p class="text-white/45 text-[11px] uppercase tracking-[.14em]">Aperçu de la carte</p>
                    <div class="flex items-start gap-2.5">
                        <span id="addPreviewEmoji" class="text-lg leading-none select-none">📢</span>
                        <div class="min-w-0">
                            <p id="addPreviewTitle" class="preview-title">Titre de l'actualité</p>
                            <p id="addPreviewMeta" class="preview-body">Publié · aperçu instantané</p>
                            <div id="addPreviewArticle" class="preview-body article-preview mt-1">Votre contenu s'affichera ici...</div>
                        </div>
                    </div>
                </div>

                <button type="submit"
                        class="w-full py-2.5 bg-brand hover:bg-brand-dk text-white font-semibold rounded-xl transition shadow-lg shadow-brand/20 text-sm">
                    Publier l'actualité
                </button>
            </form>
        </section>

        <!-- ── Liste des actualités ──────────────────────────────────────── -->
        <section class="lg:col-span-3 space-y-3 fade-up" style="animation-delay:.20s">
            <div class="flex items-center justify-between mb-1">
                <h2 class="font-semibold text-white">📋 Actualités publiées</h2>
                <span id="annCount" class="text-white/35 text-xs"><?= $total ?> entrée<?= $total > 1 ? 's' : '' ?></span>
            </div>

            <div class="glass rounded-xl p-2.5 flex flex-wrap items-center gap-2">
                <span class="text-white/55 text-xs">Tri</span>
                <select id="sortMode" class="px-2.5 py-1.5 rounded-lg bg-white/8 border border-white/12 text-white text-xs">
                    <option value="recent" class="bg-gray-900">Récent</option>
                    <option value="urgent" class="bg-gray-900">Urgent d'abord</option>
                    <option value="category" class="bg-gray-900">Catégorie</option>
                </select>
                <span class="text-white/55 text-xs">Filtre</span>
                <select id="filterStatus" class="px-2.5 py-1.5 rounded-lg bg-white/8 border border-white/12 text-white text-xs">
                    <option value="all" class="bg-gray-900">Tous</option>
                    <option value="published" class="bg-gray-900">Publié</option>
                    <option value="draft" class="bg-gray-900">Brouillon</option>
                </select>
                <select id="filterCategory" class="px-2.5 py-1.5 rounded-lg bg-white/8 border border-white/12 text-white text-xs">
                    <option value="all" class="bg-gray-900">Toutes catégories</option>
                    <option value="urgent" class="bg-gray-900">Urgent</option>
                    <option value="event" class="bg-gray-900">Événement</option>
                    <option value="info" class="bg-gray-900">Info</option>
                    <option value="general" class="bg-gray-900">Général</option>
                </select>
            </div>

            <div id="annList" class="space-y-3">
                <?php if (empty($featured)): ?>
                <p class="text-white/30 italic text-sm" id="emptyMsg">Aucune actualité publiée.</p>
                <?php else: ?>
                <?php foreach (array_reverse($featured) as $ann):
                    $annId      = htmlspecialchars($ann['id']             ?? '');
                    $annEmoji   = htmlspecialchars($ann['emoji']          ?? '📢');
                    $annTitle   = htmlspecialchars($ann['title']          ?? '');
                    $annHtml    = $ann['html_content'] ?? htmlspecialchars($ann['content'] ?? '');
                    $annColor   = htmlspecialchars($ann['color']          ?? '#3454d1');
                    $annCat     = $ann['category'] ?? 'general';
                    $annStatus  = $ann['status'] ?? 'published';
                    $annDate    = htmlspecialchars($ann['created_at'] ?? ($ann['pinned_at'] ?? ''));
                    $annUpdated = htmlspecialchars($ann['updated_at']     ?? '');
                    $badgeCls   = $catBadge[$annCat] ?? $catBadge['general'];
                    $catLabel   = $catLabels[$annCat] ?? 'Général';
                    $statusLabel = $annStatus === 'draft' ? 'Brouillon' : 'Publié';
                    $statusCls = $annStatus === 'draft' ? 'bg-amber-500/20 text-amber-300' : 'bg-emerald-500/20 text-emerald-300';
                ?>
                <div id="ann-<?= $annId ?>"
                     class="ann-card glass rounded-2xl p-4 group"
                     data-status="<?= htmlspecialchars($annStatus) ?>"
                     data-category="<?= htmlspecialchars($annCat) ?>"
                     data-sort-date="<?= htmlspecialchars($annUpdated ?: $annDate) ?>"
                     style="border-left: 3px solid <?= $annColor ?>;">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3 min-w-0 flex-1">
                            <span class="text-lg select-none mt-0.5 flex-shrink-0"><?= $annEmoji ?></span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap mb-1">
                                    <?php if ($annTitle): ?>
                                    <p class="font-semibold text-sm text-white leading-snug"><?= $annTitle ?></p>
                                    <?php endif; ?>
                                    <span class="text-xs px-2 py-0.5 rounded-full font-medium <?= $badgeCls ?>"><?= $catLabel ?></span>
                                    <span class="text-xs px-2 py-0.5 rounded-full font-medium <?= $statusCls ?>"><?= $statusLabel ?></span>
                                </div>
                                <div class="news-content text-white/50 text-xs leading-relaxed line-clamp-2">
                                    <?= strip_tags($annHtml) ?>
                                </div>
                                <p class="text-white/25 text-xs mt-2">
                                    Publié le <?= $annDate ?>
                                    <?= $annUpdated && $annUpdated !== $annDate ? ' · Modifié le ' . $annUpdated : '' ?>
                                </p>
                            </div>
                        </div>
                        <div class="flex gap-1 flex-shrink-0 opacity-0 group-hover:opacity-100 transition">
                            <button onclick="editAnn('<?= $annId ?>')"
                                    class="p-1.5 text-blue-400 hover:bg-blue-500/20 rounded-lg transition" title="Modifier">
                                <span class="text-sm">✏️</span>
                            </button>
                            <button onclick="deleteAnn('<?= $annId ?>')"
                                    class="p-1.5 text-red-400 hover:bg-red-500/20 rounded-lg transition" title="Supprimer">
                                <span class="text-sm">🗑️</span>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <section class="glass rounded-3xl p-6 space-y-4 fade-up" style="animation-delay:.24s">
        <h2 class="font-semibold text-white">📣 Bannières importantes</h2>
        <div id="bannerStatus" class="hidden text-sm rounded-xl px-4 py-2.5"></div>
        <form id="bannerForm" class="grid lg:grid-cols-5 gap-2.5">
            <input id="bannerTitle" type="text" maxlength="150" placeholder="Titre bannière"
                   class="lg:col-span-1 px-3 py-2.5 rounded-xl bg-white/8 border border-white/12 text-white text-sm">
            <input id="bannerMessage" type="text" maxlength="600" placeholder="Message important"
                   class="lg:col-span-2 px-3 py-2.5 rounded-xl bg-white/8 border border-white/12 text-white text-sm">
            <select id="bannerStyle" class="lg:col-span-1 px-3 py-2.5 rounded-xl bg-white/8 border border-white/12 text-white text-sm">
                <option value="danger" class="bg-gray-900">🚨 Critique</option>
                <option value="warning" class="bg-gray-900">⚠️ Alerte</option>
                <option value="info" class="bg-gray-900">ℹ️ Info</option>
                <option value="success" class="bg-gray-900">✅ Succès</option>
            </select>
            <button class="lg:col-span-1 py-2.5 bg-brand hover:bg-brand-dk text-white rounded-xl text-sm font-semibold">Ajouter</button>
        </form>
        <div id="bannerList" class="space-y-2"></div>
    </section>
</main>

<!-- ── Modal d'édition ──────────────────────────────────────────────────────── -->
<div id="editModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeEdit()"></div>
    <div class="relative glass rounded-3xl p-6 w-full max-w-lg space-y-4 z-10">
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-white">✏️ Modifier l'actualité</h3>
            <button onclick="closeEdit()" class="p-1.5 text-white/40 hover:text-white hover:bg-white/10 rounded-lg transition">
                <span class="text-sm">✖️</span>
            </button>
        </div>
        <div id="editStatus" class="hidden text-sm rounded-xl px-4 py-2.5"></div>
        <input type="hidden" id="editId">
        <div class="flex gap-2">
            <input type="text" id="editEmoji" maxlength="4"
                   class="w-14 px-2 py-2.5 rounded-xl bg-white/8 border border-white/12 text-white text-center text-xl focus:outline-none focus:ring-2 focus:ring-brand transition flex-shrink-0">
            <input type="text" id="editTitle" placeholder="Titre (optionnel)…"
                   class="flex-1 px-4 py-2.5 rounded-xl bg-white/8 border border-white/12 text-white placeholder-white/25 text-sm focus:outline-none focus:ring-2 focus:ring-brand transition">
        </div>
        <select id="editCategory"
                class="w-full px-4 py-2.5 rounded-xl bg-white/8 border border-white/12 text-white text-sm focus:outline-none focus:ring-2 focus:ring-brand transition cursor-pointer">
            <option value="general" class="bg-gray-900">Général</option>
            <option value="info"    class="bg-gray-900">Info</option>
            <option value="event"   class="bg-gray-900">Événement</option>
            <option value="urgent"  class="bg-gray-900">🚨 Urgent</option>
        </select>
        <select id="editStatusType"
                class="w-full px-4 py-2.5 rounded-xl bg-white/8 border border-white/12 text-white text-sm focus:outline-none focus:ring-2 focus:ring-brand transition cursor-pointer">
                    <option value="published" class="bg-gray-900">✅ Publié</option>
            <option value="draft" class="bg-gray-900">📝 Brouillon</option>
        </select>
        <div class="flex flex-wrap gap-1.5">
            <?php foreach (['📢','📰','🚨','🎉','📣','⚠️','✅','🛠️'] as $e): ?>
            <button type="button" onclick="document.getElementById('editEmoji').value='<?= $e ?>'; updateEditPreview();" class="emoji-chip" title="Choisir <?= $e ?>"><?= $e ?></button>
            <?php endforeach; ?>
        </div>
        <div class="flex items-center gap-3">
            <label class="text-white/50 text-xs">Couleur</label>
            <input type="color" id="editColor"
                   class="w-8 h-8 rounded-lg cursor-pointer bg-transparent border border-white/15">
            <div class="flex gap-1.5">
                <?php foreach (['#3454d1','#ef4444','#8b5cf6','#0ea5e9','#10b981','#f59e0b'] as $c): ?>
                <button type="button" onclick="document.getElementById('editColor').value='<?= $c ?>'"
                        class="w-5 h-5 rounded-full border border-white/20 hover:scale-110 transition"
                        style="background:<?= $c ?>"></button>
                <?php endforeach; ?>
            </div>
        </div>
        <div>
            <label class="text-white/50 text-xs mb-1.5 block">Contenu <span class="text-red-400">*</span></label>
            <div id="editEditor"></div>
        </div>
        <div class="preview-card space-y-2">
            <p class="text-white/45 text-[11px] uppercase tracking-[.14em]">Aperçu modification</p>
            <div class="flex items-start gap-2.5">
                <span id="editPreviewEmoji" class="text-lg leading-none select-none">📢</span>
                <div class="min-w-0">
                    <p id="editPreviewTitle" class="preview-title">Titre de l'actualité</p>
                    <p id="editPreviewMeta" class="preview-body">Publié · aperçu instantané</p>
                    <div id="editPreviewArticle" class="preview-body article-preview mt-1">Le contenu modifié apparaîtra ici...</div>
                </div>
            </div>
        </div>
        <button id="editSubmitBtn" onclick="submitEdit()"
                class="w-full py-2.5 bg-brand hover:bg-brand-dk text-white font-semibold rounded-xl transition text-sm">
            Enregistrer les modifications
        </button>
    </div>
</div>

<script>
const CSRF = <?= json_encode($csrfToken) ?>;

// Données des annonces pour l'édition
const ANN_DATA = <?= json_encode(array_column($featured, null, 'id'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>;
const BANNER_DATA = <?= json_encode(array_values($banners), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>;

// ── Quill : éditeur d'ajout ───────────────────────────────────────────────────
const quillAdd = new Quill('#addEditor', {
    theme: 'snow',
    placeholder: 'Rédigez votre actualité…',
    modules: { toolbar: [[{ header: [2,3,false] }], ['bold','italic','underline','strike'],
                          [{ list:'ordered' },{ list:'bullet' }], ['blockquote'], ['clean']] }
});

// ── Quill : éditeur de modification ──────────────────────────────────────────
const quillEdit = new Quill('#editEditor', {
    theme: 'snow',
    placeholder: 'Modifiez le contenu…',
    modules: { toolbar: [[{ header: [2,3,false] }], ['bold','italic','underline','strike'],
                          [{ list:'ordered' },{ list:'bullet' }], ['blockquote'], ['clean']] }
});

quillAdd.on('text-change', updateAddPreview);
quillEdit.on('text-change', updateEditPreview);
document.getElementById('addEmoji').addEventListener('input', updateAddPreview);
document.getElementById('addTitle').addEventListener('input', updateAddPreview);
document.getElementById('addStatusType').addEventListener('change', updateAddPreview);
document.getElementById('editEmoji').addEventListener('input', updateEditPreview);
document.getElementById('editTitle').addEventListener('input', updateEditPreview);
document.getElementById('editStatusType').addEventListener('change', updateEditPreview);
updateAddPreview();

// ── HELPERS ───────────────────────────────────────────────────────────────────
function showStatus(el, msg, type) {
    el.textContent = msg;
    el.className = 'text-sm rounded-xl px-4 py-2.5 ' + (type === 'success'
        ? 'bg-emerald-500/20 text-emerald-300' : 'bg-red-500/20 text-red-300');
    el.classList.remove('hidden');
    if (type === 'success') setTimeout(() => el.classList.add('hidden'), 4000);
}

function stripHtml(html) {
    return String(html || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
}

function updateAddPreview() {
    const emoji = document.getElementById('addEmoji').value.trim() || '📢';
    const title = document.getElementById('addTitle').value.trim() || 'Titre de l\'actualité';
    const status = document.getElementById('addStatusType').value === 'draft' ? 'Brouillon' : 'Publié';
    const html = quillAdd.root.innerHTML;
    document.getElementById('addPreviewEmoji').textContent = emoji;
    document.getElementById('addPreviewTitle').textContent = title;
    document.getElementById('addPreviewMeta').textContent = status + ' · aperçu instantané';
    document.getElementById('addPreviewArticle').innerHTML = stripHtml(html) ? html : 'Votre contenu s\'affichera ici...';
}

function updateEditPreview() {
    const emoji = document.getElementById('editEmoji').value.trim() || '📢';
    const title = document.getElementById('editTitle').value.trim() || 'Titre de l\'actualité';
    const status = document.getElementById('editStatusType').value === 'draft' ? 'Brouillon' : 'Publié';
    const html = quillEdit.root.innerHTML;
    document.getElementById('editPreviewEmoji').textContent = emoji;
    document.getElementById('editPreviewTitle').textContent = title;
    document.getElementById('editPreviewMeta').textContent = status + ' · aperçu instantané';
    document.getElementById('editPreviewArticle').innerHTML = stripHtml(html) ? html : 'Le contenu modifié apparaîtra ici...';
}

function applyListSortAndFilter() {
    const sortMode = document.getElementById('sortMode').value;
    const filterStatus = document.getElementById('filterStatus').value;
    const filterCategory = document.getElementById('filterCategory').value;
    const list = document.getElementById('annList');
    const cards = Array.from(list.querySelectorAll('.ann-card'));

    cards.forEach(card => {
        const okStatus = filterStatus === 'all' || card.dataset.status === filterStatus;
        const okCat = filterCategory === 'all' || card.dataset.category === filterCategory;
        card.style.display = okStatus && okCat ? '' : 'none';
    });

    const parseFrDate = (value) => {
        const m = String(value || '').match(/(\d{2})\/(\d{2})\/(\d{4})\s+à\s+(\d{2}):(\d{2})/);
        if (!m) return 0;
        return Date.UTC(Number(m[3]), Number(m[2]) - 1, Number(m[1]), Number(m[4]), Number(m[5]));
    };

    cards.sort((a, b) => {
        if (sortMode === 'urgent') {
            const av = a.dataset.category === 'urgent' ? 0 : 1;
            const bv = b.dataset.category === 'urgent' ? 0 : 1;
            if (av !== bv) return av - bv;
        }
        if (sortMode === 'category') {
            return (a.dataset.category || '').localeCompare(b.dataset.category || '');
        }
        return parseFrDate(b.dataset.sortDate) - parseFrDate(a.dataset.sortDate);
    });

    cards.forEach(card => list.appendChild(card));

    const visible = cards.filter(c => c.style.display !== 'none').length;
    const annCount = document.getElementById('annCount');
    if (annCount) annCount.textContent = `${visible} entrée${visible > 1 ? 's' : ''}`;
}

function bannerTone(style) {
    if (style === 'danger') return { cls: 'bg-red-500/20 text-red-200 border-red-500/35', icon: '🚨' };
    if (style === 'warning') return { cls: 'bg-amber-500/20 text-amber-200 border-amber-500/35', icon: '⚠️' };
    if (style === 'success') return { cls: 'bg-emerald-500/20 text-emerald-200 border-emerald-500/35', icon: '✅' };
    return { cls: 'bg-cyan-500/20 text-cyan-200 border-cyan-500/35', icon: 'ℹ️' };
}

function renderBannerList() {
    const list = document.getElementById('bannerList');
    if (!BANNER_DATA.length) {
        list.innerHTML = '<p class="text-white/35 text-sm italic">Aucune bannière pour le moment.</p>';
        return;
    }
    const ordered = BANNER_DATA.slice().sort((a, b) => String(b.updated_at || b.created_at || '').localeCompare(String(a.updated_at || a.created_at || '')));
    list.innerHTML = ordered.map(b => {
        const tone = bannerTone(b.style || 'danger');
        return `<div class="glass rounded-xl p-3 border ${tone.cls}">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-semibold text-sm">${tone.icon} ${esc(b.title || '')}</p>
                    <p class="text-sm opacity-90 mt-0.5">${esc(b.message || '')}</p>
                    <p class="text-xs opacity-70 mt-1">${b.active ? 'Active' : 'Inactive'} · ${esc(b.updated_at || b.created_at || '')}</p>
                </div>
                <div class="flex gap-1.5">
                    <button onclick="toggleBanner('${esc(b.id)}')" class="px-2 py-1 text-xs rounded-lg bg-white/15 hover:bg-white/25">${b.active ? 'Desactiver' : 'Activer'}</button>
                    <button onclick="deleteBanner('${esc(b.id)}')" class="px-2 py-1 text-xs rounded-lg bg-red-500/20 text-red-200 hover:bg-red-500/30">Suppr.</button>
                </div>
            </div>
        </div>`;
    }).join('');
}

function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

const CAT_BADGE = {
    general:'bg-blue-500/20 text-blue-300', urgent:'bg-red-500/20 text-red-300',
    event:'bg-violet-500/20 text-violet-300', info:'bg-cyan-500/20 text-cyan-300'
};
const CAT_LABEL = { general:'Général', urgent:'Urgent', event:'Événement', info:'Info' };

function buildCard(ann) {
    const badge  = CAT_BADGE[ann.category] ?? CAT_BADGE.general;
    const label  = CAT_LABEL[ann.category] ?? 'Général';
    const titleH = ann.title ? `<p class="font-semibold text-sm text-white leading-snug">${esc(ann.title)}</p>` : '';
    return `
    <div id="ann-${esc(ann.id)}" class="ann-card glass rounded-2xl p-4 group fade-up"
         data-status="${esc(ann.status || 'published')}"
         data-category="${esc(ann.category || 'general')}"
         data-sort-date="${esc(ann.updated_at || ann.created_at || '')}"
         style="border-left:3px solid ${esc(ann.color)};">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-start gap-3 min-w-0 flex-1">
                <span class="text-lg select-none mt-0.5 flex-shrink-0">${esc(ann.emoji)}</span>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 flex-wrap mb-1">
                        ${titleH}
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium ${badge}">${label}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium ${(ann.status === 'draft') ? 'bg-amber-500/20 text-amber-300' : 'bg-emerald-500/20 text-emerald-300'}">${(ann.status === 'draft') ? 'Brouillon' : 'Publié'}</span>
                    </div>
                    <div class="text-white/50 text-xs leading-relaxed line-clamp-2">
                        ${esc(ann.html_content.replace(/<[^>]+>/g,''))}
                    </div>
                    <p class="text-white/25 text-xs mt-2">Publié le ${esc(ann.created_at)}</p>
                </div>
            </div>
            <div class="flex gap-1 flex-shrink-0 opacity-0 group-hover:opacity-100 transition">
                <button onclick="editAnn('${esc(ann.id)}')"
                        class="p-1.5 text-blue-400 hover:bg-blue-500/20 rounded-lg transition" title="Modifier">
                    <span class="text-sm">✏️</span>
                </button>
                <button onclick="deleteAnn('${esc(ann.id)}')"
                        class="p-1.5 text-red-400 hover:bg-red-500/20 rounded-lg transition" title="Supprimer">
                    <span class="text-sm">🗑️</span>
                </button>
            </div>
        </div>
    </div>`;
}

// ── AJOUT ─────────────────────────────────────────────────────────────────────
document.getElementById('addForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const statusEl = document.getElementById('addStatus');
    const btn = e.target.querySelector('button[type="submit"]');
    const html = quillAdd.root.innerHTML;
    if (quillAdd.getText().trim() === '') {
        showStatus(statusEl, 'Le contenu est obligatoire.', 'error'); return;
    }
    btn.disabled = true; btn.textContent = 'Publication…';
    try {
        const res  = await fetch('/save-featured.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'add', csrf_token: CSRF,
                emoji:    document.getElementById('addEmoji').value.trim() || '📢',
                title:    document.getElementById('addTitle').value.trim(),
                category: document.getElementById('addCategory').value,
                status:   document.getElementById('addStatusType').value,
                color:    document.getElementById('addColor').value,
                html_content: html,
            }),
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Erreur serveur.');

        ANN_DATA[data.announcement.id] = data.announcement;
        const list = document.getElementById('annList');
        const empty = document.getElementById('emptyMsg');
        if (empty) empty.remove();
        list.insertAdjacentHTML('afterbegin', buildCard(data.announcement));
        showStatus(statusEl, 'Actualité publiée avec succès.', 'success');
        quillAdd.setContents([]);
        e.target.reset();
        document.getElementById('addEmoji').value = '📢';
        document.getElementById('addColor').value = '#3454d1';
        updateAddPreview();
        applyListSortAndFilter();
    } catch(err) { showStatus(statusEl, err.message, 'error'); }
    finally { btn.disabled = false; btn.textContent = 'Publier l\'actualité'; }
});

// ── ÉDITION ───────────────────────────────────────────────────────────────────
function editAnn(id) {
    const ann = ANN_DATA[id]; if (!ann) return;
    document.getElementById('editId').value       = id;
    document.getElementById('editEmoji').value    = ann.emoji    || '📢';
    document.getElementById('editTitle').value    = ann.title    || '';
    document.getElementById('editCategory').value = ann.category || 'general';
    document.getElementById('editStatusType').value = ann.status || 'published';
    document.getElementById('editColor').value    = ann.color    || '#3454d1';
    quillEdit.root.innerHTML = ann.html_content || '';
    updateEditPreview();
    document.getElementById('editStatus').classList.add('hidden');
    document.getElementById('editModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeEdit() {
    document.getElementById('editModal').classList.add('hidden');
    document.body.style.overflow = '';
}

async function submitEdit() {
    const id      = document.getElementById('editId').value;
    const statusEl = document.getElementById('editStatus');
    const btn     = document.getElementById('editSubmitBtn');
    const html    = quillEdit.root.innerHTML;
    if (quillEdit.getText().trim() === '') {
        showStatus(statusEl, 'Le contenu est obligatoire.', 'error'); return;
    }
    btn.disabled = true; btn.textContent = 'Enregistrement…';
    try {
        const res  = await fetch('/save-featured.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'update', csrf_token: CSRF, id,
                emoji:    document.getElementById('editEmoji').value.trim() || '📢',
                title:    document.getElementById('editTitle').value.trim(),
                category: document.getElementById('editCategory').value,
                status:   document.getElementById('editStatusType').value,
                color:    document.getElementById('editColor').value,
                html_content: html,
            }),
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Erreur serveur.');

        ANN_DATA[id] = data.announcement;
        const old = document.getElementById('ann-' + id);
        if (old) old.outerHTML = buildCard(data.announcement);
        applyListSortAndFilter();
        showStatus(statusEl, 'Modifications enregistrées.', 'success');
        setTimeout(closeEdit, 1200);
    } catch(err) { showStatus(statusEl, err.message, 'error'); }
    finally { btn.disabled = false; btn.textContent = 'Enregistrer les modifications'; }
}

// ── SUPPRESSION ───────────────────────────────────────────────────────────────
async function deleteAnn(id) {
    if (!confirm('Supprimer cette actualité ?')) return;
    try {
        const res  = await fetch('/save-featured.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', csrf_token: CSRF, id }),
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Erreur serveur.');
        delete ANN_DATA[id];
        const el = document.getElementById('ann-' + id);
        if (el) el.remove();
        const list = document.getElementById('annList');
        if (!list.children.length)
            list.innerHTML = '<p class="text-white/30 italic text-sm" id="emptyMsg">Aucune actualité publiée.</p>';
        applyListSortAndFilter();
    } catch(err) { alert(err.message); }
}

async function bannerApi(payload) {
    const res = await fetch('/save-banner.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload),
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Erreur serveur.');
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
        const data = await bannerApi({ action:'add', csrf_token:CSRF, title, message, style, active:true });
        BANNER_DATA.push(data.banner);
        renderBannerList();
        e.target.reset();
        document.getElementById('bannerStyle').value = 'danger';
        showStatus(statusEl, 'Bannière ajoutée.', 'success');
    } catch (err) { showStatus(statusEl, err.message, 'error'); }
});

async function toggleBanner(id) {
    const statusEl = document.getElementById('bannerStatus');
    try {
        const data = await bannerApi({ action:'toggle', csrf_token:CSRF, id });
        const idx = BANNER_DATA.findIndex(b => b.id === id);
        if (idx >= 0) BANNER_DATA[idx] = data.banner;
        renderBannerList();
        showStatus(statusEl, data.banner.active ? 'Bannière activée.' : 'Bannière désactivée.', 'success');
    } catch (err) { showStatus(statusEl, err.message, 'error'); }
}

async function deleteBanner(id) {
    if (!confirm('Supprimer cette bannière ?')) return;
    const statusEl = document.getElementById('bannerStatus');
    try {
        await bannerApi({ action:'delete', csrf_token:CSRF, id });
        const idx = BANNER_DATA.findIndex(b => b.id === id);
        if (idx >= 0) BANNER_DATA.splice(idx, 1);
        renderBannerList();
        showStatus(statusEl, 'Bannière supprimée.', 'success');
    } catch (err) { showStatus(statusEl, err.message, 'error'); }
}

document.getElementById('sortMode').addEventListener('change', applyListSortAndFilter);
document.getElementById('filterStatus').addEventListener('change', applyListSortAndFilter);
document.getElementById('filterCategory').addEventListener('change', applyListSortAndFilter);
applyListSortAndFilter();
renderBannerList();

// Fermer modal avec Échap
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeEdit(); });
</script>
</body>
</html>
