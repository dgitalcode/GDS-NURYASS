<?php
/**
 * Historique des opérations
 * GDS - Stock Management System
 */

require_once '../includes/session.php';
require_once '../config/database.php';

requireLogin('../login.php');

$pageTitle = 'Historique des Opérations';

// Paramètres de recherche et filtrage
$search = trim($_GET['search'] ?? '');
$typeFilter = $_GET['type'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

try {
    $pdo = getDBConnection();
    
    // Construire la requête avec filtres
    $where = ["1=1"];
    $params = [];
    
    // Recherche insensible à la casse (majuscules/minuscules)
    if (!empty($search)) {
        $searchTerm = trim($search);
        $where[] = "(LOWER(h.action) LIKE LOWER(:search_action) OR 
                     LOWER(h.details) LIKE LOWER(:search_details) OR
                     LOWER(u.username) LIKE LOWER(:search_username))";
        $params['search_action'] = "%$searchTerm%";
        $params['search_details'] = "%$searchTerm%";
        $params['search_username'] = "%$searchTerm%";
    }
    
    if (!empty($typeFilter)) {
        $where[] = "h.type_action = :type_action";
        $params['type_action'] = $typeFilter;
    }
    
    if (!empty($dateFrom)) {
        $where[] = "DATE(h.date_action) >= :date_from";
        $params['date_from'] = $dateFrom;
    }
    
    if (!empty($dateTo)) {
        $where[] = "DATE(h.date_action) <= :date_to";
        $params['date_to'] = $dateTo;
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Compter le total
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM historique h LEFT JOIN users u ON h.user_id = u.id WHERE $whereClause");
    foreach ($params as $key => $value) {
        $countStmt->bindValue(":$key", $value);
    }
    $countStmt->execute();
    $total = $countStmt->fetchColumn();
    $totalPages = ceil($total / $perPage);
    
    // Récupérer l'historique
    $stmt = $pdo->prepare("
        SELECT h.*, u.username 
        FROM historique h 
        LEFT JOIN users u ON h.user_id = u.id 
        WHERE $whereClause 
        ORDER BY h.date_action DESC 
        LIMIT :limit OFFSET :offset
    ");
    
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $historique = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Erreur historique: " . $e->getMessage());
    $historique = [];
    $total = 0;
    $totalPages = 0;
}

require_once '../includes/header.php';
?>
<div class="top-bar">
    <div>
        <h1 class="page-title">Historique des Opérations</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo url('dashboard.php'); ?>">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Historique</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Statistiques -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Total Opérations</h6>
                        <h3 class="mb-0"><?php echo number_format($total); ?></h3>
                    </div>
                    <i class="bi bi-clock-history fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Résultats affichés</h6>
                        <h3 class="mb-0"><?php echo number_format(count($historique)); ?></h3>
                    </div>
                    <i class="bi bi-list-ul fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Page</h6>
                        <h3 class="mb-0"><?php echo $page; ?> / <?php echo max(1, $totalPages); ?></h3>
                    </div>
                    <i class="bi bi-file-earmark-text fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Formulaire de recherche et filtres -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3" id="searchForm">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" 
                           class="form-control" 
                           name="search" 
                           id="searchInput"
                           placeholder="Rechercher par action, détails, utilisateur..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           autocomplete="off">
                </div>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="type">
                    <option value="">Tous les types</option>
                    <option value="creation" <?php echo $typeFilter === 'creation' ? 'selected' : ''; ?>>Création</option>
                    <option value="modification" <?php echo $typeFilter === 'modification' ? 'selected' : ''; ?>>Modification</option>
                    <option value="suppression" <?php echo $typeFilter === 'suppression' ? 'selected' : ''; ?>>Suppression</option>
                    <option value="paiement" <?php echo $typeFilter === 'paiement' ? 'selected' : ''; ?>>Paiement</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" 
                       class="form-control" 
                       name="date_from" 
                       value="<?php echo htmlspecialchars($dateFrom); ?>" 
                       placeholder="Du">
            </div>
            <div class="col-md-2">
                <input type="date" 
                       class="form-control" 
                       name="date_to" 
                       value="<?php echo htmlspecialchars($dateTo); ?>" 
                       placeholder="Au">
            </div>
            <div class="col-md-2">
                <div class="btn-group w-100" role="group">
                    <button type="submit" class="btn btn-primary">
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
            <?php if (!empty($search) || !empty($typeFilter) || !empty($dateFrom) || !empty($dateTo)): ?>
            <div class="col-md-12 mt-2">
                <a href="index.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Effacer les filtres
                </a>
                <small class="text-muted ms-2">
                    <?php echo $total; ?> résultat(s) trouvé(s)
                    <?php if (!empty($search)): ?>
                        pour "<?php echo htmlspecialchars($search); ?>"
                    <?php endif; ?>
                </small>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Tableau de l'historique -->
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">Liste des Opérations</h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($historique)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <p class="text-muted mt-3">
                    <?php echo (!empty($search) || !empty($typeFilter) || !empty($dateFrom) || !empty($dateTo)) 
                        ? 'Aucun résultat trouvé pour votre recherche' 
                        : 'Aucun historique enregistré'; ?>
                </p>
                <?php if (!empty($search) || !empty($typeFilter) || !empty($dateFrom) || !empty($dateTo)): ?>
                    <a href="index.php" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left me-2"></i>Voir tout l'historique
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Utilisateur</th>
                            <th>Action</th>
                            <th>Type</th>
                            <th>Détails</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historique as $h): ?>
                            <tr>
                                <td>
                                    <small><?php echo date('d/m/Y H:i', strtotime($h['date_action'])); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($h['username'] ?? '-'); ?></strong>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($h['action']); ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $h['type_action'] === 'creation' ? 'success' : 
                                            ($h['type_action'] === 'modification' ? 'warning' : 
                                            ($h['type_action'] === 'suppression' ? 'danger' : 'info')); 
                                    ?>">
                                        <?php 
                                        $typeLabels = [
                                            'creation' => 'Création',
                                            'modification' => 'Modification',
                                            'suppression' => 'Suppression',
                                            'paiement' => 'Paiement'
                                        ];
                                        echo htmlspecialchars($typeLabels[$h['type_action']] ?? $h['type_action']); 
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted"><?php echo htmlspecialchars($h['details'] ?? '-'); ?></small>
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
                                <a class="page-link" 
                                   href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($typeFilter); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>">
                                    Précédent
                                </a>
                            </li>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" 
                                       href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($typeFilter); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" 
                                   href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($typeFilter); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>">
                                    Suivant
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Fonction pour réinitialiser tous les champs du formulaire
function resetFilters() {
    const form = document.getElementById('searchForm');
    if (form) {
        // Vider tous les champs
        form.querySelector('input[name="search"]').value = '';
        form.querySelector('select[name="type"]').value = '';
        form.querySelector('input[name="date_from"]').value = '';
        form.querySelector('input[name="date_to"]').value = '';
        
        // Rediriger vers la page sans paramètres
        window.location.href = 'index.php';
    }
}

// Recherche automatique après 1 seconde d'inactivité
let searchTimeout;
const searchInput = document.getElementById('searchInput');
const searchForm = document.getElementById('searchForm');

if (searchInput && searchForm) {
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const searchValue = this.value.trim();
        
        // Si le champ est vide ou contient au moins 1 caractère, lancer la recherche après 1 seconde
        if (searchValue.length === 0 || searchValue.length >= 1) {
            searchTimeout = setTimeout(function() {
                // Si le champ a changé, soumettre le formulaire
                if (searchInput.value.trim() === searchValue) {
                    // Réinitialiser à la page 1 lors d'une nouvelle recherche
                    const form = searchForm;
                    const pageInput = document.createElement('input');
                    pageInput.type = 'hidden';
                    pageInput.name = 'page';
                    pageInput.value = '1';
                    form.appendChild(pageInput);
                    searchForm.submit();
                }
            }, 1000);
        }
    });
    
    // Permettre la recherche avec Entrée
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(searchTimeout);
            // Réinitialiser à la page 1
            const pageInput = document.createElement('input');
            pageInput.type = 'hidden';
            pageInput.name = 'page';
            pageInput.value = '1';
            searchForm.appendChild(pageInput);
            searchForm.submit();
        }
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>






