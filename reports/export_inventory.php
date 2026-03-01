<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SESSION['role'] !== 'ADMIN') { exit('Accès refusé'); }

// 1. Récupération des filtres
$date_start = $_GET['date_start'] ?? date('Y-m-01');
$date_end = $_GET['date_end'] ?? date('Y-m-t');
$selected_site = $_GET['id_site'] ?? '';

$params = [$date_start . ' 00:00:00', $date_end . ' 23:59:59'];
$site_condition = "";
if (!empty($selected_site)) {
    $site_condition = " AND m.id_site = ? ";
    $params[] = $selected_site;
}

// 2. Requête des mouvements
$sql = "SELECT m.*, p.nom_produit, s.nom as nom_site 
        FROM mouvements_stock m 
        JOIN produits p ON m.id_produit = p.id_produit
        LEFT JOIN sites s ON m.id_site = s.id_site
        WHERE m.date_mouvement BETWEEN ? AND ? $site_condition
        ORDER BY m.date_mouvement DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$mouvements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Entêtes Excel
$filename = "Rapport_Inventaire_" . date('d-m-Y') . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
?>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<table border="1">
    <tr>
        <th colspan="6" style="background-color: #F57C00; color: white; font-size: 16px;">
            RAPPORT D'INVENTAIRE DU <?= date('d/m/Y', strtotime($date_start)) ?> AU <?= date('d/m/Y', strtotime($date_end)) ?>
        </th>
    </tr>
    <tr style="background-color: #eee; font-weight: bold;">
        <th>Date & Heure</th>
        <th>Produit</th>
        <th>Site</th>
        <th>Type</th>
        <th>Quantité</th>
        <th>Responsable</th>
    </tr>
    <?php foreach ($mouvements as $m): 
        $is_entree = (strtolower($m['type_mouvement']) === 'entree');
        $color = $is_entree ? "#dcfce7" : "#fee2e2";
        $text_color = $is_entree ? "#16a34a" : "#991b1b";
    ?>
    <tr>
        <td><?= date('d/m/Y H:i', strtotime($m['date_mouvement'])) ?></td>
        <td><?= htmlspecialchars($m['nom_produit']) ?></td>
        <td><?= htmlspecialchars($m['nom_site'] ?? 'N/A') ?></td>
        <td style="background-color: <?= $color ?>; color: <?= $text_color ?>; font-weight: bold; text-align: center;">
            <?= strtoupper($m['type_mouvement']) ?>
        </td>
        <td align="right" style="font-weight: bold;"><?= $m['quantite'] ?></td>
        <td><?= htmlspecialchars($m['responsable_nom']) ?></td>
    </tr>
    <?php endforeach; ?>
</table>