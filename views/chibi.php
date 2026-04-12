<?php
$user        = $_SESSION['user'];
$config      = require __DIR__ . '/../config.php';
$isAdmin     = in_array($user['email'], $config['admins'] ?? []);
$currentPage = 'avatar';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Badge Professionnel - Groupe Speed Cloud</title>
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
    <?php include __DIR__ . '/_nav.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto mb-8">
            <h1 class="text-2xl sm:text-3xl font-bold text-white mb-2">🏷️ Badge Professionnel</h1>
            <p class="text-gray-400">Créez votre badge professionnel pour l'association</p>
        </div>

        <div class="max-w-4xl mx-auto">
            <div class="grid lg:grid-cols-2 gap-8">
                
                <!-- Options -->
                <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 shadow-2xl border border-white/20">
                    <h2 class="text-xl font-bold text-white mb-6">⚙️ Personnalisation</h2>
                    
                    <form id="badgeForm" class="space-y-5">
                        <!-- Name -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-200 mb-2">Nom complet</label>
                            <input type="text" id="name" value="<?= htmlspecialchars($user['name']) ?>" 
                                class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-speed-purple transition">
                        </div>
                        
                        <!-- Job Title -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-200 mb-2">Poste</label>
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

                        <!-- Badge Style -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-200 mb-2">Style</label>
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

                        <!-- Background Color -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-200 mb-2">Fond</label>
                            <div class="grid grid-cols-6 gap-2">
                                <label class="cursor-pointer">
                                    <input type="radio" name="bgColor" value="transparent" class="peer hidden" checked>
                                    <div class="w-full aspect-square rounded-lg border-2 border-white/20 peer-checked:border-speed-purple transition bg-[url('data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%228%22%20height%3D%228%22%3E%3Crect%20width%3D%224%22%20height%3D%224%22%20fill%3D%22%23ccc%22%2F%3E%3Crect%20x%3D%224%22%20y%3D%224%22%20width%3D%224%22%20height%3D%224%22%20fill%3D%22%23ccc%22%2F%3E%3C%2Fsvg%3E')]"></div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="bgColor" value="ffffff" class="peer hidden">
                                    <div class="w-full aspect-square rounded-lg border-2 border-white/20 peer-checked:border-speed-purple transition" style="background: #ffffff;"></div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="bgColor" value="8a4dfd" class="peer hidden">
                                    <div class="w-full aspect-square rounded-lg border-2 border-white/20 peer-checked:border-white transition" style="background: #8a4dfd;"></div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="bgColor" value="1a1a2e" class="peer hidden">
                                    <div class="w-full aspect-square rounded-lg border-2 border-white/20 peer-checked:border-white transition" style="background: #1a1a2e;"></div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="bgColor" value="0f172a" class="peer hidden">
                                    <div class="w-full aspect-square rounded-lg border-2 border-white/20 peer-checked:border-white transition" style="background: #0f172a;"></div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="bgColor" value="f8fafc" class="peer hidden">
                                    <div class="w-full aspect-square rounded-lg border-2 border-white/20 peer-checked:border-speed-purple transition" style="background: #f8fafc;"></div>
                                </label>
                            </div>
                        </div>

                        <!-- Size -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-200 mb-2">Taille : <span id="sizeValue">256</span>px</label>
                            <input type="range" id="size" min="128" max="512" value="256" 
                                class="w-full h-2 bg-white/20 rounded-lg appearance-none cursor-pointer accent-speed-purple">
                        </div>

                        <!-- Show Cloudy -->
                        <div class="flex items-center gap-3">
                            <input type="checkbox" id="showCloudy" checked class="w-5 h-5 rounded border-white/20 bg-white/10 text-speed-purple focus:ring-speed-purple cursor-pointer">
                            <label for="showCloudy" class="text-sm text-gray-200 cursor-pointer">Afficher l'anneau Groupe Speed</label>
                        </div>
                    </form>
                </div>

                <!-- Preview -->
                <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 shadow-2xl border border-white/20">
                    <h2 class="text-xl font-bold text-white mb-6">👀 Aperçu</h2>
                    
                    <div class="flex justify-center mb-6">
                        <div class="bg-white/5 rounded-2xl p-4 border border-white/10">
                            <canvas id="badgeCanvas" class="mx-auto rounded-xl"></canvas>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <button id="downloadPng" class="w-full py-3 bg-speed-purple text-white rounded-lg font-semibold hover:bg-speed-purple-dark transition flex items-center justify-center gap-2">
                            📥 Télécharger PNG
                        </button>
                    </div>

                    <div class="mt-6 p-4 bg-speed-purple/20 rounded-lg border border-speed-purple/30">
                        <h3 class="text-white font-semibold mb-2">💡 Utilisation</h3>
                        <ul class="text-gray-300 text-sm space-y-1 list-disc list-inside">
                            <li>Photo de profil Gmail / Slack</li>
                            <li>Avatar pour les outils internes</li>
                            <li>Badge d'identification</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-8 text-gray-400 text-sm">
            © <?= date('Y') ?> Association Groupe Speed Cloud - Tous droits réservés
        </div>
    </div>

    <script>
        const badgeCanvas = document.getElementById('badgeCanvas');
        const nameInput = document.getElementById('name');
        const sizeInput = document.getElementById('size');
        const sizeValue = document.getElementById('sizeValue');
        
        const cloudyLogo = new Image();
        cloudyLogo.crossOrigin = 'anonymous';
        cloudyLogo.src = 'https://sign.groupe-speed.cloud/assets/images/cloudy.png';
        cloudyLogo.onload = () => drawBadge();
        
        function getInitials(name) {
            return name.split(' ').map(word => word[0]).join('').toUpperCase().substring(0, 2);
        }
        
        function stringToColor(str) {
            let hash = 0;
            for (let i = 0; i < str.length; i++) {
                hash = str.charCodeAt(i) + ((hash << 5) - hash);
            }
            const colors = ['#8a4dfd', '#6366f1', '#ec4899', '#10b981', '#f59e0b', '#ef4444', '#06b6d4'];
            return colors[Math.abs(hash) % colors.length];
        }
        
        function lightenColor(hex, percent) {
            const num = parseInt(hex.replace('#', ''), 16);
            const amt = Math.round(2.55 * percent);
            const R = Math.min(255, (num >> 16) + amt);
            const G = Math.min(255, ((num >> 8) & 0x00FF) + amt);
            const B = Math.min(255, (num & 0x0000FF) + amt);
            return '#' + (0x1000000 + R * 0x10000 + G * 0x100 + B).toString(16).slice(1);
        }
        
        function darkenColor(hex, percent) {
            const num = parseInt(hex.replace('#', ''), 16);
            const amt = Math.round(2.55 * percent);
            const R = Math.max(0, (num >> 16) - amt);
            const G = Math.max(0, ((num >> 8) & 0x00FF) - amt);
            const B = Math.max(0, (num & 0x0000FF) - amt);
            return '#' + (0x1000000 + R * 0x10000 + G * 0x100 + B).toString(16).slice(1);
        }
        
        function drawBadge() {
            const size = parseInt(sizeInput.value);
            const name = nameInput.value || 'Membre';
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
            const radius = size * 0.36;
            
            // Ring config
            const ringWidth = size * 0.045;
            const ringRadius = radius + ringWidth / 2 + size * 0.015;
            
            // Global shadow
            if (showCloudy) {
                ctx.save();
                ctx.shadowColor = 'rgba(138, 77, 253, 0.4)';
                ctx.shadowBlur = size * 0.06;
                ctx.shadowOffsetY = size * 0.015;
                ctx.beginPath();
                ctx.arc(centerX, centerY, ringRadius + ringWidth / 2, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(138, 77, 253, 0.01)';
                ctx.fill();
                ctx.restore();
            }
            
            // Premium ring
            if (showCloudy && cloudyLogo.complete) {
                ctx.save();
                
                const ringGradient = ctx.createLinearGradient(
                    centerX - ringRadius, centerY - ringRadius,
                    centerX + ringRadius, centerY + ringRadius
                );
                ringGradient.addColorStop(0, '#a855f7');
                ringGradient.addColorStop(0.3, '#8a4dfd');
                ringGradient.addColorStop(0.5, '#7c3aed');
                ringGradient.addColorStop(0.7, '#8a4dfd');
                ringGradient.addColorStop(1, '#6d28d9');
                
                ctx.beginPath();
                ctx.arc(centerX, centerY, ringRadius + ringWidth / 2, 0, Math.PI * 2);
                ctx.arc(centerX, centerY, ringRadius - ringWidth / 2, 0, Math.PI * 2, true);
                ctx.fillStyle = ringGradient;
                ctx.fill();
                
                // Highlight
                ctx.beginPath();
                ctx.arc(centerX, centerY, ringRadius + ringWidth / 2 - 1, 0, Math.PI * 2);
                ctx.arc(centerX, centerY, ringRadius - ringWidth / 2 + 1, 0, Math.PI * 2, true);
                const highlightGradient = ctx.createLinearGradient(0, centerY - ringRadius, 0, centerY);
                highlightGradient.addColorStop(0, 'rgba(255, 255, 255, 0.5)');
                highlightGradient.addColorStop(0.5, 'rgba(255, 255, 255, 0.1)');
                highlightGradient.addColorStop(1, 'rgba(255, 255, 255, 0)');
                ctx.fillStyle = highlightGradient;
                ctx.fill();
                
                ctx.restore();
            }
            
            // Main circle shadow
            ctx.save();
            ctx.shadowColor = 'rgba(0, 0, 0, 0.2)';
            ctx.shadowBlur = size * 0.03;
            ctx.shadowOffsetY = size * 0.01;
            ctx.beginPath();
            ctx.arc(centerX, centerY, radius, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(0,0,0,0.01)';
            ctx.fill();
            ctx.restore();
            
            // Style-specific rendering
            if (badgeStyle === 'modern') {
                const bgGradient = ctx.createRadialGradient(
                    centerX - radius * 0.3, centerY - radius * 0.3, 0,
                    centerX, centerY, radius * 1.2
                );
                bgGradient.addColorStop(0, lightenColor(primaryColor, 15));
                bgGradient.addColorStop(0.7, primaryColor);
                bgGradient.addColorStop(1, darkenColor(primaryColor, 15));
                
                ctx.beginPath();
                ctx.arc(centerX, centerY, radius, 0, Math.PI * 2);
                ctx.fillStyle = bgGradient;
                ctx.fill();
                
                ctx.beginPath();
                ctx.arc(centerX, centerY, radius, 0, Math.PI * 2);
                const highlight = ctx.createRadialGradient(
                    centerX - radius * 0.25, centerY - radius * 0.35, 0,
                    centerX, centerY, radius
                );
                highlight.addColorStop(0, 'rgba(255,255,255,0.35)');
                highlight.addColorStop(0.4, 'rgba(255,255,255,0.1)');
                highlight.addColorStop(1, 'rgba(255,255,255,0)');
                ctx.fillStyle = highlight;
                ctx.fill();
                
            } else if (badgeStyle === 'gradient') {
                const gradient = ctx.createLinearGradient(
                    centerX - radius, centerY - radius,
                    centerX + radius, centerY + radius
                );
                gradient.addColorStop(0, lightenColor(primaryColor, 10));
                gradient.addColorStop(0.5, '#8a4dfd');
                gradient.addColorStop(1, '#6d28d9');
                
                ctx.beginPath();
                ctx.arc(centerX, centerY, radius, 0, Math.PI * 2);
                ctx.fillStyle = gradient;
                ctx.fill();
                
                ctx.beginPath();
                ctx.arc(centerX, centerY, radius, 0, Math.PI * 2);
                const highlight = ctx.createRadialGradient(
                    centerX - radius * 0.3, centerY - radius * 0.3, 0,
                    centerX, centerY, radius
                );
                highlight.addColorStop(0, 'rgba(255,255,255,0.25)');
                highlight.addColorStop(0.5, 'rgba(255,255,255,0.05)');
                highlight.addColorStop(1, 'rgba(255,255,255,0)');
                ctx.fillStyle = highlight;
                ctx.fill();
                
            } else if (badgeStyle === 'neon') {
                ctx.save();
                ctx.shadowColor = primaryColor;
                ctx.shadowBlur = size * 0.1;
                ctx.beginPath();
                ctx.arc(centerX, centerY, radius * 0.95, 0, Math.PI * 2);
                ctx.strokeStyle = primaryColor;
                ctx.lineWidth = size * 0.025;
                ctx.stroke();
                ctx.restore();
                
                ctx.save();
                ctx.shadowColor = '#8a4dfd';
                ctx.shadowBlur = size * 0.05;
                ctx.beginPath();
                ctx.arc(centerX, centerY, radius * 0.88, 0, Math.PI * 2);
                ctx.strokeStyle = 'rgba(138, 77, 253, 0.5)';
                ctx.lineWidth = size * 0.01;
                ctx.stroke();
                ctx.restore();
                
                const innerGradient = ctx.createRadialGradient(centerX, centerY, 0, centerX, centerY, radius * 0.85);
                innerGradient.addColorStop(0, 'rgba(138, 77, 253, 0.15)');
                innerGradient.addColorStop(1, 'rgba(138, 77, 253, 0.05)');
                ctx.beginPath();
                ctx.arc(centerX, centerY, radius * 0.85, 0, Math.PI * 2);
                ctx.fillStyle = innerGradient;
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
            
            // Cloudy logo on ring
            if (showCloudy && cloudyLogo.complete) {
                ctx.save();
                
                const logoSize = size * 0.16;
                const logoAngle = Math.PI / 4 + Math.PI / 2;
                const logoCenterX = centerX + Math.cos(logoAngle) * ringRadius;
                const logoCenterY = centerY + Math.sin(logoAngle) * ringRadius;
                const logoBgRadius = logoSize / 2 + size * 0.018;
                
                ctx.shadowColor = 'rgba(0, 0, 0, 0.35)';
                ctx.shadowBlur = size * 0.025;
                ctx.shadowOffsetX = size * 0.005;
                ctx.shadowOffsetY = size * 0.008;
                
                ctx.beginPath();
                ctx.arc(logoCenterX, logoCenterY, logoBgRadius, 0, Math.PI * 2);
                ctx.fillStyle = '#ffffff';
                ctx.fill();
                
                ctx.shadowColor = 'transparent';
                ctx.shadowBlur = 0;
                ctx.shadowOffsetX = 0;
                ctx.shadowOffsetY = 0;
                
                const borderGradient = ctx.createLinearGradient(
                    logoCenterX - logoBgRadius, logoCenterY - logoBgRadius,
                    logoCenterX + logoBgRadius, logoCenterY + logoBgRadius
                );
                borderGradient.addColorStop(0, '#a855f7');
                borderGradient.addColorStop(0.5, '#8a4dfd');
                borderGradient.addColorStop(1, '#7c3aed');
                ctx.strokeStyle = borderGradient;
                ctx.lineWidth = size * 0.012;
                ctx.stroke();
                
                ctx.beginPath();
                ctx.arc(logoCenterX, logoCenterY, logoSize / 2, 0, Math.PI * 2);
                ctx.clip();
                ctx.drawImage(
                    cloudyLogo, 
                    logoCenterX - logoSize / 2, 
                    logoCenterY - logoSize / 2, 
                    logoSize, 
                    logoSize
                );
                
                ctx.restore();
            }
        }
        
        // Event listeners
        nameInput.addEventListener('input', drawBadge);
        sizeInput.addEventListener('input', () => {
            sizeValue.textContent = sizeInput.value;
            drawBadge();
        });
        document.querySelectorAll('input[name="bgColor"]').forEach(input => {
            input.addEventListener('change', drawBadge);
        });
        document.querySelectorAll('input[name="badgeStyle"]').forEach(input => {
            input.addEventListener('change', drawBadge);
        });
        document.getElementById('badgeJob')?.addEventListener('change', drawBadge);
        document.getElementById('showCloudy')?.addEventListener('change', drawBadge);
        
        // Download PNG
        document.getElementById('downloadPng').addEventListener('click', () => {
            const link = document.createElement('a');
            const name = nameInput.value.replace(/\s+/g, '_').toLowerCase() || 'badge';
            link.download = `badge_${name}.png`;
            link.href = badgeCanvas.toDataURL('image/png');
            link.click();
        });
        
        // Init
        drawBadge();
    </script>
</body>
</html>
