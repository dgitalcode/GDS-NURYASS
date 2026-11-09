<?php
/**
 * Liste des bons
 * GDS - Stock Management System
 */

require_once '../includes/session.php';
require_once '../config/database.php';

requireLogin('../login.php');

$pageTitle = 'Gestion des Bons';

// Paramètres de recherche et filtrage
$search = trim($_GET['search'] ?? '');
$typeFilter = $_GET['type'] ?? '';
$statutFilter = $_GET['statut'] ?? '';
$clientFilter = intval($_GET['client_id'] ?? 0);
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

try {
    $pdo = getDBConnection();
    
    // Construire la requête avec filtres
    $where = ["1=1"];
    $params = [];
    
    // Recherche insensible à la casse (majuscules/minuscules)
    if (!empty($search)) {
        $searchTerm = trim($search);
        $where[] = "(LOWER(b.numero_bon) LIKE LOWER(:search_numero) OR 
                     LOWER(c.nom) LIKE LOWER(:search_nom) OR 
                     LOWER(c.prenom) LIKE LOWER(:search_prenom) OR 
                     LOWER(c.nom_entreprise) LIKE LOWER(:search_entreprise))";
        $params['search_numero'] = "%$searchTerm%";
        $params['search_nom'] = "%$searchTerm%";
        $params['search_prenom'] = "%$searchTerm%";
        $params['search_entreprise'] = "%$searchTerm%";
    }
    
    if (!empty($typeFilter)) {
        $where[] = "b.type_bon = :type_bon";
        $params['type_bon'] = $typeFilter;
    }
    
    if (!empty($statutFilter)) {
        $where[] = "b.statut_paiement = :statut_paiement";
        $params['statut_paiement'] = $statutFilter;
    }
    
    if ($clientFilter > 0) {
        $where[] = "b.client_id = :client_id";
        $params['client_id'] = $clientFilter;
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Compter le total
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM bons b
        LEFT JOIN clients c ON b.client_id = c.id
        WHERE $whereClause
    ");
    $countStmt->execute($params);
    $totalBons = $countStmt->fetchColumn();
    $totalPages = ceil($totalBons / $perPage);
    
    // Récupérer les bons
    $stmt = $pdo->prepare("
        SELECT b.*, 
               c.nom, c.prenom, c.nom_entreprise, c.type_client,
               u.username as created_by
        FROM bons b
        LEFT JOIN clients c ON b.client_id = c.id
        LEFT JOIN users u ON b.user_id = u.id
        WHERE $whereClause
        ORDER BY b.date_bon DESC
        LIMIT :limit OFFSET :offset
    ");
    
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $bons = $stmt->fetchAll();
    
    // Récupérer les clients pour le filtre
    $clients = $pdo->query("SELECT id, nom, prenom, nom_entreprise, type_client FROM clients WHERE actif = 1 ORDER BY nom")->fetchAll();
    
    // Statistiques
    $stats = [
        'total' => $pdo->query("SELECT COUNT(*) FROM bons")->fetchColumn(),
        'non_payes' => $pdo->query("SELECT COUNT(*) FROM bons WHERE statut_paiement = 'non_paye'")->fetchColumn(),
        'entrees' => $pdo->query("SELECT COUNT(*) FROM bons WHERE type_bon = 'entree'")->fetchColumn(),
        'sorties' => $pdo->query("SELECT COUNT(*) FROM bons WHERE type_bon = 'sortie'")->fetchColumn()
    ];
    
} catch (PDOException $e) {
    error_log("Erreur bons index: " . $e->getMessage());
    $bons = [];
    $clients = [];
    $totalBons = 0;
    $totalPages = 0;
    $stats = ['total' => 0, 'non_payes' => 0, 'entrees' => 0, 'sorties' => 0];
}

require_once '../includes/header.php';
?>

<div class="top-bar">
    <div>
        <h1 class="page-title">Gestion des Bons</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo url('dashboard.php'); ?>">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Bons</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="create.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Nouveau Bon
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
                        <h6 class="mb-0">Total Bons</h6>
                        <h3 class="mb-0"><?php echo number_format($stats['total']); ?></h3>
                    </div>
                    <i class="bi bi-receipt fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Non Payés</h6>
                        <h3 class="mb-0"><?php echo number_format($stats['non_payes']); ?></h3>
                    </div>
                    <i class="bi bi-exclamation-triangle fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Entrées</h6>
                        <h3 class="mb-0"><?php echo number_format($stats['entrees']); ?></h3>
                    </div>
                    <i class="bi bi-arrow-down-circle fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Sorties</h6>
                        <h3 class="mb-0"><?php echo number_format($stats['sorties']); ?></h3>
                    </div>
                    <i class="bi bi-arrow-up-circle fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3" id="searchForm">
            <div class="col-md-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" 
                           class="form-control" 
                           name="search" 
                           id="searchInput"
                           placeholder="Rechercher par numéro, client..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           autocomplete="off">
                </div>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="type">
                    <option value="">Tous les types</option>
                    <option value="entree" <?php echo $typeFilter === 'entree' ? 'selected' : ''; ?>>Entrée</option>
                    <option value="sortie" <?php echo $typeFilter === 'sortie' ? 'selected' : ''; ?>>Sortie</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="statut">
                    <option value="">Tous les statuts</option>
                    <option value="paye" <?php echo $statutFilter === 'paye' ? 'selected' : ''; ?>>Payé</option>
                    <option value="non_paye" <?php echo $statutFilter === 'non_paye' ? 'selected' : ''; ?>>Non Payé</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="client_id">
                    <option value="">Tous les clients</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?php echo $client['id']; ?>" <?php echo $clientFilter == $client['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($client['type_client'] === 'entreprise' ? ($client['nom_entreprise'] ?? $client['nom']) : ($client['nom'] . ' ' . ($client['prenom'] ?? ''))); ?>
                        </option>
                    <?php endforeach; ?>
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
            <?php if (!empty($search) || !empty($typeFilter) || !empty($statutFilter) || $clientFilter > 0): ?>
            <div class="col-md-12 mt-2">
                <a href="index.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Effacer les filtres
                </a>
                <?php if (!empty($search)): ?>
                    <small class="text-muted ms-2">
                        <?php echo $totalBons; ?> résultat(s) trouvé(s) pour "<?php echo htmlspecialchars($search); ?>"
                    </small>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Tableau des bons -->
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">Liste des Bons</h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($bons)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <p class="text-muted mt-3">Aucun bon trouvé</p>
                <a href="create.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Créer le premier bon
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>N° Bon</th>
                            <th>Client</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Statut</th>
                            <th>Créé par</th>
                            <th width="150">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bons as $bon): ?>
                            <tr>
                                <td><strong>#<?php echo htmlspecialchars($bon['numero_bon']); ?></strong></td>
                                <td>
                                    <?php 
                                    if ($bon['type_client'] === 'entreprise') {
                                        echo htmlspecialchars($bon['nom_entreprise'] ?? $bon['nom']);
                                    } else {
                                        echo htmlspecialchars($bon['nom'] . ' ' . ($bon['prenom'] ?? ''));
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $bon['type_bon'] === 'entree' ? 'success' : 'primary'; ?>">
                                        <?php echo $bon['type_bon'] === 'entree' ? 'Entrée' : 'Sortie'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($bon['date_bon'])); ?></td>
                                <td><strong><?php echo number_format($bon['total'], 2); ?> DH</strong></td>
                                <td>
                                    <span class="badge bg-<?php echo $bon['statut_paiement'] === 'paye' ? 'success' : 'warning'; ?>">
                                        <?php echo $bon['statut_paiement'] === 'paye' ? 'Payé' : 'Non Payé'; ?>
                                    </span>
                                </td>
                                <td><small><?php echo htmlspecialchars($bon['created_by'] ?? '-'); ?></small></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="view.php?id=<?php echo $bon['id']; ?>" class="btn btn-outline-info" title="Voir">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo $bon['id']; ?>" class="btn btn-outline-primary" title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="delete.php?id=<?php echo $bon['id']; ?>" class="btn btn-outline-danger" title="Supprimer" onclick="return confirmDelete(event, 'Êtes-vous sûr de vouloir supprimer ce bon ?', 'Supprimer le bon')">
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
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($typeFilter); ?>&statut=<?php echo urlencode($statutFilter); ?>&client_id=<?php echo $clientFilter; ?>">Précédent</a>
                            </li>
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($typeFilter); ?>&statut=<?php echo urlencode($statutFilter); ?>&client_id=<?php echo $clientFilter; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($typeFilter); ?>&statut=<?php echo urlencode($statutFilter); ?>&client_id=<?php echo $clientFilter; ?>">Suivant</a>
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
        form.querySelector('select[name="statut"]').value = '';
        form.querySelector('select[name="client_id"]').value = '';
        
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

