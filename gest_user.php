<?php
// ... Gardez toute votre logique PHP identique au début ...
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SESSION['role'] !== 'ADMIN') {
    header('Location: /');
    exit;
}
// ... (Toute la logique de traitement et de filtrage reste la même) ...
$message = "";

// --- LOGIQUE DE TRAITEMENT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // A. Ajouter un nouveau produit au catalogue Admin
    if ($action === 'creer_produit') {
        $nom = trim($_POST['nom_produit']);
        $seuil = intval($_POST['seuil'] ?? 5);
        
        $stmt = $pdo->prepare("INSERT INTO produits_admin (nom_produit, seuil_alerte) VALUES (?, ?)");
        $stmt->execute([$nom, $seuil]);
        $message = "Produit ajouté au catalogue personnel.";
    }

    // B. Gérer une Entrée ou une Sortie
    if ($action === 'mouvement') {
        $id_p = intval($_POST['id_produit']);
        $type = $_POST['type'];
        $qte = intval($_POST['quantite']);
        
        $operateur = ($type === 'ENTREE') ? "+" : "-";
        
        // Mise à jour du stock admin
        $pdo->prepare("UPDATE produits_admin SET quantite_globale = quantite_globale $operateur ? WHERE id_produit_admin = ?")
            ->execute([$qte, $id_p]);

        // Enregistrement du mouvement
        $pdo->prepare("INSERT INTO mouvements_stock_admin (id_produit_admin, type_mouvement, quantite, commentaire) VALUES (?, ?, ?, ?)")
            ->execute([$id_p, $type, $qte, $_POST['commentaire'] ?? '']);
            
        $message = "Stock mis à jour avec succès.";
    }
}

// --- LOGIQUE DE FILTRAGE (MISE À JOUR POUR PÉRIODE) ---
$where_clauses = [];
$params = [];

// Filtre Produit
if (isset($_GET['f_produit']) && $_GET['f_produit'] !== '') {
    $where_clauses[] = "m.id_produit_admin = ?";
    $params[] = (int)$_GET['f_produit'];
}

// Filtre Action
if (isset($_GET['f_action']) && $_GET['f_action'] !== '') {
    $where_clauses[] = "m.type_mouvement = ?";
    $params[] = $_GET['f_action'];
}

// FILTRE PÉRIODE (Date début et Date fin)
if (!empty($_GET['f_date_debut']) && !empty($_GET['f_date_fin'])) {
    // Si les deux dates sont remplies
    $where_clauses[] = "DATE(m.date_mouvement) BETWEEN ? AND ?";
    $params[] = $_GET['f_date_debut'];
    $params[] = $_GET['f_date_fin'];
} elseif (!empty($_GET['f_date_debut'])) {
    // Si seule la date de début est remplie
    $where_clauses[] = "DATE(m.date_mouvement) >= ?";
    $params[] = $_GET['f_date_debut'];
} elseif (!empty($_GET['f_date_fin'])) {
    // Si seule la date de fin est remplie
    $where_clauses[] = "DATE(m.date_mouvement) <= ?";
    $params[] = $_GET['f_date_fin'];
}

// Construction de la requête de base
$sql_flux = "SELECT m.*, p.nom_produit 
             FROM mouvements_stock_admin m 
             JOIN produits_admin p ON m.id_produit_admin = p.id_produit_admin";

// Ajout des conditions si elles existent
if (!empty($where_clauses)) {
    $sql_flux .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql_flux .= " ORDER BY m.date_mouvement DESC LIMIT 30";

// Exécution sécurisée
$stmt_flux = $pdo->prepare($sql_flux);
$stmt_flux->execute($params);
$flux = $stmt_flux->fetchAll(PDO::FETCH_ASSOC);

// --- LECTURE DES DONNÉES ---
$inventaire = $pdo->query("SELECT * FROM produits_admin ORDER BY nom_produit ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include_once __DIR__ . '/../includes/header.php'; ?>

<?php if ($message): ?>
<div id="temp-alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; background: #10b981; color: white; padding: 15px 25px; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); font-weight: bold; animation: slideIn 0.5s ease-out;">
    ✅ <?= $message ?>
</div>
<script>setTimeout(() => { document.getElementById('temp-alert').style.display = 'none'; }, 3000);</script>
<style>@keyframes slideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }</style>
<?php endif; ?>
?>

<?php include_once __DIR__ . '/../includes/header.php'; ?>

<style>
    :root {
        --glass-bg: rgba(255, 255, 255, 0.95);
        --accent-gradient: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
        --shadow: 0 8px 30px rgba(0,0,0,0.08);
        --primary: #FF9800;
    }

    /* Conteneur Principal Responsif */
    .admin-container { 
        max-width: 1400px; 
        margin: 20px auto; 
        padding: 0 15px; 
    }

    /* Header adaptable */
    .header-section {
        background: var(--accent-gradient);
        padding: 20px;
        border-radius: 15px;
        color: white;
        margin-bottom: 25px;
        text-align: center;
        box-shadow: 0 10px 20px rgba(255, 152, 0, 0.2);
    }

    /* GRID PRINCIPAL : 1 colonne sur mobile, 2 sur desktop */
    .grid-admin { 
        display: grid; 
        grid-template-columns: 1fr; 
        gap: 20px; 
    }

    @media (min-width: 1024px) { 
        .grid-admin { 
            grid-template-columns: 350px 1fr; 
        }
    }

    .stock-card { 
        background: white; 
        border-radius: 18px; 
        padding: 20px; 
        box-shadow: var(--shadow); 
        border: 1px solid #edf2f7;
        height: fit-content;
    }

    /* Tableaux Responsifs */
    .table-responsive {
        width: 100%;
        overflow-x: auto; /* Permet le défilement horizontal sur petit écran */
        -webkit-overflow-scrolling: touch;
        margin-top: 10px;
    }

    .table-nms { 
        width: 100%; 
        border-collapse: collapse; 
        min-width: 500px; /* Force une largeur min pour garder la lisibilité */
    }

    .table-nms th { 
        text-align: left; padding: 12px; color: #94a3b8; font-size: 11px; 
        text-transform: uppercase; border-bottom: 1px solid #eee; 
    }
    .table-nms td { padding: 12px; border-bottom: 1px solid #f8fafc; font-size: 14px; }

    /* Barre de Filtres Responsives */
    .filter-bar {
        display: flex;
        flex-wrap: wrap; /* Les filtres passent à la ligne si besoin */
        gap: 15px;
        background: #f8fafc;
        padding: 15px;
        border-radius: 12px;
        margin-bottom: 20px;
    }

    .filter-group { 
        flex: 1 1 200px; /* Grandit et rétrécit, mini 200px */
    }

    .filter-group label { 
        display: block; font-size: 11px; font-weight: 800; color: #94a3b8; 
        margin-bottom: 5px; text-transform: uppercase; 
    }

    .filter-input {
        width: 100%; padding: 10px; border-radius: 8px; 
        border: 1px solid #e2e8f0; font-size: 13px;
    }

    /* Boutons */
    .btn-group-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        width: 100%;
        margin-top: 15px;
    }

    .user_profile_btn, .logout_desktop_btn, .btn-filter, .btn-reset {
        width: 100%;
        padding: 12px;
        border-radius: 10px;
        font-weight: 700;
        text-align: center;
        border: none;
        cursor: pointer;
        font-size: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }

    .user_profile_btn { background: var(--accent-gradient); color: white; }
    .logout_desktop_btn { background: #3b82f6; color: white; }
    .btn-filter { background: #1e293b; color: white; grid-column: span 2; }
    .btn-reset { background: #e2e8f0; color: #64748b; }

    /* Statuts */
    .status-pill {
        padding: 4px 10px;
        border-radius: 50px;
        font-size: 10px;
        font-weight: 800;
        white-space: nowrap;
    }
    .status-pill.ok { background: #dcfce7; color: #16a34a; }
    .status-pill.low { background: #fff1f2; color: #e11d48; border: 1px solid #fecaca; }

    /* Icônes de flux */
    .flow-icon {
        width: 28px; height: 28px; border-radius: 6px;
        display: flex; align-items: center; justify-content: center;
    }
    .icon-entree { background: #dcfce7; color: #16a34a; }
    .icon-sortie { background: #fee2e2; color: #ef4444; }

    @media (max-width: 768px) {
        .header-section { padding: 15px; }
        .stock-card { padding: 15px; }
        .hide-mobile { display: none; }
    }
</style>



<div class="admin-container">
    <div class="header-section">
        <h1 style="margin:0; font-size: 24px;">🛡️ Réserve Admin</h1>
        <p style="margin:5px 0 0; font-size: 14px; opacity: 0.9;">Gestion du stock central</p>
    </div>

    <div class="grid-admin">
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <div class="stock-card">
                <h3 style="font-size:15px; margin-top:0;">✨ Nouveau Produit</h3>
                <form method="post">
                    <input type="hidden" name="action" value="creer_produit">
                    <div class="form-group" style="margin-bottom:10px;">
                        <input type="text" name="nom_produit" class="filter-input" placeholder="Nom du produit" required>
                    </div>
                    <div class="form-group" style="margin-bottom:15px;">
                        <input type="number" name="seuil" class="filter-input" placeholder="Seuil d'alerte (ex: 5)">
                    </div>
                    <button type="submit" class="user_profile_btn">Ajouter au catalogue</button>
                </form>
            </div>

            <div class="stock-card">
                <h3 style="font-size:15px; margin-top:0;">🔄 Mouvement Stock</h3>
                <form method="post">
                    <input type="hidden" name="action" value="mouvement">
                    <select name="id_produit" class="filter-input" style="margin-bottom:10px;" required>
                        <?php foreach($inventaire as $i): ?>
                            <option value="<?= $i['id_produit_admin'] ?>"><?= htmlspecialchars($i['nom_produit']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="type" class="filter-input" style="margin-bottom:10px;">
                        <option value="ENTREE">Encaisser Stock (+)</option>
                        <option value="SORTIE">Décaisser Stock (-)</option>
                    </select>
                    <input type="number" name="quantite" class="filter-input" style="margin-bottom:15px;" placeholder="Quantité" required>
                    <button type="submit" class="logout_desktop_btn">Valider l'opération</button>
                </form>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 20px;">
            <div class="stock-card">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:15px;">
                    <h3 style="margin:0; font-size:16px;">📊 État du Stock</h3>
                    <a href="export_inventaire.php" class="btn-reset" style="padding: 5px 12px; font-size: 11px; background:#6366f1; color:white;">📥 Export</a>
                </div>
                
                <div class="table-responsive">
                    <table class="table-nms">
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th>Quantité</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($inventaire as $i): 
                                $is_low = ($i['quantite_globale'] <= $i['seuil_alerte']);
                            ?>
                            <tr>
                                <td style="font-weight:700;"><?= htmlspecialchars($i['nom_produit']) ?></td>
                                <td><span style="background:#f1f5f9; padding:4px 8px; border-radius:5px; font-weight:800;"><?= $i['quantite_globale'] ?></span></td>
                                <td>
                                    <span class="status-pill <?= $is_low ? 'low' : 'ok' ?>">
                                        <?= $is_low ? '⚠️ FAIBLE' : '✅ OK' ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="stock-card">
                <h3 style="margin-top:0; font-size:16px;">📜 Historique des Flux</h3>
                
                <form method="GET" class="filter-bar">
                    <div class="filter-group">
                        <label>Produit</label>
                        <select name="f_produit" class="filter-input">
                            <option value="">Tous</option>
                            <?php foreach($inventaire as $i): ?>
                                <option value="<?= $i['id_produit_admin'] ?>" <?= (isset($_GET['f_produit']) && $_GET['f_produit'] == $i['id_produit_admin']) ? 'selected' : '' ?>><?= htmlspecialchars($i['nom_produit']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Type</label>
                        <select name="f_action" class="filter-input">
                            <option value="">Tous</option>
                            <option value="ENTREE">Entrées</option>
                            <option value="SORTIE">Sorties</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Du (Début)</label>
                        <input type="date" name="f_date_debut" class="filter-input" value="<?= htmlspecialchars($_GET['f_date_debut'] ?? '') ?>">
                    </div>

                    <div class="filter-group">
                        <label>Au (Fin)</label>
                        <input type="date" name="f_date_fin" class="filter-input" value="<?= htmlspecialchars($_GET['f_date_fin'] ?? '') ?>">
                    </div>
                    <div class="btn-group-actions">
                        <button type="submit" class="btn-filter">🔍 Filtrer</button>
                        <a href="admin_stock.php" class="btn-reset">🧹 Reset</a>
                    </div>
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
                            <?php foreach($flux as $f): 
                                $is_entree = ($f['type_mouvement'] == 'ENTREE');
                            ?>
                            <tr>
                                <td style="font-size:12px;">
                                    <b><?= date('d/m/y', strtotime($f['date_mouvement'])) ?></b><br>
                                    <span style="color:#94a3b8;"><?= date('H:i', strtotime($f['date_mouvement'])) ?></span>
                                </td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <div class="flow-icon <?= $is_entree ? 'icon-entree' : 'icon-sortie' ?>">
                                            <?= $is_entree ? '↓' : '↑' ?>
                                        </div>
                                        <b><?= htmlspecialchars($f['nom_produit']) ?></b>
                                    </div>
                                </td>
                                <td><span style="font-size:10px; font-weight:800; color:<?= $is_entree ? '#16a34a' : '#ef4444' ?>;"><?= $f['type_mouvement'] ?></span></td>
                                <td style="text-align:right; font-weight:800;"><?= $is_entree ? '+' : '-' ?><?= $f['quantite'] ?></td>
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