<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Sécurité : Seul l'admin peut exporter
if ($_SESSION['role'] !== 'ADMIN') { exit('Accès refusé'); }

// Requête pour obtenir l'état actuel des produits
$sql = "SELECT * FROM produits_admin ORDER BY nom_produit ASC";
$stmt = $pdo->query($sql);
$inventaire = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Configuration Excel
$filename = "Inventaire_Physique_" . date('d-m-Y') . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<table border="1">
    <thead>
        <tr>
            <th colspan="6" style="background-color: #10b981; color: white; font-size: 18px; height: 40px;">
                ÉTAT GÉNÉRAL DU STOCK ET VALEUR D'INVENTAIRE
            </th>
        </tr>
        <tr style="background-color: #f8fafc; font-weight: bold;">
            <th width="250">Désignation Produit</th>
            <th width="80">Unité</th>
            <th width="80">Seuil</th>
            <th width="100">Stock Actuel</th>
            <th width="120">Prix Unit. (FCFA)</th>
            <th width="150">Valeur Totale (FCFA)</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $valeur_totale_stock = 0;
        $total_articles = 0;

        foreach ($inventaire as $row): 
            // Calcul de la valeur
            $valeur_produit = $row['quantite_globale'] * $row['prix_unitaire'];
            $valeur_totale_stock += $valeur_produit;
            $total_articles += $row['quantite_globale'];

            // Vérification du seuil d'alerte
            $is_low = ($row['quantite_globale'] <= $row['seuil_alerte']);
            
            // Style conditionnel : Rouge si stock <= seuil
            $style_ligne = $is_low ? 'style="background-color: #fee2e2; color: #991b1b;"' : '';
        ?>
        <tr <?= $style_ligne ?>>
            <td><?= htmlspecialchars($row['nom_produit']) ?></td>
            <td align="center"><?= $row['unite_mesure'] ?></td>
            <td align="center"><?= $row['seuil_alerte'] ?></td>
            <td align="center" style="font-weight: bold;">
                <?= $row['quantite_globale'] ?>
                <?= $is_low ? ' (⚠️)' : '' ?>
            </td>
            <td align="right"><?= number_format($row['prix_unitaire'], 0, '', ' ') ?></td>
            <td align="right" style="font-weight: bold;">
                <?= number_format($valeur_produit, 0, '', ' ') ?>
            </td>
        </tr>
        <?php endforeach; ?>
        
        <tr style="background-color: #f1f5f9; font-weight: bold; height: 35px;">
            <td colspan="3" align="right">VOLUME GLOBAL :</td>
            <td align="center"><?= $total_articles ?></td>
            <td align="right">VALEUR TOTALE :</td>
            <td align="right" style="color: #059669; font-size: 14px;">
                <?= number_format($valeur_totale_stock, 0, '', ' ') ?> FCFA
            </td>
        </tr>
    </tbody>
</table>

<br><br>
<table border="0" style="width: 100%;">
    <tr>
        <td colspan="2" style="font-style: italic; font-size: 11px;">
            * Les lignes en rouge indiquent un stock inférieur ou égal au seuil d'alerte.
        </td>
    </tr>
    <tr style="height: 100px;">
        <td width="50%" style="text-align: center; vertical-align: top;">
            <strong>Le Magasinier</strong><br>
            <br><br>...........................................
        </td>
        <td width="50%" style="text-align: center; vertical-align: top;">
            <strong>La Direction</strong><br>
            <br><br>...........................................
        </td>
    </tr>
</table>