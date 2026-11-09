<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireLogin('../login.php');
$pageTitle = 'Modifier Bon';
$bonId = intval($_GET['id'] ?? 0);
if (!$bonId) { header('Location: index.php'); exit(); }
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM bons WHERE id = :id");
    $stmt->execute(['id' => $bonId]);
    $bon = $stmt->fetch();
    if (!$bon) { $_SESSION['error_message'] = 'Bon introuvable.'; header('Location: index.php'); exit(); }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $statutPaiement = $_POST['statut_paiement'] ?? $bon['statut_paiement'];
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE bons SET statut_paiement = :statut WHERE id = :id")->execute(['statut' => $statutPaiement, 'id' => $bonId]);
        if ($statutPaiement === 'paye' && $bon['statut_paiement'] === 'non_paye') {
            $stmt = $pdo->prepare("SELECT montant_actuel FROM credits WHERE client_id = :client_id");
            $stmt->execute(['client_id' => $bon['client_id']]);
            $montantAvant = $stmt->fetchColumn();
            $pdo->prepare("UPDATE credits SET montant_actuel = GREATEST(0, montant_actuel - :montant) WHERE client_id = :client_id")->execute(['montant' => $bon['total'], 'client_id' => $bon['client_id']]);
            $stmt = $pdo->prepare("SELECT montant_actuel FROM credits WHERE client_id = :client_id");
            $stmt->execute(['client_id' => $bon['client_id']]);
            $montantApres = $stmt->fetchColumn();
            $user = getCurrentUser();
            $pdo->prepare("INSERT INTO credits_transactions (credit_id, client_id, bon_id, type_transaction, montant, montant_avant, montant_apres, user_id, details) SELECT id, :client_id, :bon_id, 'paiement', :montant, :montant_avant, :montant_apres, :user_id, :details FROM credits WHERE client_id = :client_id2")->execute(['client_id' => $bon['client_id'], 'bon_id' => $bonId, 'montant' => $bon['total'], 'montant_avant' => $montantAvant, 'montant_apres' => $montantApres, 'user_id' => $user['id'], 'details' => "Paiement bon: " . $bon['numero_bon'], 'client_id2' => $bon['client_id']]);
        }
        $pdo->commit();
        $_SESSION['success_message'] = 'Bon modifié avec succès!';
        header('Location: view.php?id=' . $bonId); exit();
    }
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log("Erreur edit bon: " . $e->getMessage());
    $_SESSION['error_message'] = 'Erreur.';
    header('Location: index.php'); exit();
}
require_once '../includes/header.php';
?>
<div class="top-bar"><div><h1 class="page-title">Modifier Bon</h1></div><div><a href="view.php?id=<?php echo $bonId; ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Retour</a></div></div>
<div class="row"><div class="col-md-6 mx-auto">
<div class="card"><div class="card-body">
<form method="POST">
<div class="mb-3"><label class="form-label">Statut Paiement</label>
<select class="form-select" name="statut_paiement">
<option value="paye" <?php echo $bon['statut_paiement'] === 'paye' ? 'selected' : ''; ?>>Payé</option>
<option value="non_paye" <?php echo $bon['statut_paiement'] === 'non_paye' ? 'selected' : ''; ?>>Non Payé</option>
</select></div>
<div class="d-grid gap-2"><a href="view.php?id=<?php echo $bonId; ?>" class="btn btn-outline-secondary">Annuler</a>
<button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-2"></i>Enregistrer</button></div>
</form></div></div></div></div>
<?php require_once '../includes/footer.php'; ?>






