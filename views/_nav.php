<?php
/**
 * Navigation partagée — MD3
 * Requiert $user, $currentPage dans le scope.
 * $isAdmin est optionnel.
 */
$isAdmin = $isAdmin ?? false;
$cp = $currentPage ?? '';
?>
<style>
    /* ── MD3 Top App Bar ──────────────────────────────────────────── */
    .md-top-bar {
        background: rgba(13,12,20,.92);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        border-bottom: 1px solid var(--outline-var);
        position: sticky;
        top: 0;
        z-index: 50;
    }

    /* ── Desktop Tab Navigation ───────────────────────────────────── */
    .md-tab {
        padding: 8px 18px;
        border-radius: var(--shape-full);
        font-size: .8125rem;
        font-weight: 500;
        letter-spacing: .00625em;
        text-decoration: none;
        color: var(--on-surface-var);
        transition: background .14s, color .14s;
        white-space: nowrap;
    }
    .md-tab:hover {
        background: rgba(208,188,255,.08);
        color: var(--on-surface);
    }
    .md-tab.t-active {
        background: var(--secondary-cnt);
        color: var(--secondary-cnt-on);
    }
    .md-tab.t-status {
        background: rgba(0,83,32,.5);
        color: var(--success-lt);
    }
    .md-tab.t-admin {
        background: rgba(92,57,0,.5);
        color: var(--warning-lt);
    }

    /* ── MD3 Bottom Navigation Bar (mobile) ──────────────────────── */
    .md-nav-bar {
        position: fixed;
        bottom: 0; left: 0; right: 0;
        height: 72px;
        background: rgba(22,20,35,.97);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        border-top: 1px solid var(--outline-var);
        display: flex;
        align-items: center;
        justify-content: space-around;
        z-index: 50;
        padding-bottom: env(safe-area-inset-bottom, 0px);
    }
    .nav-dest {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        flex: 1;
        padding: 8px 4px;
        color: var(--on-surface-var);
        text-decoration: none;
        transition: color .14s;
    }
    .nav-dest .nav-pill {
        width: 64px; height: 32px;
        border-radius: var(--shape-full);
        display: flex; align-items: center; justify-content: center;
        transition: background .14s;
    }
    .nav-dest.active .nav-pill { background: var(--secondary-cnt); }
    .nav-dest.active { color: var(--on-surface); }
    .nav-dest .nav-label { font-size: .625rem; font-weight: 500; letter-spacing: .03em; }
    .nav-dest svg { width: 22px; height: 22px; flex-shrink: 0; }

    /* ── Responsive rules ─────────────────────────────────────────── */
    @media (min-width: 768px) {
        .md-nav-bar { display: none !important; }
        .md-nav-tabs { display: flex !important; }
        body { padding-bottom: 0 !important; }
    }
    @media (max-width: 767px) {
        .md-nav-tabs { display: none !important; }
        .md-nav-bar { display: flex !important; }
        body { padding-bottom: 72px; }
    }
</style>

<header class="md-top-bar" role="banner">
    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between gap-3">

        <!-- Logo -->
        <a href="/" class="flex items-center gap-2.5 transition-opacity hover:opacity-80 flex-shrink-0"
           aria-label="Speed Cloud — Accueil">
            <img src="/assets/images/cloudy.png" alt="" class="w-8 h-8 rounded-xl" aria-hidden="true">
            <span class="font-bold text-sm tracking-tight hidden sm:block" style="color:var(--on-surface);">Speed Cloud</span>
        </a>

        <!-- Centre — desktop tabs -->
        <nav class="md-nav-tabs flex-1 flex justify-center gap-1" aria-label="Navigation principale">
            <a href="/" class="md-tab <?= ($cp === 'portail' || $cp === '') ? 't-active' : '' ?>">Portail</a>
            <a href="/news.php" class="md-tab <?= $cp === 'news' ? 't-active' : '' ?>">Actualités</a>
            <a href="/status.php" class="md-tab <?= $cp === 'status' ? 't-status' : '' ?>">Statuts</a>
            <?php if ($isAdmin): ?>
            <a href="/admin.php" class="md-tab <?= $cp === 'admin' ? 't-admin' : '' ?>">Admin</a>
            <?php endif; ?>
        </nav>

        <!-- Droite — avatar + déconnexion -->
        <div class="flex items-center gap-2 flex-shrink-0">
            <?php if (!empty($user['picture'])): ?>
            <img src="<?= htmlspecialchars($user['picture']) ?>" alt="Avatar"
                 class="w-8 h-8 rounded-full flex-shrink-0"
                 style="border:2px solid var(--outline-var);">
            <?php endif; ?>
            <span class="hidden lg:block text-xs truncate max-w-[120px] font-medium"
                  style="color:var(--on-surface-var);">
                <?= htmlspecialchars($user['name'] ?? '') ?>
            </span>
            <a href="/logout.php"
               class="text-xs px-3 py-1.5 rounded-full transition-all"
               style="color:var(--on-surface-var);border:1px solid var(--outline-var);"
               onmouseover="this.style.background='rgba(208,188,255,.08)'"
               onmouseout="this.style.background=''">
                Déco
            </a>
        </div>
    </div>
</header>

<!-- MD3 Bottom Navigation Bar (mobile only) -->
<nav class="md-nav-bar" aria-label="Navigation mobile">
    <a href="/" class="nav-dest <?= ($cp === 'portail' || $cp === '') ? 'active' : '' ?>" aria-label="Portail">
        <div class="nav-pill" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
        </div>
        <span class="nav-label">Portail</span>
    </a>
    <a href="/news.php" class="nav-dest <?= $cp === 'news' ? 'active' : '' ?>" aria-label="Actualités">
        <div class="nav-pill" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20 3H4v10c0 2.21 1.79 4 4 4h6c2.21 0 4-1.79 4-4v-3h2c1.11 0 2-.89 2-2V5c0-1.11-.89-2-2-2zm0 5h-2V5h2v3zM4 19h16v2H4z"/></svg>
        </div>
        <span class="nav-label">Actualités</span>
    </a>
    <a href="/status.php" class="nav-dest <?= $cp === 'status' ? 'active' : '' ?>" aria-label="Statuts">
        <div class="nav-pill" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M1 9l2 2c4.97-4.97 13.03-4.97 18 0l2-2C16.93 2.93 7.08 2.93 1 9zm8 8l3 3 3-3a4.237 4.237 0 0 0-6 0zm-4-4 2 2a7.074 7.074 0 0 1 10 0l2-2C15.14 9.14 8.87 9.14 5 13z"/></svg>
        </div>
        <span class="nav-label">Statuts</span>
    </a>
    <?php if ($isAdmin): ?>
    <a href="/admin.php" class="nav-dest <?= $cp === 'admin' ? 'active' : '' ?>" aria-label="Admin">
        <div class="nav-pill" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 11v8.93c-3.61-1.23-6.5-4.67-6.97-8.93H12zm0 0V3.18l6 2.67V12h-6z"/></svg>
        </div>
        <span class="nav-label">Admin</span>
    </a>
    <?php endif; ?>
</nav>
