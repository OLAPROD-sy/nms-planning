<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Sécurité : Seul l'admin peut exporter
if ($_SESSION['role'] !== 'ADMIN') { exit('Accès refusé'); }

// 1. Récupération des filtres
$where_clauses = [];
$params = [];

if (!empty($_GET['f_action'])) {
    $where_clauses[] = "m.type_mouvement = ?";
    $params[] = $_GET['f_action'];
}

if (!empty($_GET['f_date_debut']) && !empty($_GET['f_date_fin'])) {
    $where_clauses[] = "DATE(m.date_mouvement) BETWEEN ? AND ?";
    $params[] = $_GET['f_date_debut']; 
    $params[] = $_GET['f_date_fin'];
}

// Initialisation du total général
$total_general_periode = 0;

// Dans votre boucle d'affichage ou avant l'export
foreach ($flux as &$f) {
    // Calcul du montant par ligne
    $f['montant_ligne'] = $f['quantite'] * $f['prix_mouvement'];
    // Accumulation pour le total en bas de tableau
    $total_general_periode += $f['montant_ligne'];
}

// 2. Requête SQL
$sql = "SELECT m.*, p.nom_produit, p.unite_mesure 
        FROM mouvements_stock_admin m 
        JOIN produits_admin p ON m.id_produit_admin = p.id_produit_admin";

if (!empty($where_clauses)) { 
    $sql .= " WHERE " . implode(" AND ", $where_clauses); 
}
$sql .= " ORDER BY m.date_mouvement DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$donnees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Configuration des headers Excel
$filename = "Inventaire_" . ($_GET['f_action'] ?? 'Global') . "_" . date('d-m-Y') . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<table border="1">
    <thead>
        <tr>
            <th colspan="6" style="background-color: #F57C00; color: white; font-size: 18px; height: 40px;">
                RAPPORT D'INVENTAIRE - <?= htmlspecialchars($_GET['f_action'] ?? 'TOUT') ?>
            </th>
        </tr>
        <tr style="background-color: #eee; font-weight: bold;">
            <th width="150">Date & Heure</th>
            <th width="250">Désignation Produit</th>
            <th width="100">Type Action</th>
            <th width="80">Quantité</th>
            <th width="120">Prix Unit. (FCFA)</th>
            <th width="150">Montant Total (FCFA)</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $grand_total = 0;
        $total_quantite = 0;
        foreach ($donnees as $row): 
            $montant_ligne = $row['quantite'] * $row['prix_mouvement'];
            $grand_total += $montant_ligne;
            $total_quantite += $row['quantite'];
        ?>
        <tr>
            <td><?= date('d/m/Y H:i', strtotime($row['date_mouvement'])) ?></td>
            <td><?= htmlspecialchars($row['nom_produit']) ?> (<?= $row['unite_mesure'] ?>)</td>
            <td align="center"><?= $row['type_mouvement'] ?></td>
            <td align="center"><?= $row['quantite'] ?></td>
            <td align="right"><?= number_format($row['prix_mouvement'], 0, '', ' ') ?></td>
            <td align="right" style="font-weight: bold;"><?= number_format($montant_ligne, 0, '', ' ') ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr style="background-color: #f1f5f9; font-weight: bold;">
            <td colspan="3" align="right">QUANTITÉ TOTALE D'ARTICLES :</td>
            <td align="center"><?= $total_quantite ?></td>
            <td colspan="2"></td>
        </tr>
        <tr style="background-color: #fff1f2; font-weight: bold; height: 30px;">
            <td colspan="5" align="right">MONTANT TOTAL GÉNÉRAL (FCFA) :</td>
            <td align="right" style="color: #ef4444; font-size: 14px;"><?= number_format($grand_total, 0, '', ' ') ?></td>
        </tr>
    </tfoot>
</table>

<br><br>
<table border="0" style="width: 100%;">
    <tr>
        <td colspan="3" style="text-align: left; font-style: italic;">
            Document généré le <?= date('d/m/Y à H:i') ?> par le système de gestion de stock.
        </td>
    </tr>
    <tr style="height: 100px;">
        <td width="33%" style="text-align: center; vertical-align: top;">
            <strong>Le Magasinier</strong><br>
            <small>(Nom et Signature)</small>
            <br><br>...........................................
        </td>
        <td width="33%"></td>
        <td width="33%" style="text-align: center; vertical-align: top;">
            <strong>La Direction</strong><br>
            <small>(Cachet et Signature)</small>
            <br><br>...........................................
        </td>
    </tr>
</table>