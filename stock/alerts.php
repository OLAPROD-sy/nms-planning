<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if ($_SESSION['role'] !== 'ADMIN') {
    $_SESSION['flash_error'] = 'Accès refusé.';
    header('Location: /'); exit;
}

// 1. Détection dynamique du nom du site
$siteNameCol = null;
try {
    $cols = $pdo->query("SHOW COLUMNS FROM sites")->fetchAll(PDO::FETCH_COLUMN, 0);
    foreach (['nom','name','site_nom','nom_site','titre'] as $candidate) {
        if (in_array($candidate, $cols, true)) { $siteNameCol = $candidate; break; }
    }
} catch (Exception $e) { $siteNameCol = null; }

$sites_map = [];
if ($siteNameCol) {
    $stmtSites = $pdo->prepare("SELECT id_site, `$siteNameCol` as site_name FROM sites");
    $stmtSites->execute();
    $sites_map = $stmtSites->fetchAll(PDO::FETCH_KEY_PAIR);
}

// 1. Détection dynamique du nom du site (Gardez votre code existant pour $sites_map)
// ... (votre code SHOW COLUMNS et $sites_map reste identique)

// 2. Gestion du Filtre par Site
$filter_site = isset($_GET['f_site']) && $_GET['f_site'] !== '' ? (int)$_GET['f_site'] : null;

// Préparation des clauses WHERE
$where_alertes = "p.quantite_actuelle < p.quantite_alerte";
$where_faibles = "p.quantite_actuelle < 10";
$params = [];

if ($filter_site) {
    $where_alertes .= " AND p.id_site = ?";
    $where_faibles .= " AND p.id_site = ?";
    $params = [$filter_site];
}

// Récupération des Produits Critiques
$stmt = $pdo->prepare("SELECT p.* FROM produits p WHERE $where_alertes ORDER BY p.quantite_actuelle ASC");
$stmt->execute($params);
$alertes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des Stocks Faibles
$stmt = $pdo->prepare("SELECT p.* FROM produits p WHERE $where_faibles ORDER BY p.quantite_actuelle ASC");
$stmt->execute($params);
$stocks_faibles = $stmt->fetchAll(PDO::FETCH_ASSOC);

include_once __DIR__ . '/../includes/header.php'; 
?>


<div class="alerts-container">
    <div class="header-section">
    <h1 style="margin-bottom: 20px; font-size: 1.5em;">⚠️ Tableau de Bord des Alertes</h1>
    </div>
    <div class="filter-section" style="background: white; padding: 15px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
    <form method="GET" style="display: flex; align-items: center; gap: 10px; width: 100%;">
        <label style="font-weight: bold; color: var(--dark);">📍 Filtrer par site :</label>
        <select name="f_site" onchange="this.form.submit()" style="padding: 10px; border-radius: 8px; border: 1px solid #ddd; flex: 1; max-width: 300px; font-size: 16px;">
            <option value="">🌍 Tous les sites</option>
            <?php foreach($sites_map as $id => $name): ?>
                <option value="<?= $id ?>" <?= ($filter_site == $id) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($name) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if($filter_site): ?>
            <a href="alerts.php" style="color: var(--danger); text-decoration: none; font-size: 0.9em;">✖ Réinitialiser</a>
        <?php endif; ?>
    </form>
</div>
    <div class="kpi-grid">
        <div class="kpi-card kpi-critique">
            <h3>Produits Critiques</h3>
            <div class="value"><?= count($alertes) ?></div>
        </div>
        <div class="kpi-card kpi-faible">
            <h3>Stocks Faibles (< 10)</h3>
            <div class="value"><?= count($stocks_faibles) ?></div>
        </div>
        <div class="kpi-card" style="border-left-color: var(--success);">
            <h3>État Global</h3>
            <div class="value" style="font-size: 1.2em;"><?= count($alertes) === 0 ? '✅ Sain' : '🚨 Action Requise' ?></div>
        </div>
    </div>

    <div class="alert-section">
        <h2 style="color: var(--danger);">🔴 Produits Critiques</h2>
        <?php if (empty($alertes)): ?>
            <p style="color: var(--success); margin-top:10px;">✅ Aucun produit sous le seuil d'alerte.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Site</th>
                            <th>Niveau</th>
                            <th>Seuil</th>
                            <th>Besoin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alertes as $a): 
                            $percent = ($a['quantite_alerte'] > 0) ? ($a['quantite_actuelle'] / $a['quantite_alerte']) * 100 : 0;
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($a['nom_produit']) ?></strong></td>
                            <td><span style="color:#7f8c8d; font-size: 0.85em;">📍 <?= htmlspecialchars($sites_map[$a['id_site']] ?? 'Global') ?></span></td>
                            <td>
                                <div class="progress-bar"><div class="progress-fill" style="width: <?= min(100, $percent) ?>%; background: var(--danger);"></div></div>
                                <span style="font-weight:bold; color: var(--danger)"><?= $a['quantite_actuelle'] ?></span>
                            </td>
                            <td><?= $a['quantite_alerte'] ?></td>
                            <td><span class="badge-status badge-critique">+ <?= max(0, $a['quantite_alerte'] - $a['quantite_actuelle']) ?> manquant</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="alert-section">
        <h2 style="color: var(--warning);">🟠 Stocks Faibles (< 10)</h2>
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Site</th>
                        <th>Quantité</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stocks_faibles as $s): 
                        $is_critique = $s['quantite_actuelle'] < $s['quantite_alerte'];
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($s['nom_produit']) ?></td>
                        <td><span style="color:#7f8c8d; font-size: 0.85em;">📍 <?= htmlspecialchars($sites_map[$s['id_site']] ?? 'Global') ?></span></td>
                        <td style="font-weight:bold"><?= $s['quantite_actuelle'] ?></td>
                        <td>
                            <span class="badge-status <?= $is_critique ? 'badge-critique' : 'badge-faible' ?>">
                                <?= $is_critique ? '🔴 Critique' : '🟠 Faible' ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top: 10px;">
        <a class="btn-main" href="/stock/gest_stock.php" style="background: var(--success); text-decoration:none; display:inline-block; padding: 15px 25px; color:white; border-radius:8px; font-weight: bold;">📦 Gérer les stocks</a>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>