<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SESSION['role'] !== 'ADMIN') {
    header('Location: /');
    exit;
}

$message = "";

// --- RÉCUPÉRATION DES SITES ET LEURS SUPERVISEURS ---
$sites = $pdo->query("
    SELECT s.id_site, s.nom_site, u.nom, u.prenom 
    FROM sites s 
    LEFT JOIN users u ON s.id_site = u.id_site AND u.role = 'SUPERVISEUR'
    ORDER BY s.nom_site ASC
")->fetchAll(PDO::FETCH_ASSOC);

// --- LOGIQUE DE TRAITEMENT (INCHANGÉE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'creer_produit') {
        $nom = trim($_POST['nom_produit']);
        $seuil = intval($_POST['seuil'] ?? 5);
        $prix = floatval($_POST['prix_unitaire'] ?? 0);
        $unite = trim($_POST['unite_mesure'] ?? 'Unité');
        
        $stmt = $pdo->prepare("INSERT INTO produits_admin (nom_produit, seuil_alerte, prix_unitaire, unite_mesure) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nom, $seuil, $prix, $unite]);
        $message = "Produit ajouté au catalogue.";
    }

    if ($action === 'mouvement') {
        $id_p = intval($_POST['id_produit']);
        $type = $_POST['type'];
        $qte = intval($_POST['quantite']);
        $prix_saisi = floatval($_POST['prix_saisi'] ?? 0);
        $id_site = ($type === 'SORTIE') ? intval($_POST['id_site_destination']) : null;
        
        $operateur = ($type === 'ENTREE') ? "+" : "-";
        
        $sql_up = "UPDATE produits_admin SET quantite_globale = quantite_globale $operateur ? WHERE id_produit_admin = ?";
        $pdo->prepare($sql_up)->execute([$qte, $id_p]);

        // INSERTION AVEC LE SITE
        $pdo->prepare("INSERT INTO mouvements_stock_admin (id_produit_admin, type_mouvement, quantite, prix_mouvement, commentaire, id_site_destination) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute([$id_p, $type, $qte, $prix_saisi, $_POST['commentaire'] ?? '', $id_site]);
            
        $message = "Stock mis à jour avec succès.";
    }
}

// Filtre par Action (Nouveau)
if (isset($_GET['f_action']) && $_GET['f_action'] !== '') {
    $where_clauses[] = "m.type_mouvement = ?";
    $params[] = $_GET['f_action'];
}

// --- LOGIQUE DE FILTRAGE (INCHANGÉE) ---
$where_clauses = [];
$params = [];
if (isset($_GET['f_produit']) && $_GET['f_produit'] !== '') { $where_clauses[] = "m.id_produit_admin = ?"; $params[] = (int)$_GET['f_produit']; }
if (isset($_GET['f_action']) && $_GET['f_action'] !== '') { $where_clauses[] = "m.type_mouvement = ?"; $params[] = $_GET['f_action']; }
if (!empty($_GET['f_date_debut']) && !empty($_GET['f_date_fin'])) {
    $where_clauses[] = "DATE(m.date_mouvement) BETWEEN ? AND ?";
    $params[] = $_GET['f_date_debut']; $params[] = $_GET['f_date_fin'];
}

// --- MISE À JOUR DE LA REQUÊTE DU TABLEAU HISTORIQUE ---
$sql_flux = "SELECT m.*, p.nom_produit, p.unite_mesure, s.nom_site, u.nom as sup_nom, u.prenom as sup_prenom 
             FROM mouvements_stock_admin m 
             JOIN produits_admin p ON m.id_produit_admin = p.id_produit_admin
             LEFT JOIN sites s ON m.id_site_destination = s.id_site
             LEFT JOIN users u ON s.id_site = u.id_site AND u.role = 'SUPERVISEUR'";
if (!empty($where_clauses)) { $sql_flux .= " WHERE " . implode(" AND ", $where_clauses); }
$sql_flux .= " ORDER BY m.date_mouvement DESC LIMIT 30";
$stmt_flux = $pdo->prepare($sql_flux);
$stmt_flux->execute($params);
$flux = $stmt_flux->fetchAll(PDO::FETCH_ASSOC);

$inventaire = $pdo->query("SELECT * FROM produits_admin ORDER BY nom_produit ASC")->fetchAll(PDO::FETCH_ASSOC);
$nb_critique = 0;
foreach($inventaire as $inv) { if($inv['quantite_globale'] <= $inv['seuil_alerte']) $nb_critique++; }

// Initialisation du total général
$total_general_periode = 0;

// Dans votre boucle d'affichage ou avant l'export
foreach ($flux as &$f) {
    // Calcul du montant par ligne
    $f['montant_ligne'] = $f['quantite'] * $f['prix_mouvement'];
    // Accumulation pour le total en bas de tableau
    $total_general_periode += $f['montant_ligne'];
}
?>

<?php include_once __DIR__ . '/../includes/header.php'; ?>

<style>
    :root { --accent-gradient: linear-gradient(135deg, #FF9800 0%, #F57C00 100%); --shadow: 0 8px 30px rgba(0,0,0,0.08); }
    
    /* RESET CRITIQUE POUR MOBILE */
    * { box-sizing: border-box; }
    body, html { overflow-x: auto; width: 100%; margin: 0; padding: 0; }
    
    /* Adaptabilité Globale */
    .admin-container { max-width: 1400px; margin: 20px auto; padding: 0 15px; box-sizing: border-box; }
    .header-section { background: var(--accent-gradient); padding: 30px 20px; border-radius: 20px; color: white; margin-bottom: 25px; text-align: center; box-shadow: 0 10px 20px rgba(255, 152, 0, 0.2); }
    
    /* Grille Responsive */
    .grid-admin { display: grid; grid-template-columns: 1fr; gap: 20px; }
    @media (min-width: 1024px) { .grid-admin { grid-template-columns: 350px 1fr; } }

    /* Cartes */
    .stock-card { background: white; border-radius: 18px; padding: 20px; box-shadow: var(--shadow); border: 1px solid #edf2f7; box-sizing: border-box; }
    
    /* Formulaires Responsifs (Flex-Wrap) */
    .responsive-form { display: flex; flex-wrap: wrap; gap: 10px; width: 100%; }
    .filter-input { flex: 1; min-width: 140px; padding: 12px; border-radius: 8px; border: 1px solid #ddd; font-size: 16px; box-sizing: border-box; }
    
    /* Tableaux Responsifs (Scroll) */
    .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; margin-top: 10px; }
    .table-nms { width: 100%; border-collapse: collapse; min-width: 600px; }
    .table-nms th { text-align: left; padding: 12px; color: #94a3b8; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid #eee; }
    .table-nms td { padding: 12px; border-bottom: 1px solid #f8fafc; }

    /* Widgets */
    .stats-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 25px; }
    .status-pill { padding: 4px 10px; border-radius: 50px; font-size: 10px; font-weight: 800; }
    .status-pill.ok { background: #dcfce7; color: #16a34a; }
    .status-pill.low { background: #fff1f2; color: #e11d48; }
    .user_profile_btn { background: var(--accent-gradient); color: white; border: none; padding: 14px; border-radius: 10px; font-weight: 700; cursor: pointer; width: 100%; }

    /* Barre de recherche */
    .search-bar-container { position: relative; margin-bottom: 15px; }
    .search-bar-container input { width: 100%; padding: 10px 40px 10px 15px; border-radius: 10px; border: 1px solid #e2e8f0; outline: none; transition: 0.3s; }
    .search-bar-container input:focus { border-color: #F57C00; box-shadow: 0 0 0 3px rgba(245, 124, 0, 0.1); }
</style>

<div class="admin-container">
    <div class="header-section">
        <h1 style="margin:0;">🛡️ Réserve Admin Intelligence</h1>
        <p style="margin:5px 0 0; opacity: 0.9;">Gestion financière et matérielle</p>
    </div>

    <div class="stats-container">
        <div class="stock-card" style="border-left: 5px solid #ef4444;">
            <small style="color: #94a3b8; font-weight: 800;">ALERTE RÉAPPRO</small>
            <div style="font-size: 24px; font-weight: 900;"><?= $nb_critique ?> Produits</div>
        </div>
        <div class="stock-card" style="border-left: 5px solid #10b981;">
            <small style="color: #94a3b8; font-weight: 800;">VALEUR DU STOCK</small>
            <?php $total_v = 0; foreach($inventaire as $v) { $total_v += ($v['quantite_globale'] * $v['prix_unitaire']); } ?>
            <div style="font-size: 24px; font-weight: 900; color: #10b981;"><?= number_format($total_v, 0, '.', ' ') ?> FCFA</div>
        </div>
    </div>

    <div class="grid-admin">
        <div style="display: flex; flex-direction: column; gap: 20px;overflow: auto;">
            <div class="stock-card">
                <h3>✨ Nouveau Produit</h3>
                <form method="post" class="responsive-form">
                    <input type="hidden" name="action" value="creer_produit">
                    <input type="text" name="nom_produit" placeholder="Nom du produit" required class="filter-input" style="flex: 2;">
                    <select name="unite_mesure" class="filter-input">
                        <option value="U">Unité (U)</option>
                        <option value="Carton">Carton</option>
                        <option value="Paquet">Paquet</option>
                        <option value="Litre">Litre</option>
                    </select>
                    <input type="number" step="0.01" name="prix_unitaire" placeholder="Prix d'achat" class="filter-input">
                    <input type="number" name="seuil" placeholder="Seuil" class="filter-input">
                    <button type="submit" class="user_profile_btn">Enregistrer</button>
                </form>
            </div>

            <div class="stock-card">
                <h3>🔄 Mouvement Stock</h3>
                <form method="post" class="responsive-form" id="mouvementForm">
                    <input type="hidden" name="action" value="mouvement">
                    
                    <select name="id_produit" required class="filter-input" style="flex: 2;">
                        <?php foreach($inventaire as $i): ?>
                            <option value="<?= $i['id_produit_admin'] ?>"><?= htmlspecialchars($i['nom_produit']) ?> (<?= $i['unite_mesure'] ?>)</option>
                        <?php endforeach; ?>
                    </select>

                    <select name="type" id="typeMouvement" class="filter-input" onchange="toggleSiteSelection()">
                        <option value="ENTREE">Entrée (+)</option>
                        <option value="SORTIE">Sortie (-)</option>
                    </select>

                    <select name="id_site_destination" id="siteSelection" class="filter-input" style="display:none; border: 2px solid #FF9800;">
                        <option value="">-- Choisir Site Destination --</option>
                        <?php foreach($sites as $st): ?>
                            <option value="<?= $st['id_site'] ?>">
                                <?= htmlspecialchars($st['nom_site']) ?> (Sup: <?= htmlspecialchars($st['nom'] ?? 'Aucun') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <input type="number" name="quantite" placeholder="Qté" required class="filter-input">
                    <input type="number" step="0.01" name="prix_saisi" placeholder="Prix Unit." class="filter-input">
                    <button type="submit" class="user_profile_btn" style="background:green;">Valider</button>
                </form>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 20px; overflow: auto;">
            <div class="stock-card">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 15px;">
                    <h3 style="margin:0;">📊 État du Stock</h3>

                    <button type="button" onclick="window.location.href='export_stock.php'" style="background:#059669; color:white; border:none; padding:8px 15px; border-radius:8px; cursor:pointer; font-weight:bold; font-size: 13px;">
                        📥 Exporter État Actuel
                    </button>

                    <div class="search-bar-container">
                        <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="🔍 Rechercher un produit...">
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table-nms" id="stockTable">
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th>Prix Unit.</th>
                                <th>Stock</th>
                                <th>Valeur</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($inventaire as $i): 
                                $is_low = ($i['quantite_globale'] <= $i['seuil_alerte']);
                                $val = $i['quantite_globale'] * $i['prix_unitaire'];
                            ?>
                            <tr>
                                <td style="font-weight:700;"><?= htmlspecialchars($i['nom_produit']) ?><br><small style="color:#94a3b8"><?= $i['unite_mesure'] ?></small></td>
                                <td><?= number_format($i['prix_unitaire'], 0, '.', ' ') ?></td>
                                <td><span style="font-weight:800; background:#f1f5f9; padding:4px 8px; border-radius:5px;"><?= $i['quantite_globale'] ?></span></td>
                                <td style="font-weight:800;"><?= number_format($val, 0, '.', ' ') ?></td>
                                <td><span class="status-pill <?= $is_low ? 'low' : 'ok' ?>"><?= $is_low ? '⚠️ BAS' : '✅ OK' ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="stock-card">
                <h3>📜 Historique des flux</h3>
                <form method="GET" class="responsive-form" style="background: #f8fafc; padding: 15px; border-radius: 12px; margin-bottom: 15px;">
                    <input type="date" name="f_date_debut" class="filter-input" value="<?= $_GET['f_date_debut'] ?? '' ?>">
                    <input type="date" name="f_date_fin" class="filter-input" value="<?= $_GET['f_date_fin'] ?? '' ?>">

                    <select name="f_action" class="filter-input">
                        <option value="">-- Toutes les actions --</option>
                        <option value="ENTREE" <?= (isset($_GET['f_action']) && $_GET['f_action'] === 'ENTREE') ? 'selected' : '' ?>>Entrées uniquement</option>
                        <option value="SORTIE" <?= (isset($_GET['f_action']) && $_GET['f_action'] === 'SORTIE') ? 'selected' : '' ?>>Sorties uniquement</option>
                    </select>
                    <button type="submit" style="background:#FF9800; color:white; border:none; padding:10px 20px; border-radius:8px; cursor:pointer;">Filtrer</button>
                    <button type="button" onclick="window.location.href='gest_stock.php'" style="background:#64748b; color:white; border:none; padding:10px 20px; border-radius:8px; cursor:pointer;">Réinitialiser</button> 
                    <button type="button" onclick="exportExcel()" style="background:#27ae60; color:white; border:none; padding:10px 20px; border-radius:8px; cursor:pointer; font-weight:bold;">
                        📥 Exporter Excel
                    </button>
                </form>

                <div class="table-responsive">
                    <table class="table-nms">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Produit</th>
                                <th>Action</th>
                                <th>Destination / Superviseur</th> <th style="text-align:right;">Quantité</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($flux as $f): $is_e = ($f['type_mouvement'] == 'ENTREE'); ?>
                            <tr>
                                <td><small><?= date('d/m/y H:i', strtotime($f['date_mouvement'])) ?></small></td>
                                <td style="font-weight:700;"><?= htmlspecialchars($f['nom_produit']) ?></td>
                                <td><span style="font-size:10px; font-weight:800; color:<?= $is_e ? '#16a34a':'#ef4444'?>;"><?= $f['type_mouvement'] ?></span></td>
                                
                                <td>
                                    <?php if($f['type_mouvement'] === 'SORTIE' && $f['nom_site']): ?>
                                        <div style="font-size: 12px; font-weight: bold; color: #F57C00;">📍 <?= htmlspecialchars($f['nom_site']) ?></div>
                                        <div style="font-size: 10px; color: #64748b;">👤 Sup: <?= htmlspecialchars($f['sup_nom'] . ' ' . $f['sup_prenom']) ?></div>
                                    <?php else: ?>
                                        <span style="color: #cbd5e1;">---</span>
                                    <?php endif; ?>
                                </td>

                                <td style="text-align:right; font-weight:800;"><?= $is_e ? '+':'-' ?><?= $f['quantite'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Fonction de recherche instantanée
function searchTable() {
    let input = document.getElementById("searchInput");
    let filter = input.value.toUpperCase();
    let table = document.getElementById("stockTable");
    let tr = table.getElementsByTagName("tr");

    for (let i = 1; i < tr.length; i++) {
        let td = tr[i].getElementsByTagName("td")[0];
        if (td) {
            let textValue = td.textContent || td.innerText;
            tr[i].style.display = textValue.toUpperCase().indexOf(filter) > -1 ? "" : "none";
        }
    }
}
function exportExcel() {
    // On récupère les valeurs actuelles des filtres
    const debut = document.querySelector('input[name="f_date_debut"]').value;
    const fin = document.querySelector('input[name="f_date_fin"]').value;
    const action = document.querySelector('select[name="f_action"]').value;
    
    // On redirige vers la page d'export avec les paramètres
    window.location.href = `export_inventaire2.php?f_date_debut=${debut}&f_date_fin=${fin}&f_action=${action}`;
}

function toggleSiteSelection() {
    const type = document.getElementById('typeMouvement').value;
    const siteSelect = document.getElementById('siteSelection');
    
    if (type === 'SORTIE') {
        siteSelect.style.display = 'block';
        siteSelect.setAttribute('required', 'required');
    } else {
        siteSelect.style.display = 'none';
        siteSelect.removeAttribute('required');
    }
}
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>