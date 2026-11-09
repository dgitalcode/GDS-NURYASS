<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireLogin('../login.php');
$pageTitle = 'Détails Crédit';
$creditId = intval($_GET['id'] ?? 0);
if (!$creditId) { header('Location: index.php'); exit(); }
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT cr.*, c.* FROM credits cr LEFT JOIN clients c ON cr.client_id = c.id WHERE cr.id = :id");
    $stmt->execute(['id' => $creditId]);
    $credit = $stmt->fetch();
    if (!$credit) { $_SESSION['error_message'] = 'Crédit introuvable.'; header('Location: index.php'); exit(); }
    $stmt = $pdo->prepare("SELECT ct.*, u.username, b.numero_bon FROM credits_transactions ct LEFT JOIN users u ON ct.user_id = u.id LEFT JOIN bons b ON ct.bon_id = b.id WHERE ct.credit_id = :credit_id ORDER BY ct.date_transaction DESC");
    $stmt->execute(['credit_id' => $creditId]);
    $transactions = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur view credit: " . $e->getMessage());
    $_SESSION['error_message'] = 'Erreur.';
    header('Location: index.php'); exit();
}
require_once '../includes/header.php';
?>
<div class="top-bar"><div><h1 class="page-title">Crédit Client</h1></div><div><a href="index.php" class="btn btn-outline-secondary">Retour</a></div></div>
<div class="row"><div class="col-md-6"><div class="card mb-4"><div class="card-header bg-white"><h5>Informations</h5></div><div class="card-body"><p><strong>Client:</strong> <?php echo htmlspecialchars($credit['type_client'] === 'entreprise' ? ($credit['nom_entreprise'] ?? $credit['nom']) : ($credit['nom'] . ' ' . ($credit['prenom'] ?? ''))); ?></p><p><strong>Crédit Actuel:</strong> <h3 class="text-danger"><?php echo number_format($credit['montant_actuel'], 2); ?> DH</h3></p><p><strong>Maximum:</strong> <?php echo number_format($credit['max_montant'], 2); ?> DH</p></div></div></div><div class="col-md-6"><div class="card"><div class="card-header bg-white"><h5>Historique</h5></div><div class="card-body"><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Date</th><th>Type</th><th>Montant</th><th>Avant</th><th>Après</th></tr></thead><tbody><?php foreach ($transactions as $trans): ?><tr><td><?php echo date('d/m/Y H:i', strtotime($trans['date_transaction'])); ?></td><td><span class="badge bg-<?php echo $trans['type_transaction'] === 'paiement' ? 'success' : 'primary'; ?>"><?php echo $trans['type_transaction']; ?></span></td><td><?php echo number_format($trans['montant'], 2); ?> DH</td><td><?php echo number_format($trans['montant_avant'], 2); ?> DH</td><td><?php echo number_format($trans['montant_apres'], 2); ?> DH</td></tr><?php endforeach; ?></tbody></table></div></div></div></div></div>
<div class="mt-3"><a href="paiement.php?id=<?php echo $creditId; ?>" class="btn btn-success"><i class="bi bi-cash me-2"></i>Enregistrer un Paiement</a></div>
<?php require_once '../includes/footer.php'; ?>






