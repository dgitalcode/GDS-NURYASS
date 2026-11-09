<?php
/**
 * Modifier un type de produit
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

$pageTitle = 'Modifier Type';

$errors = [];
$type = null;
$typeId = intval($_GET['id'] ?? 0);

if (!$typeId) {
    header('Location: index.php');
    exit();
}

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM types_produits WHERE id = :id");
    $stmt->execute(['id' => $typeId]);
    $type = $stmt->fetch();
    
    if (!$type) {
        $_SESSION['error_message'] = 'Type introuvable.';
        header('Location: index.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("Erreur edit type: " . $e->getMessage());
    $_SESSION['error_message'] = 'Erreur lors du chargement du type.';
    header('Location: index.php');
    exit();
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomType = trim($_POST['nom_type'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    // Validation
    if (empty($nomType)) {
        $errors[] = 'Le nom du type est requis.';
    } else {
        try {
            // Vérifier si le nom existe déjà pour un autre type
            $stmt = $pdo->prepare("SELECT id FROM types_produits WHERE nom_type = :nom AND id != :id");
            $stmt->execute(['nom' => $nomType, 'id' => $typeId]);
            if ($stmt->fetch()) {
                $errors[] = 'Ce nom de type existe déjà.';
            }
        } catch (PDOException $e) {
            error_log("Erreur vérification: " . $e->getMessage());
        }
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Mettre à jour le type
            $stmt = $pdo->prepare("
                UPDATE types_produits SET
                    nom_type = :nom_type,
                    description = :description
                WHERE id = :id
            ");
            
            $stmt->execute([
                'id' => $typeId,
                'nom_type' => $nomType,
                'description' => $description ?: null
            ]);
            
            // Historique
            $user = getCurrentUser();
            $stmt = $pdo->prepare("
                INSERT INTO historique (user_id, action, type_action, details, ip_address)
                VALUES (:user_id, :action, :type_action, :details, :ip_address)
            ");
            $stmt->execute([
                'user_id' => $user['id'],
                'action' => 'Modification d\'un type de produit',
                'type_action' => 'modification',
                'details' => "Type modifié: $nomType",
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            $pdo->commit();
            
            $_SESSION['success_message'] = 'Type modifié avec succès!';
            header('Location: index.php');
            exit();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Erreur modification type: " . $e->getMessage());
            $errors[] = 'Une erreur est survenue lors de la modification.';
        }
    }
}

require_once '../includes/header.php';
?>

<div class="top-bar">
    <div>
        <h1 class="page-title">Modifier Type</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo url('dashboard.php'); ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php">Types Produits</a></li>
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
                <h5 class="mb-0">Informations du Type</h5>
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
                        <label for="nom_type" class="form-label">Nom du Type <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nom_type" name="nom_type" value="<?php echo htmlspecialchars($type['nom_type']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($type['description'] ?? ''); ?></textarea>
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





