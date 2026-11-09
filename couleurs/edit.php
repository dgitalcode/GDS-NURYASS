<?php
/**
 * Modifier une couleur
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

$pageTitle = 'Modifier Couleur';

$errors = [];
$couleur = null;
$couleurId = intval($_GET['id'] ?? 0);

if (!$couleurId) {
    header('Location: index.php');
    exit();
}

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM couleurs WHERE id = :id");
    $stmt->execute(['id' => $couleurId]);
    $couleur = $stmt->fetch();
    
    if (!$couleur) {
        $_SESSION['error_message'] = 'Couleur introuvable.';
        header('Location: index.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("Erreur edit couleur: " . $e->getMessage());
    $_SESSION['error_message'] = 'Erreur lors du chargement de la couleur.';
    header('Location: index.php');
    exit();
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomCouleur = trim($_POST['nom_couleur'] ?? '');
    $codeCouleur = trim($_POST['code_couleur'] ?? '');
    
    // Validation
    if (empty($nomCouleur)) {
        $errors[] = 'Le nom de la couleur est requis.';
    } else {
        try {
            // Vérifier si le nom existe déjà pour une autre couleur
            $stmt = $pdo->prepare("SELECT id FROM couleurs WHERE nom_couleur = :nom AND id != :id");
            $stmt->execute(['nom' => $nomCouleur, 'id' => $couleurId]);
            if ($stmt->fetch()) {
                $errors[] = 'Ce nom de couleur existe déjà.';
            }
        } catch (PDOException $e) {
            error_log("Erreur vérification: " . $e->getMessage());
        }
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Mettre à jour la couleur
            $stmt = $pdo->prepare("
                UPDATE couleurs SET
                    nom_couleur = :nom_couleur,
                    code_couleur = :code_couleur
                WHERE id = :id
            ");
            
            $stmt->execute([
                'id' => $couleurId,
                'nom_couleur' => $nomCouleur,
                'code_couleur' => $codeCouleur ?: null
            ]);
            
            // Historique
            $user = getCurrentUser();
            $stmt = $pdo->prepare("
                INSERT INTO historique (user_id, action, type_action, details, ip_address)
                VALUES (:user_id, :action, :type_action, :details, :ip_address)
            ");
            $stmt->execute([
                'user_id' => $user['id'],
                'action' => 'Modification d\'une couleur',
                'type_action' => 'modification',
                'details' => "Couleur modifiée: $nomCouleur",
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            $pdo->commit();
            
            $_SESSION['success_message'] = 'Couleur modifiée avec succès!';
            header('Location: index.php');
            exit();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Erreur modification couleur: " . $e->getMessage());
            $errors[] = 'Une erreur est survenue lors de la modification.';
        }
    }
}

require_once '../includes/header.php';
?>

<div class="top-bar">
    <div>
        <h1 class="page-title">Modifier Couleur</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo url('dashboard.php'); ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php">Couleurs</a></li>
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
                <h5 class="mb-0">Informations de la Couleur</h5>
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
                        <label for="nom_couleur" class="form-label">Nom de la Couleur <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nom_couleur" name="nom_couleur" value="<?php echo htmlspecialchars($couleur['nom_couleur']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="code_couleur" class="form-label">Code Couleur (Hex)</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" id="code_couleur" name="code_couleur" value="<?php echo htmlspecialchars($couleur['code_couleur'] ?? '#000000'); ?>" style="width: 80px;">
                            <input type="text" class="form-control" id="code_couleur_text" placeholder="#000000" value="<?php echo htmlspecialchars($couleur['code_couleur'] ?? '#000000'); ?>" pattern="^#[0-9A-Fa-f]{6}$">
                        </div>
                        <small class="form-text text-muted">Sélectionnez une couleur ou entrez un code hexadécimal</small>
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

<script>
// Synchroniser le color picker et le champ texte
document.getElementById('code_couleur').addEventListener('input', function() {
    document.getElementById('code_couleur_text').value = this.value;
});

document.getElementById('code_couleur_text').addEventListener('input', function() {
    const value = this.value;
    if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
        document.getElementById('code_couleur').value = value;
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>





