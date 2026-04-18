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
        :root {
            --bg: #0D0C14;
            --surface-1: #221F2E;
            --primary-cnt: #4F378B;
            --primary-cnt-on: #EADDFF;
            --on-surface: #E6E1E5;
            --on-surface-var: #CAC4D0;
            --outline-var: #49454F;
            --danger: #F2B8B8;
            --danger-cnt: rgba(140,29,24,.55);
            --shape-xl: 28px;
            --shape-full: 9999px;
            --elev-2: 0 1px 2px rgba(0,0,0,.4), 0 2px 6px 2px rgba(0,0,0,.2);
        }
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .bg-ambient {
            position: fixed; inset: 0; pointer-events: none; z-index: 0;
            background:
                radial-gradient(ellipse 70% 55% at 8% 0%, rgba(79,55,139,.35) 0%, transparent 55%),
                radial-gradient(ellipse 55% 45% at 94% 100%, rgba(0,79,80,.25) 0%, transparent 55%);
        }
    </style>
</head>
<body style="color:var(--on-surface);">
<div class="bg-ambient" aria-hidden="true"></div>
<div class="relative z-10 w-full max-w-sm text-center">
    <div class="rounded-[28px] p-8" style="background:var(--surface-1);box-shadow:var(--elev-2);">
        <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4"
             style="background:var(--danger-cnt);border:1px solid rgba(242,184,184,.25);">
            <span class="text-3xl" aria-hidden="true">⛔</span>
        </div>
        <h1 class="text-xl font-bold mb-2" style="color:var(--on-surface);">Accès refusé</h1>
        <p class="text-sm mb-6" style="color:var(--on-surface-var);">Cette page est réservée aux administrateurs du portail.</p>
        <div class="flex items-center justify-center gap-3 flex-wrap">
            <a href="/"
               class="px-5 py-2.5 rounded-full text-sm font-medium transition"
               style="background:var(--primary-cnt);color:var(--primary-cnt-on);"
               onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'">
                Retour à l'accueil
            </a>
            <a href="/logout.php"
               class="px-5 py-2.5 rounded-full text-sm font-medium transition"
               style="background:rgba(255,255,255,.07);color:var(--on-surface-var);border:1px solid var(--outline-var);"
               onmouseover="this.style.background='rgba(255,255,255,.12)'" onmouseout="this.style.background='rgba(255,255,255,.07)'">
                Déconnexion
            </a>
        </div>
    </div>
</div>
</body>
</html>
