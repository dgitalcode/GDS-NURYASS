<?php
/**
 * Liste des crédits
 * GDS - Stock Management System
 */

require_once '../includes/session.php';
require_once '../config/database.php';

requireLogin('../login.php');

$pageTitle = 'Gestion des Crédits';

// Paramètres de recherche
$search = trim($_GET['search'] ?? '');
$clientFilter = intval($_GET['client_id'] ?? 0);

try {
    $pdo = getDBConnection();
    
    // Construire la requête avec filtres
    $where = ["cr.statut = 'actif'"];
    $params = [];
    
    // Recherche insensible à la casse (majuscules/minuscules)
    if (!empty($search)) {
        $searchTerm = trim($search);
        $where[] = "(LOWER(c.nom) LIKE LOWER(:search_nom) OR 
                     LOWER(c.prenom) LIKE LOWER(:search_prenom) OR 
                     LOWER(c.nom_entreprise) LIKE LOWER(:search_entreprise) OR
                     LOWER(c.telephone) LIKE LOWER(:search_tel) OR
                     LOWER(c.email) LIKE LOWER(:search_email) OR
                     LOWER(c.cin) LIKE LOWER(:search_cin) OR
                     LOWER(c.patente) LIKE LOWER(:search_patente))";
        $params['search_nom'] = "%$searchTerm%";
        $params['search_prenom'] = "%$searchTerm%";
        $params['search_entreprise'] = "%$searchTerm%";
        $params['search_tel'] = "%$searchTerm%";
        $params['search_email'] = "%$searchTerm%";
        $params['search_cin'] = "%$searchTerm%";
        $params['search_patente'] = "%$searchTerm%";
    }
    
    if ($clientFilter > 0) {
        $where[] = "cr.client_id = :client_id";
        $params['client_id'] = $clientFilter;
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Requête principale
    $stmt = $pdo->prepare("
        SELECT cr.*, 
               c.nom, c.prenom, c.nom_entreprise, c.type_client,
               c.telephone, c.email, c.cin, c.patente
        FROM credits cr 
        LEFT JOIN clients c ON cr.client_id = c.id 
        WHERE $whereClause 
        ORDER BY cr.montant_actuel DESC
    ");
    
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    
    $stmt->execute();
    $credits = $stmt->fetchAll();
    
    // Récupérer tous les clients pour le filtre
    $clients = $pdo->query("
        SELECT id, nom, prenom, nom_entreprise, type_client 
        FROM clients 
        WHERE actif = 1 
        ORDER BY nom
    ")->fetchAll();
    
    // Total des crédits
    $totalCredits = $pdo->query("
        SELECT COALESCE(SUM(montant_actuel), 0) 
        FROM credits 
        WHERE statut = 'actif'
    ")->fetchColumn();
    
} catch (PDOException $e) {
    error_log("Erreur credits: " . $e->getMessage());
    $credits = [];
    $clients = [];
    $totalCredits = 0;
}

require_once '../includes/header.php';
?>
<div class="top-bar">
    <div>
        <h1 class="page-title">Gestion des Crédits</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo url('dashboard.php'); ?>">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Crédits</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Statistiques -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Total Crédits</h6>
                        <h3 class="mb-0"><?php echo number_format($totalCredits, 2); ?> DH</h3>
                    </div>
                    <i class="bi bi-credit-card fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Clients avec Crédit</h6>
                        <h3 class="mb-0"><?php echo number_format(count($credits)); ?></h3>
                    </div>
                    <i class="bi bi-people fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Formulaire de recherche -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3" id="searchForm">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" 
                           class="form-control" 
                           name="search" 
                           id="searchInput"
                           placeholder="Rechercher par nom, prénom, entreprise, téléphone, email, CIN, patente..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           autocomplete="off">
                </div>
            </div>
            <div class="col-md-4">
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
                    <?php echo count($credits); ?> résultat(s) trouvé(s) pour "<?php echo htmlspecialchars($search); ?>"
                </small>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Tableau des crédits -->
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">Liste des Crédits</h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($credits)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <p class="text-muted mt-3">
                    <?php echo !empty($search) ? 'Aucun résultat trouvé pour votre recherche' : 'Aucun crédit actif'; ?>
                </p>
                <?php if (!empty($search)): ?>
                    <a href="index.php" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left me-2"></i>Voir tous les crédits
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Client</th>
                            <th>Contact</th>
                            <th>Crédit Actuel</th>
                            <th>Maximum</th>
                            <th>%</th>
                            <th width="150">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($credits as $credit): ?>
                            <?php 
                            $pourcentage = $credit['max_montant'] > 0 ? ($credit['montant_actuel'] / $credit['max_montant']) * 100 : 0;
                            $clientName = '';
                            if ($credit['type_client'] === 'entreprise') {
                                $clientName = htmlspecialchars($credit['nom_entreprise'] ?? $credit['nom'] ?? '-');
                            } else {
                                $clientName = htmlspecialchars(trim(($credit['nom'] ?? '') . ' ' . ($credit['prenom'] ?? '')) ?: '-');
                            }
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo $clientName; ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <?php if ($credit['type_client'] === 'entreprise' && !empty($credit['patente'])): ?>
                                            <i class="bi bi-building me-1"></i>Patente: <?php echo htmlspecialchars($credit['patente']); ?>
                                        <?php elseif ($credit['type_client'] === 'personne' && !empty($credit['cin'])): ?>
                                            <i class="bi bi-person me-1"></i>CIN: <?php echo htmlspecialchars($credit['cin']); ?>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if ($credit['telephone']): ?>
                                        <div><i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($credit['telephone']); ?></div>
                                    <?php endif; ?>
                                    <?php if ($credit['email']): ?>
                                        <div><i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($credit['email']); ?></div>
                                    <?php endif; ?>
                                    <?php if (!$credit['telephone'] && !$credit['email']): ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong class="text-danger"><?php echo number_format($credit['montant_actuel'], 2); ?> DH</strong>
                                </td>
                                <td><?php echo number_format($credit['max_montant'], 2); ?> DH</td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-<?php echo $pourcentage >= 80 ? 'danger' : ($pourcentage >= 50 ? 'warning' : 'success'); ?>" 
                                             style="width: <?php echo min(100, $pourcentage); ?>%">
                                            <?php echo number_format($pourcentage, 1); ?>%
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="view.php?id=<?php echo $credit['id']; ?>" 
                                           class="btn btn-outline-primary" 
                                           title="Voir">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="paiement.php?id=<?php echo $credit['id']; ?>" 
                                           class="btn btn-outline-success" 
                                           title="Paiement">
                                            <i class="bi bi-cash"></i>
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

<script>
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
            searchForm.submit();
        }
    });
}

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
</script>

<?php require_once '../includes/footer.php'; ?>






