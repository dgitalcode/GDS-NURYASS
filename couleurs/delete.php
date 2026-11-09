<?php
/**
 * Supprimer une couleur
 * GDS - Stock Management System
 */

require_once '../includes/session.php';
require_once '../config/database.php';

requireLogin('../login.php');

if (!isAdmin()) {
    $_SESSION['error_message'] = 'Accès refusé.';
    header('Location: ' . url('dashboard.php'));
    exit();
}

$couleurId = intval($_GET['id'] ?? 0);

if (!$couleurId) {
    $_SESSION['error_message'] = 'ID couleur invalide.';
    header('Location: index.php');
    exit();
}

try {
    $pdo = getDBConnection();
    
    // Vérifier si la couleur existe
    $stmt = $pdo->prepare("SELECT * FROM couleurs WHERE id = :id");
    $stmt->execute(['id' => $couleurId]);
    $couleur = $stmt->fetch();
    
    if (!$couleur) {
        $_SESSION['error_message'] = 'Couleur introuvable.';
        header('Location: index.php');
        exit();
    }
    
    // Vérifier s'il y a des produits qui utilisent cette couleur
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM produits WHERE couleur_id = :couleur_id");
    $stmt->execute(['couleur_id' => $couleurId]);
    $nbProduits = $stmt->fetchColumn();
    
    if ($nbProduits > 0) {
        $_SESSION['error_message'] = "Impossible de supprimer cette couleur car elle est utilisée par $nbProduits produit(s). Veuillez d'abord modifier ou supprimer les produits associés.";
        header('Location: index.php');
        exit();
    }
    
    // Supprimer la couleur
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("DELETE FROM couleurs WHERE id = :id");
    $stmt->execute(['id' => $couleurId]);
    
    // Historique
    $user = getCurrentUser();
    $stmt = $pdo->prepare("
        INSERT INTO historique (user_id, action, type_action, details, ip_address)
        VALUES (:user_id, :action, :type_action, :details, :ip_address)
    ");
    $stmt->execute([
        'user_id' => $user['id'],
        'action' => 'Suppression d\'une couleur',
        'type_action' => 'suppression',
        'details' => "Couleur supprimée: " . $couleur['nom_couleur'],
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    $pdo->commit();
    
    $_SESSION['success_message'] = 'Couleur supprimée avec succès.';
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erreur suppression couleur: " . $e->getMessage());
    $_SESSION['error_message'] = 'Erreur lors de la suppression de la couleur.';
}

header('Location: index.php');
exit();





