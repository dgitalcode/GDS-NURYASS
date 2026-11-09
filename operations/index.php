<?php
/**
 * Liste des opérations (Bons + Factures + Transactions Crédit)
 * GDS - Stock Management System
 */

require_once '../includes/session.php';
require_once '../config/database.php';

requireLogin();

$pageTitle = 'Dernières Opérations';

// Paramètres de recherche et filtrage
$search = $_GET['search'] ?? '';
$typeFilter = $_GET['type'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

try {
    $pdo = getDBConnection();
    
    // Construire la requête avec filtres
    $where = [];
    $params = [];
    
    // Requête de base pour toutes les opérations
    $baseQuery = "
        SELECT 
            'bon' as type_operation,
            b.id,
            b.numero_bon as numero,
            b.date_bon as date_op,
            b.total as montant,
            b.type_bon,
            b.statut_paiement,
            c.nom,
            c.prenom,
            c.nom_entreprise,
            c.type_client,
            NULL as type_transaction,
            b.client_id
        FROM bons b
        LEFT JOIN clients c ON b.client_id = c.id
        WHERE 1=1
    ";
    
    if (!empty($search)) {
        $baseQuery .= " AND (b.numero_bon LIKE :search_bon OR c.nom LIKE :search_bon OR c.nom_entreprise LIKE :search_bon)";
    }
    
    if ($typeFilter === 'bon_entree') {
        $baseQuery .= " AND b.type_bon = 'entree'";
    } elseif ($typeFilter === 'bon_sortie') {
        $baseQuery .= " AND b.type_bon = 'sortie'";
    }
    
    if (!empty($dateFrom)) {
        $baseQuery .= " AND DATE(b.date_bon) >= :date_from_bon";
    }
    
    if (!empty($dateTo)) {
        $baseQuery .= " AND DATE(b.date_bon) <= :date_to_bon";
    }
    
    $baseQuery .= "
        UNION ALL
        SELECT 
            'facture' as type_operation,
            f.id,
            f.numero_facture as numero,
            f.date_facture as date_op,
            f.total as montant,
            NULL as type_bon,
            NULL as statut_paiement,
            c.nom,
            c.prenom,
            c.nom_entreprise,
            c.type_client,
            NULL as type_transaction,
            f.client_id
        FROM factures f
        LEFT JOIN clients c ON f.client_id = c.id
        WHERE 1=1
    ";
    
    if (!empty($search)) {
        $baseQuery .= " AND (f.numero_facture LIKE :search_facture OR c.nom LIKE :search_facture OR c.nom_entreprise LIKE :search_facture)";
    }
    
    if ($typeFilter === 'facture') {
        // Déjà filtré par type_operation
    }
    
    if (!empty($dateFrom)) {
        $baseQuery .= " AND DATE(f.date_facture) >= :date_from_facture";
    }
    
    if (!empty($dateTo)) {
        $baseQuery .= " AND DATE(f.date_facture) <= :date_to_facture";
    }
    
    $baseQuery .= "
        UNION ALL
        SELECT 
            'credit' as type_operation,
            ct.id,
            CONCAT('TRANS-', ct.id) as numero,
            ct.date_transaction as date_op,
            ct.montant,
            NULL as type_bon,
            NULL as statut_paiement,
            c.nom,
            c.prenom,
            c.nom_entreprise,
            c.type_client,
            ct.type_transaction,
            ct.client_id
        FROM credits_transactions ct
        LEFT JOIN clients c ON ct.client_id = c.id
        WHERE 1=1
    ";
    
    if (!empty($search)) {
        $baseQuery .= " AND (c.nom LIKE :search_credit OR c.nom_entreprise LIKE :search_credit)";
    }
    
    if ($typeFilter === 'credit_ajout') {
        $baseQuery .= " AND ct.type_transaction = 'ajout'";
    } elseif ($typeFilter === 'credit_paiement') {
        $baseQuery .= " AND ct.type_transaction = 'paiement'";
    }
    
    if (!empty($dateFrom)) {
        $baseQuery .= " AND DATE(ct.date_transaction) >= :date_from_credit";
    }
    
    if (!empty($dateTo)) {
        $baseQuery .= " AND DATE(ct.date_transaction) <= :date_to_credit";
    }
    
    // Compter le total
    $countQuery = "SELECT COUNT(*) FROM (" . $baseQuery . ") as operations";
    $countStmt = $pdo->prepare($countQuery);
    
    if (!empty($search)) {
        $searchTerm = "%$search%";
        $countStmt->bindValue(':search_bon', $searchTerm);
        $countStmt->bindValue(':search_facture', $searchTerm);
        $countStmt->bindValue(':search_credit', $searchTerm);
    }
    
    if (!empty($dateFrom)) {
        $countStmt->bindValue(':date_from_bon', $dateFrom);
        $countStmt->bindValue(':date_from_facture', $dateFrom);
        $countStmt->bindValue(':date_from_credit', $dateFrom);
    }
    
    if (!empty($dateTo)) {
        $countStmt->bindValue(':date_to_bon', $dateTo);
        $countStmt->bindValue(':date_to_facture', $dateTo);
        $countStmt->bindValue(':date_to_credit', $dateTo);
    }
    
    $countStmt->execute();
    $totalOperations = $countStmt->fetchColumn();
    $totalPages = ceil($totalOperations / $perPage);
    
    // Récupérer les opérations avec pagination
    $query = $baseQuery . " ORDER BY date_op DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($query);
    
    if (!empty($search)) {
        $searchTerm = "%$search%";
        $stmt->bindValue(':search_bon', $searchTerm);
        $stmt->bindValue(':search_facture', $searchTerm);
        $stmt->bindValue(':search_credit', $searchTerm);
    }
    
    if (!empty($dateFrom)) {
        $stmt->bindValue(':date_from_bon', $dateFrom);
        $stmt->bindValue(':date_from_facture', $dateFrom);
        $stmt->bindValue(':date_from_credit', $dateFrom);
    }
    
    if (!empty($dateTo)) {
        $stmt->bindValue(':date_to_bon', $dateTo);
        $stmt->bindValue(':date_to_facture', $dateTo);
        $stmt->bindValue(':date_to_credit', $dateTo);
    }
    
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $operations = $stmt->fetchAll();
    
    // Calculer les totaux
    $totalEntrees = 0;
    $totalSorties = 0;
    foreach ($operations as $op) {
        $montant = floatval($op['montant']);
        if ($op['type_operation'] === 'bon' && $op['type_bon'] === 'entree') {
            $totalEntrees += $montant;
        } elseif ($op['type_operation'] === 'bon' && $op['type_bon'] === 'sortie') {
            $totalSorties += $montant;
        } elseif ($op['type_operation'] === 'facture') {
            $totalEntrees += $montant;
        } elseif ($op['type_operation'] === 'credit') {
            if ($op['type_transaction'] === 'ajout') {
                $totalSorties += $montant;
            } elseif ($op['type_transaction'] === 'paiement') {
                $totalEntrees += $montant;
            }
        }
    }
    
} catch (PDOException $e) {
    error_log("Erreur operations index: " . $e->getMessage());
    $operations = [];
    $totalOperations = 0;
    $totalPages = 0;
    $totalEntrees = 0;
    $totalSorties = 0;
}

require_once '../includes/header.php';
?>

<div class="top-bar">
    <div>
        <h1 class="page-title">Dernières Opérations</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo url('dashboard.php'); ?>">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Opérations</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Statistiques -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Total Entrées</h6>
                        <h3 class="mb-0"><?php echo number_format($totalEntrees, 2); ?> DH</h3>
                    </div>
                    <i class="bi bi-arrow-down-circle fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Total Sorties</h6>
                        <h3 class="mb-0"><?php echo number_format($totalSorties, 2); ?> DH</h3>
                    </div>
                    <i class="bi bi-arrow-up-circle fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Solde Net</h6>
                        <h3 class="mb-0"><?php echo number_format($totalEntrees - $totalSorties, 2); ?> DH</h3>
                    </div>
                    <i class="bi bi-calculator fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtres et recherche -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" 
                           class="form-control" 
                           name="search" 
                           placeholder="Rechercher par numéro, client..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="type">
                    <option value="">Tous les types</option>
                    <option value="bon_entree" <?php echo $typeFilter === 'bon_entree' ? 'selected' : ''; ?>>Bon Entrée</option>
                    <option value="bon_sortie" <?php echo $typeFilter === 'bon_sortie' ? 'selected' : ''; ?>>Bon Sortie</option>
                    <option value="facture" <?php echo $typeFilter === 'facture' ? 'selected' : ''; ?>>Facture</option>
                    <option value="credit_ajout" <?php echo $typeFilter === 'credit_ajout' ? 'selected' : ''; ?>>Crédit Ajouté</option>
                    <option value="credit_paiement" <?php echo $typeFilter === 'credit_paiement' ? 'selected' : ''; ?>>Paiement Crédit</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" placeholder="Du">
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" placeholder="Au">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100">
                    <i class="bi bi-funnel me-2"></i>Filtrer
                </button>
            </div>
            <?php if (!empty($search) || !empty($typeFilter) || !empty($dateFrom) || !empty($dateTo)): ?>
            <div class="col-md-12">
                <a href="index.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Effacer les filtres
                </a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Tableau des opérations -->
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">Liste des Opérations</h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($operations)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <p class="text-muted mt-3">Aucune opération trouvée</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>N°</th>
                            <th>Client</th>
                            <th>Montant</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($operations as $op): ?>
                            <?php
                            // Déterminer le signe et la couleur selon le type d'opération
                            $montant = floatval($op['montant']);
                            $isPositive = false;
                            $badgeClass = '';
                            $typeLabel = '';
                            $linkUrl = '';
                            
                            if ($op['type_operation'] === 'bon') {
                                if ($op['type_bon'] === 'entree') {
                                    $isPositive = true;
                                    $badgeClass = 'success';
                                    $typeLabel = 'Bon Entrée';
                                } else {
                                    $isPositive = false;
                                    $badgeClass = 'danger';
                                    $typeLabel = 'Bon Sortie';
                                }
                                $linkUrl = url('bons/view.php?id=' . $op['id']);
                            } elseif ($op['type_operation'] === 'facture') {
                                $isPositive = true;
                                $badgeClass = 'info';
                                $typeLabel = 'Facture';
                                $linkUrl = url('factures/view.php?id=' . $op['id']);
                            } elseif ($op['type_operation'] === 'credit') {
                                if ($op['type_transaction'] === 'ajout') {
                                    $isPositive = false;
                                    $badgeClass = 'warning';
                                    $typeLabel = 'Crédit Ajouté';
                                } elseif ($op['type_transaction'] === 'paiement') {
                                    $isPositive = true;
                                    $badgeClass = 'success';
                                    $typeLabel = 'Paiement Crédit';
                                } else {
                                    $isPositive = true;
                                    $badgeClass = 'secondary';
                                    $typeLabel = 'Annulation';
                                }
                                $linkUrl = url('credits/view.php?id=' . $op['client_id']);
                            }
                            
                            $clientName = '';
                            if ($op['type_client'] === 'entreprise') {
                                $clientName = htmlspecialchars($op['nom_entreprise'] ?? $op['nom'] ?? '-');
                            } else {
                                $clientName = htmlspecialchars(trim(($op['nom'] ?? '') . ' ' . ($op['prenom'] ?? '')) ?: '-');
                            }
                            ?>
                            <tr>
                                <td>
                                    <small><?php echo date('d/m/Y H:i', strtotime($op['date_op'])); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $badgeClass; ?>">
                                        <?php echo $typeLabel; ?>
                                    </span>
                                </td>
                                <td>
                                    <strong>#<?php echo htmlspecialchars($op['numero']); ?></strong>
                                </td>
                                <td>
                                    <small><?php echo $clientName; ?></small>
                                </td>
                                <td>
                                    <strong class="text-<?php echo $isPositive ? 'success' : 'danger'; ?>">
                                        <?php echo $isPositive ? '+' : '-'; ?>
                                        <?php echo number_format($montant, 2); ?> DH
                                    </strong>
                                </td>
                                <td>
                                    <?php if ($linkUrl): ?>
                                        <a href="<?php echo $linkUrl; ?>" class="btn btn-sm btn-outline-info" title="Voir">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    <?php endif; ?>
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
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($typeFilter); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>">Précédent</a>
                            </li>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($typeFilter); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($typeFilter); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>">Suivant</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>





