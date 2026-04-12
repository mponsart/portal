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

        <!-- Liens -->
            <div class="flex items-center gap-2">
                <a href="/news.php"
                   class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition
                          <?= $currentPage === 'news'
                                ? 'bg-white/15 text-white border border-white/20'
                                : 'text-white/55 hover:bg-white/10 hover:text-white' ?>">
                    <span>📰</span>
                    <span class="hidden sm:inline">Actualités</span>
                </a>
                <a href="/status.php"
                   class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition
                          <?= $currentPage === 'status'
                                ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40'
                                : 'text-emerald-300/80 hover:bg-emerald-500/15 hover:text-emerald-200' ?>">
                    <span>🟢</span>
                    <span class="hidden sm:inline">Statuts</span>
                </a>
            <?php if ($isAdmin): ?>
            <a href="/admin.php"
               class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition
                      <?= $currentPage === 'admin'
                            ? 'bg-amber-500/25 text-amber-300 border border-amber-500/40'
                            : 'text-amber-400/80 hover:bg-amber-500/15 hover:text-amber-300' ?>">
                <span>⚙️</span>
                <span class="hidden sm:inline">Admin</span>
            </a>
            <?php endif; ?>

            <!-- Avatar -->
            <div class="flex items-center gap-2 pl-2" style="border-left:1px solid rgba(255,255,255,.1);">
                <?php if (!empty($user['picture'])): ?>
                <img src="<?= htmlspecialchars($user['picture']) ?>" alt=""
                     class="w-7 h-7 rounded-full border border-white/15 flex-shrink-0">
                <?php endif; ?>
                <span class="hidden md:block text-white/60 text-xs"><?= htmlspecialchars($user['name']) ?></span>
            </div>
        </div>

    </div>
</nav>
