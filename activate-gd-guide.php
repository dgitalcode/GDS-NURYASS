<?php
/**
 * Guide d'activation de l'extension GD
 * Accédez à: http://localhost/GDS-NURYASS/activate-gd-guide.php
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activer l'extension GD - GDS NURYASS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #f5f7fa;
            padding: 20px;
        }
        .step-box {
            background: white;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 15px 0;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .code-block {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            margin: 10px 0;
            overflow-x: auto;
        }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">
                            <i class="bi bi-tools me-2"></i>
                            Guide d'Activation de l'Extension GD
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php
                        $gdLoaded = extension_loaded('gd');
                        $phpIniPath = php_ini_loaded_file();
                        $phpVersion = phpversion();
                        ?>
                        
                        <div class="alert <?php echo $gdLoaded ? 'alert-success' : 'alert-warning'; ?>">
                            <h5>
                                <i class="bi bi-<?php echo $gdLoaded ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                                Statut de l'extension GD : <?php echo $gdLoaded ? '✅ Activée' : '❌ Non activée'; ?>
                            </h5>
                            <?php if ($gdLoaded): ?>
                                <p class="mb-0">Parfait ! L'extension GD est déjà activée. Vous pouvez générer les icônes maintenant.</p>
                            <?php else: ?>
                                <p class="mb-0">L'extension GD n'est pas activée. Suivez les étapes ci-dessous pour l'activer.</p>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!$gdLoaded): ?>
                            <h4 class="mt-4">📋 Étapes pour activer GD dans XAMPP</h4>
                            
                            <div class="step-box">
                                <h5><span class="badge bg-primary">Étape 1</span> Localiser le fichier php.ini</h5>
                                <p>Le fichier <code>php.ini</code> se trouve généralement dans :</p>
                                <div class="code-block">
                                    C:\xampp\php\php.ini
                                </div>
                                <?php if ($phpIniPath): ?>
                                    <div class="alert alert-info">
                                        <strong>Fichier php.ini détecté :</strong><br>
                                        <code><?php echo htmlspecialchars($phpIniPath); ?></code>
                                    </div>
                                <?php endif; ?>
                                <p><strong>Astuce :</strong> Vous pouvez aussi trouver le chemin en regardant dans XAMPP Control Panel > Apache > Config > PHP (php.ini)</p>
                            </div>
                            
                            <div class="step-box">
                                <h5><span class="badge bg-primary">Étape 2</span> Ouvrir php.ini</h5>
                                <ol>
                                    <li>Ouvrez le fichier <code>php.ini</code> avec un éditeur de texte (Notepad++, Visual Studio Code, ou même le Bloc-notes)</li>
                                    <li><strong>Important :</strong> Utilisez un éditeur avec droits d'administration si nécessaire</li>
                                </ol>
                            </div>
                            
                            <div class="step-box">
                                <h5><span class="badge bg-primary">Étape 3</span> Rechercher la ligne extension=gd</h5>
                                <p>Dans le fichier php.ini, recherchez (Ctrl+F) :</p>
                                <div class="code-block">
                                    ;extension=gd
                                </div>
                                <p>Vous devriez trouver une ligne qui ressemble à ça (avec un point-virgule au début) :</p>
                                <div class="code-block">
                                    ;extension=gd
                                </div>
                            </div>
                            
                            <div class="step-box">
                                <h5><span class="badge bg-primary">Étape 4</span> Décommenter la ligne</h5>
                                <p>Supprimez le point-virgule (<code>;</code>) au début de la ligne :</p>
                                <div class="code-block">
                                    <span style="color: #ff6b6b;">;extension=gd</span>  ← AVANT (commenté)
                                </div>
                                <div class="code-block">
                                    <span style="color: #51cf66;">extension=gd</span>  ← APRÈS (activé)
                                </div>
                                <div class="warning-box">
                                    <strong>⚠️ Attention :</strong> Assurez-vous de modifier la bonne ligne. Il peut y avoir plusieurs lignes similaires. 
                                    Recherchez spécifiquement <code>;extension=gd</code> (avec le point-virgule).
                                </div>
                            </div>
                            
                            <div class="step-box">
                                <h5><span class="badge bg-primary">Étape 5</span> Sauvegarder et redémarrer Apache</h5>
                                <ol>
                                    <li>Sauvegardez le fichier <code>php.ini</code> (Ctrl+S)</li>
                                    <li>Ouvrez le <strong>XAMPP Control Panel</strong></li>
                                    <li>Arrêtez Apache (bouton "Stop")</li>
                                    <li>Attendez quelques secondes</li>
                                    <li>Redémarrez Apache (bouton "Start")</li>
                                </ol>
                                <div class="warning-box">
                                    <strong>💡 Astuce :</strong> Si Apache ne démarre pas, vérifiez qu'il n'y a pas d'erreur de syntaxe dans php.ini. 
                                    Le problème peut aussi venir d'un autre module mal configuré.
                                </div>
                            </div>
                            
                            <div class="step-box">
                                <h5><span class="badge bg-primary">Étape 6</span> Vérifier l'activation</h5>
                                <p>Après avoir redémarré Apache, rechargez cette page pour vérifier que GD est maintenant activé.</p>
                                <a href="activate-gd-guide.php" class="btn btn-primary">
                                    <i class="bi bi-arrow-clockwise me-2"></i>Recharger la page
                                </a>
                            </div>
                            
                            <hr>
                            
                            <h5>🔍 Vérification alternative</h5>
                            <p>Vous pouvez aussi créer un fichier <code>phpinfo.php</code> avec ce contenu :</p>
                            <div class="code-block">
                                &lt;?php phpinfo(); ?&gt;
                            </div>
                            <p>Puis accédez à <code>http://localhost/GDS-NURYASS/phpinfo.php</code> et recherchez "gd" pour voir si l'extension est chargée.</p>
                            
                        <?php else: ?>
                            <div class="alert alert-success">
                                <h5>✅ Extension GD activée !</h5>
                                <p>Vous pouvez maintenant générer les icônes PWA.</p>
                                <a href="generate-icons-web.php" class="btn btn-success">
                                    <i class="bi bi-image me-2"></i>Générer les icônes maintenant
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <hr>
                        
                        <h5>📊 Informations système</h5>
                        <table class="table table-sm">
                            <tr>
                                <th>Version PHP :</th>
                                <td><code><?php echo htmlspecialchars($phpVersion); ?></code></td>
                            </tr>
                            <tr>
                                <th>Extension GD :</th>
                                <td>
                                    <?php if ($gdLoaded): ?>
                                        <span class="badge bg-success">✅ Activée</span>
                                        <?php if (function_exists('gd_info')): ?>
                                            <?php $gdInfo = gd_info(); ?>
                                            <br><small>Version: <?php echo htmlspecialchars($gdInfo['GD Version'] ?? 'Unknown'); ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-danger">❌ Non activée</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Fichier php.ini :</th>
                                <td><code><?php echo htmlspecialchars($phpIniPath ?: 'Non trouvé'); ?></code></td>
                            </tr>
                        </table>
                        
                        <div class="mt-4">
                            <a href="check-pwa.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Retour à la vérification PWA
                            </a>
                            <a href="dashboard.php" class="btn btn-outline-primary">
                                <i class="bi bi-speedometer2 me-2"></i>Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>




