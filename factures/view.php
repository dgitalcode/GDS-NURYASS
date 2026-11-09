<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireLogin('../login.php');
$pageTitle = 'Détails Facture';
$factureId = intval($_GET['id'] ?? 0);
if (!$factureId) { header('Location: index.php'); exit(); }
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT f.*, c.nom, c.prenom, c.nom_entreprise, c.type_client, c.telephone, c.adresse, c.email, c.cin, c.patente, u.username FROM factures f LEFT JOIN clients c ON f.client_id = c.id LEFT JOIN users u ON f.user_id = u.id WHERE f.id = :id");
    $stmt->execute(['id' => $factureId]);
    $facture = $stmt->fetch();
    if (!$facture) { $_SESSION['error_message'] = 'Facture introuvable.'; header('Location: index.php'); exit(); }
    $stmt = $pdo->prepare("SELECT fd.*, p.nom_produit, tp.nom_type, col.nom_couleur FROM factures_details fd LEFT JOIN produits p ON fd.produit_id = p.id LEFT JOIN types_produits tp ON p.type_id = tp.id LEFT JOIN couleurs col ON p.couleur_id = col.id WHERE fd.facture_id = :facture_id");
    $stmt->execute(['facture_id' => $factureId]);
    $details = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur view facture: " . $e->getMessage());
    $_SESSION['error_message'] = 'Erreur lors du chargement.';
    header('Location: index.php'); exit();
}
require_once '../includes/header.php';
?>
<div class="top-bar">
    <div><h1 class="page-title">Facture #<?php echo htmlspecialchars($facture['numero_facture']); ?></h1></div>
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
    
    <div class="invoice-content-wrapper">
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
            <p class="print-subtitle">FACTURE</p>
        </div>
    </div>
    
    <div class="print-info">
        <div class="print-info-row">
            <div class="print-info-col">
                <div class="info-box">
                    <h6>Informations de la Facture</h6>
                    <p><strong>Numéro:</strong> <?php echo htmlspecialchars($facture['numero_facture']); ?></p>
                    <p><strong>Date:</strong> <?php echo date('d/m/Y H:i', strtotime($facture['date_facture'])); ?></p>
                    <p><strong>Statut:</strong> <?php echo ucfirst($facture['statut']); ?></p>
                    <?php if ($facture['bon_id']): ?>
                        <p><strong>Bon associé:</strong> #<?php echo htmlspecialchars($facture['bon_id']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="print-info-col">
                <div class="info-box">
                    <h6>Informations de l'Entreprise</h6>
                    <?php if ($facture['nom_entreprise']): ?>
                        <p><strong>Raison Sociale:</strong> <?php echo htmlspecialchars($facture['nom_entreprise']); ?></p>
                    <?php endif; ?>
                    <?php if ($facture['patente']): ?>
                        <p><strong>Patente:</strong> <?php echo htmlspecialchars($facture['patente']); ?></p>
                    <?php endif; ?>
                    <?php if ($facture['adresse']): ?>
                        <p><strong>Adresse:</strong> <?php echo htmlspecialchars($facture['adresse']); ?></p>
                    <?php endif; ?>
                    <?php if ($facture['telephone']): ?>
                        <p><strong>Téléphone:</strong> <?php echo htmlspecialchars($facture['telephone']); ?></p>
                    <?php endif; ?>
                    <?php if ($facture['email']): ?>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($facture['email']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="print-products">
        <h5>Détails des Produits</h5>
        <table class="table table-bordered">
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
                        <td><?php echo htmlspecialchars($detail['nom_produit'] . ($detail['nom_type'] ? ' (' . $detail['nom_type'] . ($detail['nom_couleur'] ? ' - ' . $detail['nom_couleur'] : '') . ')' : '')); ?></td>
                        <td class="text-center"><?php echo $detail['quantite']; ?></td>
                        <td class="text-end"><?php echo number_format($detail['prix_unitaire'], 2); ?> DH</td>
                        <td class="text-end"><?php echo number_format($detail['sous_total'], 2); ?> DH</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-end">TOTAL</th>
                    <th class="text-end"><?php echo number_format($facture['total'], 2); ?> DH</th>
                </tr>
            </tfoot>
        </table>
    </div>
    </div>
    
    <div class="print-signatures">
        <div class="signature-row">
            <div class="signature-box">
                <div class="signature-line"></div>
                <p class="signature-label">Signature Client</p>
            </div>
            <div class="signature-box">
                <div class="cachet-box">
                   
                </div>
                <p class="signature-label">Cachet & Signature</p>
            </div>
        </div>
    </div>
    
    <div class="print-footer">
        <p class="text-center text-muted">
            <small>Facture générée le <?php echo date('d/m/Y à H:i'); ?> par <?php echo htmlspecialchars($facture['username'] ?? 'Système'); ?></small>
        </p>
    </div>
</div>

<!-- Affichage normal (non imprimable) -->
<div class="row no-print">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-white"><h5>Informations</h5></div>
            <div class="card-body">
                <p><strong>Client:</strong> <?php echo htmlspecialchars($facture['type_client'] === 'entreprise' ? ($facture['nom_entreprise'] ?? $facture['nom']) : ($facture['nom'] . ' ' . ($facture['prenom'] ?? ''))); ?></p>
                <p><strong>Date:</strong> <?php echo date('d/m/Y H:i', strtotime($facture['date_facture'])); ?></p>
                <p><strong>Statut:</strong> <span class="badge bg-<?php echo $facture['statut'] === 'validee' ? 'success' : ($facture['statut'] === 'annulee' ? 'danger' : 'warning'); ?>"><?php echo ucfirst($facture['statut']); ?></span></p>
                <?php if ($facture['bon_id']): ?>
                    <p><strong>Bon associé:</strong> <a href="<?php echo url('bons/view.php?id=' . $facture['bon_id']); ?>">Bon #<?php echo htmlspecialchars($facture['bon_id']); ?></a></p>
                <?php endif; ?>
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
                                <td><?php echo htmlspecialchars($detail['nom_produit'] . ($detail['nom_type'] ? ' (' . $detail['nom_type'] . ($detail['nom_couleur'] ? ' - ' . $detail['nom_couleur'] : '') . ')' : '')); ?></td>
                                <td><?php echo $detail['quantite']; ?></td>
                                <td><?php echo number_format($detail['prix_unitaire'], 2); ?> DH</td>
                                <td><?php echo number_format($detail['sous_total'], 2); ?> DH</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot><tr><th colspan="3">Total</th><th><?php echo number_format($facture['total'], 2); ?> DH</th></tr></tfoot>
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
        display: flex !important;
        flex-direction: column;
        width: 100%;
        padding: 0;
        max-width: 210mm;
        margin: 0;
        min-height: 100vh;
        box-sizing: border-box;
    }
    
    .invoice-content-wrapper {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    .print-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #667eea;
    }
    
    .print-logo {
        flex-shrink: 0;
    }
    
    .logo-print {
        max-width: 250px;
        max-height: 160px;
        width: auto;
        height: auto;
        object-fit: contain;
    }
    
    .logo-placeholder {
        font-size: 56px;
        font-weight: bold;
        color: #667eea;
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
    
    .print-info {
        margin-bottom: 0px;
    }
    
    .print-info-row {
        display: flex;
        gap: 15px;
        margin-bottom: 0px;
    }
    
    .print-info-col {
        flex: 1;
    }
    
    .info-box {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        border-left: 4px solid #667eea;
    }
    
    .info-box h6 {
        margin: 0 0 12px 0;
        font-size: 16px;
        font-weight: bold;
        color: #667eea;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .info-box p {
        margin-bottom: 0px;
        font-size: 13px;
        line-height: 1.6;
    }
    
    .info-box strong {
        color: #333;
        min-width: 100px;
        display: inline-block;
    }
    
    .print-products {
        margin-bottom: 15px;
    }
    
    .print-products h5 {
        margin-bottom: 10px;
        font-size: 14px;
        font-weight: bold;
        color: #333;
        padding-bottom: 5px;
        border-bottom: 2px solid #667eea;
    }
    
    .print-products table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
    }
    
    .print-products th,
    .print-products td {
        padding: 6px 5px;
        border: 1px solid #ddd;
        text-align: left;
        font-size: 10px;
    }
    
    .print-products th {
        background-color: #667eea;
        color: white;
        font-weight: bold;
        text-align: center;
        font-size: 11px;
        padding: 8px 5px;
    }
    
    .print-products td {
        background-color: #fff;
    }
    
    .print-products tbody tr:nth-child(even) {
        background-color: #f8f9fa;
    }
    
    .print-products tfoot th {
        background-color: #e9ecef;
        font-size: 12px;
        font-weight: bold;
        padding: 8px 5px;
    }
    
    .print-signatures {
        margin-top: auto;
        padding-top: 40px;
        margin-bottom: 20px;
        page-break-inside: avoid;
        flex-shrink: 0;
    }
    
    .signature-row {
        display: flex;
        justify-content: space-between;
        gap: 30px;
    }
    
    .signature-box {
        flex: 1;
        text-align: center;
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
    }
    
    .cachet-box {
        width: 100%;
        height: 80px;
        border: 2px dashed #999;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 8px;
        background-color: #fafafa;
    }
    
    .cachet-placeholder {
        color: #999;
        font-size: 14px;
        font-style: italic;
    }
    
    .print-footer {
        margin-top: 30px;
        padding-top: 15px;
        border-top: 1px solid #ddd;
        text-align: center;
    }
    
    .print-footer p {
        margin: 0;
        font-size: 11px;
        color: #666;
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
    
    /* Masquer l'URL dans le footer du navigateur lors de l'impression */
    body::after {
        content: "" !important;
        display: none !important;
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
    
    /* Afficher le footer avec les informations essentielles */
    .print-footer {
        display: block !important;
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






