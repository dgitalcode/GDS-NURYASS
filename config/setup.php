<?php
/**
 * Script de configuration initiale
 * À exécuter une seule fois pour créer la base de données et l'utilisateur admin
 * GDS - Stock Management System
 */

require_once 'database.php';

// Lire le fichier SQL
$sqlFile = __DIR__ . '/../db.sql';

if (!file_exists($sqlFile)) {
    die("Erreur: Le fichier db.sql n'existe pas.\n");
}

try {
    // Se connecter sans spécifier la base de données
    $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Créer la base de données si elle n'existe pas
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE " . DB_NAME);
    
    echo "✓ Base de données créée/existe déjà\n";
    
    // Lire et exécuter le fichier SQL
    $sql = file_get_contents($sqlFile);
    
    // Diviser les requêtes (séparées par ;)
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($queries as $query) {
        if (!empty($query) && !preg_match('/^--/', $query)) {
            try {
                $pdo->exec($query);
            } catch (PDOException $e) {
                // Ignorer les erreurs de table déjà existante
                if (strpos($e->getMessage(), 'already exists') === false) {
                    echo "⚠ Erreur: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    echo "✓ Tables créées avec succès\n";
    
    // Vérifier si l'utilisateur admin existe
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $adminExists = $stmt->fetchColumn() > 0;
    
    if (!$adminExists) {
        // Créer l'utilisateur admin par défaut
        // Mot de passe: admin123 (à changer après la première connexion)
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password, role) 
            VALUES ('admin', :password, 'admin')
        ");
        $stmt->execute(['password' => $hashedPassword]);
        
        echo "✓ Utilisateur admin créé\n";
        echo "  Username: admin\n";
        echo "  Password: admin123 (À CHANGER après la première connexion!)\n";
    } else {
        echo "✓ Utilisateur admin existe déjà\n";
    }
    
    echo "\n✅ Configuration terminée avec succès!\n";
    echo "Vous pouvez maintenant accéder à l'application via login.php\n";
    
} catch (PDOException $e) {
    die("❌ Erreur: " . $e->getMessage() . "\n");
}






