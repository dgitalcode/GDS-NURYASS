<?php
/**
 * Diagnostic PWA - Pourquoi le bouton d'installation n'apparaît pas
 * Accédez à: http://localhost/GDS-NURYASS/pwa-install-diagnostic.php
 */
require_once 'includes/session.php';
require_once 'config/database.php';
requireLogin();
$pageTitle = 'Diagnostic Installation PWA';
require_once 'includes/header.php';
?>
<div class="top-bar">
    <div>
        <h1 class="page-title">Diagnostic Installation PWA</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo url('dashboard.php'); ?>">Dashboard</a></li>
                <li class="breadcrumb-item active">Diagnostic PWA</li>
            </ol>
        </nav>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="bi bi-bug me-2"></i>Vérification des Conditions PWA</h5>
    </div>
    <div class="card-body">
        <div id="diagnostic-results">
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
                <p class="mt-3">Vérification en cours...</p>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="bi bi-lightbulb me-2"></i>Solutions et Alternatives</h5>
    </div>
    <div class="card-body">
        <h6>Méthode 1 : Installation via la barre d'adresse (Chrome/Edge)</h6>
        <ol>
            <li>Ouvrez l'application dans <strong>Chrome</strong> ou <strong>Edge</strong></li>
            <li>Regardez dans la <strong>barre d'adresse</strong> (à droite)</li>
            <li>Vous devriez voir une icône <strong>+</strong> ou <strong>⬇</strong> (télécharger)</li>
            <li>Cliquez dessus pour installer</li>
        </ol>
        
        <hr>
        
        <h6>Méthode 2 : Installation via le menu (Chrome)</h6>
        <ol>
            <li>Cliquez sur les <strong>trois points</strong> (⋮) en haut à droite</li>
            <li>Recherchez <strong>"Installer GDS NURYASS"</strong> ou <strong>"Installer l'application"</strong></li>
            <li>Cliquez pour installer</li>
        </ol>
        
        <hr>
        
        <h6>Méthode 3 : Installation manuelle (Tous navigateurs)</h6>
        <ol>
            <li>Créez un raccourci sur le bureau</li>
            <li>Faites un clic droit sur le bureau > <strong>Nouveau > Raccourci</strong></li>
            <li>Entrez l'URL : <code>http://localhost/GDS-NURYASS/dashboard.php</code></li>
            <li>Nommez-le "GDS NURYASS"</li>
            <li>Faites un clic droit sur le raccourci > <strong>Propriétés</strong></li>
            <li>Cliquez sur <strong>"Changer d'icône"</strong> et sélectionnez une icône</li>
        </ol>
        
        <hr>
        
        <h6>Méthode 4 : Installation via Edge (Recommandé pour Windows)</h6>
        <ol>
            <li>Ouvrez l'application dans <strong>Microsoft Edge</strong></li>
            <li>Cliquez sur l'icône <strong>+</strong> dans la barre d'adresse</li>
            <li>Ou allez dans <strong>Menu (⋮) > Applications > Installer cette application</strong></li>
        </ol>
        
        <div class="alert alert-warning mt-4">
            <h6><i class="bi bi-exclamation-triangle me-2"></i>Pourquoi le bouton n'apparaît pas ?</h6>
            <ul class="mb-0">
                <li>L'application est peut-être déjà installée</li>
                <li>Vous avez peut-être déjà refusé l'installation (essayez en navigation privée)</li>
                <li>Le navigateur ne supporte pas l'installation PWA (utilisez Chrome/Edge)</li>
                <li>Le manifest.json ou le service worker a une erreur</li>
            </ul>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const resultsDiv = document.getElementById('diagnostic-results');
    let html = '';
    
    // 1. Vérifier le support Service Worker
    const swSupported = 'serviceWorker' in navigator;
    html += createCheckItem(
        'Support Service Worker',
        swSupported,
        swSupported ? 'Votre navigateur supporte les Service Workers' : 'Votre navigateur ne supporte pas les Service Workers. Utilisez Chrome, Edge ou Firefox récent.'
    );
    
    // 2. Vérifier le manifest
    fetch('./manifest.json')
        .then(response => {
            const manifestOk = response.ok;
            html += createCheckItem(
                'Manifest.json accessible',
                manifestOk,
                manifestOk ? 'Le manifest.json est accessible' : 'Le manifest.json n\'est pas accessible. Vérifiez que le fichier existe.'
            );
            return response.json();
        })
        .then(manifest => {
            if (manifest) {
                html += createCheckItem(
                    'Manifest.json valide',
                    manifest.name && manifest.icons,
                    manifest.name && manifest.icons ? 'Le manifest.json est valide' : 'Le manifest.json contient des erreurs'
                );
            }
        })
        .catch(() => {
            html += createCheckItem('Manifest.json accessible', false, 'Erreur lors de la lecture du manifest.json');
        });
    
    // 3. Vérifier le service worker
    if (swSupported) {
        navigator.serviceWorker.getRegistrations().then(registrations => {
            const swRegistered = registrations.length > 0;
            html += createCheckItem(
                'Service Worker enregistré',
                swRegistered,
                swRegistered ? 'Le Service Worker est enregistré' : 'Le Service Worker n\'est pas enregistré. Vérifiez la console pour les erreurs.'
            );
            
            // Vérifier l'état
            if (swRegistered) {
                registrations[0].update();
            }
        });
    }
    
    // 4. Vérifier si déjà installé
    const isInstalled = window.matchMedia('(display-mode: standalone)').matches || 
                       window.navigator.standalone === true ||
                       document.referrer.includes('android-app://');
    html += createCheckItem(
        'Application déjà installée',
        isInstalled,
        isInstalled ? 'L\'application semble déjà installée' : 'L\'application n\'est pas encore installée'
    );
    
    // 5. Vérifier HTTPS/localhost
    const isSecure = location.protocol === 'https:' || 
                    location.hostname === 'localhost' || 
                    location.hostname === '127.0.0.1';
    html += createCheckItem(
        'Connexion sécurisée (HTTPS/localhost)',
        isSecure,
        isSecure ? 'Connexion sécurisée (localhost ou HTTPS)' : 'HTTPS requis pour la production. Localhost fonctionne sans HTTPS.'
    );
    
    // 6. Vérifier les icônes
    const iconSizes = [72, 96, 128, 144, 152, 192, 384, 512];
    let iconsChecked = 0;
    let iconsFound = 0;
    
    iconSizes.forEach(size => {
        const img = new Image();
        img.onload = function() {
            iconsFound++;
            iconsChecked++;
            if (iconsChecked === iconSizes.length) {
                html += createCheckItem(
                    'Icônes PWA',
                    iconsFound === iconSizes.length,
                    `${iconsFound}/${iconSizes.length} icônes trouvées`
                );
                updateResults();
            }
        };
        img.onerror = function() {
            iconsChecked++;
            if (iconsChecked === iconSizes.length) {
                html += createCheckItem(
                    'Icônes PWA',
                    iconsFound === iconSizes.length,
                    `${iconsFound}/${iconSizes.length} icônes trouvées`
                );
                updateResults();
            }
        };
        img.src = `./images/icon-${size}x${size}.png`;
    });
    
    // 7. Vérifier beforeinstallprompt
    let beforeInstallPromptFired = false;
    window.addEventListener('beforeinstallprompt', function(e) {
        beforeInstallPromptFired = true;
        html += createCheckItem(
            'Événement beforeinstallprompt',
            true,
            'L\'événement beforeinstallprompt a été déclenché. Le bouton devrait apparaître.'
        );
        updateResults();
    });
    
    // Attendre un peu pour voir si beforeinstallprompt se déclenche
    setTimeout(() => {
        if (!beforeInstallPromptFired) {
            html += createCheckItem(
                'Événement beforeinstallprompt',
                false,
                'L\'événement beforeinstallprompt ne s\'est pas déclenché. Cela peut signifier que l\'app est déjà installée ou que les critères ne sont pas remplis.'
            );
            updateResults();
        }
    }, 3000);
    
    function updateResults() {
        resultsDiv.innerHTML = html;
    }
    
    function createCheckItem(name, status, message) {
        const icon = status ? 'check-circle-fill text-success' : 'x-circle-fill text-danger';
        const bgClass = status ? 'bg-light border-success' : 'bg-light border-danger';
        return `
            <div class="p-3 mb-2 border rounded ${bgClass}">
                <div class="d-flex align-items-center">
                    <i class="bi ${icon} fs-4 me-3"></i>
                    <div>
                        <strong>${name}</strong><br>
                        <small class="text-muted">${message}</small>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Mise à jour initiale
    setTimeout(updateResults, 1000);
});
</script>

<?php require_once 'includes/footer.php'; ?>



