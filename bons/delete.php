<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireLogin('../login.php');
$bonId = intval($_GET['id'] ?? 0);
if (!$bonId) { $_SESSION['error_message'] = 'ID invalide.'; header('Location: index.php'); exit(); }
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM bons WHERE id = :id");
    $stmt->execute(['id' => $bonId]);
    $bon = $stmt->fetch();
    if (!$bon) { $_SESSION['error_message'] = 'Bon introuvable.'; header('Location: index.php'); exit(); }
    $pdo->beginTransaction();
    // Supprimer les détails
    $pdo->prepare("DELETE FROM bons_details WHERE bon_id = :id")->execute(['id' => $bonId]);
    // Supprimer le bon
    $pdo->prepare("DELETE FROM bons WHERE id = :id")->execute(['id' => $bonId]);
    $user = getCurrentUser();
    $pdo->prepare("INSERT INTO historique (user_id, bon_id, action, type_action, details, ip_address) VALUES (:user_id, :bon_id, 'Suppression d\'un bon', 'suppression', :details, :ip_address)")->execute(['user_id' => $user['id'], 'bon_id' => $bonId, 'details' => "Bon supprimé: " . $bon['numero_bon'], 'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
    $pdo->commit();
    $_SESSION['success_message'] = 'Bon supprimé avec succès.';
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log("Erreur suppression bon: " . $e->getMessage());
    $_SESSION['error_message'] = 'Erreur lors de la suppression.';
}
header('Location: index.php'); exit();
?>






