<?php
/**
 * Script pour générer les icônes PWA à partir du logo existant
 * Exécutez ce script une fois pour créer toutes les icônes nécessaires
 * 
 * Nécessite l'extension GD de PHP
 */

// Vérifier si GD est disponible
if (!extension_loaded('gd')) {
    die("❌ L'extension GD n'est pas installée. Installez-la pour générer les icônes.\n");
}

// Chemin du logo source
$logoPath = __DIR__ . '/images/logo.png';
$outputDir = __DIR__ . '/images/';

// Tailles d'icônes requises pour PWA
$sizes = [72, 96, 128, 144, 152, 192, 384, 512];

// Créer le dossier images s'il n'existe pas
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// Si le logo n'existe pas, créer une icône par défaut
if (!file_exists($logoPath)) {
    echo "⚠️ Logo non trouvé, création d'une icône par défaut...\n";
    createDefaultIcon($outputDir . 'icon-512x512.png', 512);
    $logoPath = $outputDir . 'icon-512x512.png';
}

// Charger l'image source
$sourceImage = imagecreatefromstring(file_get_contents($logoPath));
if (!$sourceImage) {
    die("❌ Impossible de charger l'image source.\n");
}

$sourceWidth = imagesx($sourceImage);
$sourceHeight = imagesy($sourceImage);

echo "📦 Génération des icônes PWA...\n\n";

// Générer chaque taille d'icône
foreach ($sizes as $size) {
    $outputPath = $outputDir . "icon-{$size}x{$size}.png";
    
    // Créer une nouvelle image avec la taille souhaitée
    $newImage = imagecreatetruecolor($size, $size);
    
    // Rendre le fond transparent
    imagealphablending($newImage, false);
    imagesavealpha($newImage, true);
    $transparent = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
    imagefill($newImage, 0, 0, $transparent);
    
    // Calculer les dimensions pour garder les proportions
    $ratio = min($size / $sourceWidth, $size / $sourceHeight);
    $newWidth = $sourceWidth * $ratio;
    $newHeight = $sourceHeight * $ratio;
    $x = ($size - $newWidth) / 2;
    $y = ($size - $newHeight) / 2;
    
    // Redimensionner et copier l'image
    imagecopyresampled(
        $newImage, $sourceImage,
        $x, $y, 0, 0,
        $newWidth, $newHeight,
        $sourceWidth, $sourceHeight
    );
    
    // Sauvegarder l'icône
    imagepng($newImage, $outputPath);
    imagedestroy($newImage);
    
    echo "✅ Créé: icon-{$size}x{$size}.png\n";
}

imagedestroy($sourceImage);

echo "\n✨ Toutes les icônes ont été générées avec succès!\n";
echo "📁 Emplacement: {$outputDir}\n";

/**
 * Créer une icône par défaut avec le nom de l'application
 */
function createDefaultIcon($path, $size) {
    $image = imagecreatetruecolor($size, $size);
    
    // Fond dégradé
    $color1 = imagecolorallocate($image, 102, 126, 234); // #667eea
    $color2 = imagecolorallocate($image, 118, 75, 162); // #764ba2
    
    // Remplir avec un dégradé simple
    for ($y = 0; $y < $size; $y++) {
        $ratio = $y / $size;
        $r = (int)(102 + (118 - 102) * $ratio);
        $g = (int)(126 + (75 - 126) * $ratio);
        $b = (int)(234 + (162 - 234) * $ratio);
        $color = imagecolorallocate($image, $r, $g, $b);
        imageline($image, 0, $y, $size, $y, $color);
    }
    
    // Ajouter du texte (si possible)
    $textColor = imagecolorallocate($image, 255, 255, 255);
    $fontSize = $size / 4;
    $text = "GDS";
    $bbox = imagettfbbox($fontSize, 0, __DIR__ . '/arial.ttf', $text);
    $textWidth = $bbox[4] - $bbox[0];
    $textHeight = $bbox[1] - $bbox[7];
    $x = ($size - $textWidth) / 2;
    $y = ($size + $textHeight) / 2;
    
    // Utiliser une police système si disponible
    $font = null;
    $fonts = [
        'C:/Windows/Fonts/arial.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        '/System/Library/Fonts/Helvetica.ttc'
    ];
    
    foreach ($fonts as $fontPath) {
        if (file_exists($fontPath)) {
            $font = $fontPath;
            break;
        }
    }
    
    if ($font) {
        imagettftext($image, $fontSize, 0, $x, $y, $textColor, $font, $text);
    } else {
        // Utiliser la police par défaut
        imagestring($image, 5, $x, $y - 20, $text, $textColor);
    }
    
    imagepng($image, $path);
    imagedestroy($image);
}




