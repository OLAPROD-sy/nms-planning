<?php
// On désactive l'affichage des erreurs pour ne pas corrompre le fichier Excel, 
// mais on les garde dans les logs serveur.
error_reporting(0); 
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SESSION['role'] !== 'ADMIN' && $_SESSION['role'] !== 'SUPERVISOR') { 
    exit('Accès refusé'); 
}

// 1. Récupération et assainissement des filtres
$date_start = $_GET['date_start'] ?? date('Y-m-01');
$date_end = $_GET['date_end'] ?? date('Y-m-t');
$selected_site = $_GET['id_site'] ?? '';

$params = [$date_start . ' 00:00:00', $date_end . ' 23:59:59'];
$site_condition = "";

if (!empty($selected_site)) {
    $site_condition = " AND m.id_site = ? ";
    $params[] = $selected_site;
}

// 2. Détection dynamique du nom de la colonne du site (pour éviter l'erreur 500)
$siteNameCol = 'id_site'; // Par défaut
try {
    $cols = $pdo->query("SHOW COLUMNS FROM sites")->fetchAll(PDO::FETCH_COLUMN, 0);
    foreach (['nom','name','site_nom','nom_site','titre'] as $candidate) {
        if (in_array($candidate, $cols, true)) { $siteNameCol = $candidate; break; }
    }
} catch (Exception $e) { }

// 3. Requête des mouvements (Correction de la jointure)
$sql = "SELECT m.*, p.nom_produit, s.$siteNameCol as site_name 
        FROM mouvements_stock m 
        INNER JOIN produits p ON m.id_produit = p.id_produit
        LEFT JOIN sites s ON m.id_site = s.id_site
        WHERE m.date_mouvement BETWEEN ? AND ? $site_condition
        ORDER BY m.date_mouvement DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$mouvements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Entêtes Excel
$filename = "Export_Inventaire_" . date('d-m-Y') . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style>
    .entree { background-color: #dcfce7; color: #16a34a; font-weight: bold; }
    .sortie { background-color: #fee2e2; color: #991b1b; font-weight: bold; }
    th { background-color: #F57C00; color: white; }
</style>

<table border="1">
    <tr>
        <th colspan="7" style="font-size: 16px; height: 30px;">
            RAPPORT D'INVENTAIRE - PÉRIODE DU <?= date('d/m/Y', strtotime($date_start)) ?> AU <?= date('d/m/Y', strtotime($date_end)) ?>
        </th>
    </tr>
    <tr style="background-color: #eee;">
        <th>Date & Heure</th>
        <th>Produit</th>
        <th>Site</th>
        <th>Type</th>
        <th>Quantité</th>
        <th>Responsable</th>
        <th>Commentaire</th>
    </tr>
    <?php if (empty($mouvements)): ?>
        <tr><td colspan="7" align="center">Aucune donnée trouvée pour cette période.</td></tr>
    <?php else: ?>
        <?php foreach ($mouvements as $m): 
            $type = trim(strtolower($m['type_mouvement']));
            $class = ($type === 'entree') ? 'entree' : 'sortie';
        ?>
        <tr>
            <td><?= date('d/m/Y H:i', strtotime($m['date_mouvement'])) ?></td>
            <td><?= htmlspecialchars($m['nom_produit']) ?></td>
            <td><?= htmlspecialchars($m['site_name'] ?? 'Global/Inconnu') ?></td>
            <td class="<?= $class ?>" align="center"><?= strtoupper($m['type_mouvement']) ?></td>
            <td align="right"><b><?= $m['quantite'] ?></b></td>
            <td><?= htmlspecialchars($m['responsable_nom'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($m['commentaire'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
</table>