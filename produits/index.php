<?php
/**
 * Liste des produits
 * GDS - Stock Management System
 */

require_once '../includes/session.php';
require_once '../config/database.php';

requireLogin();

$pageTitle = 'Gestion des Produits';

// Paramètres de recherche et filtrage
$search = $_GET['search'] ?? '';
$typeFilter = intval($_GET['type'] ?? 0);
$couleurFilter = intval($_GET['couleur'] ?? 0);
$stockFilter = $_GET['stock'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

try {
    $pdo = getDBConnection();
    
    // Récupérer les types et couleurs pour les filtres
    $types = $pdo->query("SELECT * FROM types_produits ORDER BY nom_type")->fetchAll();
    $couleurs = $pdo->query("SELECT * FROM couleurs ORDER BY nom_couleur")->fetchAll();
    
    // Construire la requête avec filtres
    $where = ["p.actif = 1"];
    $params = [];
    
    if (!empty($search)) {
        $searchTerm = trim($search);
        $where[] = "(LOWER(p.nom_produit) LIKE LOWER(:search_nom) OR LOWER(p.description) LIKE LOWER(:search_desc))";
        $params['search_nom'] = "%$searchTerm%";
        $params['search_desc'] = "%$searchTerm%";
    }
    
    if ($typeFilter > 0) {
        $where[] = "p.type_id = :type_id";
        $params['type_id'] = $typeFilter;
    }
    
    if ($couleurFilter > 0) {
        $where[] = "p.couleur_id = :couleur_id";
        $params['couleur_id'] = $couleurFilter;
    }
    
    if ($stockFilter === 'faible') {
        $where[] = "p.stock <= p.stock_minimum";
    } elseif ($stockFilter === 'rupture') {
        $where[] = "p.stock = 0";
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Compter le total
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM produits p 
        WHERE $whereClause
    ");
    $countStmt->execute($params);
    $totalProduits = $countStmt->fetchColumn();
    $totalPages = ceil($totalProduits / $perPage);
    
    // Récupérer les produits
    $stmt = $pdo->prepare("
        SELECT p.*, 
               tp.nom_type,
               c.nom_couleur,
               c.code_couleur
        FROM produits p
        LEFT JOIN types_produits tp ON p.type_id = tp.id
        LEFT JOIN couleurs c ON p.couleur_id = c.id
        WHERE $whereClause
        ORDER BY p.nom_produit ASC
        LIMIT :limit OFFSET :offset
    ");
    
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $produits = $stmt->fetchAll();
    
    // Statistiques
    $stats = [
        'total' => $pdo->query("SELECT COUNT(*) FROM produits WHERE actif = 1")->fetchColumn(),
        'stock_faible' => $pdo->query("SELECT COUNT(*) FROM produits WHERE stock <= stock_minimum AND actif = 1")->fetchColumn(),
        'rupture' => $pdo->query("SELECT COUNT(*) FROM produits WHERE stock = 0 AND actif = 1")->fetchColumn(),
        'valeur_stock' => $pdo->query("SELECT COALESCE(SUM(prix * stock), 0) FROM produits WHERE actif = 1")->fetchColumn()
    ];
    
} catch (PDOException $e) {
    error_log("Erreur produits index: " . $e->getMessage());
    $produits = [];
    $types = [];
    $couleurs = [];
    $totalProduits = 0;
    $totalPages = 0;
    $stats = ['total' => 0, 'stock_faible' => 0, 'rupture' => 0, 'valeur_stock' => 0];
}

require_once '../includes/header.php';
?>

<div class="top-bar">
    <div>
        <h1 class="page-title">Gestion des Produits</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo url('dashboard.php'); ?>">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Produits</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="create.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Nouveau Produit
        </a>
    </div>
</div>

<!-- Statistiques -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Total Produits</h6>
                        <h3 class="mb-0"><?php echo number_format($stats['total']); ?></h3>
                    </div>
                    <i class="bi bi-box fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Stock Faible</h6>
                        <h3 class="mb-0"><?php echo number_format($stats['stock_faible']); ?></h3>
                    </div>
                    <i class="bi bi-exclamation-triangle fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Rupture</h6>
                        <h3 class="mb-0"><?php echo number_format($stats['rupture']); ?></h3>
                    </div>
                    <i class="bi bi-x-circle fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Valeur Stock</h6>
                        <h3 class="mb-0"><?php echo number_format($stats['valeur_stock'], 0); ?> DH</h3>
                    </div>
                    <i class="bi bi-currency-exchange fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtres et recherche -->
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
                           placeholder="Rechercher un produit (nom ou description)..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           autocomplete="off">
                </div>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="type">
                    <option value="">Tous les types</option>
                    <?php foreach ($types as $type): ?>
                        <option value="<?php echo $type['id']; ?>" <?php echo $typeFilter == $type['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type['nom_type']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="couleur">
                    <option value="">Toutes les couleurs</option>
                    <?php foreach ($couleurs as $couleur): ?>
                        <option value="<?php echo $couleur['id']; ?>" <?php echo $couleurFilter == $couleur['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($couleur['nom_couleur']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="stock">
                    <option value="">Tous</option>
                    <option value="faible" <?php echo $stockFilter === 'faible' ? 'selected' : ''; ?>>Stock Faible</option>
                    <option value="rupture" <?php echo $stockFilter === 'rupture' ? 'selected' : ''; ?>>Rupture</option>
                </select>
            </div>
            <div class="col-md-2">
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
                <a href="index.php?type=<?php echo $typeFilter; ?>&couleur=<?php echo $couleurFilter; ?>&stock=<?php echo urlencode($stockFilter); ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Effacer la recherche
                </a>
                <small class="text-muted ms-2">
                    <?php echo $totalProduits; ?> résultat(s) trouvé(s) pour "<?php echo htmlspecialchars($search); ?>"
                </small>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Tableau des produits -->
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">Liste des Produits</h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($produits)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <p class="text-muted mt-3">Aucun produit trouvé</p>
                <a href="create.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Ajouter le premier produit
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Produit</th>
                            <th>Type</th>
                            <th>Couleur</th>
                            <th>Prix</th>
                            <th>Stock</th>
                            <th>Valeur</th>
                            <th width="150">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produits as $produit): ?>
                            <?php 
                            $stockPourcentage = $produit['stock_minimum'] > 0 ? ($produit['stock'] / $produit['stock_minimum']) * 100 : 100;
                            $stockClass = $produit['stock'] == 0 ? 'danger' : ($produit['stock'] <= $produit['stock_minimum'] ? 'warning' : 'success');
                            $valeurStock = $produit['prix'] * $produit['stock'];
                            ?>
                            <tr>
                                <td><strong>#<?php echo $produit['id']; ?></strong></td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($produit['nom_produit']); ?></strong>
                                        <?php if ($produit['description']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($produit['description'], 0, 50)); ?>...</small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo htmlspecialchars($produit['nom_type'] ?? '-'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge" style="background-color: <?php echo htmlspecialchars($produit['code_couleur'] ?? '#6c757d'); ?>; color: white;">
                                        <?php echo htmlspecialchars($produit['nom_couleur'] ?? '-'); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo number_format($produit['prix'], 2); ?> DH</strong>
                                </td>
                                <td>
                                    <div>
                                        <strong class="text-<?php echo $stockClass; ?>">
                                            <?php echo number_format($produit['stock']); ?>
                                        </strong>
                                        <?php if ($produit['stock_minimum'] > 0): ?>
                                            <small class="text-muted">/ <?php echo $produit['stock_minimum']; ?> min</small>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($produit['stock'] <= $produit['stock_minimum'] && $produit['stock'] > 0): ?>
                                        <div class="progress mt-1" style="height: 4px;">
                                            <div class="progress-bar bg-warning" style="width: <?php echo min(100, $stockPourcentage); ?>%"></div>
                                        </div>
                                    <?php elseif ($produit['stock'] == 0): ?>
                                        <span class="badge bg-danger">Rupture</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo number_format($valeurStock, 2); ?> DH</strong>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="view.php?id=<?php echo $produit['id']; ?>" 
                                           class="btn btn-outline-info" 
                                           title="Voir">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo $produit['id']; ?>" 
                                           class="btn btn-outline-primary" 
                                           title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="delete.php?id=<?php echo $produit['id']; ?>" 
                                           class="btn btn-outline-danger" 
                                           title="Supprimer"
                                           onclick="return confirmDelete(event, 'Êtes-vous sûr de vouloir supprimer ce produit ?', 'Supprimer le produit')">
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
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo $typeFilter; ?>&couleur=<?php echo $couleurFilter; ?>&stock=<?php echo urlencode($stockFilter); ?>">Précédent</a>
                            </li>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo $typeFilter; ?>&couleur=<?php echo $couleurFilter; ?>&stock=<?php echo urlencode($stockFilter); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo $typeFilter; ?>&couleur=<?php echo $couleurFilter; ?>&stock=<?php echo urlencode($stockFilter); ?>">Suivant</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Recherche automatique après 2 secondes d'inactivité
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
        form.querySelector('select[name="couleur"]').value = '';
        form.querySelector('select[name="stock"]').value = '';
        
        // Rediriger vers la page sans paramètres
        window.location.href = 'index.php';
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>

