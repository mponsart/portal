<?php
$user = $_SESSION['user'];
$config = require __DIR__ . '/../config.php';
$services = $config['services'] ?? [];
$jobs = $config['jobs'] ?? [];
$currentPage = 'signatures';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signatures - Groupe Speed Cloud</title>
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
        <div class="max-w-4xl mx-auto mb-8">
            <h1 class="text-2xl sm:text-3xl font-bold text-white mb-2">✍️ Créer ma Signature</h1>
            <p class="text-gray-400">Générez votre signature email professionnelle</p>
        </div>

        <!-- Tabs -->
        <div class="max-w-4xl mx-auto mb-6">
            <div class="flex gap-2 bg-white/5 p-1 rounded-xl w-fit">
                <button id="tabPersonal" class="px-5 py-2.5 rounded-lg text-sm font-medium transition bg-speed-purple text-white">
                    👤 Personnelle
                </button>
                <button id="tabService" class="px-5 py-2.5 rounded-lg text-sm font-medium transition text-gray-300 hover:bg-white/10">
                    🏢 Service
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-4xl mx-auto bg-white/10 backdrop-blur-lg rounded-2xl p-4 sm:p-8 shadow-2xl border border-white/20">
            
            <!-- Personal Signature Form -->
            <form id="personalForm" class="grid md:grid-cols-2 gap-6 mb-8">
                <div>
                    <label for="firstname" class="block text-sm font-semibold text-gray-200 mb-2">Prénom</label>
                    <input type="text" id="firstname" value="<?= htmlspecialchars($user['firstName']) ?>" 
                        class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-speed-purple transition">
                </div>
                <div>
                    <label for="lastname" class="block text-sm font-semibold text-gray-200 mb-2">Nom</label>
                    <input type="text" id="lastname" value="<?= htmlspecialchars($user['lastName']) ?>" 
                        class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-speed-purple transition">
                </div>
                <div>
                    <label for="job" class="block text-sm font-semibold text-gray-200 mb-2">Poste</label>
                    <select id="job" class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-speed-purple transition cursor-pointer">
                        <?php foreach ($jobs as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value) ?>" class="bg-gray-800"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" id="customJob" placeholder="Votre poste personnalisé" 
                        class="hidden w-full mt-2 px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-speed-purple transition">
                </div>
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-200 mb-2">E-mail</label>
                    <input type="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" readonly
                        class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-lg text-gray-400 cursor-not-allowed">
                </div>
                <input type="hidden" id="signatureType" value="personal">
            </form>
            
            <!-- Service Signature Form (hidden by default) -->
            <form id="serviceForm" class="hidden grid md:grid-cols-2 gap-6 mb-8">
                <div class="md:col-span-2">
                    <label for="service" class="block text-sm font-semibold text-gray-200 mb-2">Service / Département</label>
                    <select id="service" class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-speed-purple transition">
                        <?php foreach ($services as $key => $service): ?>
                            <?php if ($key === ''): ?>
                            <option value="" class="bg-gray-800">-- Sélectionnez un service --</option>
                            <?php else: ?>
                            <option value="<?= htmlspecialchars($key) ?>" class="bg-gray-800"><?= htmlspecialchars($service['name']) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <input type="hidden" id="serviceSignatureType" value="service">
            </form>
            
            <!-- Client Email Selection -->
            <div class="mb-8">
                <label class="block text-sm font-semibold text-gray-200 mb-2">Format de signature</label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <label class="cursor-pointer">
                        <input type="radio" name="style" value="gmail" class="peer hidden" checked>
                        <div class="p-4 bg-white/5 border-2 border-white/10 rounded-lg text-center peer-checked:border-speed-purple peer-checked:bg-speed-purple/20 transition">
                            <span class="text-2xl block mb-1">💻</span>
                            <span class="text-white font-medium text-sm">Gmail Desktop</span>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="style" value="outlook" class="peer hidden">
                        <div class="p-4 bg-white/5 border-2 border-white/10 rounded-lg text-center peer-checked:border-speed-purple peer-checked:bg-speed-purple/20 transition">
                            <span class="text-2xl block mb-1">📧</span>
                            <span class="text-white font-medium text-sm">Outlook</span>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="style" value="mobile" class="peer hidden">
                        <div class="p-4 bg-white/5 border-2 border-white/10 rounded-lg text-center peer-checked:border-speed-purple peer-checked:bg-speed-purple/20 transition">
                            <span class="text-2xl block mb-1">📱</span>
                            <span class="text-white font-medium text-sm">Gmail Mobile</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Preview -->
            <div class="bg-white rounded-xl overflow-hidden shadow-lg">
                <div class="bg-gray-100 px-4 py-2 border-b flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <div class="hidden sm:flex gap-1">
                            <div class="w-3 h-3 rounded-full bg-red-400"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                            <div class="w-3 h-3 rounded-full bg-green-400"></div>
                        </div>
                        <span class="sm:ml-4 text-sm text-gray-500">Aperçu de la signature</span>
                    </div>
                    <button id="copyBtn" class="text-sm bg-speed-purple text-white px-4 py-2 rounded-lg hover:bg-speed-purple-dark transition w-full sm:w-auto">
                        📋 Copier la signature
                    </button>
                </div>
                <div id="preview" class="p-4 sm:p-6 bg-white min-h-[150px] overflow-x-auto">
                    <!-- Signature générée ici -->
                </div>
            </div>

            <!-- Instructions -->
            <div class="mt-6 p-4 bg-speed-purple/20 rounded-lg border border-speed-purple/30">
                <h3 class="text-white font-semibold mb-2">💡 Comment utiliser</h3>
                <ol class="text-gray-300 text-sm space-y-1 list-decimal list-inside">
                    <li>Vérifiez vos informations (prénom, nom, poste)</li>
                    <li>Sélectionnez votre client email</li>
                    <li>Cliquez sur "Copier" ou sélectionnez la signature (Ctrl+A dans l'aperçu)</li>
                    <li>Collez dans les paramètres de signature de votre client email</li>
                </ol>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8 text-gray-400 text-sm">
            © <?= date('Y') ?> Association Groupe Speed Cloud - Tous droits réservés
        </div>
    </div>

    <!-- Services data for JS -->
    <script>
        const servicesData = <?= json_encode($services) ?>;
    </script>

    <script>
        const personalForm = document.getElementById('personalForm');
        const serviceForm = document.getElementById('serviceForm');
        const tabPersonal = document.getElementById('tabPersonal');
        const tabService = document.getElementById('tabService');
        const preview = document.getElementById('preview');
        const copyBtn = document.getElementById('copyBtn');
        
        let currentTab = 'personal';
        
        // Tab switching
        tabPersonal.addEventListener('click', () => {
            currentTab = 'personal';
            tabPersonal.classList.add('bg-speed-purple', 'text-white');
            tabPersonal.classList.remove('text-gray-300', 'hover:bg-white/10');
            tabService.classList.remove('bg-speed-purple', 'text-white');
            tabService.classList.add('text-gray-300', 'hover:bg-white/10');
            personalForm.classList.remove('hidden');
            serviceForm.classList.add('hidden');
            updatePreview();
        });
        
        tabService.addEventListener('click', () => {
            currentTab = 'service';
            tabService.classList.add('bg-speed-purple', 'text-white');
            tabService.classList.remove('text-gray-300', 'hover:bg-white/10');
            tabPersonal.classList.remove('bg-speed-purple', 'text-white');
            tabPersonal.classList.add('text-gray-300', 'hover:bg-white/10');
            serviceForm.classList.remove('hidden');
            personalForm.classList.add('hidden');
            updatePreview();
        });
        
        async function updatePreview() {
            const style = document.querySelector('input[name="style"]:checked').value;
            let data;
            
            if (currentTab === 'personal') {
                const jobSelect = personalForm.job;
                const customJob = document.getElementById('customJob');
                const jobValue = jobSelect.value === '__autre__' ? customJob.value : jobSelect.value;
                data = new URLSearchParams({
                    style: style,
                    type: 'personal',
                    name: `${personalForm.firstname.value} ${personalForm.lastname.value}`.trim(),
                    job: jobValue,
                    email: personalForm.email.value
                });
            } else {
                const serviceKey = serviceForm.service.value;
                const serviceInfo = servicesData[serviceKey] || {};
                data = new URLSearchParams({
                    style: style,
                    type: 'service',
                    service: serviceKey,
                    name: serviceInfo.name || '',
                    email: serviceInfo.email || '',
                    job: ''
                });
            }
            
            try {
                const response = await fetch('/signature.php?' + data.toString());
                preview.innerHTML = await response.text();
            } catch (e) {
                console.error(e);
            }
        }
        
        // Events for personal form
        personalForm.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', updatePreview);
        });
        personalForm.job.addEventListener('change', function() {
            const customJobInput = document.getElementById('customJob');
            if (this.value === '__autre__') {
                customJobInput.classList.remove('hidden');
                customJobInput.focus();
            } else {
                customJobInput.classList.add('hidden');
                customJobInput.value = '';
            }
            updatePreview();
        });
        document.getElementById('customJob').addEventListener('input', updatePreview);
        
        // Events for service form
        serviceForm.service.addEventListener('change', updatePreview);
        
        // Events for style selection
        document.querySelectorAll('input[name="style"]').forEach(input => {
            input.addEventListener('change', updatePreview);
        });
        
        // Copy button
        copyBtn.addEventListener('click', async () => {
            try {
                const selection = window.getSelection();
                const range = document.createRange();
                range.selectNodeContents(preview);
                selection.removeAllRanges();
                selection.addRange(range);
                document.execCommand('copy');
                selection.removeAllRanges();
                
                copyBtn.textContent = '✓ Copié !';
                setTimeout(() => copyBtn.textContent = 'Copier', 2000);
            } catch (e) {
                console.error(e);
            }
        });
        
        // Init
        updatePreview();
    </script>
</body>
</html>
