<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireLogin('../login.php');
if (!isAdmin()) { $_SESSION['error_message'] = 'Accès refusé.'; header('Location: ' . url('dashboard.php')); exit(); }
$pageTitle = 'Gestion des Utilisateurs';
try {
    $pdo = getDBConnection();
    $users = $pdo->query("SELECT * FROM users ORDER BY date_creation DESC")->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur users: " . $e->getMessage());
    $users = [];
}
require_once '../includes/header.php';
?>
<div class="top-bar">
    <div>
        <h1 class="page-title">Gestion des Utilisateurs</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo url('dashboard.php'); ?>">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Utilisateurs</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="create.php" class="btn btn-primary">
            <i class="bi bi-person-plus me-2"></i>Nouvel Utilisateur
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">Liste des Utilisateurs</h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($users)): ?>
            <div class="text-center py-5">
                <i class="bi bi-people fs-1 text-muted"></i>
                <p class="text-muted mt-3">Aucun utilisateur enregistré</p>
                <a href="create.php" class="btn btn-primary">
                    <i class="bi bi-person-plus me-2"></i>Ajouter le premier utilisateur
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Rôle</th>
                            <th>Statut</th>
                            <th>Date de création</th>
                            <th width="150">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <?php $currentUser = getCurrentUser(); ?>
                            <tr>
                                <td><strong>#<?php echo $user['id']; ?></strong></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                    <?php if ($user['id'] == $currentUser['id']): ?>
                                        <span class="badge bg-info ms-2">Vous</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                        <?php echo $user['role'] === 'admin' ? 'Admin' : 'Caissier'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $user['actif'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $user['actif'] ? 'Actif' : 'Inactif'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($user['date_creation'])); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn btn-outline-primary" title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($user['id'] != $currentUser['id']): ?>
                                            <a href="delete.php?id=<?php echo $user['id']; ?>" class="btn btn-outline-danger" title="Supprimer définitivement" onclick="return confirmDelete(event, '⚠️ ATTENTION: Cette action est irréversible !\n\nÊtes-vous sûr de vouloir supprimer définitivement cet utilisateur ?', 'Supprimer l\'utilisateur')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="btn btn-outline-secondary disabled" title="Vous ne pouvez pas supprimer votre propre compte">
                                                <i class="bi bi-lock"></i>
                                            </span>
                                        <?php endif; ?>
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

