<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès refusé — Groupe Speed Cloud</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="/assets/images/cloudy.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #07080e;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .bg-ambient {
            position: fixed; inset: 0; pointer-events: none; z-index: 0;
            background:
                radial-gradient(ellipse 65% 50% at 10%  5%,  rgba(124,58,237,.22) 0%, transparent 58%),
                radial-gradient(ellipse 50% 40% at 92% 95%,  rgba(8,145,178,.16)  0%, transparent 56%);
        }
    </style>
</head>
<body class="text-white">
<div class="bg-ambient"></div>
<div class="relative z-10 w-full max-w-md text-center">
    <div class="rounded-2xl p-8"
         style="background:rgba(255,255,255,.055);border:1px solid rgba(255,255,255,.09);">
        <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4"
             style="background:rgba(220,38,38,.18);border:1px solid rgba(220,38,38,.35);">
            <span class="text-3xl">⛔</span>
        </div>
        <h1 class="text-xl font-bold text-white mb-2">Accès refusé</h1>
        <p class="text-white/55 text-sm mb-6">Cette page est réservée aux administrateurs du portail.</p>
        <div class="flex items-center justify-center gap-3">
            <a href="/"
               class="px-4 py-2 rounded-xl text-white text-sm font-semibold transition"
               style="background:#7c3aed;"
               onmouseover="this.style.background='#6d28d9'" onmouseout="this.style.background='#7c3aed'">
                Retour à l'accueil
            </a>
            <a href="/logout.php"
               class="px-4 py-2 rounded-xl text-sm font-semibold transition text-white/60 hover:text-white"
               style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);">
                Déconnexion
            </a>
        </div>
    </div>
</div>
</body>
</html>
