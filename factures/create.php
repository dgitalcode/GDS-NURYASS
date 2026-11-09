<?php
require_once '../includes/session.php';
require_once '../config/database.php';
requireLogin('../login.php');
$pageTitle = 'Nouvelle Facture';
$errors = [];
$clientId = intval($_GET['client_id'] ?? 0);
try {
    $pdo = getDBConnection();
    // Récupérer seulement les entreprises
    $entreprises = $pdo->query("SELECT id, nom, nom_entreprise FROM clients WHERE actif = 1 AND type_client = 'entreprise' ORDER BY COALESCE(nom_entreprise, nom)")->fetchAll();
    $produits = $pdo->query("SELECT p.*, tp.nom_type, c.nom_couleur FROM produits p LEFT JOIN types_produits tp ON p.type_id = tp.id LEFT JOIN couleurs c ON p.couleur_id = c.id WHERE p.actif = 1 ORDER BY p.nom_produit")->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur: " . $e->getMessage());
    $entreprises = [];
    $produits = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientId = intval($_POST['client_id'] ?? 0);
    $bonId = intval($_POST['bon_id'] ?? 0);
    $produitsData = $_POST['produits'] ?? [];
    $nouvelleEntreprise = $_POST['nouvelle_entreprise'] ?? '0';
    
    // Si nouvelle entreprise, créer d'abord l'entreprise
    if ($nouvelleEntreprise === '1') {
        $nomEntreprise = trim($_POST['nom_entreprise'] ?? '');
        $patente = trim($_POST['patente'] ?? '');
        $adresse = trim($_POST['adresse'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $creditMax = floatval($_POST['credit_max'] ?? 5000);
        
        if (empty($nomEntreprise)) {
            $errors[] = 'Le nom de l\'entreprise est requis.';
        }
        if (empty($adresse)) {
            $errors[] = 'L\'adresse est requise.';
        }
        
        if (empty($errors)) {
            try {
                $pdo = getDBConnection();
                $pdo->beginTransaction();
                
                // Insérer l'entreprise
                $stmt = $pdo->prepare("
                    INSERT INTO clients (
                        type_client, nom, adresse, 
                        nom_entreprise, patente, telephone, email, credit_max
                    ) VALUES (
                        'entreprise', :nom, :adresse,
                        :nom_entreprise, :patente, :telephone, :email, :credit_max
                    )
                ");
                
                $stmt->execute([
                    'nom' => $nomEntreprise,
                    'adresse' => $adresse,
                    'nom_entreprise' => $nomEntreprise,
                    'patente' => $patente ?: null,
                    'telephone' => $telephone ?: null,
                    'email' => $email ?: null,
                    'credit_max' => $creditMax
                ]);
                
                $clientId = $pdo->lastInsertId();
                
                // Créer l'enregistrement de crédit pour cette entreprise
                $stmt = $pdo->prepare("
                    INSERT INTO credits (client_id, montant_actuel, max_montant, statut)
                    VALUES (:client_id, 0, :max_montant, 'actif')
                ");
                $stmt->execute([
                    'client_id' => $clientId,
                    'max_montant' => $creditMax
                ]);
                
                $pdo->commit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Erreur création entreprise: " . $e->getMessage());
                $errors[] = 'Erreur lors de la création de l\'entreprise.';
            }
        }
    }
    
    // Vérifier qu'on a bien une entreprise (soit existante, soit créée)
    if ($clientId <= 0) {
        if ($nouvelleEntreprise !== '1') {
            $errors[] = 'Entreprise requise.';
        } else {
            // Si on a essayé de créer une nouvelle entreprise mais que ça a échoué
            if (empty($errors)) {
                $errors[] = 'Erreur lors de la création de l\'entreprise.';
            }
        }
    }
    if (empty($produitsData)) {
        $errors[] = 'Au moins un produit requis.';
    }
    
    // Vérifier le stock disponible pour chaque produit
    if (empty($errors) && !empty($produitsData)) {
        try {
            $pdo = getDBConnection();
            foreach ($produitsData as $prod) {
                $produitId = intval($prod['produit_id'] ?? 0);
                $quantiteDemandee = intval($prod['quantite'] ?? 0);
                
                if ($produitId > 0 && $quantiteDemandee > 0) {
                    $stmt = $pdo->prepare("SELECT nom_produit, stock FROM produits WHERE id = :id");
                    $stmt->execute(['id' => $produitId]);
                    $produitInfo = $stmt->fetch();
                    
                    if ($produitInfo && $quantiteDemandee > $produitInfo['stock']) {
                        $errors[] = "STOCK INSUFFISANT! Le produit \"" . $produitInfo['nom_produit'] . "\" n'a que " . $produitInfo['stock'] . " unités en stock, mais vous demandez " . $quantiteDemandee . " unités.";
                    }
                }
            }
        } catch (PDOException $e) {
            error_log("Erreur vérification stock: " . $e->getMessage());
            $errors[] = 'Erreur lors de la vérification du stock.';
        }
    }
    
    if (empty($errors)) {
        try {
            $pdo = getDBConnection();
            $pdo->beginTransaction();
            $user = getCurrentUser();
            $stmt = $pdo->query("SELECT COUNT(*) FROM factures WHERE DATE(date_facture) = CURDATE()");
            $numeroFacture = 'FAC-' . date('Ymd') . '-' . str_pad($stmt->fetchColumn() + 1, 4, '0', STR_PAD_LEFT);
            $total = 0;
            foreach ($produitsData as $prod) {
                $quantite = intval($prod['quantite'] ?? 0);
                $prix = floatval($prod['prix'] ?? 0);
                $total += $quantite * $prix;
            }
            $stmt = $pdo->prepare("INSERT INTO factures (numero_facture, client_id, bon_id, user_id, total) VALUES (:numero, :client_id, :bon_id, :user_id, :total)");
            $stmt->execute(['numero' => $numeroFacture, 'client_id' => $clientId, 'bon_id' => $bonId ?: null, 'user_id' => $user['id'], 'total' => $total]);
            $factureId = $pdo->lastInsertId();
            foreach ($produitsData as $prod) {
                $produitId = intval($prod['produit_id'] ?? 0);
                $quantite = intval($prod['quantite'] ?? 0);
                $prixUnitaire = floatval($prod['prix'] ?? 0);
                if ($produitId > 0 && $quantite > 0) {
                    $pdo->prepare("INSERT INTO factures_details (facture_id, produit_id, quantite, prix_unitaire, sous_total) VALUES (:facture_id, :produit_id, :quantite, :prix_unitaire, :sous_total)")->execute(['facture_id' => $factureId, 'produit_id' => $produitId, 'quantite' => $quantite, 'prix_unitaire' => $prixUnitaire, 'sous_total' => $quantite * $prixUnitaire]);
                    
                    // Mettre à jour le stock (déduire la quantité vendue)
                    $stmt = $pdo->prepare("UPDATE produits SET stock = GREATEST(0, stock - :quantite) WHERE id = :id");
                    $stmt->execute(['quantite' => $quantite, 'id' => $produitId]);
                }
            }
            
            // Mettre à jour le crédit de l'entreprise (ajouter le total de la facture au crédit)
            $stmt = $pdo->prepare("
                UPDATE credits 
                SET montant_actuel = montant_actuel + :total,
                    date_modification = CURRENT_TIMESTAMP
                WHERE client_id = :client_id
            ");
            $stmt->execute(['total' => $total, 'client_id' => $clientId]);
            $pdo->prepare("INSERT INTO historique (user_id, client_id, facture_id, action, type_action, details, ip_address) VALUES (:user_id, :client_id, :facture_id, 'Création d\'une facture', 'creation', :details, :ip_address)")->execute(['user_id' => $user['id'], 'client_id' => $clientId, 'facture_id' => $factureId, 'details' => "Facture créée: $numeroFacture", 'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
            $pdo->commit();
            $_SESSION['success_message'] = 'Facture créée!';
            header('Location: view.php?id=' . $factureId); exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Erreur création facture: " . $e->getMessage());
            $errors[] = 'Erreur lors de la création.';
        }
    }
}

require_once '../includes/header.php';
?>

<div class="top-bar">
    <div>
        <h1 class="page-title">Nouvelle Facture</h1>
    </div>
    <div>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Retour
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" id="error-alert" style="display: none;">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const errorMessages = <?php echo json_encode($errors); ?>;
                            let errorText = '';
                            errorMessages.forEach(function(msg) {
                                errorText += msg + '<br>';
                            });
                            
                            Swal.fire({
                                icon: 'error',
                                title: 'Erreur de validation',
                                html: errorText,
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#dc3545'
                            });
                        });
                    </script>
                <?php endif; ?>
                
                <form method="POST" id="factureForm">
                    <div class="mb-4">
                        <label class="form-label mb-3">Choisir une entreprise <span class="text-danger">*</span></label>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <button type="button" class="btn btn-outline-primary w-100 p-3" id="btn-entreprise-existante" onclick="showEntrepriseExistante()">
                                    <i class="bi bi-building fs-4 d-block mb-2"></i>
                                    <strong>Entreprise existante</strong>
                                </button>
                            </div>
                            <div class="col-md-6">
                                <button type="button" class="btn btn-outline-success w-100 p-3" id="btn-nouvelle-entreprise" onclick="showNouvelleEntreprise()">
                                    <i class="bi bi-plus-circle fs-4 d-block mb-2"></i>
                                    <strong>Nouvelle entreprise</strong>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Sélection d'entreprise existante -->
                        <div id="entreprise-existante-form" style="display: none;" class="mb-3">
                            <label class="form-label">Sélectionner une entreprise <span class="text-danger">*</span></label>
                            <select class="form-select" name="client_id" id="entreprise-select">
                                <option value="">Sélectionner une entreprise</option>
                                <?php foreach ($entreprises as $entreprise): ?>
                                    <option value="<?php echo $entreprise['id']; ?>" <?php echo $clientId == $entreprise['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($entreprise['nom_entreprise'] ?? $entreprise['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Formulaire pour nouvelle entreprise -->
                        <div id="nouvelle-entreprise-form" style="display: none;" class="mb-4 p-3 border rounded bg-light">
                        <h6 class="mb-3">Informations de la nouvelle entreprise</h6>
                        <input type="hidden" name="nouvelle_entreprise" id="nouvelle_entreprise" value="0">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nom de l'entreprise <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nom_entreprise" id="nom_entreprise_new" placeholder="Raison sociale">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Numéro de Patente</label>
                                <input type="text" class="form-control" name="patente" id="patente_new" placeholder="Patente">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Adresse <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="adresse" id="adresse_new" rows="2" placeholder="Adresse complète"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" name="telephone" id="telephone_new" placeholder="Téléphone">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="email_new" placeholder="Email">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Crédit Maximum Autorisé (DH)</label>
                            <input type="number" class="form-control" name="credit_max" id="credit_max_new" value="5000" min="0" step="0.01">
                            <small class="form-text text-muted">Montant maximum de crédit autorisé</small>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h5>Produits</h5>
                    
                    <div id="produits-container">
                        <div class="produit-row mb-3 p-3 border rounded">
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label class="form-label small">Produit</label>
                                    <select class="form-select produit-select" name="produits[0][produit_id]" required>
                                        <option value="">Sélectionner un produit</option>
                                        <?php foreach ($produits as $produit): ?>
                                            <option value="<?php echo $produit['id']; ?>" 
                                                    data-prix="<?php echo number_format($produit['prix'], 2, '.', ''); ?>" 
                                                    data-stock="<?php echo $produit['stock']; ?>">
                                                <?php echo htmlspecialchars($produit['nom_produit'] . ($produit['nom_type'] ? ' (' . $produit['nom_type'] . ($produit['nom_couleur'] ? ' - ' . $produit['nom_couleur'] : '') . ')' : '')); ?>
                                                <?php if ($produit['stock'] <= 0): ?>
                                                    (Stock: 0)
                                                <?php elseif ($produit['stock'] < 10): ?>
                                                    (Stock: <?php echo $produit['stock']; ?>)
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small">Quantité</label>
                                    <input type="number" class="form-control quantite-input" name="produits[0][quantite]" min="1" value="1" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small">Prix Unitaire (DH)</label>
                                    <input type="number" class="form-control prix-input" name="produits[0][prix]" step="0.01" readonly>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small">Sous-Total (DH)</label>
                                    <input type="text" class="form-control sous-total" readonly value="0.00">
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label small">&nbsp;</label>
                                    <button type="button" class="btn btn-danger w-100 remove-produit" style="display: none;" title="Supprimer">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" class="btn btn-outline-primary mb-3" id="add-produit">
                        <i class="bi bi-plus-circle me-2"></i>Ajouter un produit
                    </button>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h4>Total: <span id="total-facture" class="text-primary">0.00</span> DH</h4>
                        </div>
                        <div class="col-md-6 text-end">
                            <a href="index.php" class="btn btn-outline-secondary me-2">Annuler</a>
                            <button type="submit" class="btn btn-primary">Créer la facture</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let produitIndex = 1;

// Fonction pour calculer le sous-total d'une ligne
function calculateSousTotal(row) {
    const select = row.querySelector('.produit-select');
    const quantiteInput = row.querySelector('.quantite-input');
    const prixInput = row.querySelector('.prix-input');
    const sousTotalInput = row.querySelector('.sous-total');
    
    if (select.value) {
        const selectedOption = select.options[select.selectedIndex];
        const prix = parseFloat(selectedOption.dataset.prix) || 0;
        prixInput.value = prix.toFixed(2);
    }
    
    const quantite = parseFloat(quantiteInput.value) || 0;
    const prix = parseFloat(prixInput.value) || 0;
    const sousTotal = quantite * prix;
    
    sousTotalInput.value = sousTotal.toFixed(2);
    calculateTotal();
}

// Fonction pour calculer le total général
function calculateTotal() {
    let total = 0;
    document.querySelectorAll('.sous-total').forEach(input => {
        total += parseFloat(input.value) || 0;
    });
    document.getElementById('total-facture').textContent = total.toFixed(2);
}

// Ajouter un produit
document.getElementById('add-produit').addEventListener('click', function() {
    const container = document.getElementById('produits-container');
    const firstRow = container.firstElementChild;
    const newRow = firstRow.cloneNode(true);
    
    // Réinitialiser les champs
    newRow.querySelectorAll('select, input').forEach(input => {
        if (input.name) {
            input.name = input.name.replace(/\[0\]/, '[' + produitIndex + ']');
        }
        if (input.tagName === 'SELECT') {
            input.value = '';
        } else if (input.classList.contains('quantite-input')) {
            input.value = '1';
        } else if (input.classList.contains('prix-input')) {
            input.value = '';
        } else if (input.classList.contains('sous-total')) {
            input.value = '0.00';
        }
    });
    
    // Afficher le bouton supprimer
    newRow.querySelector('.remove-produit').style.display = 'block';
    
    container.appendChild(newRow);
    produitIndex++;
    
    // Réinitialiser les event listeners pour la nouvelle ligne
    attachEventListeners(newRow);
});

// Supprimer un produit
document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-produit')) {
        const row = e.target.closest('.produit-row');
        const allRows = document.querySelectorAll('.produit-row');
        if (allRows.length > 1) {
            row.remove();
            calculateTotal();
        }
    }
});

// Attacher les event listeners à une ligne
function attachEventListeners(row) {
    const select = row.querySelector('.produit-select');
    const quantiteInput = row.querySelector('.quantite-input');
    const prixInput = row.querySelector('.prix-input');
    
    // Quand on sélectionne un produit
    select.addEventListener('change', function() {
        calculateSousTotal(row);
    });
    
    // Quand on change la quantité
    quantiteInput.addEventListener('input', function() {
        calculateSousTotal(row);
    });
    
    // Quand on change le prix (au cas où on modifie manuellement)
    prixInput.addEventListener('input', function() {
        calculateSousTotal(row);
    });
}

// Attacher les event listeners aux lignes existantes
document.querySelectorAll('.produit-row').forEach(row => {
    attachEventListeners(row);
});

// Calculer le total initial (au cas où il y aurait déjà des valeurs)
calculateTotal();

// Initialiser l'affichage au chargement de la page
// Si un client_id est déjà sélectionné (depuis l'URL), afficher le formulaire d'entreprise existante
document.addEventListener('DOMContentLoaded', function() {
    const select = document.getElementById('entreprise-select');
    if (select && select.value) {
        showEntrepriseExistante();
    }
});

// Fonction pour afficher le formulaire d'entreprise existante
function showEntrepriseExistante() {
    const formExistante = document.getElementById('entreprise-existante-form');
    const formNouvelle = document.getElementById('nouvelle-entreprise-form');
    const hiddenInput = document.getElementById('nouvelle_entreprise');
    const select = document.getElementById('entreprise-select');
    const btnExistante = document.getElementById('btn-entreprise-existante');
    const btnNouvelle = document.getElementById('btn-nouvelle-entreprise');
    
    // Afficher le formulaire d'entreprise existante
    formExistante.style.display = 'block';
    formNouvelle.style.display = 'none';
    hiddenInput.value = '0';
    select.setAttribute('required', 'required');
    
    // Retirer les attributs required des champs de nouvelle entreprise
    document.getElementById('nom_entreprise_new').removeAttribute('required');
    document.getElementById('adresse_new').removeAttribute('required');
    
    // Vider les champs de nouvelle entreprise
    document.getElementById('nom_entreprise_new').value = '';
    document.getElementById('patente_new').value = '';
    document.getElementById('adresse_new').value = '';
    document.getElementById('telephone_new').value = '';
    document.getElementById('email_new').value = '';
    document.getElementById('credit_max_new').value = '5000';
    
    // Mettre à jour les styles des boutons
    btnExistante.classList.remove('btn-outline-primary');
    btnExistante.classList.add('btn-primary');
    btnNouvelle.classList.remove('btn-success');
    btnNouvelle.classList.add('btn-outline-success');
}

// Fonction pour afficher le formulaire de nouvelle entreprise
function showNouvelleEntreprise() {
    const formExistante = document.getElementById('entreprise-existante-form');
    const formNouvelle = document.getElementById('nouvelle-entreprise-form');
    const hiddenInput = document.getElementById('nouvelle_entreprise');
    const select = document.getElementById('entreprise-select');
    const btnExistante = document.getElementById('btn-entreprise-existante');
    const btnNouvelle = document.getElementById('btn-nouvelle-entreprise');
    
    // Afficher le formulaire de nouvelle entreprise
    formExistante.style.display = 'none';
    formNouvelle.style.display = 'block';
    hiddenInput.value = '1';
    select.removeAttribute('required');
    select.value = '';
    
    // Rendre les champs requis
    document.getElementById('nom_entreprise_new').setAttribute('required', 'required');
    document.getElementById('adresse_new').setAttribute('required', 'required');
    
    // Mettre à jour les styles des boutons
    btnNouvelle.classList.remove('btn-outline-success');
    btnNouvelle.classList.add('btn-success');
    btnExistante.classList.remove('btn-primary');
    btnExistante.classList.add('btn-outline-primary');
}

// Validation du formulaire
document.getElementById('factureForm').addEventListener('submit', function(e) {
    const hiddenInput = document.getElementById('nouvelle_entreprise');
    const formNouvelle = document.getElementById('nouvelle-entreprise-form');
    
    // Si nouvelle entreprise, valider les champs
    if (hiddenInput.value === '1' && formNouvelle.style.display !== 'none') {
        const nomEntreprise = document.getElementById('nom_entreprise_new').value.trim();
        const adresse = document.getElementById('adresse_new').value.trim();
        
        if (!nomEntreprise || !adresse) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Champs manquants',
                text: 'Veuillez remplir tous les champs obligatoires pour la nouvelle entreprise.',
                confirmButtonColor: '#dc3545'
            });
            return false;
        }
    } else {
        // Vérifier qu'une entreprise existante est sélectionnée
        const select = document.getElementById('entreprise-select');
        if (!select.value) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Entreprise requise',
                text: 'Veuillez sélectionner une entreprise ou créer une nouvelle entreprise.',
                confirmButtonColor: '#dc3545'
            });
            return false;
        }
    }
    
    // Vérifier le stock avant soumission
    let stockErrors = [];
    document.querySelectorAll('.produit-select').forEach(function(select) {
        if (select.value) {
            const selectedOption = select.options[select.selectedIndex];
            const stock = parseInt(selectedOption.dataset.stock) || 0;
            const quantiteInput = select.closest('.produit-row').querySelector('.quantite-input');
            const quantite = parseInt(quantiteInput.value) || 0;
            const produitNom = selectedOption.text.split('(')[0].trim();
            
            if (quantite > stock) {
                stockErrors.push(`Le produit "${produitNom}" n'a que ${stock} unités en stock, mais vous demandez ${quantite} unités.`);
            }
        }
    });
    
    if (stockErrors.length > 0) {
        e.preventDefault();
        let errorText = stockErrors.join('<br>');
        Swal.fire({
            icon: 'error',
            title: 'Stock insuffisant',
            html: errorText,
            confirmButtonColor: '#dc3545'
        });
        return false;
    }
});
</script>

<style>
#btn-entreprise-existante, #btn-nouvelle-entreprise {
    height: 120px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

#btn-entreprise-existante:hover, #btn-nouvelle-entreprise:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

#btn-entreprise-existante i, #btn-nouvelle-entreprise i {
    font-size: 2.5rem;
}
</style>

<?php require_once '../includes/footer.php'; ?>
