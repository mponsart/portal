<?php
$user = $_SESSION['user'];
$config = require __DIR__ . '/../config.php';
$currentPage = 'avatar';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer mon Avatar - Groupe Speed Cloud</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'speed-purple': '#8a4dfd',
                        'speed-purple-dark': '#7040d9',
                    }
                }
            }
        }
    </script>
    <link rel="icon" type="image/png" href="https://sign.groupe-speed.cloud/assets/images/cloudy.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Titillium+Web:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-gray-900" style="font-family: 'Titillium Web', sans-serif;">
    
    <!-- Navigation Bar -->
    <nav class="bg-black/30 backdrop-blur-md border-b border-white/10 sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <a href="/" class="flex items-center gap-3 hover:opacity-80 transition">
                    <img src="/assets/images/cloudy.png" alt="" class="w-10 h-10 rounded-lg">
                    <span class="text-white font-bold text-lg hidden sm:block">Groupe Speed Cloud</span>
                </a>
                
                <!-- Nav Links -->
                <div class="flex items-center gap-1 sm:gap-2">
                    <a href="/" class="px-3 sm:px-4 py-2 rounded-lg text-sm font-medium transition <?= $currentPage === 'signatures' ? 'bg-speed-purple text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white' ?>">
                        <span class="hidden sm:inline">✍️ Signatures</span>
                        <span class="sm:hidden">✍️</span>
                    </a>
                    <a href="/chibi.php" class="px-3 sm:px-4 py-2 rounded-lg text-sm font-medium transition <?= $currentPage === 'avatar' ? 'bg-speed-purple text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white' ?>">
                        <span class="hidden sm:inline">✨ Avatar</span>
                        <span class="sm:hidden">✨</span>
                    </a>
                </div>
                
                <!-- User Menu -->
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <!-- Page Header -->
        <div class="max-w-5xl mx-auto mb-8">
            <h1 class="text-2xl sm:text-3xl font-bold text-white mb-2">✨ Créer mon Avatar</h1>
            <p class="text-gray-400">Personnalisez votre avatar unique pour l'association</p>
        </div>

        <!-- Main Content -->
        <div class="max-w-5xl mx-auto">
            <div class="grid lg:grid-cols-2 gap-8">
                
                <!-- Customization Panel -->
                <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 shadow-2xl border border-white/20">
                    <h2 class="text-xl font-bold text-white mb-6">✨ Personnalise ton Avatar</h2>
                    
                    <!-- Mode Selector -->
                    <div class="flex gap-2 mb-6">
                        <button type="button" id="modeChihi" class="flex-1 py-2 px-4 rounded-lg bg-speed-purple text-white font-medium transition">
                            🎨 Chibi
                        </button>
                        <button type="button" id="modeBadge" class="flex-1 py-2 px-4 rounded-lg bg-white/10 text-gray-300 font-medium hover:bg-white/20 transition">
                            🏷️ Badge Speed
                        </button>
                    </div>
                    
                    <form id="chibiForm" class="space-y-5">
                        <!-- Seed / Name -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-200 mb-2">Nom (génère un avatar unique)</label>
                            <input type="text" id="seed" value="<?= htmlspecialchars($user['name']) ?>" 
                                class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-speed-purple transition">
                        </div>

                        <!-- Chibi Options -->
                        <div id="chibiOptions">
                            <!-- Style -->
                            <div class="mb-5">
                                <label class="block text-sm font-semibold text-gray-200 mb-2">Style d'avatar</label>
                                <select id="style" class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-speed-purple transition cursor-pointer">
                                    <option value="lorelei" class="bg-gray-800">Lorelei (Chibi mignon)</option>
                                    <option value="adventurer" class="bg-gray-800">Adventurer (Cartoon)</option>
                                    <option value="avataaars" class="bg-gray-800">Avataaars (Style Memoji)</option>
                                    <option value="big-smile" class="bg-gray-800">Big Smile (Souriant)</option>
                                    <option value="notionists" class="bg-gray-800">Notionists (Minimaliste)</option>
                                    <option value="fun-emoji" class="bg-gray-800">Fun Emoji</option>
                                    <option value="thumbs" class="bg-gray-800">Thumbs (Pouces)</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Badge Options (hidden by default) -->
                        <div id="badgeOptions" class="hidden space-y-5">
                            <!-- Badge Style -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-200 mb-2">Style du badge</label>
                                <div class="grid grid-cols-3 gap-2">
                                    <label class="cursor-pointer">
                                        <input type="radio" name="badgeStyle" value="modern" class="peer hidden" checked>
                                        <div class="p-3 bg-white/5 border-2 border-white/10 rounded-lg text-center peer-checked:border-speed-purple peer-checked:bg-speed-purple/20 transition">
                                            <span class="text-white text-sm">Moderne</span>
                                        </div>
                                    </label>
                                    <label class="cursor-pointer">
                                        <input type="radio" name="badgeStyle" value="gradient" class="peer hidden">
                                        <div class="p-3 bg-white/5 border-2 border-white/10 rounded-lg text-center peer-checked:border-speed-purple peer-checked:bg-speed-purple/20 transition">
                                            <span class="text-white text-sm">Dégradé</span>
                                        </div>
                                    </label>
                                    <label class="cursor-pointer">
                                        <input type="radio" name="badgeStyle" value="neon" class="peer hidden">
                                        <div class="p-3 bg-white/5 border-2 border-white/10 rounded-lg text-center peer-checked:border-speed-purple peer-checked:bg-speed-purple/20 transition">
                                            <span class="text-white text-sm">Néon</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Job Title for Badge -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-200 mb-2">Poste affiché</label>
                                <select id="badgeJob" class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-speed-purple transition cursor-pointer">
                                    <option value="" class="bg-gray-800">Sans poste</option>
                                    <?php 
                                    $jobs = $config['jobs'] ?? [];
                                    foreach ($jobs as $key => $label): 
                                        if ($key && $key !== '__autre__'):
                                    ?>
                                    <option value="<?= htmlspecialchars($label) ?>" class="bg-gray-800"><?= htmlspecialchars($label) ?></option>
                                    <?php endif; endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Show Cloudy -->
                            <div class="flex items-center gap-3">
                                <input type="checkbox" id="showCloudy" checked class="w-5 h-5 rounded border-white/20 bg-white/10 text-speed-purple focus:ring-speed-purple cursor-pointer">
                                <label for="showCloudy" class="text-sm text-gray-200 cursor-pointer">Afficher le logo Cloudy</label>
                            </div>
                        </div>

                        <!-- Background Color -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-200 mb-2">Couleur de fond</label>
                            <div class="grid grid-cols-6 gap-2">
                                <label class="cursor-pointer">
                                    <input type="radio" name="bgColor" value="transparent" class="peer hidden" checked>
                                    <div class="w-full aspect-square rounded-lg border-2 border-white/20 peer-checked:border-speed-purple transition bg-[url('data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%228%22%20height%3D%228%22%3E%3Crect%20width%3D%224%22%20height%3D%224%22%20fill%3D%22%23ccc%22%2F%3E%3Crect%20x%3D%224%22%20y%3D%224%22%20width%3D%224%22%20height%3D%224%22%20fill%3D%22%23ccc%22%2F%3E%3C%2Fsvg%3E')]"></div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="bgColor" value="8a4dfd" class="peer hidden">
                                    <div class="w-full aspect-square rounded-lg border-2 border-white/20 peer-checked:border-white transition" style="background: #8a4dfd;"></div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="bgColor" value="6366f1" class="peer hidden">
                                    <div class="w-full aspect-square rounded-lg border-2 border-white/20 peer-checked:border-white transition" style="background: #6366f1;"></div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="bgColor" value="ec4899" class="peer hidden">
                                    <div class="w-full aspect-square rounded-lg border-2 border-white/20 peer-checked:border-white transition" style="background: #ec4899;"></div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="bgColor" value="10b981" class="peer hidden">
                                    <div class="w-full aspect-square rounded-lg border-2 border-white/20 peer-checked:border-white transition" style="background: #10b981;"></div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="bgColor" value="f59e0b" class="peer hidden">
                                    <div class="w-full aspect-square rounded-lg border-2 border-white/20 peer-checked:border-white transition" style="background: #f59e0b;"></div>
                                </label>
                            </div>
                        </div>

                        <!-- Size -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-200 mb-2">Taille : <span id="sizeValue">200</span>px</label>
                            <input type="range" id="size" min="64" max="512" value="200" 
                                class="w-full h-2 bg-white/20 rounded-lg appearance-none cursor-pointer accent-speed-purple">
                        </div>

                        <!-- Flip -->
                        <div class="flex items-center gap-3">
                            <input type="checkbox" id="flip" class="w-5 h-5 rounded border-white/20 bg-white/10 text-speed-purple focus:ring-speed-purple cursor-pointer">
                            <label for="flip" class="text-sm text-gray-200 cursor-pointer">Retourner horizontalement</label>
                        </div>

                        <!-- Rotate -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-200 mb-2">Rotation : <span id="rotateValue">0</span>°</label>
                            <input type="range" id="rotate" min="0" max="360" value="0" step="15"
                                class="w-full h-2 bg-white/20 rounded-lg appearance-none cursor-pointer accent-speed-purple">
                        </div>

                        <!-- Random Button -->
                        <button type="button" id="randomBtn" class="w-full py-3 bg-white/10 border border-white/20 rounded-lg text-white font-medium hover:bg-white/20 transition flex items-center justify-center gap-2">
                            🎲 Générer aléatoirement
                        </button>
                    </form>
                </div>

                <!-- Preview Panel -->
                <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 shadow-2xl border border-white/20">
                    <h2 class="text-xl font-bold text-white mb-6">👀 Aperçu</h2>
                    
                    <!-- Avatar Preview -->
                    <div class="flex justify-center mb-6">
                        <div id="chibiPreview" class="bg-white/5 rounded-2xl p-4 border border-white/10">
                            <img id="chibiImage" src="" alt="Ton Avatar" class="mx-auto rounded-xl">
                            <canvas id="badgeCanvas" class="hidden mx-auto rounded-xl"></canvas>
                        </div>
                    </div>

                    <!-- Download Buttons -->
                    <div class="space-y-3">
                        <button id="downloadPng" class="w-full py-3 bg-speed-purple text-white rounded-lg font-semibold hover:bg-speed-purple-dark transition flex items-center justify-center gap-2">
                            📥 Télécharger PNG
                        </button>
                        <button id="downloadSvg" class="w-full py-3 bg-white/10 border border-white/20 rounded-lg text-white font-medium hover:bg-white/20 transition flex items-center justify-center gap-2">
                            📄 Télécharger SVG
                        </button>
                        <button id="copyUrl" class="w-full py-3 bg-white/10 border border-white/20 rounded-lg text-white font-medium hover:bg-white/20 transition flex items-center justify-center gap-2">
                            🔗 Copier l'URL
                        </button>
                    </div>

                    <!-- Tips -->
                    <div class="mt-6 p-4 bg-speed-purple/20 rounded-lg border border-speed-purple/30">
                        <h3 class="text-white font-semibold mb-2">💡 Astuces</h3>
                        <ul class="text-gray-300 text-sm space-y-1 list-disc list-inside">
                            <li><b>Chibi</b> : 7 styles différents via DiceBear</li>
                            <li><b>Badge Speed</b> : Avatar unique avec tes initiales + poste</li>
                            <li>Le badge inclut le logo Cloudy !</li>
                            <li>Utilise l'avatar dans ta signature email</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8 text-gray-400 text-sm">
            © <?= date('Y') ?> Association Groupe Speed Cloud - Tous droits réservés
        </div>
    </div>

    <script>
        // Elements
        const chibiImage = document.getElementById('chibiImage');
        const badgeCanvas = document.getElementById('badgeCanvas');
        const seedInput = document.getElementById('seed');
        const styleSelect = document.getElementById('style');
        const sizeInput = document.getElementById('size');
        const sizeValue = document.getElementById('sizeValue');
        const rotateInput = document.getElementById('rotate');
        const rotateValue = document.getElementById('rotateValue');
        const flipCheckbox = document.getElementById('flip');
        const modeChihi = document.getElementById('modeChihi');
        const modeBadge = document.getElementById('modeBadge');
        const chibiOptions = document.getElementById('chibiOptions');
        const badgeOptions = document.getElementById('badgeOptions');
        
        let currentMode = 'chibi';
        const cloudyLogo = new Image();
        cloudyLogo.src = 'https://sign.groupe-speed.cloud/assets/images/cloudy.png';
        
        // Mode switching
        modeChihi.addEventListener('click', () => {
            currentMode = 'chibi';
            modeChihi.classList.add('bg-speed-purple', 'text-white');
            modeChihi.classList.remove('bg-white/10', 'text-gray-300');
            modeBadge.classList.remove('bg-speed-purple', 'text-white');
            modeBadge.classList.add('bg-white/10', 'text-gray-300');
            chibiOptions.classList.remove('hidden');
            badgeOptions.classList.add('hidden');
            chibiImage.classList.remove('hidden');
            badgeCanvas.classList.add('hidden');
            updatePreview();
        });
        
        modeBadge.addEventListener('click', () => {
            currentMode = 'badge';
            modeBadge.classList.add('bg-speed-purple', 'text-white');
            modeBadge.classList.remove('bg-white/10', 'text-gray-300');
            modeChihi.classList.remove('bg-speed-purple', 'text-white');
            modeChihi.classList.add('bg-white/10', 'text-gray-300');
            badgeOptions.classList.remove('hidden');
            chibiOptions.classList.add('hidden');
            chibiImage.classList.add('hidden');
            badgeCanvas.classList.remove('hidden');
            updatePreview();
        });
        
        // Generate Chibi URL
        function generateAvatarUrl(format = 'svg') {
            const seed = encodeURIComponent(seedInput.value || 'default');
            const style = styleSelect.value;
            const size = sizeInput.value;
            const bgColor = document.querySelector('input[name="bgColor"]:checked').value;
            const flip = flipCheckbox.checked;
            const rotate = rotateInput.value;
            
            let url = `https://api.dicebear.com/7.x/${style}/${format}?seed=${seed}&size=${size}`;
            
            if (bgColor !== 'transparent') {
                url += `&backgroundColor=${bgColor}`;
            }
            if (flip) {
                url += '&flip=true';
            }
            if (rotate !== '0') {
                url += `&rotate=${rotate}`;
            }
            
            return url;
        }
        
        // Get initials from name
        function getInitials(name) {
            return name.split(' ').map(word => word[0]).join('').toUpperCase().substring(0, 2);
        }
        
        // Generate unique color from name
        function stringToColor(str) {
            let hash = 0;
            for (let i = 0; i < str.length; i++) {
                hash = str.charCodeAt(i) + ((hash << 5) - hash);
            }
            const colors = ['#8a4dfd', '#6366f1', '#ec4899', '#10b981', '#f59e0b', '#ef4444', '#06b6d4'];
            return colors[Math.abs(hash) % colors.length];
        }
        
        // Draw Badge
        function drawBadge() {
            const size = parseInt(sizeInput.value);
            const name = seedInput.value || 'Membre';
            const initials = getInitials(name);
            const badgeStyle = document.querySelector('input[name="badgeStyle"]:checked')?.value || 'modern';
            const job = document.getElementById('badgeJob')?.value || '';
            const showCloudy = document.getElementById('showCloudy')?.checked ?? true;
            const bgColor = document.querySelector('input[name="bgColor"]:checked').value;
            
            badgeCanvas.width = size;
            badgeCanvas.height = size;
            const ctx = badgeCanvas.getContext('2d');
            
            // Background
            if (bgColor === 'transparent') {
                ctx.clearRect(0, 0, size, size);
            } else {
                ctx.fillStyle = '#' + bgColor;
                ctx.fillRect(0, 0, size, size);
            }
            
            const primaryColor = stringToColor(name);
            const centerX = size / 2;
            const centerY = size / 2;
            const radius = size * 0.42;
            
            // Style-specific rendering
            if (badgeStyle === 'modern') {
                // Clean circle
                ctx.beginPath();
                ctx.arc(centerX, centerY, radius, 0, Math.PI * 2);
                ctx.fillStyle = primaryColor;
                ctx.fill();
                
                // Inner shadow
                const gradient = ctx.createRadialGradient(centerX - radius*0.3, centerY - radius*0.3, 0, centerX, centerY, radius);
                gradient.addColorStop(0, 'rgba(255,255,255,0.2)');
                gradient.addColorStop(1, 'rgba(0,0,0,0.1)');
                ctx.fillStyle = gradient;
                ctx.fill();
                
            } else if (badgeStyle === 'gradient') {
                // Gradient circle
                const gradient = ctx.createLinearGradient(0, 0, size, size);
                gradient.addColorStop(0, primaryColor);
                gradient.addColorStop(1, '#8a4dfd');
                ctx.beginPath();
                ctx.arc(centerX, centerY, radius, 0, Math.PI * 2);
                ctx.fillStyle = gradient;
                ctx.fill();
                
            } else if (badgeStyle === 'neon') {
                // Neon glow effect
                ctx.shadowColor = primaryColor;
                ctx.shadowBlur = size * 0.08;
                ctx.beginPath();
                ctx.arc(centerX, centerY, radius * 0.95, 0, Math.PI * 2);
                ctx.strokeStyle = primaryColor;
                ctx.lineWidth = size * 0.03;
                ctx.stroke();
                ctx.shadowBlur = 0;
                
                // Inner fill
                ctx.beginPath();
                ctx.arc(centerX, centerY, radius * 0.9, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(138, 77, 253, 0.2)';
                ctx.fill();
            }
            
            // Initials
            ctx.fillStyle = badgeStyle === 'neon' ? primaryColor : '#ffffff';
            ctx.font = `bold ${size * 0.28}px 'Titillium Web', Arial, sans-serif`;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(initials, centerX, job ? centerY - size * 0.06 : centerY);
            
            // Job title
            if (job) {
                ctx.fillStyle = badgeStyle === 'neon' ? '#ffffff' : 'rgba(255,255,255,0.9)';
                ctx.font = `600 ${size * 0.08}px 'Titillium Web', Arial, sans-serif`;
                const maxWidth = radius * 1.6;
                let fontSize = size * 0.08;
                ctx.font = `600 ${fontSize}px 'Titillium Web', Arial, sans-serif`;
                while (ctx.measureText(job).width > maxWidth && fontSize > size * 0.04) {
                    fontSize -= 1;
                    ctx.font = `600 ${fontSize}px 'Titillium Web', Arial, sans-serif`;
                }
                ctx.fillText(job, centerX, centerY + size * 0.14);
            }
            
            // Cloudy logo
            if (showCloudy && cloudyLogo.complete) {
                const logoSize = size * 0.18;
                ctx.save();
                ctx.beginPath();
                ctx.arc(size - logoSize/2 - size*0.05, size - logoSize/2 - size*0.05, logoSize/2 + 2, 0, Math.PI * 2);
                ctx.fillStyle = '#ffffff';
                ctx.fill();
                ctx.clip();
                ctx.drawImage(cloudyLogo, size - logoSize - size*0.05, size - logoSize - size*0.05, logoSize, logoSize);
                ctx.restore();
            }
        }
        
        function updatePreview() {
            if (currentMode === 'chibi') {
                const url = generateAvatarUrl('svg');
                chibiImage.src = url;
                chibiImage.style.width = sizeInput.value + 'px';
                chibiImage.style.height = sizeInput.value + 'px';
            } else {
                drawBadge();
            }
        }
        
        // Event listeners
        seedInput.addEventListener('input', updatePreview);
        styleSelect.addEventListener('change', updatePreview);
        sizeInput.addEventListener('input', () => {
            sizeValue.textContent = sizeInput.value;
            updatePreview();
        });
        rotateInput.addEventListener('input', () => {
            rotateValue.textContent = rotateInput.value;
            updatePreview();
        });
        flipCheckbox.addEventListener('change', updatePreview);
        document.querySelectorAll('input[name="bgColor"]').forEach(input => {
            input.addEventListener('change', updatePreview);
        });
        document.querySelectorAll('input[name="badgeStyle"]').forEach(input => {
            input.addEventListener('change', updatePreview);
        });
        document.getElementById('badgeJob')?.addEventListener('change', updatePreview);
        document.getElementById('showCloudy')?.addEventListener('change', updatePreview);
        
        // Random button
        document.getElementById('randomBtn').addEventListener('click', () => {
            const randomSeed = Math.random().toString(36).substring(2, 10);
            seedInput.value = randomSeed;
            
            if (currentMode === 'chibi') {
                const styles = ['lorelei', 'adventurer', 'avataaars', 'big-smile', 'notionists', 'fun-emoji', 'thumbs'];
                styleSelect.value = styles[Math.floor(Math.random() * styles.length)];
                rotateInput.value = 0;
                rotateValue.textContent = '0';
                flipCheckbox.checked = Math.random() > 0.5;
            } else {
                const badgeStyles = document.querySelectorAll('input[name="badgeStyle"]');
                badgeStyles[Math.floor(Math.random() * badgeStyles.length)].checked = true;
            }
            
            const colors = document.querySelectorAll('input[name="bgColor"]');
            colors[Math.floor(Math.random() * colors.length)].checked = true;
            
            updatePreview();
        });
        
        // Download PNG
        document.getElementById('downloadPng').addEventListener('click', async () => {
            if (currentMode === 'chibi') {
                const url = generateAvatarUrl('png');
                const response = await fetch(url);
                const blob = await response.blob();
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = `chibi-${seedInput.value || 'avatar'}.png`;
                link.click();
            } else {
                const link = document.createElement('a');
                link.href = badgeCanvas.toDataURL('image/png');
                link.download = `badge-speed-${seedInput.value || 'membre'}.png`;
                link.click();
            }
        });
        
        // Download SVG
        document.getElementById('downloadSvg').addEventListener('click', async () => {
            if (currentMode === 'chibi') {
                const url = generateAvatarUrl('svg');
                const response = await fetch(url);
                const blob = await response.blob();
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = `chibi-${seedInput.value || 'avatar'}.svg`;
                link.click();
            } else {
                // For badge, convert canvas to PNG (SVG not available for canvas)
                const link = document.createElement('a');
                link.href = badgeCanvas.toDataURL('image/png');
                link.download = `badge-speed-${seedInput.value || 'membre'}.png`;
                link.click();
            }
        });
        
        // Copy URL
        document.getElementById('copyUrl').addEventListener('click', async () => {
            let url;
            if (currentMode === 'chibi') {
                url = generateAvatarUrl('svg');
            } else {
                url = badgeCanvas.toDataURL('image/png');
            }
            await navigator.clipboard.writeText(url);
            const btn = document.getElementById('copyUrl');
            btn.innerHTML = '✓ Copié !';
            setTimeout(() => {
                btn.innerHTML = '🔗 Copier l\'URL';
            }, 2000);
        });
        
        // Init when logo loads
        cloudyLogo.onload = updatePreview;
        updatePreview();
    </script>
</body>
</html>
