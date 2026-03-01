<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Sécurité : Seul l'admin ou le superviseur peut exporter l'état global
if (!in_array($_SESSION['role'], ['ADMIN', 'SUPERVISEUR'])) { exit('Accès refusé'); }

// Requête pour obtenir l'état du stock actuel
// Note : Ajustez les noms des colonnes selon votre table 'produits_admin'
$sql = "SELECT nom_produit, unite_mesure, prix_achat, stock_actuel 
        FROM produits_admin 
        ORDER BY nom_produit ASC";

$stmt = $pdo->query($sql);
$produits = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Configuration Excel
$filename = "Etat_du_Stock_" . date('d-m-Y') . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
?>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<table border="1">
    <thead>
        <tr>
            <th colspan="5" style="background-color: #16a34a; color: white; font-size: 16px; height: 35px;">
                ÉTAT GÉNÉRAL DU STOCK AU <?= date('d/m/Y H:i') ?>
            </th>
        </tr>
        <tr style="background-color: #f1f5f9; font-weight: bold;">
            <th width="250">Désignation Produit</th>
            <th width="100">Unité</th>
            <th width="100">Prix Unit.</th>
            <th width="100">Stock Dispo.</th>
            <th width="150">Valeur Totale (FCFA)</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $valeur_totale_stock = 0;
        foreach ($produits as $p): 
            $valeur_ligne = $p['stock_actuel'] * $p['prix_achat'];
            $valeur_totale_stock += $valeur_ligne;
        ?>
        <tr>
            <td><?= htmlspecialchars($p['nom_produit']) ?></td>
            <td align="center"><?= htmlspecialchars($p['unite_mesure']) ?></td>
            <td align="right"><?= number_format($p['prix_achat'], 0, '', ' ') ?></td>
            <td align="center" style="<?= $p['stock_actuel'] <= 5 ? 'color:red; font-weight:bold;' : '' ?>">
                <?= $p['stock_actuel'] ?>
            </td>
            <td align="right" style="font-weight: bold;"><?= number_format($valeur_ligne, 0, '', ' ') ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr style="background-color: #f8fafc; font-weight: bold; height: 30px;">
            <td colspan="4" align="right">VALEUR TOTALE DU STOCK IMMOBILISÉ :</td>
            <td align="right" style="color: #16a34a;"><?= number_format($valeur_totale_stock, 0, '', ' ') ?> FCFA</td>
        </tr>
    </tfoot>
</table>

<br>
<table border="0" style="width: 100%;">
    <tr>
        <td width="50%" style="text-align: center;"><strong>Le Magasinier</strong><br><br>...........................</td>
        <td width="50%" style="text-align: center;"><strong>La Direction</strong><br><br>...........................</td>
    </tr>
</table>