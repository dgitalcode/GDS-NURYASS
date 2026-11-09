<?php
/**
 * Script pour corriger automatiquement tous les chemins relatifs
 * À exécuter une seule fois
 */

function fixFile($filePath) {
    if (!file_exists($filePath)) return;
    
    $content = file_get_contents($filePath);
    $original = $content;
    
    // Remplacer les patterns de chemins relatifs
    $patterns = [
        // Breadcrumbs
        '/href="\.\.\/dashboard\.php"/' => 'href="<?php echo url(\'dashboard.php\'); ?>"',
        '/href="\.\.\/clients\//' => 'href="<?php echo url(\'clients/',
        '/href="\.\.\/produits\//' => 'href="<?php echo url(\'produits/',
        '/href="\.\.\/bons\//' => 'href="<?php echo url(\'bons/',
        '/href="\.\.\/credits\//' => 'href="<?php echo url(\'credits/',
        '/href="\.\.\/factures\//' => 'href="<?php echo url(\'factures/',
        '/href="\.\.\/historique\//' => 'href="<?php echo url(\'historique/',
        '/href="\.\.\/users\//' => 'href="<?php echo url(\'users/',
        '/href="\.\.\/types_produits\//' => 'href="<?php echo url(\'types_produits/',
        '/href="\.\.\/couleurs\//' => 'href="<?php echo url(\'couleurs/',
        '/href="\.\.\/auth\//' => 'href="<?php echo url(\'auth/',
        
        // Depuis la racine
        '/href="dashboard\.php"/' => 'href="<?php echo url(\'dashboard.php\'); ?>"',
        '/href="clients\/index\.php"/' => 'href="<?php echo url(\'clients/index.php\'); ?>"',
        '/href="produits\/index\.php"/' => 'href="<?php echo url(\'produits/index.php\'); ?>"',
        '/href="bons\/index\.php"/' => 'href="<?php echo url(\'bons/index.php\'); ?>"',
        '/href="credits\/index\.php"/' => 'href="<?php echo url(\'credits/index.php\'); ?>"',
        '/href="factures\/index\.php"/' => 'href="<?php echo url(\'factures/index.php\'); ?>"',
        '/href="historique\/index\.php"/' => 'href="<?php echo url(\'historique/index.php\'); ?>"',
        
        // Actions rapides dans dashboard
        '/href="clients\/create\.php"/' => 'href="<?php echo url(\'clients/create.php\'); ?>"',
        '/href="produits\/create\.php"/' => 'href="<?php echo url(\'produits/create.php\'); ?>"',
        '/href="bons\/create\.php"/' => 'href="<?php echo url(\'bons/create.php\'); ?>"',
        '/href="factures\/create\.php"/' => 'href="<?php echo url(\'factures/create.php\'); ?>"',
    ];
    
    foreach ($patterns as $pattern => $replacement) {
        $content = preg_replace($pattern, $replacement, $content);
    }
    
    if ($content !== $original) {
        file_put_contents($filePath, $content);
        echo "✓ Corrigé: $filePath\n";
    }
}

// Liste des fichiers à corriger
$files = [
    'dashboard.php',
    'clients/index.php',
    'clients/create.php',
    'clients/edit.php',
    'clients/view.php',
    'produits/index.php',
    'produits/create.php',
    'produits/edit.php',
    'produits/view.php',
    'bons/index.php',
    'bons/create.php',
    'bons/view.php',
    'credits/index.php',
    'credits/view.php',
    'factures/index.php',
    'factures/create.php',
    'factures/view.php',
    'historique/index.php',
];

foreach ($files as $file) {
    if (file_exists($file)) {
        fixFile($file);
    }
}

echo "\n✅ Correction terminée!\n";






