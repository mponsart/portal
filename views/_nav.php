<?php
/**
 * Navigation partagée
 * Requiert $user, $currentPage dans le scope.
 * $isAdmin est optionnel.
 */
$isAdmin = $isAdmin ?? false;
?>
<nav style="background:rgba(6,8,15,.75);border-bottom:1px solid rgba(255,255,255,.08);"
     class="sticky top-0 z-50 backdrop-blur-xl">
    <div class="w-full max-w-6xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between gap-4">

        <!-- Logo -->
        <a href="/" class="flex items-center gap-2.5 hover:opacity-80 transition flex-shrink-0">
            <img src="/assets/images/cloudy.png" alt="" class="w-8 h-8 rounded-lg">
            <span class="text-white font-semibold text-sm hidden sm:block">Groupe Speed Cloud</span>
        </a>

        <!-- Droite : admin + profil + déco -->
        <div class="flex items-center gap-2">
            <?php if ($isAdmin): ?>
            <a href="/admin.php"
               class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition
                      <?= $currentPage === 'admin'
                            ? 'bg-amber-500/25 text-amber-300 border border-amber-500/40'
                            : 'text-amber-400/80 hover:bg-amber-500/15 hover:text-amber-300' ?>">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0
                             002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0
                             001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0
                             00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0
                             00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0
                             00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0
                             00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0
                             001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="hidden sm:inline">Admin</span>
            </a>
            <?php endif; ?>

            <!-- Avatar + déconnexion -->
            <div class="flex items-center gap-2 pl-2" style="border-left:1px solid rgba(255,255,255,.1);">
                <?php if (!empty($user['picture'])): ?>
                <img src="<?= htmlspecialchars($user['picture']) ?>" alt=""
                     class="w-7 h-7 rounded-full border border-white/15 flex-shrink-0">
                <?php endif; ?>
                <span class="hidden md:block text-white/60 text-xs"><?= htmlspecialchars($user['name']) ?></span>
                <a href="/logout.php"
                   class="p-1.5 text-white/35 hover:text-white hover:bg-white/10 rounded-lg transition"
                   title="Déconnexion">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </a>
            </div>
        </div>

    </div>
</nav>
