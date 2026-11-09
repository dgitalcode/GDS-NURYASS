<?php
/**
 * Header et Navigation
 * GDS - Stock Management System
 */

if (!isset($pageTitle)) {
    $pageTitle = 'Dashboard';
}

$currentUser = getCurrentUser();

// Note: La vérification de connexion est déjà faite via requireLogin() dans chaque page
// On ne fait pas de redirection ici pour éviter les boucles infinies
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - NURYASS</title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#764ba2">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="GDS NURYASS">
    <meta name="description" content="Système de gestion de stock, bons, factures et crédits clients pour NURYASS">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="<?php echo url('manifest.json'); ?>">
    
    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" href="<?php echo url('images/icon-192x192.png'); ?>">
    <link rel="apple-touch-icon" sizes="152x152" href="<?php echo url('images/icon-152x152.png'); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo url('images/icon-192x192.png'); ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo url('images/icon-96x96.png'); ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo url('images/icon-96x96.png'); ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* SweetAlert2 Dark Mode Support */
        body.dark-mode .swal2-popup {
            background-color: #2d2d2d !important;
            color: #e0e0e0 !important;
        }
        
        body.dark-mode .swal2-title {
            color: #e0e0e0 !important;
        }
        
        body.dark-mode .swal2-content {
            color: #e0e0e0 !important;
        }
        
        body.dark-mode .swal2-confirm {
            background-color: #667eea !important;
        }
        
        body.dark-mode .swal2-cancel {
            background-color: #6c757d !important;
        }
        
        body.dark-mode .swal2-html-container {
            color: #e0e0e0 !important;
        }
        
        body.dark-mode .swal2-icon.swal2-warning {
            border-color: #ffc107 !important;
            color: #ffc107 !important;
        }
        
        body.dark-mode .swal2-icon.swal2-error {
            border-color: #dc3545 !important;
            color: #dc3545 !important;
        }
        
        body.dark-mode .swal2-icon.swal2-success {
            border-color: #28a745 !important;
            color: #28a745 !important;
        }
        
        body.dark-mode .swal2-icon.swal2-info {
            border-color: #17a2b8 !important;
            color: #17a2b8 !important;
        }
        
        /* SweetAlert2 Toast Styles */
        .swal2-toast {
            background-color: #2d2d2d !important;
        }
        
        body.dark-mode .swal2-toast {
            background-color: #1a1a1a !important;
        }
        
        body.dark-mode .swal2-toast .swal2-title {
            color: #e0e0e0 !important;
        }
        
        body.dark-mode .swal2-toast .swal2-content {
            color: #e0e0e0 !important;
        }
        
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --sidebar-width: 260px;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        /* Dark Mode Styles */
        body.dark-mode {
            background-color: #1a1a1a;
            color: #e0e0e0;
        }
        
        body.dark-mode .top-bar {
            background-color: #2d2d2d;
            color: #e0e0e0;
        }
        
        body.dark-mode .card {
            background-color: #2d2d2d;
            color: #e0e0e0;
            border-color: #404040;
        }
        
        body.dark-mode .card-header {
            background-color: #2d2d2d !important;
            border-bottom-color: #404040;
        }
        
        /* Tableaux - Dark Mode */
        body.dark-mode .table {
            color: #e0e0e0;
            border-color: #404040;
        }
        
        body.dark-mode .table thead th {
            background-color: #1a1a1a;
            color: #e0e0e0;
            border-color: #404040;
        }
        
        body.dark-mode .table tbody td {
            background-color: #2d2d2d;
            color: #e0e0e0;
            border-color: #404040;
        }
        
        body.dark-mode .table tbody tr {
            border-color: #404040;
            background-color: #2d2d2d;
        }
        
        body.dark-mode .table tbody tr:hover {
            background-color: #333333;
        }
        
        body.dark-mode .table tbody tr:hover td {
            background-color: #333333;
            color: #e0e0e0;
        }
        
        /* Table-light (en-têtes Bootstrap) */
        body.dark-mode .table-light,
        body.dark-mode .table-light thead th {
            background-color: #1a1a1a !important;
            color: #e0e0e0 !important;
            border-color: #404040 !important;
        }
        
        /* Table-hover */
        body.dark-mode .table-hover tbody tr {
            background-color: #2d2d2d;
        }
        
        body.dark-mode .table-hover tbody tr:hover {
            background-color: #333333;
            color: #e0e0e0;
        }
        
        body.dark-mode .table-hover tbody tr:hover td {
            background-color: #333333;
            color: #e0e0e0;
        }
        
        /* Table-bordered */
        body.dark-mode .table-bordered {
            border-color: #404040;
        }
        
        body.dark-mode .table-bordered th,
        body.dark-mode .table-bordered td {
            border-color: #404040;
        }
        
        /* Table responsive */
        body.dark-mode .table-responsive {
            border-color: #404040;
        }
        
        body.dark-mode .form-control,
        body.dark-mode .form-select {
            background-color: #1a1a1a;
            border-color: #404040;
            color: #e0e0e0;
        }
        
        body.dark-mode .form-control:focus,
        body.dark-mode .form-select:focus {
            background-color: #1a1a1a;
            border-color: #667eea;
            color: #e0e0e0;
        }
        
        body.dark-mode .breadcrumb-item a {
            color: #667eea;
        }
        
        body.dark-mode .text-muted {
            color: #a0a0a0 !important;
        }
        
        body.dark-mode .stat-card {
            background-color: #2d2d2d;
            color: #e0e0e0;
        }
        
        body.dark-mode .alert {
            background-color: #2d2d2d;
            border-color: #404040;
        }
        
        body.dark-mode .page-title {
            color: #e0e0e0;
        }
        
        body.dark-mode .breadcrumb {
            background-color: transparent;
        }
        
        body.dark-mode .breadcrumb-item.active {
            color: #a0a0a0;
        }
        
        body.dark-mode .btn-outline-primary,
        body.dark-mode .btn-outline-secondary,
        body.dark-mode .btn-outline-info,
        body.dark-mode .btn-outline-danger,
        body.dark-mode .btn-outline-success {
            border-color: #667eea;
            color: #667eea;
        }
        
        body.dark-mode .btn-outline-primary:hover,
        body.dark-mode .btn-outline-secondary:hover,
        body.dark-mode .btn-outline-info:hover {
            background-color: #667eea;
            border-color: #667eea;
            color: #fff;
        }
        
        body.dark-mode .badge {
            background-color: #404040;
            color: #e0e0e0;
        }
        
        body.dark-mode .badge.bg-primary {
            background-color: #667eea !important;
            color: #fff;
        }
        
        body.dark-mode .badge.bg-success {
            background-color: #48bb78 !important;
            color: #fff;
        }
        
        body.dark-mode .badge.bg-danger {
            background-color: #f56565 !important;
            color: #fff;
        }
        
        body.dark-mode .badge.bg-warning {
            background-color: #ed8936 !important;
            color: #000;
        }
        
        body.dark-mode .badge.bg-info {
            background-color: #4299e1 !important;
            color: #fff;
        }
        
        body.dark-mode .badge.bg-secondary {
            background-color: #718096 !important;
            color: #fff;
        }
        
        /* Liens dans les tableaux */
        body.dark-mode .table a {
            color: #667eea;
        }
        
        body.dark-mode .table a:hover {
            color: #764ba2;
        }
        
        /* Textes dans les tableaux */
        body.dark-mode .table strong {
            color: #e0e0e0;
        }
        
        body.dark-mode .table small {
            color: #b0b0b0;
        }
        
        /* Couleurs de texte spécifiques */
        body.dark-mode .text-primary {
            color: #667eea !important;
        }
        
        body.dark-mode .text-success {
            color: #48bb78 !important;
        }
        
        body.dark-mode .text-danger {
            color: #f56565 !important;
        }
        
        body.dark-mode .text-warning {
            color: #ed8936 !important;
        }
        
        body.dark-mode .text-info {
            color: #4299e1 !important;
        }
        
        body.dark-mode .text-secondary {
            color: #a0a0a0 !important;
        }
        
        /* Pagination */
        body.dark-mode .pagination .page-link {
            background-color: #2d2d2d;
            border-color: #404040;
            color: #e0e0e0;
        }
        
        body.dark-mode .pagination .page-link:hover {
            background-color: #333333;
            border-color: #667eea;
            color: #667eea;
        }
        
        body.dark-mode .pagination .page-item.active .page-link {
            background-color: #667eea;
            border-color: #667eea;
            color: #fff;
        }
        
        body.dark-mode .pagination .page-item.disabled .page-link {
            background-color: #1a1a1a;
            border-color: #404040;
            color: #666;
        }
        
        /* Progress bars */
        body.dark-mode .progress {
            background-color: #1a1a1a;
        }
        
        body.dark-mode .progress-bar {
            background-color: #667eea;
        }
        
        /* Labels et headings */
        body.dark-mode label,
        body.dark-mode .form-label {
            color: #e0e0e0;
        }
        
        body.dark-mode h1,
        body.dark-mode h2,
        body.dark-mode h3,
        body.dark-mode h4,
        body.dark-mode h5,
        body.dark-mode h6 {
            color: #e0e0e0;
        }
        
        body.dark-mode .card-body h5,
        body.dark-mode .card-header h5 {
            color: #e0e0e0;
        }
        
        /* Input groups */
        body.dark-mode .input-group-text {
            background-color: #1a1a1a;
            border-color: #404040;
            color: #e0e0e0;
        }
        
        /* List groups */
        body.dark-mode .list-group-item {
            background-color: #2d2d2d;
            border-color: #404040;
            color: #e0e0e0;
        }
        
        body.dark-mode .list-group-item:hover {
            background-color: #333333;
        }
        
        body.dark-mode .settings-dropdown {
            background: rgba(30, 30, 30, 0.98);
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
            color: white;
            z-index: 1000;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        
        .sidebar-header .logo-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0;
            width: 100%;
        }
        
        .sidebar-header .logo-img {
            max-width: 200px;
            max-height: 150px;
            width: auto;
            height: auto;
            object-fit: contain;
            margin: 0 auto;
            padding: 0;
            display: block;
        }
        
        .sidebar-header p {
            margin: 5px 0 0 0;
            font-size: 12px;
            opacity: 0.8;
            display: none;
        }
        
        .sidebar-menu {
            padding: 20px 0;
            display: flex;
            flex-direction: column;
            min-height: calc(100vh - 120px);
        }
        
        .sidebar-menu .settings-menu {
            margin-top: auto;
            margin-bottom: 20px;
        }
        
        .sidebar-menu .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 2px 10px;
            border-radius: 8px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
        }
        
        .sidebar-menu .nav-link:hover,
        .sidebar-menu .nav-link.active {
            background: rgba(255,255,255,0.15);
            color: white;
            transform: translateX(5px);
        }
        
        .sidebar-menu .nav-link i {
            width: 24px;
            margin-right: 12px;
            font-size: 18px;
        }
        
        .settings-menu {
            position: relative;
            margin-top: auto;
        }
        
        .settings-menu .nav-link {
            margin-bottom: 0;
        }
        
        .settings-dropdown {
            position: absolute;
            bottom: 100%;
            left: 0;
            right: 0;
            margin-bottom: 10px;
            background: rgba(0, 0, 0, 0.95);
            border-radius: 8px;
            padding: 0;
            display: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.4);
            z-index: 1001;
            overflow: hidden;
            min-width: 200px;
        }
        
        .settings-menu.active .settings-dropdown {
            display: block;
        }
        
        .settings-menu.active .nav-link {
            background: rgba(255,255,255,0.15);
        }
        
        .settings-dropdown .dropdown-item {
            color: rgba(255,255,255,0.9);
            padding: 12px 20px;
            display: flex;
            align-items: center;
            text-decoration: none;
            transition: all 0.3s;
            border: none;
            width: 100%;
            background: transparent;
        }
        
        .settings-dropdown .dropdown-item:hover {
            background: rgba(255,255,255,0.15);
            color: white;
        }
        
        .settings-dropdown .dropdown-item i {
            width: 24px;
            margin-right: 12px;
            font-size: 18px;
        }
        
        .user-info-dropdown {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.05);
        }
        
        .user-info-dropdown .avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 24px;
        }
        
        .user-info-dropdown .username {
            text-align: center;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 3px;
            color: white;
        }
        
        .user-info-dropdown .role {
            text-align: center;
            font-size: 12px;
            opacity: 0.8;
            color: rgba(255,255,255,0.8);
        }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            min-height: 100vh;
        }
        
        .top-bar {
            background: white;
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            color: #2d3748;
        }
        
        .breadcrumb {
            margin: 0;
            background: none;
            padding: 0;
        }
        
        /* Cards */
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            border-left: 4px solid;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .stat-card.primary { border-left-color: #667eea; }
        .stat-card.success { border-left-color: #48bb78; }
        .stat-card.warning { border-left-color: #ed8936; }
        .stat-card.danger { border-left-color: #f56565; }
        .stat-card.info { border-left-color: #4299e1; }
        
        .stat-card .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 15px;
        }
        
        .stat-card.primary .stat-icon { background: rgba(102, 126, 234, 0.1); color: #667eea; }
        .stat-card.success .stat-icon { background: rgba(72, 187, 120, 0.1); color: #48bb78; }
        .stat-card.warning .stat-icon { background: rgba(237, 137, 54, 0.1); color: #ed8936; }
        .stat-card.danger .stat-icon { background: rgba(245, 101, 101, 0.1); color: #f56565; }
        .stat-card.info .stat-icon { background: rgba(66, 153, 225, 0.1); color: #4299e1; }
        
        .stat-card .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #2d3748;
            margin: 10px 0;
        }
        
        .stat-card .stat-label {
            color: #718096;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Mobile Menu Toggle Button */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1001;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 10px;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            transition: all 0.3s;
        }
        
        .mobile-menu-toggle:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 15px rgba(0,0,0,0.3);
        }
        
        .mobile-menu-toggle.active {
            background: #f56565;
        }
        
        /* Overlay pour mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .sidebar-overlay.show {
            display: block;
            opacity: 1;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
                z-index: 1000;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 70px 15px 20px 15px;
            }
            
            .top-bar {
                padding: 10px 15px;
                margin-bottom: 15px;
            }
            
            .page-title {
                font-size: 20px;
            }
            
            .sidebar-header {
                padding: 20px 15px;
            }
            
            .sidebar-header .logo-img {
                max-width: 150px;
                max-height: 100px;
            }
            
            .sidebar-menu .nav-link {
                padding: 12px 15px;
                font-size: 14px;
            }
            
            .stat-card {
                padding: 20px;
            }
            
            .stat-card .stat-value {
                font-size: 24px;
            }
        }
        
        @media (max-width: 480px) {
            .main-content {
                padding: 60px 10px 15px 10px;
            }
            
            .top-bar {
                padding: 8px 10px;
                flex-direction: column;
                gap: 10px;
            }
            
            .page-title {
                font-size: 18px;
            }
            
            .sidebar-header .logo-img {
                max-width: 120px;
                max-height: 80px;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle Button -->
    <button class="mobile-menu-toggle" id="mobile-menu-toggle" aria-label="Toggle menu">
        <i class="bi bi-list" id="menu-icon"></i>
    </button>
    
    <!-- Overlay pour fermer le menu mobile -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo-container">
                <?php 
                $logoPath = url('images/logo.png');
                $logoExists = file_exists(__DIR__ . '/../images/logo.png');
                if ($logoExists): 
                ?>
                    <img src="<?php echo $logoPath; ?>" alt="NURYASS Logo" class="logo-img">
                <?php else: ?>
                    <!-- Logo par défaut si l'image n'existe pas -->
                    <div style="width: 120px; height: 120px; background: rgba(255,255,255,0.2); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-building" style="font-size: 60px;"></i>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <nav class="sidebar-menu">
            <a href="<?php echo url('dashboard.php'); ?>" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
            
            <a href="<?php echo url('clients/index.php'); ?>" class="nav-link">
                <i class="bi bi-people"></i>
                <span>Clients</span>
            </a>
            
            <a href="<?php echo url('produits/index.php'); ?>" class="nav-link">
                <i class="bi bi-box"></i>
                <span>Produits</span>
            </a>
            
            <a href="<?php echo url('bons/index.php'); ?>" class="nav-link">
                <i class="bi bi-receipt"></i>
                <span>Bons</span>
            </a>
            
            <a href="<?php echo url('credits/index.php'); ?>" class="nav-link">
                <i class="bi bi-credit-card"></i>
                <span>Crédits</span>
            </a>
            
            <a href="<?php echo url('factures/index.php'); ?>" class="nav-link">
                <i class="bi bi-file-earmark-text"></i>
                <span>Factures</span>
            </a>
            
            <a href="<?php echo url('historique/index.php'); ?>" class="nav-link">
                <i class="bi bi-clock-history"></i>
                <span>Historique</span>
            </a>
            
            <?php if (isAdmin()): ?>
            <div class="mt-3 px-3">
                <small class="text-uppercase" style="opacity: 0.6; font-size: 11px;">Administration</small>
            </div>
            
            <a href="<?php echo url('users/index.php'); ?>" class="nav-link">
                <i class="bi bi-person-gear"></i>
                <span>Utilisateurs</span>
            </a>
            
            <a href="<?php echo url('types_produits/index.php'); ?>" class="nav-link">
                <i class="bi bi-tags"></i>
                <span>Types Produits</span>
            </a>
            
            <a href="<?php echo url('couleurs/index.php'); ?>" class="nav-link">
                <i class="bi bi-palette"></i>
                <span>Couleurs</span>
            </a>
            <?php endif; ?>
            
            <!-- Install PWA Button (hidden by default) -->
            <a href="#" class="nav-link" id="install-pwa-btn" style="display: none; visibility: hidden;">
                <i class="bi bi-download"></i>
                <span>Installer l'application</span>
            </a>
            
            <!-- Link to diagnostic if button doesn't appear -->
            <a href="<?php echo url('pwa-install-diagnostic.php'); ?>" class="nav-link">
                <i class="bi bi-question-circle"></i>
                <span>Aide Installation</span>
            </a>
            
            <!-- Settings Menu -->
            <div class="settings-menu">
                <a href="#" class="nav-link" id="settings-toggle">
                    <i class="bi bi-gear"></i>
                    <span>Paramètres</span>
                </a>
                <div class="settings-dropdown">
                    <div class="user-info-dropdown">
                        <div class="avatar">
                            <i class="bi bi-person-circle"></i>
                        </div>
                        <div class="username"><?php echo htmlspecialchars($currentUser['username']); ?></div>
                        <div class="role">
                            <?php echo $currentUser['role'] === 'admin' ? 'Administrateur' : 'Caissier'; ?>
                        </div>
                    </div>
                    <a href="#" class="dropdown-item" id="dark-mode-toggle">
                        <i class="bi bi-moon-stars" id="dark-mode-icon"></i>
                        <span id="dark-mode-text">Mode Sombre</span>
                    </a>
                    <a href="<?php echo url('auth/logout.php'); ?>" class="dropdown-item">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Déconnexion</span>
                    </a>
                </div>
            </div>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Messages de succès/erreur -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

