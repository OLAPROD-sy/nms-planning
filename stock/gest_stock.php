<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SESSION['role'] !== 'ADMIN') {
    header('Location: /');
    exit;
}

$message = "";

// --- LOGIQUE DE TRAITEMENT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'creer_produit') {
        $nom = trim($_POST['nom_produit']);
        $seuil = intval($_POST['seuil'] ?? 5);
        $prix = floatval($_POST['prix_unitaire'] ?? 0);
        $unite = trim($_POST['unite_mesure'] ?? 'Unité');
        
        $stmt = $pdo->prepare("INSERT INTO produits_admin (nom_produit, seuil_alerte, prix_unitaire, unite_mesure) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nom, $seuil, $prix, $unite]);
        $message = "Produit ajouté au catalogue personnel.";
    }

    if ($action === 'mouvement') {
        $id_p = intval($_POST['id_produit']);
        $type = $_POST['type'];
        $qte = intval($_POST['quantite']);
        $prix_saisi = floatval($_POST['prix_saisi'] ?? 0);
        
        $operateur = ($type === 'ENTREE') ? "+" : "-";
        
        // Mise à jour du stock et du prix actuel (si Entrée)
        $sql_up = "UPDATE produits_admin SET quantite_globale = quantite_globale $operateur ?";
        $params_up = [$qte];
        if($type === 'ENTREE' && $prix_saisi > 0) {
            $sql_up .= ", prix_unitaire = ?";
            $params_up[] = $prix_saisi;
        }
        $sql_up .= " WHERE id_produit_admin = ?";
        $params_up[] = $id_p;
        
        $pdo->prepare($sql_up)->execute($params_up);

        // Enregistrement du mouvement
        $pdo->prepare("INSERT INTO mouvements_stock_admin (id_produit_admin, type_mouvement, quantite, prix_mouvement, commentaire) VALUES (?, ?, ?, ?, ?)")
            ->execute([$id_p, $type, $qte, $prix_saisi, $_POST['commentaire'] ?? '']);
            
        $message = "Stock mis à jour avec succès.";
    }
}

// --- LOGIQUE DE FILTRAGE ---
$where_clauses = [];
$params = [];

if (isset($_GET['f_produit']) && $_GET['f_produit'] !== '') {
    $where_clauses[] = "m.id_produit_admin = ?";
    $params[] = (int)$_GET['f_produit'];
}
if (isset($_GET['f_action']) && $_GET['f_action'] !== '') {
    $where_clauses[] = "m.type_mouvement = ?";
    $params[] = $_GET['f_action'];
}
if (!empty($_GET['f_date_debut']) && !empty($_GET['f_date_fin'])) {
    $where_clauses[] = "DATE(m.date_mouvement) BETWEEN ? AND ?";
    $params[] = $_GET['f_date_debut'];
    $params[] = $_GET['f_date_fin'];
}

$sql_flux = "SELECT m.*, p.nom_produit, p.unite_mesure FROM mouvements_stock_admin m 
             JOIN produits_admin p ON m.id_produit_admin = p.id_produit_admin";

if (!empty($where_clauses)) { $sql_flux .= " WHERE " . implode(" AND ", $where_clauses); }
$sql_flux .= " ORDER BY m.date_mouvement DESC LIMIT 30";

$stmt_flux = $pdo->prepare($sql_flux);
$stmt_flux->execute($params);
$flux = $stmt_flux->fetchAll(PDO::FETCH_ASSOC);

$inventaire = $pdo->query("SELECT * FROM produits_admin ORDER BY nom_produit ASC")->fetchAll(PDO::FETCH_ASSOC);

// --- CALCULS INTELLIGENTS ---
$nb_critique = 0;
foreach($inventaire as $inv) { if($inv['quantite_globale'] <= $inv['seuil_alerte']) $nb_critique++; }
?>

<?php include_once __DIR__ . '/../includes/header.php'; ?>

<style>
    :root { 
        --accent-gradient: linear-gradient(135deg, #FF9800 0%, #F57C00 100%); 
        --shadow: 0 8px 30px rgba(0,0,0,0.08); 
    }

    /* Container adaptable */
    .admin-container { 
        max-width: 1400px; 
        margin: 10px auto; 
        padding: 0 10px; 
    }

    /* Header plus petit sur mobile */
    .header-section { 
        background: var(--accent-gradient); 
        padding: 20px 15px; 
        border-radius: 15px; 
        color: white; 
        margin-bottom: 20px; 
        text-align: center; 
        box-shadow: 0 10px 20px rgba(255, 152, 0, 0.2); 
    }

    /* GRILLE PRINCIPALE : 1 colonne sur mobile, 2 sur PC */
    .grid-admin { 
        display: grid; 
        grid-template-columns: 1fr; 
        gap: 15px; 
    }

    @media (min-width: 1024px) { 
        .grid-admin { grid-template-columns: 350px 1fr; gap: 25px; } 
    }

    /* STOCK CARD RESPONSIVE */
    .stock-card { 
        background: white; 
        border-radius: 15px; 
        padding: 5px; /* Réduit sur mobile */
        box-shadow: var(--shadow); 
        border: 1px solid #edf2f7; 
        width: 100%; /* S'assure qu'elle ne dépasse pas */
        box-sizing: border-box;
    }

    @media (min-width: 768px) {
        .stock-card { padding: 25px; } /* Plus d'espace sur PC */
    }

    /* Inputs adaptables */
    .filter-input { 
        width: 100%; 
        padding: 12px; 
        border-radius: 10px; 
        border: 1px solid #e2e8f0; 
        margin-bottom: 12px; 
        font-size: 16px; /* Évite le zoom auto sur iPhone */
        box-sizing: border-box;
    }

    /* Tableaux : Scroll horizontal propre */
    .table-responsive { 
        width: 100%; 
        overflow-x: auto; 
        -webkit-overflow-scrolling: touch; 
        margin-top: 10px;
    }

    .table-nms { width: 100%; border-collapse: collapse; min-width: 500px; }
    .table-nms th { text-align: left; padding: 10px; color: #94a3b8; font-size: 10px; text-transform: uppercase; }
    .table-nms td { padding: 12px 10px; border-bottom: 1px solid #f8fafc; font-size: 14px; }

    /* Bouton Propre */
    .user_profile_btn { 
        background: var(--accent-gradient); 
        color: white; 
        border: none; 
        padding: 14px; 
        border-radius: 12px; 
        font-weight: 700; 
        cursor: pointer; 
        width: 100%; 
        display: block;
    }

    /* Widgets du haut (Alerte et Valeur) */
    .stats-container {
        display: grid; 
        grid-template-columns: 1fr; /* 1 par ligne sur mobile */
        gap: 15px; 
        margin-bottom: 20px;
    }

    @media (min-width: 600px) {
        .stats-container { grid-template-columns: 1fr 1fr; } /* 2 par ligne sur tablette/PC */
    }
</style>

<div class="admin-container">
    <div class="header-section">
        <h1 style="margin:0;">🛡️ Réserve Admin Intelligence</h1>
        <p style="margin:5px 0 0; opacity: 0.9;">Gestion financière et matérielle</p>
    </div>

    <div class="stats-container">
        <div class="stock-card" style="border-left: 5px solid #ef4444;">
            <small style="color: #94a3b8; font-weight: 800;">ALERTE RÉAPPRO</small>
            <div style="font-size: 22px; font-weight: 900;"><?= $nb_critique ?> Produits</div>
        </div>
        <div class="stock-card" style="border-left: 5px solid #10b981;">
            <small style="color: #94a3b8; font-weight: 800;">VALEUR DU STOCK</small>
            <?php 
                $total_v = 0; 
                foreach($inventaire as $v) { $total_v += ($v['quantite_globale'] * $v['prix_unitaire']); } 
            ?>
            <div style="font-size: 22px; font-weight: 900; color: #10b981;"><?= number_format($total_v, 0, '.', ' ') ?> <small style="font-size:12px">FCFA</small></div>
        </div>
    </div>

    <div class="grid-admin">
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <div class="stock-card">
                <h3>✨ Nouveau Produit</h3>
                <form method="post">
                    <input type="hidden" name="action" value="creer_produit">
                    <input type="text" name="nom_produit" placeholder="Nom du produit" required class="filter-input">
                    <select name="unite_mesure" class="filter-input">
                        <option value="U">Unité (U)</option>
                        <option value="Carton">Carton</option>
                        <option value="Paquet">Paquet</option>
                        <option value="Litre">Litre</option>
                    </select>
                    <input type="number" step="0.01" name="prix_unitaire" placeholder="Prix d'achat initial" class="filter-input">
                    <input type="number" name="seuil" placeholder="Seuil d'alerte" class="filter-input">
                    <button type="submit" class="user_profile_btn">Enregistrer au Catalogue</button>
                </form>
            </div>

            <div class="stock-card">
                <h3>🔄 Mouvement Stock</h3>
                <form method="post">
                    <input type="hidden" name="action" value="mouvement">
                    <select name="id_produit" required class="filter-input">
                        <?php foreach($inventaire as $i): ?>
                            <option value="<?= $i['id_produit_admin'] ?>"><?= htmlspecialchars($i['nom_produit']) ?> (<?= $i['unite_mesure'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <select name="type" class="filter-input">
                        <option value="ENTREE">Entrée Stock (+)</option>
                        <option value="SORTIE">Sortie Stock (-)</option>
                    </select>
                    <input type="number" name="quantite" placeholder="Quantité" required class="filter-input">
                    <input type="number" step="0.01" name="prix_saisi" placeholder="Prix Unitaire (facultatif)" class="filter-input">
                    <button type="submit" class="user_profile_btn" style="background:#1e293b;">Valider l'opération</button>
                </form>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 20px;">
            <div class="stock-card">
                <h3>📊 État du Stock</h3>
                <div class="table-responsive">
                    <table class="table-nms">
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
                                <td style="font-weight:800;"><?= number_format($val, 0, '.', ' ') ?> FCFA</td>
                                <td><span class="status-pill <?= $is_low ? 'low' : 'ok' ?>"><?= $is_low ? '⚠️ BAS' : '✅ OK' ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="stock-card">
                <h3>📜 Historique des flux</h3>
                <form method="GET" class="filter-bar" style="display: flex; flex-wrap: wrap; gap: 10px; background: #f8fafc; padding: 15px; border-radius: 12px; margin-bottom: 15px;">
                    <input type="date" name="f_date_debut" class="filter-input" style="flex:1; margin:0;" value="<?= $_GET['f_date_debut'] ?? '' ?>">
                    <input type="date" name="f_date_fin" class="filter-input" style="flex:1; margin:0;" value="<?= $_GET['f_date_fin'] ?? '' ?>">
                    <button type="submit" style="background:#FF9800; color:white; border:none; padding:10px 20px; border-radius:8px; cursor:pointer;">🔍 Filtrer la période</button>
                </form>

                <div class="table-responsive">
                    <table class="table-nms">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Produit</th>
                                <th>Action</th>
                                <th style="text-align:right;">Quantité</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($flux as $f): $is_e = ($f['type_mouvement'] == 'ENTREE'); ?>
                            <tr>
                                <td><small><?= date('d/m/y H:i', strtotime($f['date_mouvement'])) ?></small></td>
                                <td style="font-weight:700;"><?= htmlspecialchars($f['nom_produit']) ?></td>
                                <td><span style="font-size:10px; font-weight:800; color:<?= $is_e ? '#16a34a':'#ef4444'?>;"><?= $f['type_mouvement'] ?></span></td>
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

<?php include_once __DIR__ . '/../includes/footer.php'; ?>