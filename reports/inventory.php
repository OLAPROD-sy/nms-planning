<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if ($_SESSION['role'] !== 'ADMIN' && $_SESSION['role'] !== 'SUPERVISOR') {
    $_SESSION['flash_error'] = 'Accès refusé.';
    header('Location: /'); exit;
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

// 2. Gestion du Site (Filtrage)
$selected_site = $_GET['id_site'] ?? '';
$site_condition = "";
$query_params = [$date_start . ' 00:00:00', $date_end . ' 23:59:59'];

if (!empty($selected_site)) {
    $site_condition = " AND m.id_site = ? ";
    $query_params[] = $selected_site;
}

// 3. Récupération des noms de sites pour la liste déroulante
$sites_map = [];
try {
    $stmtSites = $pdo->query("SELECT id_site, nom FROM sites"); // Ajuste 'nom' si ta colonne s'appelle autrement
    $sites_map = $stmtSites->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) { $sites_map = []; }

// 4. Données du Journal (Appliqué avec le filtre de site)
$stmt = $pdo->prepare("
    SELECT m.*, p.nom_produit
    FROM mouvements_stock m
    JOIN produits p ON m.id_produit = p.id_produit
    WHERE m.date_mouvement BETWEEN ? AND ? $site_condition
    ORDER BY m.date_mouvement DESC
");
$stmt->execute($query_params);
$mouvements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. Données du Résumé (Appliqué avec le filtre de site)
$stmt = $pdo->prepare("
    SELECT 
        p.nom_produit,
        m.id_site,
        SUM(CASE WHEN m.type_mouvement = 'entree' THEN m.quantite ELSE 0 END) as total_entrees,
        SUM(CASE WHEN m.type_mouvement = 'sortie' THEN m.quantite ELSE 0 END) as total_sorties,
        (SUM(CASE WHEN m.type_mouvement = 'entree' THEN m.quantite ELSE 0 END) - 
         SUM(CASE WHEN m.type_mouvement = 'sortie' THEN m.quantite ELSE 0 END)) as bilan_net
    FROM mouvements_stock m
    JOIN produits p ON m.id_produit = p.id_produit
    WHERE m.date_mouvement BETWEEN ? AND ? $site_condition
    GROUP BY p.id_produit, m.id_site
    ORDER BY p.nom_produit
");
$stmt->execute($query_params);
$resume = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcul des KPI
$global_in = 0; $global_out = 0;
foreach($resume as $r) { $global_in += $r['total_entrees']; $global_out += $r['total_sorties']; }

include_once __DIR__ . '/../includes/header.php'; 
?>

<style>
    :root {  --accent-gradient: linear-gradient(135deg, #FF9800 0%, #F57C00 100%); --p-green: #27ae60; --p-red: #e74c3c; --p-blue: #3498db; --p-dark: #2c3e50; }
    .inventory-container { padding: 30px; background: #f4f7f6; min-height: 100vh; font-family: 'Segoe UI', sans-serif; }
    
    /* LOGO : Masqué par défaut à l'écran */
    .logo-print { display: none; }

    @media print {
        @page { size: A4 portrait; margin: 1cm; }
        
        /* Navigation et Filtres cachés */
        nav, .sidebar, .filter-card, .btn-main, footer { display: none !important; }
        
        /* Logo affiché uniquement en haut du PDF */
        .logo-print { 
            display: block !important; 
            width: 120px; 
            margin: 0 auto 20px auto; 
        }

        .inventory-container { padding: 0 !important; background: white !important; }
        
        .dashboard-header {
            background: none !important;
            color: black !important;
            text-align: center !important;
            padding: 0 !important;
            box-shadow: none !important;
            border-bottom: 2px solid #F57C00 !important;
        }

        .dashboard-header h1 { font-size: 20pt !important; color: #F57C00 !important; }
        .section-card { box-shadow: none !important; border: 1px solid #eee !important; page-break-inside: avoid; }
        
        /* Forcer les couleurs des badges */
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        .badge-in { background: #e8f5e9 !important; color: #27ae60 !important; }
        .badge-out { background: #ffebee !important; color: #e74c3c !important; }
        
        .print-footer { display: block !important; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }
    }

    .print-footer { display: none; }

    /* Styles Ecran */
    .dashboard-header { background: var(--accent-gradient); color: white; padding: 35px; border-radius: 20px; margin-bottom: 30px; box-shadow: 0 10px 20px rgba(245, 124, 0, 0.2); }
    .filter-card { background: white; padding: 20px; border-radius: 12px; margin-bottom: 25px; border-left: 5px solid var(--p-blue); }
    .filter-grid { display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; }
    .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .kpi-card { background: white; padding: 20px; border-radius: 12px; text-align: center; }
    .section-card { background: white; border-radius: 12px; padding: 25px; margin-bottom: 30px; }
    .custom-table { width: 100%; border-collapse: collapse; }
    .custom-table th { background: #f8f9fa; padding: 12px; text-align: left; font-size: 0.8em; border-bottom: 2px solid #eee; }
    .custom-table td { padding: 15px 12px; border-bottom: 1px solid #f1f1f1; }
    .bilan-box { padding: 5px 12px; border-radius: 6px; font-weight: bold; }
</style>

<div class="inventory-container">
    <div class="dashboard-header">
        <img src="/assets/img/logo.png" class="logo-print" alt="Logo">
        <h1>📊 Rapport d'Inventaire <?= !empty($selected_site) ? "- Site : " . htmlspecialchars($sites_map[$selected_site]) : "(Global)" ?></h1>
        <p>Période du <?= date('d/m/Y', strtotime($date_start)) ?> au <?= date('d/m/Y', strtotime($date_end)) ?></p>
    </div>

    <div class="filter-card">
        <form method="get" class="filter-grid">
            <div style="flex: 1; min-width: 150px;">
                <label style="font-size: 0.8em; font-weight: bold; color: #7f8c8d;">Période</label>
                <select name="period" class="form-control" onchange="this.form.submit()">
                    <option value="semaine" <?= $period === 'semaine' ? 'selected' : '' ?>>Cette semaine</option>
                    <option value="mois" <?= $period === 'mois' ? 'selected' : '' ?>>Ce mois-ci</option>
                    <option value="annee" <?= $period === 'annee' ? 'selected' : '' ?>>Cette année</option>
                    <option value="custom" <?= $period === 'custom' ? 'selected' : '' ?>>Personnalisé</option>
                </select>
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label style="font-size: 0.8em; font-weight: bold; color: #7f8c8d;">Date Début</label>
                <input type="date" name="date_start" class="form-control" value="<?= $date_start ?>">
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label style="font-size: 0.8em; font-weight: bold; color: #7f8c8d;">Date Fin</label>
                <input type="date" name="date_end" class="form-control" value="<?= $date_end ?>">
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label style="font-size: 0.8em; font-weight: bold; color: #7f8c8d;">Site</label>
                <select name="id_site" class="form-control" onchange="this.form.submit()">
                    <option value="">Tous les sites</option>
                    <?php foreach ($sites_map as $id => $name): ?>
                        <option value="<?= $id ?>" <?= ($selected_site == $id) ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" style="background: var(--p-blue); color:white; padding: 10px 20px; border:none; border-radius:8px; cursor:pointer;">Appliquer</button>
            <button type="button" onclick="window.print()" style="background: #2ecc71; color:white; border:none; padding:10px 20px; border-radius:8px; cursor:pointer;">📄 Exporter PDF</button>
        </form>
    </div>

    <div class="kpi-grid">
        <div class="kpi-card"><h3>Total Entrées</h3><div class="value" style="color: var(--p-green);">+ <?= $global_in ?></div></div>
        <div class="kpi-card"><h3>Total Sorties</h3><div class="value" style="color: var(--p-red);">- <?= $global_out ?></div></div>
        <div class="kpi-card"><h3>Bilan Net</h3><div class="value" style="color: <?= ($global_in-$global_out)>=0?'var(--p-green)':'var(--p-red)' ?>;"><?= ($global_in-$global_out)>=0?'+':'' ?><?= $global_in-$global_out ?></div></div>
    </div>

    <div class="section-card">
        <div class="section-title">📈 Performance par Produit</div>
        <table class="custom-table">
            <thead>
                <tr><th>Produit</th><th>Site</th><th>Entrées</th><th>Sorties</th><th>Bilan</th></tr>
            </thead>
            <tbody>
                <?php foreach ($resume as $r): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($r['nom_produit']) ?></strong></td>
                    <td>📍 <?= htmlspecialchars($sites_map[$r['id_site']] ?? 'Global') ?></td>
                    <td style="color: var(--p-green);">+ <?= $r['total_entrees'] ?></td>
                    <td style="color: var(--p-red);">- <?= $r['total_sorties'] ?></td>
                    <td>
                        <span class="bilan-box" style="background: <?= $r['bilan_net']>=0?'#e8f5e9':'#ffebee' ?>; color: <?= $r['bilan_net']>=0?'var(--p-green)':'var(--p-red)' ?>;">
                            <?= $r['bilan_net'] >= 0 ? '▲' : '▼' ?> <?= abs($r['bilan_net']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="section-card" style="page-break-before: always;">
        <div class="section-title">📋 Journal Détaillé</div>
        <table class="custom-table">
            <thead>
                <tr><th>Date</th><th>Produit</th><th>Site</th><th>Type</th><th>Qté</th><th>Responsable</th></tr>
            </thead>
            <tbody>
                <?php foreach ($mouvements as $m): $isEntree = (trim(strtolower($m['type_mouvement'])) === 'entree'); ?>
                <tr>
                    <td><?= date('d/m/Y H:i', strtotime($m['date_mouvement'])) ?></td>
                    <td><strong><?= htmlspecialchars($m['nom_produit']) ?></strong></td>
                    <td><?= htmlspecialchars($sites_map[$m['id_site']] ?? 'N/A') ?></td>
                    <td><span class="badge <?= $isEntree?'badge-in':'badge-out' ?>"><?= $isEntree?'⬆️ ENTRÉE':'⬇️ SORTIE' ?></span></td>
                    <td><?= $m['quantite'] ?></td>
                    <td><?= htmlspecialchars($m['responsable_nom']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="print-footer">
        <table style="width: 100%; text-align: center;">
            <tr>
                <td><strong>Responsable Stock</strong><br><br>________________</td>
                <td><strong>Direction</strong><br><br>________________</td>
            </tr>
        </table>
        <p style="text-align: center; font-size: 0.8em; color: #666; margin-top: 20px;">Généré le <?= date('d/m/Y H:i') ?> - NMS Planning</p>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/footer.php'; ?>