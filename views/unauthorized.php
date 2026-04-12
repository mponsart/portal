<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès refusé - Groupe Speed Cloud</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="/assets/images/cloudy.png">
    <link href="https://fonts.googleapis.com/css2?family=Titillium+Web:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Titillium Web', sans-serif;
            background: radial-gradient(circle at 15% 10%, #1d4ed8 0%, #0b132b 45%, #020617 100%);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4 text-white">
    <div class="w-full max-w-lg rounded-3xl border border-white/15 bg-white/10 backdrop-blur-xl p-8 text-center shadow-2xl">
        <p class="text-5xl mb-3">⛔</p>
        <h1 class="text-2xl font-bold">Vous n'avez pas l'autorisation</h1>
        <p class="text-white/70 mt-2">Cette page est réservée aux administrateurs du portail.</p>
        <div class="mt-6 flex items-center justify-center gap-3">
            <a href="/" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 transition font-semibold text-sm">Retour à l'accueil</a>
            <a href="/logout.php" class="px-4 py-2 rounded-lg border border-white/20 hover:bg-white/10 transition font-semibold text-sm">Se déconnecter</a>
        </div>
    </div>
</body>
</html>
