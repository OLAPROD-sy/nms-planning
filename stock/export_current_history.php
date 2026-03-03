<?php
// On active l'affichage des erreurs pour voir s'il y a un souci de chemin
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SESSION['role'] === 'AGENT') {
    exit('Accès refusé');
}

// 1. Logique de récupération de l'ID du Site
$id_site = null;

if ($_SESSION['role'] === 'SUPERVISEUR') {
    $id_site = $_SESSION['id_site'];
} else {
    $id_site = $_GET['id_site'] ?? null;
}

if (!$id_site) {
    exit('Erreur : ID du site manquant.');
}

// 2. Récupération du nom du site (On vérifie le nom de la colonne)
try {
    // On tente 'nom', si ça échoue on tente 'nom_site'
    $stmtS = $pdo->prepare("SELECT nom_site FROM sites WHERE id_site = ?");
    $stmtS->execute([$id_site]);
    $site_name = $stmtS->fetchColumn();
} catch (Exception $e) {
    $site_name = "Site_" . $id_site;
}

$site_name = $site_name ?: "Site_" . $id_site;

// 3. Récupération des produits
$stmt = $pdo->prepare("SELECT nom_produit, quantite_actuelle, quantite_alerte FROM produits WHERE id_site = ? ORDER BY nom_produit ASC");
$stmt->execute([$id_site]);
$produits = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Configuration Excel
$filename = "etat_stock_" . str_replace(' ', '_', $site_name) . "_" . date('Ymd_Hi') . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
?>
<table border="1">
    <thead>
        <tr>
            <th colspan="3" style="background-color: #2c3e50; color: white; font-size: 16px; height: 30px;">
                ÉTAT DES STOCKS - <?= htmlspecialchars($site_name) ?> (le <?= date('d/m/Y H:i') ?>)
            </th>
        </tr>
        <tr style="background-color: #f39c12; color: white;">
            <th style="width: 250px;">Désignation du Produit</th>
            <th style="width: 150px;">Quantité en Stock</th>
            <th style="width: 150px;">Seuil d'Alerte</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($produits as $p): 
            $is_low = ($p['quantite_actuelle'] <= $p['quantite_alerte']);
        ?>
            <tr>
                <td><?= htmlspecialchars($p['nom_produit']) ?></td>
                <td style="text-align:center; font-weight:bold; <?= $is_low ? 'background-color: #ffebee; color: #c62828;' : '' ?>">
                    <?= $p['quantite_actuelle'] ?>
                    <?= $is_low ? ' (ATTENTION : SEUIL ATTEINT)' : '' ?>
                </td>
                <td style="text-align:center; color: #7f8c8d;"><?= $p['quantite_alerte'] ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>