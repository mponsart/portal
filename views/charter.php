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
    <title>Charte Informatique — Groupe Speed Cloud</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="/assets/images/cloudy.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php include __DIR__ . '/_ui-tokens.php'; ?>
    <style>
        body { font-family:'Inter',sans-serif; background:var(--bg); color:var(--on-surface); color-scheme:dark; }
        .charter h2 { font-size:1rem; font-weight:700; color:var(--on-surface); margin-top:1.2rem; margin-bottom:.4rem; }
        .charter p, .charter li { color:var(--on-surface-var); font-size:.9rem; line-height:1.8; }
        .charter ul { list-style:disc; padding-left:1.3rem; margin-top:.3rem; }
        .charter li { margin:.2rem 0; }
    </style>
</head>
<body class="min-h-screen text-white relative">
<div class="bg-ambient" aria-hidden="true"></div>

<main class="relative z-10 w-full max-w-3xl mx-auto px-4 sm:px-6 py-10 page-stack">

    <!-- Header -->
    <div class="panel p-6 sm:p-8">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-2xl flex items-center justify-center flex-shrink-0"
                 style="background:var(--primary-cnt);border:1px solid rgba(208,188,255,.2);">
                <span class="text-xl" aria-hidden="true">📜</span>
            </div>
            <div>
                <h1 class="text-xl font-bold" style="color:var(--on-surface);">Charte Informatique</h1>
                <p class="text-xs mt-0.5" style="color:var(--on-surface-var);">Validation requise à la première connexion.</p>
            </div>
        </div>
        <p class="text-sm" style="color:var(--on-surface-var);">
            Bienvenue <strong style="color:var(--on-surface);"><?= htmlspecialchars((string)($user['firstName'] ?? $user['name'] ?? '')) ?></strong>.
            Veuillez lire et accepter la charte avant d'accéder au portail.
        </p>
        <p class="text-xs mt-2" style="color:var(--outline);">Version : <?= htmlspecialchars($charterVersion) ?></p>
    </div>

    <!-- Charter content -->
    <div class="panel p-6 sm:p-8 charter">
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
    </div>

    <!-- Accept form -->
    <div class="panel p-5 sm:p-6">
        <form method="post" action="/accept-charter.php" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="accept_charter" value="yes">
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" id="confirmCharter" required
                       class="mt-1 w-4 h-4 flex-shrink-0 accent-violet-500">
                <span class="text-sm leading-relaxed" style="color:var(--on-surface-var);">
                    Je confirme avoir lu, compris et accepté la charte informatique de l'association.
                </span>
            </label>
            <button type="submit"
                    class="w-full py-3 rounded-full font-medium text-sm transition"
                    style="background:var(--primary-cnt);color:var(--primary-cnt-on);"
                    onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">
                Valider et accéder au portail →
            </button>
        </form>
    </div>

</main>
</body>
</html>
