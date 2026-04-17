<?php
/**
 * Navigation partagée
 * Requiert $user, $currentPage dans le scope.
 * $isAdmin est optionnel.
 */
$isAdmin = $isAdmin ?? false;
$cp = $currentPage ?? '';
?>
<nav style="background:rgba(7,8,14,.88);border-bottom:1px solid rgba(124,58,237,.15);"
     class="sticky top-0 z-50 backdrop-blur-xl">
    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between gap-3">

        <!-- Logo -->
        <a href="/" class="flex items-center gap-2 hover:opacity-80 transition flex-shrink-0">
            <img src="/assets/images/cloudy.png" alt="" class="w-7 h-7 rounded-lg">
            <span class="text-white font-bold text-sm tracking-tight hidden sm:block">Speed Cloud</span>
        </a>

        <!-- Centre — pill nav -->
        <div class="flex-1 flex justify-center">
            <div class="flex items-center gap-0.5 rounded-full border px-1 py-1"
                 style="background:rgba(255,255,255,.04);border-color:rgba(255,255,255,.08);">
                <a href="/"
                   class="px-3 py-1 rounded-full text-xs font-semibold transition
                          <?= ($cp === 'portail' || $cp === '')
                                ? 'text-violet-300 border border-violet-500/35'
                                : 'text-white/50 hover:text-white hover:bg-white/[.07]' ?>"
                   style="<?= ($cp === 'portail' || $cp === '') ? 'background:rgba(124,58,237,.2)' : '' ?>">
                    Portail
                </a>
                <a href="/news.php"
                   class="px-3 py-1 rounded-full text-xs font-semibold transition
                          <?= $cp === 'news'
                                ? 'text-violet-300 border border-violet-500/35'
                                : 'text-white/50 hover:text-white hover:bg-white/[.07]' ?>"
                   style="<?= $cp === 'news' ? 'background:rgba(124,58,237,.2)' : '' ?>">
                    Actualités
                </a>
                <a href="/status.php"
                   class="px-3 py-1 rounded-full text-xs font-semibold transition
                          <?= $cp === 'status'
                                ? 'text-emerald-300 border border-emerald-500/35'
                                : 'text-white/50 hover:text-white hover:bg-white/[.07]' ?>"
                   style="<?= $cp === 'status' ? 'background:rgba(5,150,105,.15)' : '' ?>">
                    Statuts
                </a>
                <?php if ($isAdmin): ?>
                <a href="/admin.php"
                   class="px-3 py-1 rounded-full text-xs font-semibold transition
                          <?= $cp === 'admin'
                                ? 'text-amber-300 border border-amber-500/35'
                                : 'text-amber-400/60 hover:text-amber-300 hover:bg-amber-500/[.08]' ?>"
                   style="<?= $cp === 'admin' ? 'background:rgba(217,119,6,.15)' : '' ?>">
                    Admin
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Droite — avatar + déconnexion -->
        <div class="flex items-center gap-2 flex-shrink-0">
            <?php if (!empty($user['picture'])): ?>
            <img src="<?= htmlspecialchars($user['picture']) ?>" alt="Avatar"
                 class="w-7 h-7 rounded-full border border-white/15 flex-shrink-0">
            <?php endif; ?>
            <span class="hidden lg:block text-white/45 text-xs truncate max-w-[120px]">
                <?= htmlspecialchars($user['name'] ?? '') ?>
            </span>
            <a href="/logout.php"
               class="text-xs text-white/40 hover:text-white/75 px-2 py-1 rounded-lg transition border border-transparent hover:border-white/10 hover:bg-white/[.05]">
                Déco
            </a>
        </div>

    </div>
</nav>
