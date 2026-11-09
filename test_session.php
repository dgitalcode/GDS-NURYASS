<?php
/**
 * Script de test pour diagnostiquer les problèmes de session
 */

// Démarrer la session
session_start();

echo "<h2>Test de Session</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? "Active" : "Inactive") . "\n";
echo "\nVariables de session:\n";
print_r($_SESSION);
echo "\n\n";
echo "Script Name: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "PHP Self: " . $_SERVER['PHP_SELF'] . "\n";
echo "</pre>";

// Tester la connexion
require_once 'includes/session.php';

echo "<h3>Test isLoggedIn()</h3>";
echo "isLoggedIn(): " . (isLoggedIn() ? "TRUE" : "FALSE") . "<br>";

if (isLoggedIn()) {
    $user = getCurrentUser();
    echo "<pre>";
    print_r($user);
    echo "</pre>";
} else {
    echo "<p>Vous n'êtes pas connecté. <a href='login.php'>Se connecter</a></p>";
}






