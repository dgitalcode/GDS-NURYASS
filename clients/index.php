<?php
/**
 * Liste des clients
 * GDS - Stock Management System
 */

require_once '../includes/session.php';
require_once '../config/database.php';

requireLogin();

$pageTitle = 'Gestion des Clients';

// Paramètres de recherche et filtrage
$search = $_GET['search'] ?? '';
$typeFilter = $_GET['type'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

try {
    $pdo = getDBConnection();
    
    // Construire la requête avec filtres
    $where = ["actif = 1"];
    $params = [];
    
    if (!empty($search)) {
        $searchTerm = trim($search);
        $where[] = "(LOWER(nom) LIKE LOWER(:search_nom) OR LOWER(prenom) LIKE LOWER(:search_prenom) OR LOWER(nom_entreprise) LIKE LOWER(:search_entreprise) OR LOWER(cin) LIKE LOWER(:search_cin) OR LOWER(patente) LIKE LOWER(:search_patente) OR LOWER(telephone) LIKE LOWER(:search_tel) OR LOWER(email) LIKE LOWER(:search_email))";
        $params['search_nom'] = "%$searchTerm%";
        $params['search_prenom'] = "%$searchTerm%";
        $params['search_entreprise'] = "%$searchTerm%";
        $params['search_cin'] = "%$searchTerm%";
        $params['search_patente'] = "%$searchTerm%";
        $params['search_tel'] = "%$searchTerm%";
        $params['search_email'] = "%$searchTerm%";
    }
    
    if (!empty($typeFilter)) {
        $where[] = "type_client = :type";
        $params['type'] = $typeFilter;
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Compter le total
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM clients WHERE $whereClause");
    $countStmt->execute($params);
    $totalClients = $countStmt->fetchColumn();
    $totalPages = ceil($totalClients / $perPage);
    
    // Récupérer les clients
    $stmt = $pdo->prepare("
        SELECT c.*, 
               cr.montant_actuel as credit_actuel,
               cr.max_montant as credit_max
        FROM clients c
        LEFT JOIN credits cr ON c.id = cr.client_id
        WHERE $whereClause
        ORDER BY c.date_creation DESC
        LIMIT :limit OFFSET :offset
    ");
    
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $clients = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Erreur clients index: " . $e->getMessage());
    $clients = [];
    $totalClients = 0;
    $totalPages = 0;
}

require_once '../includes/header.php';
?>

<div class="top-bar">
    <div>
        <h1 class="page-title">Gestion des Clients</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo url('dashboard.php'); ?>">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Clients</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="create.php" class="btn btn-primary">
            <i class="bi bi-person-plus me-2"></i>Nouveau Client
        </a>
    </div>
</div>

<!-- Filtres et recherche -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3" id="searchForm">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" 
                           class="form-control" 
                           name="search" 
                           id="searchInput"
                           placeholder="Rechercher par nom, CIN, téléphone, email..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           autocomplete="off">
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="type">
                    <option value="">Tous les types</option>
                    <option value="personne" <?php echo $typeFilter === 'personne' ? 'selected' : ''; ?>>Personne</option>
                    <option value="entreprise" <?php echo $typeFilter === 'entreprise' ? 'selected' : ''; ?>>Entreprise</option>
                </select>
            </div>
            <div class="col-md-3">
                <div class="btn-group w-100" role="group">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-funnel me-2"></i>Filtrer
                    </button>
                    <button type="button" 
                            class="btn btn-outline-secondary" 
                            id="resetFiltersBtn"
                            title="Réinitialiser les filtres"
                            onclick="resetFilters()">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
            <?php if (!empty($search)): ?>
            <div class="col-md-12 mt-2">
                <a href="index.php?type=<?php echo urlencode($typeFilter); ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Effacer la recherche
                </a>
                <small class="text-muted ms-2">
                    <?php echo $totalClients; ?> résultat(s) trouvé(s) pour "<?php echo htmlspecialchars($search); ?>"
                </small>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Statistiques rapides -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Total Clients</h6>
                        <h3 class="mb-0"><?php echo number_format($totalClients); ?></h3>
                    </div>
                    <i class="bi bi-people fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Personnes</h6>
                        <h3 class="mb-0">
                            <?php 
                            try {
                                $stmt = $pdo->query("SELECT COUNT(*) FROM clients WHERE type_client = 'personne' AND actif = 1");
                                echo number_format($stmt->fetchColumn());
                            } catch (Exception $e) {
                                echo '0';
                            }
                            ?>
                        </h3>
                    </div>
                    <i class="bi bi-person fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Entreprises</h6>
                        <h3 class="mb-0">
                            <?php 
                            try {
                                $stmt = $pdo->query("SELECT COUNT(*) FROM clients WHERE type_client = 'entreprise' AND actif = 1");
                                echo number_format($stmt->fetchColumn());
                            } catch (Exception $e) {
                                echo '0';
                            }
                            ?>
                        </h3>
                    </div>
                    <i class="bi bi-building fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tableau des clients -->
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">Liste des Clients</h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($clients)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <p class="text-muted mt-3">Aucun client trouvé</p>
                <a href="create.php" class="btn btn-primary">
                    <i class="bi bi-person-plus me-2"></i>Ajouter le premier client
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nom / Entreprise</th>
                            <th>Type</th>
                            <th>CIN / Patente</th>
                            <th>Contact</th>
                            <th>Crédit</th>
                            <th>Date</th>
                            <th width="150">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $client): ?>
                            <tr>
                                <td><strong>#<?php echo $client['id']; ?></strong></td>
                                <td>
                                    <div>
                                        <strong>
                                            <?php 
                                            if ($client['type_client'] === 'entreprise') {
                                                echo htmlspecialchars($client['nom_entreprise'] ?? $client['nom']);
                                            } else {
                                                echo htmlspecialchars($client['nom'] . ' ' . ($client['prenom'] ?? ''));
                                            }
                                            ?>
                                        </strong>
                                    </div>
                                    <small class="text-muted"><?php echo htmlspecialchars($client['adresse']); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $client['type_client'] === 'entreprise' ? 'info' : 'primary'; ?>">
                                        <i class="bi bi-<?php echo $client['type_client'] === 'entreprise' ? 'building' : 'person'; ?> me-1"></i>
                                        <?php echo $client['type_client'] === 'entreprise' ? 'Entreprise' : 'Personne'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($client['type_client'] === 'entreprise'): ?>
                                        <?php echo htmlspecialchars($client['patente'] ?? '-'); ?>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($client['cin'] ?? '-'); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($client['telephone']): ?>
                                        <div><i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($client['telephone']); ?></div>
                                    <?php endif; ?>
                                    <?php if ($client['email']): ?>
                                        <div><i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($client['email']); ?></div>
                                    <?php endif; ?>
                                    <?php if (!$client['telephone'] && !$client['email']): ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $creditActuel = floatval($client['credit_actuel'] ?? 0);
                                    $creditMax = floatval($client['credit_max'] ?? $client['credit_max'] ?? 5000);
                                    $pourcentage = $creditMax > 0 ? ($creditActuel / $creditMax) * 100 : 0;
                                    ?>
                                    <div>
                                        <strong><?php echo number_format($creditActuel, 2); ?> DH</strong>
                                        <small class="text-muted">/ <?php echo number_format($creditMax, 2); ?> DH</small>
                                    </div>
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar bg-<?php echo $pourcentage >= 80 ? 'danger' : ($pourcentage >= 50 ? 'warning' : 'success'); ?>" 
                                             style="width: <?php echo min(100, $pourcentage); ?>%"></div>
                                    </div>
                                </td>
                                <td>
                                    <small><?php echo date('d/m/Y', strtotime($client['date_creation'])); ?></small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="view.php?id=<?php echo $client['id']; ?>" 
                                           class="btn btn-outline-info" 
                                           title="Voir">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo $client['id']; ?>" 
                                           class="btn btn-outline-primary" 
                                           title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="delete.php?id=<?php echo $client['id']; ?>" 
                                           class="btn btn-outline-danger" 
                                           title="Supprimer"
                                           onclick="return confirmDelete(event, 'Êtes-vous sûr de vouloir supprimer ce client ?', 'Supprimer le client')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer bg-white">
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($typeFilter); ?>">Précédent</a>
                            </li>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($typeFilter); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($typeFilter); ?>">Suivant</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Recherche automatique après 1 seconde d'inactivité
let searchTimeout;
document.getElementById('searchInput')?.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const searchValue = this.value.trim();
    
    // Si le champ est vide ou contient au moins 1 caractère, lancer la recherche après 1 seconde
    if (searchValue.length === 0 || searchValue.length >= 1) {
        searchTimeout = setTimeout(function() {
            // Si le champ a changé, soumettre le formulaire
            if (document.getElementById('searchInput').value.trim() === searchValue) {
                document.querySelector('form').submit();
            }
        }, 1000);
    }
});

// Permettre la recherche avec Entrée
document.getElementById('searchInput')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        clearTimeout(searchTimeout);
        document.querySelector('form').submit();
    }
});
</script>

<script>
// Fonction pour réinitialiser tous les champs du formulaire
function resetFilters() {
    const form = document.getElementById('searchForm');
    if (form) {
        // Vider tous les champs
        form.querySelector('input[name="search"]').value = '';
        form.querySelector('select[name="type"]').value = '';
        
        // Rediriger vers la page sans paramètres
        window.location.href = 'index.php';
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>

