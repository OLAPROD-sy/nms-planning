<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if ($_SESSION['role'] !== 'ADMIN' && $_SESSION['role'] !== 'SUPERVISOR') {
    $_SESSION['flash_error'] = 'Accès refusé.';
    header('Location: /nms-planning/'); exit;
}

// 1. Gestion des périodes
$period = $_GET['period'] ?? 'semaine';
$date_start = $_GET['date_start'] ?? date('Y-m-d', strtotime('monday this week'));
$date_end = $_GET['date_end'] ?? date('Y-m-d', strtotime('sunday this week'));

if ($period === 'mois') {
    $date_start = date('Y-m-01');
    $date_end = date('Y-m-t');
} elseif ($period === 'annee') {
    $date_start = date('Y-01-01');
    $date_end = date('Y-12-31');
}

// 2. Détection dynamique du nom du site
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

// 3. Récupération des données
$stmt = $pdo->prepare('
    SELECT m.*, p.nom_produit
    FROM mouvements_stock m
    JOIN produits p ON m.id_produit = p.id_produit
    WHERE m.date_mouvement BETWEEN ? AND ?
    ORDER BY m.date_mouvement DESC
');
$stmt->execute([$date_start . ' 00:00:00', $date_end . ' 23:59:59']);
$mouvements = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare('
    SELECT 
        p.nom_produit,
        m.id_site,
        SUM(CASE WHEN m.type_mouvement = "entree" THEN m.quantite ELSE 0 END) as total_entrees,
        SUM(CASE WHEN m.type_mouvement = "sortie" THEN m.quantite ELSE 0 END) as total_sorties,
        (SUM(CASE WHEN m.type_mouvement = "entree" THEN m.quantite ELSE 0 END) - 
         SUM(CASE WHEN m.type_mouvement = "sortie" THEN m.quantite ELSE 0 END)) as bilan_net
    FROM mouvements_stock m
    JOIN produits p ON m.id_produit = p.id_produit
    WHERE m.date_mouvement BETWEEN ? AND ?
    GROUP BY p.id_produit, m.id_site
    ORDER BY p.nom_produit
');
$stmt->execute([$date_start . ' 00:00:00', $date_end . ' 23:59:59']);
$resume = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcul des KPI globaux
$global_in = 0; $global_out = 0;
foreach($resume as $r) { $global_in += $r['total_entrees']; $global_out += $r['total_sorties']; }

include_once __DIR__ . '/../includes/header.php'; 
?>

<style>
    :root {  --accent-gradient: linear-gradient(135deg, #FF9800 0%, #F57C00 100%); --p-green: #27ae60; --p-red: #e74c3c; --p-blue: #3498db; --p-dark: #2c3e50; }
    .inventory-container { padding: 30px; background: #f4f7f6; min-height: 100vh; font-family: 'Segoe UI', sans-serif; }
    

     /* Header Moderne */
    .dashboard-header {
        background: var(--accent-gradient);
        color: white;
        padding: 35px;
        border-radius: 20px;
        margin-bottom: 30px;
        box-shadow: 0 10px 20px rgba(245, 124, 0, 0.2);
        position: relative;
        overflow: hidden;
    }

    .dashboard-header::after {
        content: '🏢';
        position: absolute;
        right: -10px;
        bottom: -20px;
        font-size: 150px;
        opacity: 0.1;
    }

    .dashboard-header h1 {
        font-size: 28px;
        margin-bottom: 5px;
        font-weight: 800;
    }

    /* Utilitaires de visibilité */
    .hide-mobile { display: none; }
    @media (min-width: 768px) { .hide-mobile { display: inline; } }

    @media screen and (max-width: 768px) {
        h1 { font-size: 1.5em; }
        .filter-grid { flex-direction: column; }
        .filter-group { width: 100%; }
        .btn-main { width: 100%; }
    }
    /* Filtres */
    .filter-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); margin-bottom: 25px; border-left: 5px solid var(--p-blue); }
    .filter-grid { display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; }
    .filter-group { flex: 1; min-width: 150px; }
    .filter-group label { display: block; font-size: 0.8em; font-weight: bold; color: #7f8c8d; margin-bottom: 5px; }
    .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }

    /* KPI */
    .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .kpi-card { background: white; padding: 20px; border-radius: 12px; text-align: center; box-shadow: 0 4px 10px rgba(0,0,0,0.03); }
    .kpi-card h3 { font-size: 0.85em; color: #7f8c8d; margin-bottom: 10px; text-transform: uppercase; }
    .kpi-card .value { font-size: 1.8em; font-weight: bold; }

    /* Sections */
    .section-card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; }
    .section-title { font-size: 1.2em; font-weight: bold; color: var(--p-dark); margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
    
    /* Tableaux */
    .custom-table { width: 100%; border-collapse: collapse; }
    .custom-table th { background: #f8f9fa; padding: 12px; text-align: left; font-size: 0.8em; color: #7f8c8d; border-bottom: 2px solid #eee; }
    .custom-table td { padding: 15px 12px; border-bottom: 1px solid #f1f1f1; font-size: 0.9em; }
    
    .badge { padding: 4px 10px; border-radius: 20px; font-weight: bold; font-size: 0.75em; }
    .badge-in { background: #e8f5e9; color: var(--p-green); }
    .badge-out { background: #ffebee; color: var(--p-red); }
    .bilan-box { display: inline-block; padding: 5px 12px; border-radius: 6px; font-weight: bold; }
</style>

<div class="inventory-container">
    <div class="dashboard-header">
        <h1>📊 Inventaire & Analyse des Flux des Sites Affecté</h1>
        <p class="hide-mobile">
            Analyse détaillée des mouvements de stock pour les sites affectés, avec des indicateurs clés et un journal complet des opérations.
        </p>
    </div>

    <div class="filter-card">
        <form method="get" class="filter-grid">
            <div class="filter-group">
                <label>Période Rapide</label>
                <select name="period" class="form-control" onchange="this.form.submit()">
                    <option value="semaine" <?= $period === 'semaine' ? 'selected' : '' ?>>Cette semaine</option>
                    <option value="mois" <?= $period === 'mois' ? 'selected' : '' ?>>Ce mois-ci</option>
                    <option value="annee" <?= $period === 'annee' ? 'selected' : '' ?>>Cette année</option>
                    <option value="custom" <?= $period === 'custom' ? 'selected' : '' ?>>Personnalisé</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Date Début</label>
                <input type="date" name="date_start" class="form-control" value="<?= $date_start ?>">
            </div>
            <div class="filter-group">
                <label>Date Fin</label>
                <input type="date" name="date_end" class="form-control" value="<?= $date_end ?>">
            </div>
            <button type="submit" class="btn-main" style="background: var(--p-blue); color:white; padding: 10px 20px; border:none; border-radius:8px; cursor:pointer; height:41px;">Appliquer</button>
        </form>
    </div>

    <div class="kpi-grid">
        <div class="kpi-card">
            <h3>Total Entrées</h3>
            <div class="value" style="color: var(--p-green);">+ <?= $global_in ?></div>
        </div>
        <div class="kpi-card">
            <h3>Total Sorties</h3>
            <div class="value" style="color: var(--p-red);">- <?= $global_out ?></div>
        </div>
        <div class="kpi-card">
            <h3>Bilan Net</h3>
            <div class="value" style="color: <?= ($global_in - $global_out) >= 0 ? 'var(--p-green)' : 'var(--p-red)' ?>;">
                <?= ($global_in - $global_out) >= 0 ? '+' : '' ?><?= $global_in - $global_out ?>
            </div>
        </div>
        <div class="kpi-card">
            <h3>Mouvements</h3>
            <div class="value" style="color: var(--p-dark);"><?= count($mouvements) ?></div>
        </div>
    </div>

    <div class="section-card">
        <div class="section-title">📈 Performance par Produit & Site</div>
        <div style="overflow-x: auto;">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Site</th>
                        <th>Flux Entrant</th>
                        <th>Flux Sortant</th>
                        <th>Bilan Période</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resume as $r): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($r['nom_produit']) ?></strong></td>
                        <td><span style="color:#7f8c8d;">📍 <?= htmlspecialchars($sites_map[$r['id_site']] ?? 'Global') ?></span></td>
                        <td style="color: var(--p-green); font-weight:bold;">+ <?= $r['total_entrees'] ?></td>
                        <td style="color: var(--p-red); font-weight:bold;">- <?= $r['total_sorties'] ?></td>
                        <td>
                            <?php 
                                // On définit la couleur en PHP avant l'affichage pour éviter l'erreur de syntaxe
                                $color = ($r['bilan_net'] >= 0) ? 'var(--p-green)' : 'var(--p-red)';
                                $bgColor = ($r['bilan_net'] >= 0) ? '#e8f5e9' : '#ffebee';
                            ?>
                            <div class="bilan-box" style="background: <?= $bgColor ?>; color: <?= $color ?>;">
                                <?= $r['bilan_net'] >= 0 ? '▲' : '▼' ?> <?= abs($r['bilan_net']) ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="section-card">
        <div class="section-title">📋 Journal Détaillé des Opérations</div>
        <div style="overflow-x: auto;">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Date & Heure</th>
                        <th>Produit</th>
                        <th>Site</th>
                        <th>Type</th>
                        <th>Qté</th>
                        <th>Responsable</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mouvements as $m): 
                        // Nettoyage de la valeur pour éviter les erreurs de comparaison
                        $type = trim(strtolower($m['type_mouvement'])); 
                        $isEntree = ($type === 'entree');
                    ?>
                    <tr>
                        <td style="color:#7f8c8d;"><?= date('d/m/Y H:i', strtotime($m['date_mouvement'])) ?></td>
                        <td><strong><?= htmlspecialchars($m['nom_produit']) ?></strong></td>
                        <td><?= htmlspecialchars($sites_map[$m['id_site']] ?? 'Global') ?></td>
                        <td>
                            <?php if ($isEntree): ?>
                                <span class="badge badge-in" style="background: #e8f5e9; color: #27ae60; padding: 4px 10px; border-radius: 20px; font-weight: bold; font-size: 0.75em;">
                                    ⬆️ ENTRÉE
                                </span>
                            <?php else: ?>
                                <span class="badge badge-out" style="background: #ffebee; color: #e74c3c; padding: 4px 10px; border-radius: 20px; font-weight: bold; font-size: 0.75em;">
                                    ⬇️ SORTIE
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight:bold;"><?= $m['quantite'] ?></td>
                        <td style="font-size:0.85em; color: #2c3e50;"><?= htmlspecialchars($m['responsable_nom']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>