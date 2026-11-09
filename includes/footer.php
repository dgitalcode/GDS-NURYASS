    </div> <!-- End main-content -->
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 est déjà chargé dans header.php -->
    
    <!-- PWA Service Worker Registration -->
    <script>
        // Enregistrer le Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('<?php echo url('service-worker.js'); ?>')
                    .then(function(registration) {
                        console.log('Service Worker registered successfully:', registration.scope);
                        
                        // Vérifier les mises à jour du service worker
                        registration.addEventListener('updatefound', function() {
                            const newWorker = registration.installing;
                            newWorker.addEventListener('statechange', function() {
                                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    // Nouveau service worker disponible
                                    Swal.fire({
                                        icon: 'info',
                                        title: 'Nouvelle version disponible',
                                        text: 'Une nouvelle version est disponible. Voulez-vous recharger la page ?',
                                        showCancelButton: true,
                                        confirmButtonColor: '#667eea',
                                        cancelButtonColor: '#6c757d',
                                        confirmButtonText: 'Oui, recharger',
                                        cancelButtonText: 'Plus tard'
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            newWorker.postMessage({ type: 'SKIP_WAITING' });
                                            window.location.reload();
                                        }
                                    });
                                }
                            });
                        });
                    })
                    .catch(function(error) {
                        console.log('Service Worker registration failed:', error);
                    });
            });
        }
        
        // Gestion de l'installation PWA
        let deferredPrompt;
        let installButton = document.getElementById('install-pwa-btn');
        
        // Fonction pour afficher le bouton
        function showInstallButton() {
            if (installButton) {
                installButton.style.display = 'block';
                installButton.style.visibility = 'visible';
            }
        }
        
        // Fonction pour cacher le bouton
        function hideInstallButton() {
            if (installButton) {
                installButton.style.display = 'none';
            }
        }
        
        window.addEventListener('beforeinstallprompt', function(e) {
            console.log('beforeinstallprompt event fired');
            // Empêcher l'affichage automatique du prompt
            e.preventDefault();
            deferredPrompt = e;
            
            // Afficher le bouton d'installation
            showInstallButton();
            
            // Afficher aussi un message dans la console
            console.log('PWA install button should be visible now');
        });
        
        // Gestion du clic sur le bouton d'installation
        if (installButton) {
            installButton.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Install button clicked');
                
                if (deferredPrompt) {
                    // Afficher le prompt d'installation
                    deferredPrompt.prompt();
                    
                    // Attendre la réponse de l'utilisateur
                    deferredPrompt.userChoice.then(function(choiceResult) {
                        console.log('User choice:', choiceResult.outcome);
                        if (choiceResult.outcome === 'accepted') {
                            console.log('User accepted the install prompt');
                            Swal.fire({
                                icon: 'success',
                                title: 'Installation en cours',
                                text: 'L\'application est en cours d\'installation...',
                                timer: 2000,
                                showConfirmButton: false,
                                toast: true,
                                position: 'top-end'
                            });
                        } else {
                            console.log('User dismissed the install prompt');
                        }
                        deferredPrompt = null;
                        hideInstallButton();
                    });
                } else {
                    // Si pas de deferredPrompt, essayer d'ouvrir les instructions
                    Swal.fire({
                        icon: 'info',
                        title: 'Installation de l\'application',
                        html: '<p>Pour installer l\'application:</p><ol style="text-align: left; display: inline-block;"><li>Chrome/Edge: Cherchez l\'icône + dans la barre d\'adresse</li><li>Menu (⋮) > Installer l\'application</li><li>Ou utilisez le diagnostic: pwa-install-diagnostic.php</li></ol>',
                        confirmButtonColor: '#667eea',
                        confirmButtonText: 'Compris'
                    });
                }
            });
        }
        
        // Vérifier si l'app est déjà installée
        window.addEventListener('appinstalled', function(evt) {
            console.log('PWA installed successfully');
            hideInstallButton();
        });
        
        // Vérifier au chargement si l'app est déjà installée
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || 
                            window.navigator.standalone === true;
        
        if (isStandalone) {
            console.log('App is already installed (standalone mode)');
            hideInstallButton();
        } else {
            // Si pas installé, vérifier après un délai si beforeinstallprompt s'est déclenché
            setTimeout(function() {
                if (!deferredPrompt && installButton) {
                    // Le bouton n'est pas visible, afficher un message d'aide
                    console.log('Install button not shown. beforeinstallprompt may not have fired.');
                    console.log('Try: 1. Check browser console for errors');
                    console.log('2. Verify manifest.json is accessible');
                    console.log('3. Check service worker is registered');
                }
            }, 2000);
        }
        
        // Debug: Log l'état du bouton
        console.log('Install button element:', installButton);
        console.log('Is standalone:', isStandalone);
    </script>
    
    <script>
        // Toggle sidebar on mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            const toggle = document.getElementById('mobile-menu-toggle');
            const menuIcon = document.getElementById('menu-icon');
            
            if (sidebar && overlay && toggle) {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
                toggle.classList.toggle('active');
                
                // Changer l'icône
                if (menuIcon) {
                    if (sidebar.classList.contains('show')) {
                        menuIcon.className = 'bi bi-x-lg';
                    } else {
                        menuIcon.className = 'bi bi-list';
                    }
                }
            }
        }
        
        // Initialiser le menu mobile
        document.addEventListener('DOMContentLoaded', function() {
            const toggle = document.getElementById('mobile-menu-toggle');
            const overlay = document.getElementById('sidebar-overlay');
            const sidebar = document.getElementById('sidebar');
            
            // Bouton toggle
            if (toggle) {
                toggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    toggleSidebar();
                });
            }
            
            // Fermer en cliquant sur l'overlay
            if (overlay) {
                overlay.addEventListener('click', function() {
                    toggleSidebar();
                });
            }
            
            // Fermer en cliquant sur un lien du menu (mobile uniquement)
            if (window.innerWidth <= 768 && sidebar) {
                const menuLinks = sidebar.querySelectorAll('.nav-link');
                menuLinks.forEach(function(link) {
                    link.addEventListener('click', function() {
                        // Ne fermer que si ce n'est pas le bouton d'installation ou settings
                        if (!this.id || (this.id !== 'install-pwa-btn' && this.id !== 'settings-toggle')) {
                            setTimeout(function() {
                                if (sidebar.classList.contains('show')) {
                                    toggleSidebar();
                                }
                            }, 300);
                        }
                    });
                });
            }
            
            // Fermer le menu si on redimensionne vers desktop
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768 && sidebar && sidebar.classList.contains('show')) {
                    sidebar.classList.remove('show');
                    if (overlay) overlay.classList.remove('show');
                    if (toggle) toggle.classList.remove('active');
                    const menuIcon = document.getElementById('menu-icon');
                    if (menuIcon) menuIcon.className = 'bi bi-list';
                }
            });
        });
        
        // Settings dropdown toggle
        document.addEventListener('DOMContentLoaded', function() {
            const settingsToggle = document.getElementById('settings-toggle');
            const settingsMenu = document.querySelector('.settings-menu');
            
            if (settingsToggle && settingsMenu) {
                settingsToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    settingsMenu.classList.toggle('active');
                });
                
                // Fermer le menu si on clique ailleurs
                document.addEventListener('click', function(e) {
                    if (!settingsMenu.contains(e.target)) {
                        settingsMenu.classList.remove('active');
                    }
                });
            }
        });
        
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
        
        // Dark Mode Toggle
        document.addEventListener('DOMContentLoaded', function() {
            const darkModeToggle = document.getElementById('dark-mode-toggle');
            const darkModeIcon = document.getElementById('dark-mode-icon');
            const darkModeText = document.getElementById('dark-mode-text');
            const body = document.body;
            
            // Vérifier si le dark mode est activé dans localStorage
            const isDarkMode = localStorage.getItem('darkMode') === 'enabled';
            
            // Appliquer le dark mode si activé
            if (isDarkMode) {
                body.classList.add('dark-mode');
                darkModeIcon.className = 'bi bi-sun';
                darkModeText.textContent = 'Mode Clair';
            }
            
            // Toggle dark mode
            if (darkModeToggle) {
                darkModeToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Toggle la classe dark-mode
                    body.classList.toggle('dark-mode');
                    
                    // Sauvegarder la préférence
                    if (body.classList.contains('dark-mode')) {
                        localStorage.setItem('darkMode', 'enabled');
                        darkModeIcon.className = 'bi bi-sun';
                        darkModeText.textContent = 'Mode Clair';
                    } else {
                        localStorage.setItem('darkMode', 'disabled');
                        darkModeIcon.className = 'bi bi-moon-stars';
                        darkModeText.textContent = 'Mode Sombre';
                    }
                });
            }
        });
        
        // Fonction réutilisable pour les confirmations de suppression
        function confirmDelete(event, message, title = 'Confirmer la suppression') {
            event.preventDefault();
            const url = event.currentTarget.href;
            
            Swal.fire({
                icon: 'warning',
                title: title,
                text: message || 'Cette action est irréversible !',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Oui, supprimer',
                cancelButtonText: 'Annuler',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
            
            return false;
        }
    </script>
</body>
</html>

