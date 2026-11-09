<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireLogin('../login.php');
$pageTitle = 'Paiement Crédit';
$creditId = intval($_GET['id'] ?? 0);
if (!$creditId) { header('Location: index.php'); exit(); }
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT cr.*, c.* FROM credits cr LEFT JOIN clients c ON cr.client_id = c.id WHERE cr.id = :id");
    $stmt->execute(['id' => $creditId]);
    $credit = $stmt->fetch();
    if (!$credit) { $_SESSION['error_message'] = 'Crédit introuvable.'; header('Location: index.php'); exit(); }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $montant = floatval($_POST['montant'] ?? 0);
        if ($montant <= 0 || $montant > $credit['montant_actuel']) {
            $_SESSION['error_message'] = 'Montant invalide.';
        } else {
            $pdo->beginTransaction();
            $montantAvant = $credit['montant_actuel'];
            $pdo->prepare("UPDATE credits SET montant_actuel = montant_actuel - :montant WHERE id = :id")->execute(['montant' => $montant, 'id' => $creditId]);
            $user = getCurrentUser();
            $pdo->prepare("INSERT INTO credits_transactions (credit_id, client_id, type_transaction, montant, montant_avant, montant_apres, user_id, details) VALUES (:credit_id, :client_id, 'paiement', :montant, :montant_avant, :montant_apres, :user_id, :details)")->execute(['credit_id' => $creditId, 'client_id' => $credit['client_id'], 'montant' => $montant, 'montant_avant' => $montantAvant, 'montant_apres' => $montantAvant - $montant, 'user_id' => $user['id'], 'details' => "Paiement de crédit"]);
            $pdo->commit();
            $_SESSION['success_message'] = 'Paiement enregistré!';
            header('Location: view.php?id=' . $creditId); exit();
        }
    }
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log("Erreur paiement: " . $e->getMessage());
    $_SESSION['error_message'] = 'Erreur.';
}
require_once '../includes/header.php';
?>
<div class="top-bar"><div><h1 class="page-title">Paiement Crédit</h1></div><div><a href="view.php?id=<?php echo $creditId; ?>" class="btn btn-outline-secondary">Retour</a></div></div>
<div class="row"><div class="col-md-6 mx-auto"><div class="card"><div class="card-body"><form method="POST"><div class="mb-3"><label class="form-label">Montant à Payer (DH)</label><input type="number" class="form-control" name="montant" step="0.01" min="0.01" max="<?php echo $credit['montant_actuel']; ?>" required><small class="text-muted">Crédit actuel: <?php echo number_format($credit['montant_actuel'], 2); ?> DH</small></div><div class="d-grid"><button type="submit" class="btn btn-success"><i class="bi bi-cash me-2"></i>Enregistrer le Paiement</button></div></form></div></div></div></div>
<?php require_once '../includes/footer.php'; ?>






