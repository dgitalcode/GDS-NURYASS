<?php
/**
 * Supprimer un type de produit
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

$typeId = intval($_GET['id'] ?? 0);

if (!$typeId) {
    $_SESSION['error_message'] = 'ID type invalide.';
    header('Location: index.php');
    exit();
}

try {
    $pdo = getDBConnection();
    
    // Vérifier si le type existe
    $stmt = $pdo->prepare("SELECT * FROM types_produits WHERE id = :id");
    $stmt->execute(['id' => $typeId]);
    $type = $stmt->fetch();
    
    if (!$type) {
        $_SESSION['error_message'] = 'Type introuvable.';
        header('Location: index.php');
        exit();
    }
    
    // Vérifier s'il y a des produits qui utilisent ce type
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM produits WHERE type_id = :type_id");
    $stmt->execute(['type_id' => $typeId]);
    $nbProduits = $stmt->fetchColumn();
    
    if ($nbProduits > 0) {
        $_SESSION['error_message'] = "Impossible de supprimer ce type car il est utilisé par $nbProduits produit(s). Veuillez d'abord modifier ou supprimer les produits associés.";
        header('Location: index.php');
        exit();
    }
    
    // Supprimer le type
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("DELETE FROM types_produits WHERE id = :id");
    $stmt->execute(['id' => $typeId]);
    
    // Historique
    $user = getCurrentUser();
    $stmt = $pdo->prepare("
        INSERT INTO historique (user_id, action, type_action, details, ip_address)
        VALUES (:user_id, :action, :type_action, :details, :ip_address)
    ");
    $stmt->execute([
        'user_id' => $user['id'],
        'action' => 'Suppression d\'un type de produit',
        'type_action' => 'suppression',
        'details' => "Type supprimé: " . $type['nom_type'],
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    $pdo->commit();
    
    $_SESSION['success_message'] = 'Type supprimé avec succès.';
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erreur suppression type: " . $e->getMessage());
    $_SESSION['error_message'] = 'Erreur lors de la suppression du type.';
}

header('Location: index.php');
exit();





