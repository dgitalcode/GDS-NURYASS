<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireLogin('../login.php');
$factureId = intval($_GET['id'] ?? 0);
if (!$factureId) die('Facture introuvable');
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT f.*, c.* FROM factures f LEFT JOIN clients c ON f.client_id = c.id WHERE f.id = :id");
    $stmt->execute(['id' => $factureId]);
    $facture = $stmt->fetch();
    if (!$facture) die('Facture introuvable');
    $stmt = $pdo->prepare("SELECT fd.*, p.nom_produit FROM factures_details fd LEFT JOIN produits p ON fd.produit_id = p.id WHERE fd.facture_id = :facture_id");
    $stmt->execute(['facture_id' => $factureId]);
    $details = $stmt->fetchAll();
} catch (PDOException $e) {
    die('Erreur: ' . $e->getMessage());
}
// Génération PDF simple (HTML pour l'instant - à remplacer par TCPDF/FPDF)
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Facture <?php echo htmlspecialchars($facture['numero_facture']); ?></title>
<style>body{font-family:Arial;margin:20px;}table{width:100%;border-collapse:collapse;}th,td{border:1px solid #ddd;padding:8px;text-align:left;}th{background:#f2f2f2;}</style></head>
<body>
<h1>FACTURE <?php echo htmlspecialchars($facture['numero_facture']); ?></h1>
<p><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($facture['date_facture'])); ?></p>
<div style="margin:20px 0;padding:15px;background:#f8f9fa;border-left:4px solid #667eea;">
    <h3 style="margin-top:0;color:#667eea;">Informations de l'Entreprise</h3>
    <?php if ($facture['nom_entreprise']): ?>
        <p><strong>Raison Sociale:</strong> <?php echo htmlspecialchars($facture['nom_entreprise']); ?></p>
    <?php endif; ?>
    <?php if ($facture['nom']): ?>
        <p><strong>Nom du Représentant:</strong> <?php echo htmlspecialchars($facture['nom'] . ($facture['prenom'] ? ' ' . $facture['prenom'] : '')); ?></p>
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
<table><thead><tr><th>Produit</th><th>Quantité</th><th>Prix</th><th>Total</th></tr></thead><tbody><?php foreach ($details as $detail): ?><tr><td><?php echo htmlspecialchars($detail['nom_produit']); ?></td><td><?php echo $detail['quantite']; ?></td><td><?php echo number_format($detail['prix_unitaire'], 2); ?> DH</td><td><?php echo number_format($detail['sous_total'], 2); ?> DH</td></tr><?php endforeach; ?></tbody><tfoot><tr><th colspan="3">TOTAL</th><th><?php echo number_format($facture['total'], 2); ?> DH</th></tr></tfoot></table>
<p style="margin-top:50px;"><strong>Signature:</strong> _________________</p>
<script>window.print();</script>
</body></html>






