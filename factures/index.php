<?php
/**
 * Liste des factures
 * GDS - Stock Management System
 */

require_once '../includes/session.php';
require_once '../config/database.php';

requireLogin('../login.php');

$pageTitle = 'Gestion des Factures';

// Paramètres de recherche
$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

try {
    $pdo = getDBConnection();
    
    // Construire la requête avec filtres
    $where = ["1=1"];
    $params = [];
    
    // Recherche par nom de client (insensible à la casse - minuscules/majuscules)
    if (!empty($search)) {
        $searchTerm = trim($search);
        // Recherche insensible à la casse dans les noms de clients
        // Utilisation de LOWER() pour garantir l'insensibilité à la casse
        $where[] = "(LOWER(c.nom) LIKE LOWER(:search_nom) OR 
                     LOWER(c.prenom) LIKE LOWER(:search_prenom) OR 
                     LOWER(c.nom_entreprise) LIKE LOWER(:search_entreprise) OR
                     LOWER(CONCAT(c.nom, ' ', IFNULL(c.prenom, ''))) LIKE LOWER(:search_fullname))";
        $params['search_nom'] = "%$searchTerm%";
        $params['search_prenom'] = "%$searchTerm%";
        $params['search_entreprise'] = "%$searchTerm%";
        $params['search_fullname'] = "%$searchTerm%";
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Compter le total
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM factures f 
        LEFT JOIN clients c ON f.client_id = c.id 
        WHERE $whereClause
    ");
    foreach ($params as $key => $value) {
        $countStmt->bindValue(":$key", $value);
    }
    $countStmt->execute();
    $totalFactures = $countStmt->fetchColumn();
    $totalPages = ceil($totalFactures / $perPage);
    
    // Récupérer les factures
    $stmt = $pdo->prepare("
        SELECT f.*, 
               c.nom, c.prenom, c.nom_entreprise, c.type_client, 
               u.username 
        FROM factures f 
        LEFT JOIN clients c ON f.client_id = c.id 
        LEFT JOIN users u ON f.user_id = u.id 
        WHERE $whereClause 
        ORDER BY f.date_facture DESC 
        LIMIT :limit OFFSET :offset
    ");
    
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $factures = $stmt->fetchAll();
    
    // Total des ventes
    $totalVentes = $pdo->query("
        SELECT COALESCE(SUM(total), 0) 
        FROM factures
    ")->fetchColumn();
    
} catch (PDOException $e) {
    error_log("Erreur factures: " . $e->getMessage());
    $factures = [];
    $totalFactures = 0;
    $totalPages = 0;
    $totalVentes = 0;
}

require_once '../includes/header.php';
?>
<div class="top-bar">
    <div>
        <h1 class="page-title">Gestion des Factures</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo url('dashboard.php'); ?>">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Factures</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="create.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Nouvelle Facture
        </a>
    </div>
</div>

<!-- Statistiques -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Total Factures</h6>
                        <h3 class="mb-0"><?php echo number_format($totalFactures); ?></h3>
                    </div>
                    <i class="bi bi-file-earmark-text fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Total Ventes</h6>
                        <h3 class="mb-0"><?php echo number_format($totalVentes, 2); ?> DH</h3>
                    </div>
                    <i class="bi bi-currency-exchange fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Formulaire de recherche -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3" id="searchForm">
            <div class="col-md-10">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" 
                           class="form-control" 
                           name="search" 
                           id="searchInput"
                           placeholder="Rechercher par nom de client (insensible majuscules/minuscules)" 
                           value="<?php echo htmlspecialchars($search); ?>"
                           autocomplete="off">
                </div>
            </div>
            <div class="col-md-2">
                <div class="btn-group w-100" role="group">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search me-2"></i>Rechercher
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
                <a href="index.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Effacer la recherche
                </a>
                <small class="text-muted ms-2">
                    <?php echo $totalFactures; ?> résultat(s) trouvé(s) pour "<?php echo htmlspecialchars($search); ?>"
                </small>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Tableau des factures -->
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">Liste des Factures</h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($factures)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <p class="text-muted mt-3">
                    <?php echo !empty($search) ? 'Aucun résultat trouvé pour votre recherche' : 'Aucune facture enregistrée'; ?>
                </p>
                <?php if (empty($search)): ?>
                    <a href="create.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Créer la première facture
                    </a>
                <?php else: ?>
                    <a href="index.php" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left me-2"></i>Voir toutes les factures
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>N° Facture</th>
                            <th>Client</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Créée par</th>
                            <th width="150">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($factures as $facture): ?>
                            <?php
                            $clientName = '';
                            if ($facture['type_client'] === 'entreprise') {
                                $clientName = htmlspecialchars($facture['nom_entreprise'] ?? $facture['nom'] ?? '-');
                            } else {
                                $clientName = htmlspecialchars(trim(($facture['nom'] ?? '') . ' ' . ($facture['prenom'] ?? '')) ?: '-');
                            }
                            ?>
                            <tr>
                                <td>
                                    <strong>#<?php echo htmlspecialchars($facture['numero_facture']); ?></strong>
                                </td>
                                <td><?php echo $clientName; ?></td>
                                <td>
                                    <small><?php echo date('d/m/Y', strtotime($facture['date_facture'])); ?></small>
                                </td>
                                <td>
                                    <strong class="text-success"><?php echo number_format($facture['total'], 2); ?> DH</strong>
                                </td>
                                <td>
                                    <small><?php echo htmlspecialchars($facture['username'] ?? '-'); ?></small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="view.php?id=<?php echo $facture['id']; ?>" 
                                           class="btn btn-outline-info" 
                                           title="Voir">
                                            <i class="bi bi-eye"></i>
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
                                <a class="page-link" 
                                   href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">
                                    Précédent
                                </a>
                            </li>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" 
                                       href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" 
                                   href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">
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
        // Vider le champ de recherche
        form.querySelector('input[name="search"]').value = '';
        
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






