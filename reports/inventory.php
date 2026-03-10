<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if ($_SESSION['role'] !== 'ADMIN' && $_SESSION['role'] !== 'SUPERVISOR') {
    $_SESSION['flash_error'] = 'Accès refusé.';
    header('Location: /'); exit;
}

// 1. Gestion des périodes
$period = $_GET['period'] ?? 'semaine';

// Initialisation par défaut (Semaine en cours)
$date_start = $_GET['date_start'] ?? date('Y-m-d', strtotime('monday tonight -7 days')); 
$date_end = $_GET['date_end'] ?? date('Y-m-d', strtotime('sunday tonight'));

// Correction fiable pour la semaine en cours
if ($period === 'semaine' && !isset($_GET['date_start'])) {
    // Si on est lundi, 'monday this week' est aujourd'hui. 
    // Si on est dimanche, 'monday this week' peut varier.
    // Cette méthode est la plus robuste :
    $monday = strtotime('last monday', strtotime('tomorrow'));
    $sunday = strtotime('next sunday', $monday);
    
    $date_start = date('Y-m-d', $monday);
    $date_end = date('Y-m-d', $sunday);
} 
elseif ($period === 'mois') {
    $date_start = date('Y-m-01');
    $date_end = date('Y-m-t');
} 
elseif ($period === 'annee') {
    $date_start = date('Y-01-01');
    $date_end = date('Y-12-31');
}

// Récupération du site choisi
$selected_site = $_GET['id_site'] ?? '';
$params = [$date_start . ' 00:00:00', $date_end . ' 23:59:59'];
$site_condition = "";

if (!empty($selected_site)) {
    $site_condition = " AND m.id_site = ? ";
    $params[] = $selected_site;
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

// ... (Gardez la détection dynamique du nom du site intacte)
$filter_type = $_GET['f_type'] ?? '';
$type_condition = "";

if (!empty($filter_type)) {
    $type_condition = " AND m.type_mouvement = ? ";
    $params[] = $filter_type;
}

// 3. Récupération des données (Mouvements détaillés)
$sql_mouv = '
    SELECT m.*, p.nom_produit
    FROM mouvements_stock m
    JOIN produits p ON m.id_produit = p.id_produit
    WHERE m.date_mouvement BETWEEN ? AND ?
    ' . $site_condition . '
    ' . $type_condition . '
    ORDER BY m.date_mouvement DESC
';
$stmt = $pdo->prepare($sql_mouv);
$stmt->execute($params);
$mouvements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Résumé par Produit (Bilan net)
$sql_resume = '
    SELECT 
        p.nom_produit,
        m.id_site,
        SUM(CASE WHEN LOWER(m.type_mouvement) = "entree" THEN m.quantite ELSE 0 END) as total_entrees,
        SUM(CASE WHEN LOWER(m.type_mouvement) = "sortie" THEN m.quantite ELSE 0 END) as total_sorties,
        (SUM(CASE WHEN LOWER(m.type_mouvement) = "entree" THEN m.quantite ELSE 0 END) - 
         SUM(CASE WHEN LOWER(m.type_mouvement) = "sortie" THEN m.quantite ELSE 0 END)) as bilan_net
    FROM mouvements_stock m
    JOIN produits p ON m.id_produit = p.id_produit
    WHERE m.date_mouvement BETWEEN ? AND ?
    ' . $site_condition . '
    ' . $type_condition . '
    GROUP BY p.id_produit, m.id_site
    ORDER BY p.nom_produit
';
$stmt = $pdo->prepare($sql_resume);
$stmt->execute($params);
$resume = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcul des KPI globaux mis à jour selon les filtres
$global_in = 0; $global_out = 0;
foreach($resume as $r) { 
    $global_in += $r['total_entrees']; 
    $global_out += $r['total_sorties']; 
}
include_once __DIR__ . '/../includes/header.php'; 
?>


<div class="inventory-container">
    <div class="dashboard-header">
    <img src="/assets/img/logo.png" class="logo-print" alt="Logo">
    <i class="bi bi-buildings header-icon"></i>
    <h1><i class="bi bi-bar-chart"></i> Rapport d'Inventaire <?= !empty($selected_site) ? "- Site : " . htmlspecialchars($sites_map[$selected_site]) : "(Global)" ?></h1>
    <p>Période du <?= date('d/m/Y', strtotime($date_start)) ?> au <?= date('d/m/Y', strtotime($date_end)) ?></p>
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
            <div class="filter-group">
                <label>Filtrer par Site</label>
                <select name="id_site" class="form-control" onchange="this.form.submit()">
                    <option value="">Tous les sites</option>
                    <?php foreach ($sites_map as $id => $name): ?>
                        <option value="<?= $id ?>" <?= (isset($_GET['id_site']) && $_GET['id_site'] == $id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Type de Flux</label>
                <select name="f_type" class="form-control" onchange="this.form.submit()">
                    <option value="">Tous les flux</option>
                    <option value="entree" <?= $filter_type === 'entree' ? 'selected' : '' ?>>Entrées uniquement</option>
                    <option value="sortie" <?= $filter_type === 'sortie' ? 'selected' : '' ?>>Sorties uniquement</option>
                </select>
            </div>
            <button type="submit" class="btn-main" style="background: var(--p-blue); color:white; padding: 10px 20px; border:none; border-radius:8px; cursor:pointer; height:41px;">Appliquer</button>
            <button type="button" onclick="exportToExcel()" class="btn-main" style="background: #27ae60; color:white; border:none; padding:10px 20px; border-radius:8px; cursor:pointer; font-weight: bold;">
                <i class="bi bi-file-earmark-excel"></i> Exporter en Excel
            </button>  
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
    <div class="section-title"><i class="bi bi-pie-chart"></i> Part de consommation par site (Sorties)</div>
    <?php
        $max_out = $global_out > 0 ? $global_out : 1;
        // On regroupe les sorties par site pour le graphique
        $site_usage = [];
        foreach($resume as $r) {
            $site_name = $sites_map[$r['id_site']] ?? 'Inconnu';
            if(!isset($site_usage[$site_name])) $site_usage[$site_name] = 0;
            $site_usage[$site_name] += $r['total_sorties'];
        }
        
        foreach($site_usage as $name => $val): 
            $prc = ($val / $max_out) * 100;
        ?>
        <div style="margin-bottom: 15px;">
            <div style="display: flex; justify-content: space-between; font-size: 0.85em; margin-bottom: 5px;">
                <span><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($name) ?></span>
                <strong><?= $val ?> unités sorties</strong>
            </div>
            <div style="background: #eee; border-radius: 10px; height: 12px; width: 100%; overflow: hidden;">
                <div style="background: var(--p-red); width: <?= $prc ?>%; height: 100%; transition: width 0.5s;"></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="section-card">
        <div class="section-title"><i class="bi bi-graph-up"></i> Performance par Produit & Site</div>
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
                        <td><span style="color:#7f8c8d;"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($sites_map[$r['id_site']] ?? 'Global') ?></span></td>
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
            <div class="section-title" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <span><i class="bi bi-clipboard-data"></i> Journal Détaillé des Opérations</span>
                <span style="background: var(--p-blue); color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.7em;">
                    <?= count($mouvements) ?> résultat(s) trouvé(s)
                </span>
            </div>        
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
                            <?php 
                            $type_brut = trim(strtolower($m['type_mouvement']));
                            if ($type_brut === 'entree'): ?>
                                <span class="badge" style="background: #e8f5e9; color: #27ae60; padding: 4px 10px; border-radius: 20px; font-weight: bold; font-size: 0.75em; border: 1px solid #27ae60;">
                                    <i class="bi bi-arrow-up"></i> ENTRÉE
                                </span>
                            <?php else: ?>
                                <span class="badge" style="background: #ffebee; color: #e74c3c; padding: 4px 10px; border-radius: 20px; font-weight: bold; font-size: 0.75em; border: 1px solid #e74c3c;">
                                    <i class="bi bi-arrow-down"></i> SORTIE
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
    <div class="print-footer">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="border:none; text-align: center; width: 50%;">
                    <strong>Responsable de Stock</strong><br><br><br>
                    __________________________
                </td>
                <td style="border:none; text-align: center; width: 50%;">
                    <strong>Direction</strong><br><br><br>
                    __________________________
                </td>
            </tr>
        </table>
        <p style="text-align: center; font-size: 0.8em; margin-top: 30px; color: #666;">
            Document généré le <?= date('d/m/Y H:i') ?> - NMS Planning
        </p>
    </div>
</div>
<script>
    // Force la soumission du formulaire si une date manuelle est changée
    document.querySelectorAll('input[type="date"]').forEach(input => {
        input.addEventListener('change', () => {
            document.querySelector('.filter-grid').submit();
        });
    });

    function exportToExcel() {
    const start = document.querySelector('input[name="date_start"]').value;
    const end = document.querySelector('input[name="date_end"]').value;
    const site = document.querySelector('select[name="id_site"]').value;
    const type = document.querySelector('select[name="f_type"]').value; // Nouveau
    
    let url = `export_inventory.php?date_start=${start}&date_end=${end}`;
    if (site) url += `&id_site=${site}`;
    if (type) url += `&f_type=${type}`; // Nouveau
    
    window.location.href = url;
}
</script>
<?php include_once __DIR__ . '/../includes/footer.php'; ?>
