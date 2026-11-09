<?php
/**
 * Fichier de test simple
 */
echo "<h1>Test PHP - GDS NURYASS</h1>";
echo "<p>Si vous voyez ce message, PHP fonctionne correctement!</p>";
echo "<p>Chemin du script: " . __FILE__ . "</p>";
echo "<p>URL actuelle: " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p>Script name: " . $_SERVER['SCRIPT_NAME'] . "</p>";
echo "<hr>";
echo "<h2>Liens de test:</h2>";
echo "<ul>";
echo "<li><a href='index.php'>index.php</a></li>";
echo "<li><a href='login.php'>login.php</a></li>";
echo "<li><a href='dashboard.php'>dashboard.php</a></li>";
echo "<li><a href='clients/index.php'>clients/index.php</a></li>";
echo "<li><a href='produits/index.php'>produits/index.php</a></li>";
echo "</ul>";
?>






