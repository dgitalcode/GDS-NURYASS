# GDS - Stock Management System pour NURYASS

Système de gestion de stock, bons, factures et crédits clients.

## 📋 Prérequis

- XAMPP (PHP 7.4+ et MySQL)
- Navigateur web moderne

## 🚀 Installation

### 1. Configuration de la base de données

1. Ouvrez phpMyAdmin (http://localhost/phpmyadmin)
2. Créez une nouvelle base de données nommée `gds_nuryass`
3. Importez le fichier `db.sql` dans cette base de données

**OU** exécutez le script de configuration automatique :

```bash
php config/setup.php
```

### 2. Configuration de la connexion

Si nécessaire, modifiez les paramètres dans `config/database.php` :

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'gds_nuryass');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 3. Accès à l'application

1. Démarrez Apache et MySQL dans XAMPP
2. Accédez à : `http://localhost/GDS-NURYASS/login.php`

### 4. Connexion par défaut

- **Username:** `admin`
- **Password:** `admin123`

⚠️ **IMPORTANT:** Changez le mot de passe admin après la première connexion!

## 📁 Structure du projet

```
GDS-NURYASS/
├── config/
│   ├── database.php      # Configuration de la base de données
│   └── setup.php         # Script d'installation
├── includes/
│   └── session.php       # Gestion des sessions
├── auth/
│   ├── login_process.php # Traitement de la connexion
│   └── logout.php        # Déconnexion
├── login.php             # Page de connexion
├── index.php             # Page d'accueil (redirection)
└── db.sql                # Schéma de la base de données
```

## 🔐 Sécurité

- Mots de passe hashés avec `password_hash()`
- Protection contre les injections SQL (PDO avec requêtes préparées)
- Gestion sécurisée des sessions
- Protection CSRF (à implémenter)

## 👥 Rôles utilisateurs

- **Admin:** Accès complet au système
- **Caissier:** Accès limité aux opérations de caisse

## 📝 Fonctionnalités

- ✅ Authentification sécurisée
- 🔄 Gestion des sessions
- 📊 Dashboard (à venir)
- 👥 Gestion des clients
- 📦 Gestion des produits
- 🧾 Gestion des bons (entrée/sortie)
- 💳 Gestion des crédits
- 🧾 Génération de factures PDF
- 📜 Historique des opérations

## 🛠️ Technologies utilisées

- **Backend:** PHP 7.4+
- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap 5
- **Base de données:** MySQL
- **PDF:** (à implémenter) TCPDF ou FPDF

## 📞 Support

Pour toute question ou problème, contactez l'administrateur système.






