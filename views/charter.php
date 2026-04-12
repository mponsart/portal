<?php
$user = $_SESSION['user'];
$csrfToken = $_SESSION['csrf_token'] ?? '';
$charterVersion = current_charter_version($config ?? []);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Charte Informatique - Groupe Speed Cloud</title>
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
        .charter h2 { font-size:1.05rem; font-weight:700; color:#fff; margin-top:1rem; }
        .charter p, .charter li { color:rgba(255,255,255,.78); font-size:.92rem; line-height:1.7; }
        .charter ul { list-style:disc; padding-left:1.2rem; margin-top:.35rem; }
    </style>
</head>
<body class="min-h-screen text-white relative">
<div class="bg-ambient"></div>

<main class="relative z-10 w-full max-w-4xl mx-auto px-4 sm:px-6 py-10 page-stack">
    <section class="glass rounded-3xl p-6 sm:p-8">
        <h1 class="text-2xl sm:text-3xl font-bold">📜 Charte Informatique - Association à Distance</h1>
        <p class="text-white/50 text-sm mt-2">Bienvenue <?= htmlspecialchars((string)($user['firstName'] ?? $user['name'] ?? '')) ?>. Cette validation est requise lors de votre première connexion.</p>
        <p class="text-white/35 text-xs mt-1">Version de la charte : <?= htmlspecialchars($charterVersion) ?></p>
    </section>

    <section class="glass rounded-3xl p-6 sm:p-8 charter">
        <h2>1. Usage des comptes et accès</h2>
        <ul>
            <li>Le compte est personnel et ne doit jamais être partagé.</li>
            <li>L'utilisateur protège ses identifiants et signale immédiatement toute suspicion de compromission.</li>
            <li>L'accès aux ressources est strictement lié aux missions associatives.</li>
        </ul>

        <h2>2. Confidentialité et données</h2>
        <ul>
            <li>Les données membres, partenaires et documents internes sont confidentiels.</li>
            <li>Tout partage externe doit être explicitement autorisé.</li>
            <li>La collecte et le traitement des données doivent rester proportionnés et traçables.</li>
        </ul>

        <h2>3. Bonnes pratiques en télétravail</h2>
        <ul>
            <li>Utiliser un poste à jour (système, navigateur, antivirus).</li>
            <li>Verrouiller sa session en cas d'absence et privilégier un réseau sécurisé.</li>
            <li>Ne pas installer d'outils non validés pour traiter des données associatives.</li>
        </ul>

        <h2>4. Communication et collaboration</h2>
        <ul>
            <li>Adopter une communication respectueuse, professionnelle et inclusive.</li>
            <li>Structurer les échanges pour préserver la qualité de service à distance.</li>
            <li>Limiter la diffusion d'informations sensibles aux seules personnes concernées.</li>
        </ul>

        <h2>5. Responsabilité et conformité</h2>
        <ul>
            <li>Chaque utilisateur est responsable des actions effectuées avec son compte.</li>
            <li>Le non-respect de la charte peut entraîner la suspension d'accès.</li>
            <li>La présente charte peut être mise à jour ; une nouvelle validation peut être demandée.</li>
        </ul>
    </section>

    <section class="glass rounded-3xl p-5 sm:p-6">
        <form method="post" action="/accept-charter.php" class="space-y-3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="accept_charter" value="yes">
            <label class="flex items-start gap-2 text-sm text-white/80">
                <input type="checkbox" id="confirmCharter" required class="mt-1">
                <span>Je confirme avoir lu, compris et accepté la charte informatique de l'association.</span>
            </label>
            <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold">Valider et accéder au portail</button>
        </form>
    </section>
</main>
</body>
</html>
