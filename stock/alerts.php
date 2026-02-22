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

// 2. Récupération des données
$stmt = $pdo->prepare('SELECT p.* FROM produits p WHERE p.quantite_actuelle < p.quantite_alerte ORDER BY p.quantite_actuelle ASC');
$stmt->execute();
$alertes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare('SELECT p.* FROM produits p WHERE p.quantite_actuelle < 10 ORDER BY p.quantite_actuelle ASC');
$stmt->execute();
$stocks_faibles = $stmt->fetchAll(PDO::FETCH_ASSOC);

include_once __DIR__ . '/../includes/header.php'; 
?>

<style>
    :root { --accent-gradient: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);  --danger: #e74c3c; --warning: #f39c12; --success: #2ecc71; --dark: #2c3e50; }
    
    /* Container Principal */
    .alerts-container { padding: 20px; font-family: 'Segoe UI', sans-serif; background: #f8f9fa; min-height: 100vh; }
    
    /* Grille KPI Responsive */
    .kpi-grid { 
        display: grid; 
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
        gap: 15px; 
        margin-bottom: 25px; 
    }
    
    .kpi-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-left: 5px solid #ccc; }
    .kpi-critique { border-left-color: var(--danger); }
    .kpi-faible { border-left-color: var(--warning); }
    .kpi-card h3 { margin: 0; color: #7f8c8d; font-size: 0.8em; text-transform: uppercase; }
    .kpi-card .value { font-size: 1.8em; font-weight: bold; color: var(--dark); }

    /* Sections */
    .alert-section { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 25px; }
    .alert-section h2 { margin-top: 0; display: flex; align-items: center; gap: 10px; font-size: 1.2em; flex-wrap: wrap; }
    
    /* TABLE RESPONSIVE CORE */
    .table-responsive {
        width: 100%;
        overflow-x: auto; /* Scroll horizontal sur mobile */
        -webkit-overflow-scrolling: touch;
        margin-top: 15px;
        border-radius: 8px;
    }

    /* Header adaptable */
    .header-section {
        background: var(--accent-gradient);
        padding: 30px 20px;
        border-radius: 20px;
        color: white;
        margin-bottom: 25px;
        text-align: center; /* Centré par défaut pour mobile */
        box-shadow: 0 10px 20px rgba(255, 152, 0, 0.2);
    }

    .custom-table { width: 100%; border-collapse: collapse; min-width: 600px; /* Force la table à garder sa structure */ }
    .custom-table th { text-align: left; padding: 12px; border-bottom: 2px solid #f1f1f1; color: #7f8c8d; font-size: 0.8em; }
    .custom-table td { padding: 15px 12px; border-bottom: 1px solid #f8f9fa; font-size: 0.9em; }
    
    /* Éléments visuels */
    .progress-bar { background: #eee; border-radius: 10px; height: 8px; width: 60px; overflow: hidden; display: inline-block; vertical-align: middle; margin-right: 8px; }
    .progress-fill { height: 100%; border-radius: 10px; }
    
    .badge-status { padding: 4px 8px; border-radius: 20px; font-size: 0.7em; font-weight: bold; text-transform: uppercase; white-space: nowrap; }
    .badge-critique { background: #ffdada; color: var(--danger); }
    .badge-faible { background: #fff3cd; color: #856404; }

    /* Mobile Adjustments */
    @media (max-width: 768px) {
        .alerts-container { padding: 15px; }
        .kpi-card { padding: 15px; }
        .kpi-card .value { font-size: 1.5em; }
        .btn-main { width: 100%; text-align: center; } /* Bouton plein écran sur mobile */
    }
</style>

<div class="alerts-container">
    <div class="header-section">
    <h1 style="margin-bottom: 20px; font-size: 1.5em;">⚠️ Tableau de Bord des Alertes</h1>
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