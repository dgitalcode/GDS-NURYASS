# Correction des Chemins - Instructions

## Problème résolu ✅

J'ai créé une fonction `url()` dans `includes/session.php` qui calcule automatiquement le bon chemin relatif depuis n'importe quel dossier.

## Fichiers déjà corrigés

- ✅ `includes/session.php` - Fonction `url()` ajoutée
- ✅ `includes/header.php` - Tous les liens de navigation corrigés
- ✅ `dashboard.php` - Liens d'actions rapides corrigés
- ✅ `clients/index.php` - Breadcrumb corrigé
- ✅ `clients/create.php` - Breadcrumb corrigé
- ✅ `clients/edit.php` - Breadcrumb corrigé
- ✅ `clients/view.php` - Tous les liens corrigés

## Fichiers à corriger manuellement

Pour corriger les autres fichiers, remplacez tous les chemins relatifs :

**AVANT:**
```php
<a href="../dashboard.php">Dashboard</a>
<a href="../bons/index.php">Bons</a>
```

**APRÈS:**
```php
<a href="<?php echo url('dashboard.php'); ?>">Dashboard</a>
<a href="<?php echo url('bons/index.php'); ?>">Bons</a>
```

## Utilisation de la fonction url()

La fonction `url()` calcule automatiquement le bon chemin depuis n'importe quel dossier :

```php
// Depuis la racine (dashboard.php)
url('clients/index.php') → 'clients/index.php'

// Depuis clients/index.php
url('clients/index.php') → 'clients/index.php'

// Depuis clients/view.php
url('dashboard.php') → '../dashboard.php'
url('bons/index.php') → '../bons/index.php'
```

## Test

1. Accédez à n'importe quelle page
2. Cliquez sur les liens de navigation
3. Tous les liens devraient fonctionner correctement maintenant






