<?php
/**
 * Script d'installation automatique
 * GDS - Stock Management System
 * 
 * Accédez à ce fichier via: http://localhost/GDS-NURYASS/install.php
 */

// Vérifier si l'installation a déjà été effectuée
$configFile = __DIR__ . '/config/database.php';
$installed = false;

if (file_exists($configFile)) {
    require_once $configFile;
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        $installed = $stmt->rowCount() > 0;
    } catch (Exception $e) {
        $installed = false;
    }
}

$errors = [];
$success = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$installed) {
    $dbHost = $_POST['db_host'] ?? 'localhost';
    $dbName = $_POST['db_name'] ?? 'gds_nuryass';
    $dbUser = $_POST['db_user'] ?? 'root';
    $dbPass = $_POST['db_pass'] ?? '';
    
    try {
        // Tester la connexion
        $pdo = new PDO("mysql:host=$dbHost;charset=utf8mb4", $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Créer la base de données
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$dbName`");
        $success[] = "Base de données créée avec succès";
        
        // Lire et exécuter le fichier SQL
        $sqlFile = __DIR__ . '/db.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            
            // Diviser les requêtes
            $queries = array_filter(array_map('trim', explode(';', $sql)));
            
            $executed = 0;
            foreach ($queries as $query) {
                if (!empty($query) && !preg_match('/^--/', $query) && strlen($query) > 10) {
                    try {
                        $pdo->exec($query);
                        $executed++;
                    } catch (PDOException $e) {
                        // Ignorer les erreurs de table déjà existante
                        if (strpos($e->getMessage(), 'already exists') === false && 
                            strpos($e->getMessage(), 'Duplicate') === false) {
                            $errors[] = "Erreur SQL: " . substr($e->getMessage(), 0, 100);
                        }
                    }
                }
            }
            $success[] = "$executed requêtes exécutées avec succès";
        }
        
        // Vérifier et créer l'utilisateur admin
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'admin'");
        if ($stmt->fetchColumn() == 0) {
            $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES ('admin', ?, 'admin')");
            $stmt->execute([$hashedPassword]);
            $success[] = "Utilisateur admin créé (username: admin, password: admin123)";
        }
        
        $success[] = "Installation terminée avec succès!";
        $installed = true;
        
    } catch (PDOException $e) {
        $errors[] = "Erreur de connexion: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - GDS NURYASS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .install-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 600px;
            width: 100%;
        }
        .install-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 15px 15px 0 0;
        }
        .install-body {
            padding: 40px;
        }
    </style>
</head>
<body>
    <div class="install-card">
        <div class="install-header">
            <h1><i class="bi bi-gear-fill me-2"></i>Installation GDS</h1>
            <p class="mb-0">Configuration de la base de données</p>
        </div>
        <div class="install-body">
            <?php if ($installed): ?>
                <div class="alert alert-success">
                    <h5><i class="bi bi-check-circle-fill me-2"></i>Installation terminée!</h5>
                    <p>Le système a été installé avec succès.</p>
                    <hr>
                    <p><strong>Identifiants par défaut:</strong></p>
                    <ul>
                        <li>Username: <code>admin</code></li>
                        <li>Password: <code>admin123</code></li>
                    </ul>
                    <p class="text-danger"><strong>⚠️ Changez le mot de passe après la première connexion!</strong></p>
                    <a href="login.php" class="btn btn-primary btn-lg w-100">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Aller à la page de connexion
                    </a>
                </div>
            <?php else: ?>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <h5><i class="bi bi-exclamation-triangle-fill me-2"></i>Erreurs:</h5>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <ul class="mb-0">
                            <?php foreach ($success as $msg): ?>
                                <li><?php echo htmlspecialchars($msg); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="db_host" class="form-label">Hôte MySQL</label>
                        <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="db_name" class="form-label">Nom de la base de données</label>
                        <input type="text" class="form-control" id="db_name" name="db_name" value="gds_nuryass" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="db_user" class="form-label">Utilisateur MySQL</label>
                        <input type="text" class="form-control" id="db_user" name="db_user" value="root" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="db_pass" class="form-label">Mot de passe MySQL</label>
                        <input type="password" class="form-control" id="db_pass" name="db_pass" value="">
                        <small class="form-text text-muted">Laissez vide si pas de mot de passe (XAMPP par défaut)</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="bi bi-play-fill me-2"></i>Installer
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>






