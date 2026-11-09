<?php
/**
 * Script pour créer/réinitialiser l'utilisateur admin
 * GDS - Stock Management System
 * 
 * Accédez à ce fichier via: http://localhost/GDS-NURYASS/create_admin.php
 */

require_once 'config/database.php';

$message = '';
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? 'admin');
    $password = $_POST['password'] ?? 'admin123';
    $role = $_POST['role'] ?? 'admin';
    
    if (empty($username) || empty($password)) {
        $error = 'Le nom d\'utilisateur et le mot de passe sont requis.';
    } else {
        try {
            $pdo = getDBConnection();
            
            // Hasher le mot de passe
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Vérifier si l'utilisateur existe déjà
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $existingUser = $stmt->fetch();
            
            if ($existingUser) {
                // Mettre à jour le mot de passe
                $stmt = $pdo->prepare("UPDATE users SET password = ?, role = ?, actif = 1 WHERE username = ?");
                $stmt->execute([$hashedPassword, $role, $username]);
                $message = "✅ Utilisateur '$username' mis à jour avec succès!";
            } else {
                // Créer un nouvel utilisateur
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role, actif) VALUES (?, ?, ?, 1)");
                $stmt->execute([$username, $hashedPassword, $role]);
                $message = "✅ Utilisateur '$username' créé avec succès!";
            }
            
            $success = true;
            
            // Afficher les informations de connexion
            $message .= "<br><br><strong>Informations de connexion:</strong><br>";
            $message .= "Username: <code>$username</code><br>";
            $message .= "Password: <code>$password</code><br>";
            $message .= "Role: <code>$role</code>";
            
        } catch (PDOException $e) {
            $error = "Erreur: " . $e->getMessage();
        }
    }
}

// Vérifier les utilisateurs existants
$existingUsers = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, username, role, actif FROM users ORDER BY username");
    $existingUsers = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Erreur de connexion: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer Admin - GDS NURYASS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            border: none;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">
                            <i class="bi bi-person-plus me-2"></i>
                            Créer/Réinitialiser l'utilisateur Admin
                        </h3>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $success ? 'success' : 'info'; ?>">
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Nom d'utilisateur</label>
                                <input type="text" class="form-control" id="username" name="username" value="admin" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Mot de passe</label>
                                <input type="password" class="form-control" id="password" name="password" value="admin123" required>
                                <small class="form-text text-muted">Le mot de passe sera hashé automatiquement</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="role" class="form-label">Rôle</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="admin" selected>Admin</option>
                                    <option value="caissier">Caissier</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="bi bi-check-circle me-2"></i>
                                Créer/Mettre à jour l'utilisateur
                            </button>
                        </form>
                        
                        <?php if (!empty($existingUsers)): ?>
                            <hr>
                            <h5 class="mt-4">Utilisateurs existants:</h5>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Username</th>
                                            <th>Rôle</th>
                                            <th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($existingUsers as $user): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                                <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                                        <?php echo htmlspecialchars($user['role']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $user['actif'] ? 'success' : 'secondary'; ?>">
                                                        <?php echo $user['actif'] ? 'Actif' : 'Inactif'; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-4 text-center">
                            <a href="login.php" class="btn btn-outline-primary">
                                <i class="bi bi-arrow-left me-2"></i>
                                Retour à la page de connexion
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






