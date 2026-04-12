<?php
/**
 * Navigation partagée
 * Requiert $user, $currentPage dans le scope.
 * $isAdmin est optionnel.
 */
$isAdmin = $isAdmin ?? false;
?>
<nav class="bg-black/30 backdrop-blur-md border-b border-white/10 sticky top-0 z-50">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between h-16">

            <!-- Logo -->
            <a href="/" class="flex items-center gap-3 hover:opacity-80 transition">
                <img src="/assets/images/cloudy.png" alt="" class="w-10 h-10 rounded-lg">
                <span class="text-white font-bold text-lg hidden sm:block">Groupe Speed Cloud</span>
            </a>

            <!-- Liens -->
            <div class="flex items-center gap-1 sm:gap-2">
                <a href="/" class="px-3 sm:px-4 py-2 rounded-lg text-sm font-medium transition
                    <?= $currentPage === 'portail' ? 'bg-brand-indigo text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white' ?>">
                    <span class="hidden sm:inline">🏠 Portail</span>
                    <span class="sm:hidden">🏠</span>
                </a>
                <a href="/announcements.php" class="px-3 sm:px-4 py-2 rounded-lg text-sm font-medium transition
                    <?= $currentPage === 'annonces' ? 'bg-brand-indigo text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white' ?>">
                    <span class="hidden sm:inline">📢 Annonces</span>
                    <span class="sm:hidden">📢</span>
                </a>
                <a href="/chibi.php" class="px-3 sm:px-4 py-2 rounded-lg text-sm font-medium transition
                    <?= $currentPage === 'avatar' ? 'bg-brand-indigo text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white' ?>">
                    <span class="hidden sm:inline">🏷️ Badge</span>
                    <span class="sm:hidden">🏷️</span>
                </a>
                <?php if ($isAdmin): ?>
                <a href="/admin.php" class="px-3 sm:px-4 py-2 rounded-lg text-sm font-medium transition
                    <?= $currentPage === 'admin' ? 'bg-amber-600 text-white' : 'text-amber-400 hover:bg-amber-500/20 hover:text-amber-300' ?>">
                    <span class="hidden sm:inline">⚙️ Admin</span>
                    <span class="sm:hidden">⚙️</span>
                </a>
                <?php endif; ?>
            </div>

            <!-- Profil + déconnexion -->
            <div class="flex items-center gap-3">
                <?php if (!empty($user['picture'])): ?>
                <img src="<?= htmlspecialchars($user['picture']) ?>" alt="" class="w-8 h-8 rounded-full border-2 border-white/20">
                <?php endif; ?>
                <div class="hidden md:block text-right">
                    <p class="text-white text-sm font-medium leading-tight"><?= htmlspecialchars($user['name']) ?></p>
                    <p class="text-gray-400 text-xs"><?= htmlspecialchars($user['email']) ?></p>
                </div>
                <a href="/logout.php" class="p-2 text-gray-400 hover:text-white hover:bg-white/10 rounded-lg transition" title="Déconnexion">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </a>
            </div>

        </div>
    </div>
</nav>
