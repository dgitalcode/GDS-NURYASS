<?php
/**
 * Script pour corriger automatiquement TOUS les chemins relatifs dans le projet
 * Exécutez ce script une fois : http://localhost/GDS-NURYASS/fix_all_paths.php
 */

function fixPathsInFile($filePath) {
    if (!file_exists($filePath)) return false;
    
    $content = file_get_contents($filePath);
    $original = $content;
    
    // Patterns de remplacement pour les chemins
    $replacements = [
        // Breadcrumbs - ../dashboard.php
        'href="../dashboard.php"' => 'href="<?php echo url(\'dashboard.php\'); ?>"',
        'href="../clients/' => 'href="<?php echo url(\'clients/',
        'href="../produits/' => 'href="<?php echo url(\'produits/',
        'href="../bons/' => 'href="<?php echo url(\'bons/',
        'href="../credits/' => 'href="<?php echo url(\'credits/',
        'href="../factures/' => 'href="<?php echo url(\'factures/',
        'href="../historique/' => 'href="<?php echo url(\'historique/',
        'href="../users/' => 'href="<?php echo url(\'users/',
        'href="../types_produits/' => 'href="<?php echo url(\'types_produits/',
        'href="../couleurs/' => 'href="<?php echo url(\'couleurs/',
        'href="../auth/' => 'href="<?php echo url(\'auth/',
        
        // Depuis la racine (dashboard.php)
        'href="clients/create.php"' => 'href="<?php echo url(\'clients/create.php\'); ?>"',
        'href="produits/create.php"' => 'href="<?php echo url(\'produits/create.php\'); ?>"',
        'href="bons/create.php"' => 'href="<?php echo url(\'bons/create.php\'); ?>"',
        'href="factures/create.php"' => 'href="<?php echo url(\'factures/create.php\'); ?>"',
        'href="clients/index.php"' => 'href="<?php echo url(\'clients/index.php\'); ?>"',
        'href="produits/index.php"' => 'href="<?php echo url(\'produits/index.php\'); ?>"',
        'href="bons/index.php"' => 'href="<?php echo url(\'bons/index.php\'); ?>"',
    ];
    
    foreach ($replacements as $search => $replace) {
        $content = str_replace($search, $replace, $content);
    }
    
    // Fermer les balises PHP ouvertes dans les href
    $content = preg_replace('/href="<\?php echo url\(\'([^\']+)\'\); \?>"([^<]*)/', 'href="<?php echo url(\'$1\'); ?>">$2', $content);
    
    if ($content !== $original) {
        file_put_contents($filePath, $content);
        return true;
    }
    return false;
}

// Liste de tous les fichiers PHP à corriger
$files = glob('**/*.php', GLOB_BRACE);
$fixed = 0;

foreach ($files as $file) {
    // Ignorer certains fichiers
    if (strpos($file, 'fix_all_paths.php') !== false || 
        strpos($file, 'test') !== false ||
        strpos($file, 'install') !== false ||
        strpos($file, 'create_admin') !== false) {
        continue;
    }
    
    if (fixPathsInFile($file)) {
        echo "✓ Corrigé: $file<br>";
        $fixed++;
    }
}

echo "<br><strong>✅ Correction terminée! $fixed fichiers corrigés.</strong><br>";
echo "<br><a href='dashboard.php'>Retour au Dashboard</a>";






