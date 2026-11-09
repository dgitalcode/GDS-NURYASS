<?php
/**
 * Dashboard - Page d'accueil
 * GDS - Stock Management System
 */

require_once 'includes/session.php';
require_once 'config/database.php';

// Vérifier la connexion
requireLogin('login.php');

$pageTitle = 'Dashboard';

// Récupérer les statistiques
try {
    $pdo = getDBConnection();
    
    // Nombre de clients
    $stmt = $pdo->query("SELECT COUNT(*) FROM clients WHERE actif = 1");
    $totalClients = $stmt->fetchColumn();
    
    // Nombre de produits
    $stmt = $pdo->query("SELECT COUNT(*) FROM produits WHERE actif = 1");
    $totalProduits = $stmt->fetchColumn();
    
    // Produits en stock faible (inférieur au stock minimum)
    $stmt = $pdo->query("SELECT COUNT(*) FROM produits WHERE stock <= stock_minimum AND actif = 1");
    $produitsStockFaible = $stmt->fetchColumn();
    
    // Nombre de bons non payés (crédits)
    $stmt = $pdo->query("SELECT COUNT(*) FROM bons WHERE statut_paiement = 'non_paye'");
    $bonsNonPayes = $stmt->fetchColumn();
    
    // Montant total des crédits
    $stmt = $pdo->query("SELECT COALESCE(SUM(montant_actuel), 0) FROM credits WHERE statut = 'actif'");
    $totalCredits = $stmt->fetchColumn();
    
    // Nombre de factures ce mois
    $stmt = $pdo->query("SELECT COUNT(*) FROM factures WHERE MONTH(date_facture) = MONTH(CURRENT_DATE()) AND YEAR(date_facture) = YEAR(CURRENT_DATE())");
    $facturesMois = $stmt->fetchColumn();
    
    // Total des ventes ce mois (factures + bons de sortie)
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(total), 0) as total_ventes
        FROM (
            SELECT total FROM factures 
            WHERE MONTH(date_facture) = MONTH(CURRENT_DATE()) 
            AND YEAR(date_facture) = YEAR(CURRENT_DATE())
            UNION ALL
            SELECT total FROM bons 
            WHERE MONTH(date_bon) = MONTH(CURRENT_DATE()) 
            AND YEAR(date_bon) = YEAR(CURRENT_DATE())
            AND type_bon = 'sortie'
        ) as ventes_combinees
    ");
    $ventesMois = $stmt->fetchColumn();
    
    // Bons du jour
    $stmt = $pdo->query("SELECT COUNT(*) FROM bons WHERE DATE(date_bon) = CURDATE()");
    $bonsAujourdhui = $stmt->fetchColumn();
    
    // Ventes par jour du mois en cours (pour le graphique)
    $stmt = $pdo->prepare("
        SELECT 
            DATE(date_bon) as date_vente,
            COALESCE(SUM(total), 0) as total_bons
        FROM bons 
        WHERE MONTH(date_bon) = MONTH(CURRENT_DATE()) 
        AND YEAR(date_bon) = YEAR(CURRENT_DATE())
        AND type_bon = 'sortie'
        GROUP BY DATE(date_bon)
        ORDER BY date_bon ASC
    ");
    $stmt->execute();
    $ventesBonsParJour = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("
        SELECT 
            DATE(date_facture) as date_vente,
            COALESCE(SUM(total), 0) as total_factures
        FROM factures 
        WHERE MONTH(date_facture) = MONTH(CURRENT_DATE()) 
        AND YEAR(date_facture) = YEAR(CURRENT_DATE())
        GROUP BY DATE(date_facture)
        ORDER BY date_facture ASC
    ");
    $stmt->execute();
    $ventesFacturesParJour = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Créer un tableau associatif pour combiner les données par jour
    $ventesParJour = [];
    
    // Initialiser tous les jours du mois
    $joursDuMois = [];
    $nombreJours = date('t'); // Nombre de jours dans le mois actuel
    $moisActuel = date('Y-m');
    for ($i = 1; $i <= $nombreJours; $i++) {
        $date = $moisActuel . '-' . str_pad($i, 2, '0', STR_PAD_LEFT);
        $joursDuMois[] = $date;
        $ventesParJour[$date] = ['bons' => 0, 'factures' => 0];
    }
    
    // Remplir avec les données des bons
    foreach ($ventesBonsParJour as $vente) {
        $date = $vente['date_vente'];
        if (isset($ventesParJour[$date])) {
            $ventesParJour[$date]['bons'] = floatval($vente['total_bons']);
        }
    }
    
    // Remplir avec les données des factures
    foreach ($ventesFacturesParJour as $vente) {
        $date = $vente['date_vente'];
        if (isset($ventesParJour[$date])) {
            $ventesParJour[$date]['factures'] = floatval($vente['total_factures']);
        }
    }
    
    // Préparer les données pour le graphique
    $labels = [];
    $donneesBons = [];
    $donneesFactures = [];
    
    foreach ($joursDuMois as $date) {
        $labels[] = date('d/m', strtotime($date));
        $donneesBons[] = $ventesParJour[$date]['bons'];
        $donneesFactures[] = $ventesParJour[$date]['factures'];
    }
    
    // Derniers bons
    $stmt = $pdo->prepare("
        SELECT b.*, c.nom, c.prenom, c.nom_entreprise, c.type_client
        FROM bons b
        LEFT JOIN clients c ON b.client_id = c.id
        ORDER BY b.date_bon DESC
        LIMIT 5
    ");
    $stmt->execute();
    $derniersBons = $stmt->fetchAll();
    
    // Derniers clients
    $stmt = $pdo->query("
        SELECT * FROM clients 
        WHERE actif = 1 
        ORDER BY date_creation DESC 
        LIMIT 5
    ");
    $derniersClients = $stmt->fetchAll();
    
    // Dernières opérations (bons + factures + transactions crédit)
    $stmt = $pdo->prepare("
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
            NULL as type_transaction
        FROM bons b
        LEFT JOIN clients c ON b.client_id = c.id
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
            NULL as type_transaction
        FROM factures f
        LEFT JOIN clients c ON f.client_id = c.id
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
            ct.type_transaction
        FROM credits_transactions ct
        LEFT JOIN clients c ON ct.client_id = c.id
        ORDER BY date_op DESC
        LIMIT 5
    ");
    $stmt->execute();
    $dernieresOperations = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Erreur dashboard: " . $e->getMessage());
    $totalClients = $totalProduits = $produitsStockFaible = $bonsNonPayes = 0;
    $totalCredits = $facturesMois = $ventesMois = $bonsAujourdhui = 0;
    $derniersBons = $derniersClients = $dernieresOperations = [];
    $labels = [];
    $donneesBons = [];
    $donneesFactures = [];
}

require_once 'includes/header.php';
?>

<div class="top-bar">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item active" aria-current="page">Accueil</li>
            </ol>
        </nav>
    </div>
    <div>
        <span class="text-muted">
            <i class="bi bi-calendar3 me-2"></i>
            <?php echo date('d/m/Y'); ?>
        </span>
    </div>
</div>

<!-- Statistiques -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card primary">
            <div class="stat-icon">
                <i class="bi bi-people"></i>
            </div>
            <div class="stat-value"><?php echo number_format($totalClients); ?></div>
            <div class="stat-label">Clients Actifs</div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card success">
            <div class="stat-icon">
                <i class="bi bi-box"></i>
            </div>
            <div class="stat-value"><?php echo number_format($totalProduits); ?></div>
            <div class="stat-label">Produits</div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card warning">
            <div class="stat-icon">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div class="stat-value"><?php echo number_format($produitsStockFaible); ?></div>
            <div class="stat-label">Stock Faible</div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card danger">
            <div class="stat-icon">
                <i class="bi bi-credit-card"></i>
            </div>
            <div class="stat-value"><?php echo number_format($totalCredits, 2); ?> DH</div>
            <div class="stat-label">Total Crédits</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card info">
            <div class="stat-icon">
                <i class="bi bi-receipt"></i>
            </div>
            <div class="stat-value"><?php echo number_format($bonsNonPayes); ?></div>
            <div class="stat-label">Bons Non Payés</div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="stat-card success">
            <div class="stat-icon">
                <i class="bi bi-file-earmark-text"></i>
            </div>
            <div class="stat-value"><?php echo number_format($facturesMois); ?></div>
            <div class="stat-label">Factures ce Mois</div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="stat-card primary">
            <div class="stat-icon">
                <i class="bi bi-currency-exchange"></i>
            </div>
            <div class="stat-value"><?php echo number_format($ventesMois, 2); ?> DH</div>
            <div class="stat-label">Ventes ce Mois</div>
        </div>
    </div>
</div>

<!-- Graphique des Ventes -->
<div class="row g-4 mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-graph-up me-2"></i>Ventes ce Mois (Par jour)
                </h5>
            </div>
            <div class="card-body">
                <canvas id="ventesChart" height="80"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Contenu principal -->
<div class="row g-4">
    <!-- Derniers Bons -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-receipt me-2"></i>Derniers Bons
                </h5>
                        <a href="<?php echo url('bons/index.php'); ?>" class="btn btn-sm btn-outline-primary">
                            Voir tout <i class="bi bi-arrow-right ms-1"></i>
                        </a>
            </div>
            <div class="card-body">
                <?php if (empty($derniersBons)): ?>
                    <p class="text-muted text-center py-4">Aucun bon enregistré</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>N°</th>
                                    <th>Client</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($derniersBons as $bon): ?>
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
                                        <td><?php echo date('d/m/Y', strtotime($bon['date_bon'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $bon['statut_paiement'] === 'paye' ? 'success' : 'warning'; ?>">
                                                <?php echo $bon['statut_paiement'] === 'paye' ? 'Payé' : 'Non Payé'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Dernières Opérations -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-activity me-2"></i>Dernières Opérations
                </h5>
                <a href="<?php echo url('operations/index.php'); ?>" class="btn btn-sm btn-outline-primary">
                    Voir tout <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($dernieresOperations)): ?>
                    <p class="text-muted text-center py-4">Aucune opération enregistrée</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>N°</th>
                                    <th>Client</th>
                                    <th>Date</th>
                                    <th>Montant</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dernieresOperations as $op): ?>
                                    <?php
                                    // Déterminer le signe et la couleur selon le type d'opération
                                    $montant = floatval($op['montant']);
                                    $isPositive = false;
                                    $badgeClass = '';
                                    $typeLabel = '';
                                    
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
                                    } elseif ($op['type_operation'] === 'facture') {
                                        $isPositive = true;
                                        $badgeClass = 'info';
                                        $typeLabel = 'Facture';
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
                                            <span class="badge bg-<?php echo $badgeClass; ?>">
                                                <?php echo $typeLabel; ?>
                                            </span>
                                        </td>
                                        <td><strong>#<?php echo htmlspecialchars($op['numero']); ?></strong></td>
                                        <td><small><?php echo $clientName; ?></small></td>
                                        <td><small><?php echo date('d/m/Y H:i', strtotime($op['date_op'])); ?></small></td>
                                        <td>
                                            <strong class="text-<?php echo $isPositive ? 'success' : 'danger'; ?>">
                                                <?php echo $isPositive ? '+' : '-'; ?>
                                                <?php echo number_format($montant, 2); ?> DH
                                            </strong>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-2">
    <!-- Derniers Clients -->
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-people me-2"></i>Derniers Clients
                </h5>
                        <a href="<?php echo url('clients/index.php'); ?>" class="btn btn-sm btn-outline-primary">
                            Voir tout <i class="bi bi-arrow-right ms-1"></i>
                        </a>
            </div>
            <div class="card-body">
                <?php if (empty($derniersClients)): ?>
                    <p class="text-muted text-center py-4">Aucun client enregistré</p>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($derniersClients as $client): ?>
                            <div class="col-md-6">
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">
                                            <?php 
                                            if ($client['type_client'] === 'entreprise') {
                                                echo htmlspecialchars($client['nom_entreprise'] ?? $client['nom']);
                                            } else {
                                                echo htmlspecialchars($client['nom'] . ' ' . ($client['prenom'] ?? ''));
                                            }
                                            ?>
                                        </h6>
                                        <small class="text-muted">
                                            <i class="bi bi-<?php echo $client['type_client'] === 'entreprise' ? 'building' : 'person'; ?> me-1"></i>
                                            <?php echo $client['type_client'] === 'entreprise' ? 'Entreprise' : 'Personne'; ?>
                                            <?php if ($client['telephone']): ?>
                                                | <i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($client['telephone']); ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-primary rounded-pill">
                                        <?php echo date('d/m/Y', strtotime($client['date_creation'])); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Actions rapides -->
<div class="row g-4 mt-2">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-lightning-charge me-2"></i>Actions Rapides
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <a href="clients/create.php" class="btn btn-outline-primary w-100">
                            <i class="bi bi-person-plus me-2"></i>Nouveau Client
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="produits/create.php" class="btn btn-outline-success w-100">
                            <i class="bi bi-plus-circle me-2"></i>Nouveau Produit
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="bons/create.php" class="btn btn-outline-info w-100">
                            <i class="bi bi-receipt me-2"></i>Nouveau Bon
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="factures/create.php" class="btn btn-outline-warning w-100">
                            <i class="bi bi-file-earmark-text me-2"></i>Nouvelle Facture
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Données pour le graphique
    const labels = <?php echo json_encode($labels); ?>;
    const donneesBons = <?php echo json_encode($donneesBons); ?>;
    const donneesFactures = <?php echo json_encode($donneesFactures); ?>;
    
    // Vérifier si le dark mode est activé
    const isDarkMode = document.body.classList.contains('dark-mode');
    const textColor = isDarkMode ? '#e0e0e0' : '#333';
    const gridColor = isDarkMode ? '#404040' : '#e0e0e0';
    
    // Créer le graphique
    const ctx = document.getElementById('ventesChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Bons de Sortie',
                        data: donneesBons,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'Factures',
                        data: donneesFactures,
                        borderColor: '#48bb78',
                        backgroundColor: 'rgba(72, 187, 120, 0.1)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: textColor,
                            font: {
                                size: 12,
                                weight: 'bold'
                            },
                            usePointStyle: true,
                            padding: 15
                        }
                    },
                    tooltip: {
                        backgroundColor: isDarkMode ? 'rgba(45, 45, 45, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                        titleColor: textColor,
                        bodyColor: textColor,
                        borderColor: gridColor,
                        borderWidth: 1,
                        padding: 12,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y.toFixed(2) + ' DH';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: textColor,
                            callback: function(value) {
                                return value.toFixed(0) + ' DH';
                            }
                        },
                        grid: {
                            color: gridColor,
                            drawBorder: false
                        }
                    },
                    x: {
                        ticks: {
                            color: textColor
                        },
                        grid: {
                            color: gridColor,
                            drawBorder: false
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>

