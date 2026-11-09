<?php
/**
 * Modifier un utilisateur
 * GDS - Stock Management System
 */

require_once '../includes/session.php';
require_once '../config/database.php';

requireLogin('../login.php');

if (!isAdmin()) {
    $_SESSION['error_message'] = 'Accès refusé.';
    header('Location: ' . url('dashboard.php'));
    exit();
}

$pageTitle = 'Modifier Utilisateur';

$errors = [];
$user = null;
$userId = intval($_GET['id'] ?? 0);

if (!$userId) {
    header('Location: index.php');
    exit();
}

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $_SESSION['error_message'] = 'Utilisateur introuvable.';
        header('Location: index.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("Erreur edit user: " . $e->getMessage());
    $_SESSION['error_message'] = 'Erreur lors du chargement de l\'utilisateur.';
    header('Location: index.php');
    exit();
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'caissier';
    $actif = isset($_POST['actif']) ? 1 : 0;
    
    // Validation
    if (empty($username)) {
        $errors[] = 'Le nom d\'utilisateur est requis.';
    } else {
        try {
            // Vérifier si le nom existe déjà pour un autre utilisateur
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username AND id != :id");
            $stmt->execute(['username' => $username, 'id' => $userId]);
            if ($stmt->fetch()) {
                $errors[] = 'Ce nom d\'utilisateur existe déjà.';
            }
        } catch (PDOException $e) {
            error_log("Erreur vérification: " . $e->getMessage());
        }
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Préparer la requête de mise à jour
            if (!empty($password)) {
                // Si un nouveau mot de passe est fourni, le mettre à jour
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    UPDATE users SET
                        username = :username,
                        password = :password,
                        role = :role,
                        actif = :actif
                    WHERE id = :id
                ");
                $stmt->execute([
                    'id' => $userId,
                    'username' => $username,
                    'password' => $hashedPassword,
                    'role' => $role,
                    'actif' => $actif
                ]);
            } else {
                // Sinon, ne pas modifier le mot de passe
                $stmt = $pdo->prepare("
                    UPDATE users SET
                        username = :username,
                        role = :role,
                        actif = :actif
                    WHERE id = :id
                ");
                $stmt->execute([
                    'id' => $userId,
                    'username' => $username,
                    'role' => $role,
                    'actif' => $actif
                ]);
            }
            
            // Historique
            $currentUser = getCurrentUser();
            $stmt = $pdo->prepare("
                INSERT INTO historique (user_id, action, type_action, details, ip_address)
                VALUES (:user_id, :action, :type_action, :details, :ip_address)
            ");
            $stmt->execute([
                'user_id' => $currentUser['id'],
                'action' => 'Modification d\'un utilisateur',
                'type_action' => 'modification',
                'details' => "Utilisateur modifié: $username (Rôle: $role)",
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            $pdo->commit();
            
            $_SESSION['success_message'] = 'Utilisateur modifié avec succès!';
            header('Location: index.php');
            exit();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Erreur modification user: " . $e->getMessage());
            $errors[] = 'Une erreur est survenue lors de la modification.';
        }
    }
}

require_once '../includes/header.php';
?>

<div class="top-bar">
    <div>
        <h1 class="page-title">Modifier Utilisateur</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo url('dashboard.php'); ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php">Utilisateurs</a></li>
                <li class="breadcrumb-item active" aria-current="page">Modifier</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Retour
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mx-auto">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Informations de l'Utilisateur</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Nom d'utilisateur <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Nouveau Mot de passe</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Laisser vide pour ne pas modifier">
                        <small class="form-text text-muted">Laissez vide si vous ne voulez pas changer le mot de passe</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">Rôle</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="caissier" <?php echo $user['role'] === 'caissier' ? 'selected' : ''; ?>>Caissier</option>
                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="actif" name="actif" <?php echo $user['actif'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="actif">
                                Compte actif
                            </label>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>





