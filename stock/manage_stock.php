<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';

if ($_SESSION['role'] === 'AGENT') {
    $_SESSION['flash_error'] = 'Accès refusé.';
    header('Location: /nms-planning/index.php'); exit;
}

$id_site = ($_SESSION['role'] === 'SUPERVISEUR') ? $_SESSION['id_site'] : ($_GET['id_site'] ?? $_SESSION['id_site'] ?? NULL);
if (!$id_site) { header('Location: /nms-planning/index.php'); exit; }

// --- 1. DÉTECTION DU NOM DU SITE ---
$site_nom = "Site #$id_site"; 
try {
    $resCols = $pdo->query("SHOW COLUMNS FROM sites")->fetchAll(PDO::FETCH_COLUMN);
    $foundCol = in_array('nom', $resCols) ? 'nom' : (in_array('nom_site', $resCols) ? 'nom_site' : 'id_site');
    $stmtS = $pdo->prepare("SELECT $foundCol FROM sites WHERE id_site = ?");
    $stmtS->execute([$id_site]);
    $site_nom = $stmtS->fetchColumn() ?: "Site inconnu";
} catch (Exception $e) { }

// --- 2. TRAITEMENT : CRÉATION PRODUIT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_produit'])) {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $nom_p = trim($_POST['nom_produit']);
        $qte_init = (int)$_POST['quantite_initiale'];
        $qte_alert = (int)$_POST['quantite_alerte'];

        if (!empty($nom_p)) {
            $insP = $pdo->prepare("INSERT INTO produits (nom_produit, quantite_actuelle, quantite_alerte, id_site) VALUES (?, ?, ?, ?)");
            $insP->execute([$nom_p, $qte_init, $qte_alert, $id_site]);
            $_SESSION['flash_success'] = "Produit '$nom_p' créé avec succès.";
        }
    }
    header('Location: ' . $_SERVER['REQUEST_URI']); exit;
}

// --- 3. TRAITEMENT : MOUVEMENT (ENTRÉE/SORTIE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_mouvement'])) {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $id_p = (int)$_POST['id_produit'];
        $type = trim(strtolower($_POST['type_mouvement']));
        $qty = (int)$_POST['quantite'];

        if ($id_p > 0 && $qty > 0) {
            try {
                $pdo->beginTransaction();
                $insM = $pdo->prepare('INSERT INTO mouvements_stock (id_produit, id_user, type_mouvement, quantite, id_site, responsable_nom, date_mouvement) VALUES (?, ?, ?, ?, ?, ?, NOW())');
                $insM->execute([$id_p, $_SESSION['id_user'], $type, $qty, $id_site, $_SESSION['prenom'].' '.$_SESSION['nom']]);
                
                $op = ($type === 'entree') ? '+' : '-';
                $pdo->exec("UPDATE produits SET quantite_actuelle = GREATEST(0, quantite_actuelle $op $qty) WHERE id_produit = $id_p");
                $pdo->commit();
                $_SESSION['flash_success'] = "Mouvement enregistré.";
            } catch (Exception $e) { $pdo->rollBack(); }
        }
    }
    header('Location: ' . $_SERVER['REQUEST_URI']); exit;
}

// --- 4. RÉCUPÉRATION DONNÉES ---
$produits = $pdo->prepare('SELECT * FROM produits WHERE id_site = ? ORDER BY nom_produit');
$produits->execute([$id_site]);
$liste_produits = $produits->fetchAll(PDO::FETCH_ASSOC);

$date_f = $_GET['date_filtre'] ?? '';
$sqlH = "SELECT m.*, p.nom_produit FROM mouvements_stock m JOIN produits p ON m.id_produit = p.id_produit WHERE m.id_site = ?";
$paramsH = [$id_site];
if($date_f) { $sqlH .= " AND DATE(m.date_mouvement) = ?"; $paramsH[] = $date_f; }
$sqlH .= " ORDER BY m.date_mouvement DESC LIMIT 50";
$stmtH = $pdo->prepare($sqlH);
$stmtH->execute($paramsH);
$historique = $stmtH->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include_once __DIR__ . '/../includes/header.php'; ?>

<style>
    :root { --p-green: #2ecc71; --p-red: #e74c3c; --p-blue: #3498db; --p-orange: #f39c12; }
    .stock-wrapper { width: 100%; max-width: 1400px; margin: 0 auto; padding: 15px; box-sizing: border-box; }
    
    /* Layout */
    .stock-grid { display: flex; flex-direction: column; gap: 20px; }
    @media (min-width: 1024px) { .stock-grid { display: grid; grid-template-columns: 350px 1fr; } }

    /* Cards avec Liserés (Comme Planning) */
    .card { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 20px; border: 1px solid #eee; }
    .border-success { border-left: 6px solid var(--p-green) !important; }
    .border-info { border-left: 6px solid var(--p-blue) !important; }
    .border-warning { border-left: 6px solid var(--p-orange) !important; }
    .border-danger { border-left: 6px solid var(--p-red) !important; }

    .card h3 { margin-top: 0; font-size: 1.1em; color: #333; padding-bottom: 10px; border-bottom: 1px solid #f5f5f5; }
    .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 12px; font-size: 15px; }
    .btn-main { width: 100%; padding: 12px; border: none; border-radius: 6px; color: white; font-weight: bold; cursor: pointer; }
    
    /* Tableau */
    .table-container { width: 100%; overflow-x: auto; border-radius: 8px; }
    .table-mvt { width: 100%; border-collapse: collapse; min-width: 600px; }
    .table-mvt th { background: #f8f9fa; padding: 12px; text-align: left; font-size: 0.8em; color: #888; }
    .table-mvt td { padding: 12px; border-bottom: 1px solid #f9f9f9; font-size: 0.9em; }
    
    .badge { padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 0.75em; }
    .bg-e { background: #e8f5e9; color: #2e7d32; }
    .bg-s { background: #ffebee; color: #c62828; }
</style>

<div class="stock-wrapper">
    <div style="background: white; padding: 15px; border-radius: 10px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        <h2 style="margin:0">📦 Gestion Stock </h2>
        <div style="display:flex; gap:10px; align-items:center;">
        <span style="color: #2c3e50; font-weight: bold; background: #ecf0f1; padding: 4px 12px; border-radius: 15px; font-size: 0.9em; display: inline-block; margin-top: 5px;">
                📍 Site : <?= htmlspecialchars($site_nom) ?>
        </span>
        <a href="manage_stock.php" style="text-decoration:none; color: #666;">🔄</a>
        </div>
    </div>

    <div class="stock-grid">
        <div class="stock-sidebar">
            
            <div class="card border-success">
                <h3>📦 Nouveau Produit</h3>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <label style="font-size:0.8em">NOM DU PRODUIT</label>
                    <input type="text" name="nom_produit" class="form-control" placeholder="ex: Gants Latex" required>
                    
                    <div style="display:flex; gap:10px">
                        <div style="flex:1">
                            <label style="font-size:0.8em">STOCK INITIAL</label>
                            <input type="number" name="quantite_initiale" class="form-control" value="0">
                        </div>
                        <div style="flex:1">
                            <label style="font-size:0.8em">SEUIL ALERTE</label>
                            <input type="number" name="quantite_alerte" class="form-control" value="5">
                        </div>
                    </div>
                    <button type="submit" name="create_produit" class="btn-main" style="background: var(--p-green)">Créer la référence</button>
                </form>
            </div>

            <div class="card border-success">
                <h3>🔄 Mouvement Stock</h3>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <select name="id_produit" class="form-control" required>
                        <option value="">-- Choisir produit --</option>
                        <?php foreach($liste_produits as $p): ?>
                            <option value="<?= $p['id_produit'] ?>"><?= htmlspecialchars($p['nom_produit']) ?> (<?= $p['quantite_actuelle'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <div style="display:flex; gap:10px">
                        <select name="type_mouvement" class="form-control" style="flex:1">
                            <option value="entree">⬆️ Entrée</option>
                            <option value="sortie">⬇️ Sortie</option>
                        </select>
                        <input type="number" name="quantite" class="form-control" style="flex:1" placeholder="Qté" min="1" required>
                    </div>
                    <button type="submit" name="add_mouvement" class="btn-main" style="background: var(--p-green)">Valider</button>
                </form>
            </div>

            <div class="card border-warning">
                <h3>📊 Qté Produit Actuel & États</h3>
                <?php foreach($liste_produits as $p): 
                    $is_low = $p['quantite_actuelle'] <= $p['quantite_alerte'];
                ?>
                <div style="display:flex; justify-content:space-between; padding:8px; border-radius:5px; margin-bottom:5px; background: <?= $is_low ? '#fff5f5':'#fafafa' ?>; border-left: 4px solid <?= $is_low ? 'var(--p-red)':'var(--p-green)' ?>">
                    <span style="font-size:0.9em"><?= htmlspecialchars($p['nom_produit']) ?></span>
                    <strong><?= $p['quantite_actuelle'] ?></strong>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="stock-main">
            <div class="card border-warning">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px">
                    <h3 style="border:none; margin:0">📜 Historique </h3>
                    <form method="GET" style="display:flex; gap:5px">
                        <input type="hidden" name="id_site" value="<?= $id_site ?>">
                        <input type="date" name="date_filtre" class="form-control" style="margin:0; padding:5px" value="<?= $date_f ?>">
                        <button type="submit" style="border:none; background:#eee; padding:5px 10px; border-radius:5px; cursor:pointer">OK</button>
                    </form>
                </div>

                <div class="table-container">
                    <table class="table-mvt">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Produit</th>
                                <th>Action</th>
                                <th>Qté</th>
                                <th>Auteur</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($historique as $m): $isE = (trim(strtolower($m['type_mouvement'])) === 'entree'); ?>
                            <tr>
                                <td style="color:#888"><?= date('d/m H:i', strtotime($m['date_mouvement'])) ?></td>
                                <td><strong><?= htmlspecialchars($m['nom_produit']) ?></strong></td>
                                <td><span class="badge <?= $isE ? 'bg-e':'bg-s' ?>"><?= $isE ? 'ENTRÉE':'SORTIE' ?></span></td>
                                <td style="font-weight:bold"><?= $m['quantite'] ?></td>
                                <td style="font-size:0.8em"><?= htmlspecialchars($m['responsable_nom']) ?></td>
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
