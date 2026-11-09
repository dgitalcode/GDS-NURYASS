<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireLogin('../login.php');
$pageTitle = 'Détails Bon';
$bonId = intval($_GET['id'] ?? 0);
if (!$bonId) { header('Location: index.php'); exit(); }
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT b.*, c.nom, c.prenom, c.nom_entreprise, c.type_client, c.telephone, c.adresse, c.email, c.cin, c.patente, u.username FROM bons b LEFT JOIN clients c ON b.client_id = c.id LEFT JOIN users u ON b.user_id = u.id WHERE b.id = :id");
    $stmt->execute(['id' => $bonId]);
    $bon = $stmt->fetch();
    if (!$bon) { $_SESSION['error_message'] = 'Bon introuvable.'; header('Location: index.php'); exit(); }
    $stmt = $pdo->prepare("SELECT bd.*, p.nom_produit, tp.nom_type, c.nom_couleur FROM bons_details bd LEFT JOIN produits p ON bd.produit_id = p.id LEFT JOIN types_produits tp ON p.type_id = tp.id LEFT JOIN couleurs c ON p.couleur_id = c.id WHERE bd.bon_id = :bon_id");
    $stmt->execute(['bon_id' => $bonId]);
    $details = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur view bon: " . $e->getMessage());
    $_SESSION['error_message'] = 'Erreur lors du chargement.';
    header('Location: index.php'); exit();
}
require_once '../includes/header.php';
?>
<div class="top-bar">
    <div><h1 class="page-title">Bon #<?php echo htmlspecialchars($bon['numero_bon']); ?></h1></div>
    <div>
        <button onclick="window.print()" class="btn btn-primary me-2">
            <i class="bi bi-printer me-2"></i>Imprimer
        </button>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Retour
        </a>
    </div>
</div>
<!-- Zone imprimable -->
<div class="printable-area" id="printable-area">
    <div class="print-header">
        <div class="print-logo">
            <?php 
            $logoPath = url('images/logo.png');
            $logoExists = file_exists(__DIR__ . '/../images/logo.png');
            if ($logoExists): 
            ?>
                <img src="<?php echo $logoPath; ?>" alt="NURYASS Logo" class="logo-print">
            <?php else: ?>
                <div class="logo-placeholder">NURYASS</div>
            <?php endif; ?>
        </div>
        <div class="print-title">
            <h2>NURYASS</h2>
            <p class="print-subtitle">Bon <?php echo $bon['type_bon'] === 'entree' ? 'd\'Entrée' : 'de Sortie'; ?></p>
        </div>
    </div>
    
    <div class="print-user-info">
        <p><strong>Imprimé par:</strong> <?php echo htmlspecialchars($_SESSION['username'] ?? 'Système'); ?></p>
    </div>
    
    <div class="print-info-line">
        <span><strong>Numéro:</strong> <?php echo htmlspecialchars($bon['numero_bon']); ?></span>
        <span><strong>Date:</strong> <?php echo date('d/m/Y H:i', strtotime($bon['date_bon'])); ?></span>
        <span><strong>Type:</strong> <?php echo $bon['type_bon'] === 'entree' ? 'Entrée' : 'Sortie'; ?></span>
        <span><strong>Statut:</strong> <?php echo $bon['statut_paiement'] === 'paye' ? 'Payé' : 'Non Payé'; ?></span>
        <span><strong>Client:</strong> <?php 
            if ($bon['type_client'] === 'entreprise') {
                echo htmlspecialchars($bon['nom_entreprise'] ?? ($bon['nom'] . ($bon['prenom'] ? ' ' . $bon['prenom'] : '')));
            } else {
                echo htmlspecialchars($bon['nom'] . ' ' . ($bon['prenom'] ?? ''));
            }
        ?></span>
    </div>
    
    <div class="print-products">
        <h5>Détails des Produits</h5>
        <table class="products-table">
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Quantité</th>
                    <th>Prix Unitaire</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($details as $detail): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($detail['nom_produit'] . ' (' . $detail['nom_type'] . ' - ' . $detail['nom_couleur'] . ')'); ?></td>
                        <td class="text-center"><?php echo $detail['quantite']; ?></td>
                        <td class="text-end"><strong><?php echo number_format($detail['prix_unitaire'], 2); ?> DH</strong></td>
                        <td class="text-end"><strong><?php echo number_format($detail['sous_total'], 2); ?> DH</strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="3" class="text-end"><strong>TOTAL</strong></td>
                    <td class="text-end"><strong><?php echo number_format($bon['total'], 2); ?> DH</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <div class="print-signatures">
        <div class="signature-row">
            <div class="signature-box-right">
                <div class="signature-line"></div>
                <p class="signature-label">Signature</p>
            </div>
        </div>
    </div>
    
    <div class="print-footer">
        <p class="text-center text-muted">
            <small>Bon généré le <?php echo date('d/m/Y à H:i'); ?> par <?php echo htmlspecialchars($bon['username'] ?? 'Système'); ?></small>
        </p>
    </div>
</div>

<!-- Affichage normal (non imprimable) -->
<div class="row no-print">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-white"><h5>Informations</h5></div>
            <div class="card-body">
                <p><strong>Client:</strong> <?php echo htmlspecialchars($bon['type_client'] === 'entreprise' ? ($bon['nom_entreprise'] ?? $bon['nom']) : ($bon['nom'] . ' ' . ($bon['prenom'] ?? ''))); ?></p>
                <p><strong>Type:</strong> <span class="badge bg-<?php echo $bon['type_bon'] === 'entree' ? 'success' : 'primary'; ?>"><?php echo $bon['type_bon'] === 'entree' ? 'Entrée' : 'Sortie'; ?></span></p>
                <p><strong>Date:</strong> <?php echo date('d/m/Y H:i', strtotime($bon['date_bon'])); ?></p>
                <p><strong>Statut:</strong> <span class="badge bg-<?php echo $bon['statut_paiement'] === 'paye' ? 'success' : 'warning'; ?>"><?php echo $bon['statut_paiement'] === 'paye' ? 'Payé' : 'Non Payé'; ?></span></p>
                <?php if ($bon['details']): ?><p><strong>Détails:</strong> <?php echo nl2br(htmlspecialchars($bon['details'])); ?></p><?php endif; ?>
            </div>
        </div>
        <div class="card">
            <div class="card-header bg-white"><h5>Produits</h5></div>
            <div class="card-body">
                <table class="table">
                    <thead><tr><th>Produit</th><th>Quantité</th><th>Prix</th><th>Total</th></tr></thead>
                    <tbody>
                        <?php foreach ($details as $detail): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($detail['nom_produit'] . ' (' . $detail['nom_type'] . ' - ' . $detail['nom_couleur'] . ')'); ?></td>
                                <td><?php echo $detail['quantite']; ?></td>
                                <td><?php echo number_format($detail['prix_unitaire'], 2); ?> DH</td>
                                <td><?php echo number_format($detail['sous_total'], 2); ?> DH</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot><tr><th colspan="3">Total</th><th><?php echo number_format($bon['total'], 2); ?> DH</th></tr></tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles pour l'impression */
@media print {
    body {
        background: white;
        font-family: 'Arial', 'Helvetica', sans-serif;
    }
    
    .sidebar,
    .top-bar,
    .no-print,
    .btn,
    nav,
    .breadcrumb,
    .alert,
    .alert-success,
    .alert-danger,
    .alert-warning,
    .alert-info,
    .mobile-menu-toggle,
    #mobile-menu-toggle,
    .sidebar-overlay {
        display: none !important;
    }
    
    .main-content {
        margin-left: 0 !important;
        padding: 0 !important;
    }
    
    .printable-area {
        display: block !important;
        width: 100%;
        padding: 0;
        max-width: 210mm;
        margin: 0;
    }
    
    .print-header {
        display: none !important;
    }
    
    .print-logo {
        display: none !important;
    }
    
    .logo-print {
        display: none !important;
    }
    
    .logo-placeholder {
        display: none !important;
    }
    
    .print-title {
        flex-grow: 1;
        text-align: center;
    }
    
    .print-title h2 {
        margin: 0;
        font-size: 32px;
        font-weight: bold;
        color: #667eea;
        letter-spacing: 2px;
    }
    
    .print-subtitle {
        margin: 8px 0 0 0;
        font-size: 18px;
        color: #333;
        font-weight: 600;
    }
    
    .print-user-info {
        text-align: right;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #ddd;
    }
    
    .print-user-info p {
        margin: 0;
        font-size: 12px;
        color: #666;
    }
    
    .print-info-line {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 25px;
        padding: 15px;
        border: 2px solid #333;
        border-radius: 5px;
        background-color: #f8f9fa;
        font-size: 13px;
    }
    
    .print-info-line span {
        white-space: nowrap;
    }
    
    .print-info-line strong {
        color: #333;
        margin-right: 5px;
    }
    
    .print-products {
        margin-bottom: 30px;
    }
    
    .print-products h5 {
        margin-bottom: 15px;
        font-size: 20px;
        font-weight: 900;
        color: #000;
        padding-bottom: 10px;
        border-bottom: 3px solid #000;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .print-products table,
    .products-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
        page-break-inside: auto;
    }
    
    .products-table thead {
        display: table-header-group;
    }
    
    .products-table tfoot {
        display: table-footer-group;
    }
    
    .products-table th,
    .products-table td {
        padding: 6px 8px;
        border: 2px solid #000;
        text-align: left;
        font-size: 11px;
        vertical-align: middle;
    }
    
    .products-table th {
        background-color: #000;
        color: white;
        font-weight: 900;
        text-align: center;
        font-size: 12px;
        padding: 8px 8px;
        border: 2px solid #000;
    }
    
    .products-table td {
        background-color: #fff;
        color: #000;
        border: 2px solid #000;
        font-weight: 500;
    }
    
    .products-table tbody tr:nth-child(even) {
        background-color: #f5f5f5;
    }
    
    .products-table tbody tr:hover {
        background-color: #e9ecef;
    }
    
    .products-table tfoot {
        border-top: 3px solid #000;
    }
    
    .products-table tfoot .total-row {
        background-color: #000;
        font-size: 12px;
    }
    
    .products-table tfoot .total-row td {
        padding: 10px 8px;
        font-weight: 900;
        border-top: 3px solid #000;
        border-bottom: 3px solid #000;
        color: white;
        background-color: #000;
    }
    
    .products-table .text-center {
        text-align: center;
    }
    
    .products-table .text-end {
        text-align: right;
    }
    
    .products-table strong {
        font-weight: 900;
        font-size: 14px;
    }
    
    .print-signatures {
        margin-top: 50px;
        margin-bottom: 30px;
    }
    
    .signature-row {
        display: flex;
        justify-content: flex-end;
        margin-top: 40px;
    }
    
    .signature-box-right {
        width: 250px;
        text-align: right;
    }
    
    .signature-line {
        width: 100%;
        height: 60px;
        border-bottom: 2px solid #333;
        margin-bottom: 8px;
    }
    
    .signature-label {
        margin: 0;
        font-size: 12px;
        font-weight: bold;
        color: #666;
        text-transform: uppercase;
        text-align: right;
    }
    
    .print-footer {
        display: none !important;
    }
    
    @page {
        margin: 0 !important;
        size: A4;
        /* Masquer les en-têtes et pieds de page du navigateur (URL, titre, etc.) */
    }
    
    /* Masquer l'URL en bas de page lors de l'impression */
    @page {
        @bottom-right {
            content: "";
        }
        @bottom-left {
            content: "";
        }
        @bottom-center {
            content: "";
        }
        @top-right {
            content: "";
        }
        @top-left {
            content: "";
        }
        @top-center {
            content: "";
        }
    }
    
    /* Masquer les URLs et liens lors de l'impression */
    a[href]:after {
        content: "" !important;
    }
    
    /* Masquer les liens cliquables mais garder le texte */
    a[href*="view.php"],
    a[href*="index.php"],
    a[href*="localhost"],
    a[href*="http"] {
        text-decoration: none !important;
        color: inherit !important;
    }
    
    /* Masquer les messages d'alerte */
    .alert,
    .alert-success,
    .alert-danger,
    .alert-warning,
    .alert-info,
    .alert-dismissible,
    .alert-dismissible .btn-close {
        display: none !important;
    }
    
}

/* Styles pour l'affichage à l'écran */
.printable-area {
    display: none;
}

.no-print {
    display: block;
}
</style>

<script>
// Fonction pour masquer les URLs lors de l'impression
window.addEventListener('beforeprint', function() {
    // Ajouter un style pour masquer les en-têtes et pieds de page du navigateur
    const style = document.createElement('style');
    style.id = 'print-hide-urls';
    style.textContent = `
        @page {
            @bottom-right { content: "" !important; }
            @bottom-left { content: "" !important; }
            @bottom-center { content: "" !important; }
            @top-right { content: "" !important; }
            @top-left { content: "" !important; }
            @top-center { content: "" !important; }
        }
    `;
    document.head.appendChild(style);
});

window.addEventListener('afterprint', function() {
    // Supprimer le style après l'impression
    const style = document.getElementById('print-hide-urls');
    if (style) {
        style.remove();
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>


