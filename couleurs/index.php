<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireLogin('../login.php');
if (!isAdmin()) { $_SESSION['error_message'] = 'Accès refusé.'; header('Location: ' . url('dashboard.php')); exit(); }
$pageTitle = 'Couleurs';
try {
    $pdo = getDBConnection();
    $couleurs = $pdo->query("SELECT c.*, COUNT(p.id) as nb_produits FROM couleurs c LEFT JOIN produits p ON c.id = p.couleur_id GROUP BY c.id ORDER BY c.nom_couleur")->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur: " . $e->getMessage());
    $couleurs = [];
}
require_once '../includes/header.php';
?>
<div class="top-bar">
    <div>
        <h1 class="page-title">Couleurs</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo url('dashboard.php'); ?>">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Couleurs</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="create.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Nouvelle Couleur
        </a>
    </div>
</div>
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">Liste des Couleurs</h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($couleurs)): ?>
            <div class="text-center py-5">
                <i class="bi bi-palette fs-1 text-muted"></i>
                <p class="text-muted mt-3">Aucune couleur enregistrée</p>
                <a href="create.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Ajouter la première couleur
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Couleur</th>
                            <th>Code</th>
                            <th>Produits</th>
                            <th width="150">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($couleurs as $couleur): ?>
                            <tr>
                                <td><strong>#<?php echo $couleur['id']; ?></strong></td>
                                <td><strong><?php echo htmlspecialchars($couleur['nom_couleur']); ?></strong></td>
                                <td>
                                    <span class="badge" style="background-color: <?php echo htmlspecialchars($couleur['code_couleur'] ?? '#000000'); ?>; color: <?php echo (hexdec(substr($couleur['code_couleur'] ?? '#000000', 1)) > 0xFFFFFF/2) ? 'black' : 'white'; ?>;">
                                        <?php echo htmlspecialchars($couleur['code_couleur'] ?? '-'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo $couleur['nb_produits']; ?></span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit.php?id=<?php echo $couleur['id']; ?>" class="btn btn-outline-primary" title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="delete.php?id=<?php echo $couleur['id']; ?>" class="btn btn-outline-danger" title="Supprimer" onclick="return confirmDelete(event, 'Êtes-vous sûr de vouloir supprimer cette couleur ?', 'Supprimer la couleur')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>

