<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Sécurité : Seul l'admin peut exporter
if ($_SESSION['role'] !== 'ADMIN') { exit('Accès refusé'); }

// 1. Récupération des filtres (identique à votre page admin)
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

// 2. Requête SQL pour obtenir les données avec le prix de mouvement
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

// 3. Configuration des headers pour forcer le téléchargement Excel
$filename = "Inventaire_" . ($_GET['f_action'] ?? 'Global') . "_" . date('d-m-Y') . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

// 4. Génération du tableau HTML que Excel va interpréter
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<table border="1">
    <thead>
        <tr>
            <th colspan="6" style="background-color: #F57C00; color: white; font-size: 16px;">
                RAPPORT D'INVENTAIRE - <?= $_GET['f_action'] ?? 'TOUT' ?> (<?= date('d/m/Y') ?>)
            </th>
        </tr>
        <tr style="background-color: #eee;">
            <th>Date & Heure</th>
            <th>Désignation Produit</th>
            <th>Type Action</th>
            <th>Quantité</th>
            <th>Prix Unitaire (FCFA)</th>
            <th>Montant Total (FCFA)</th>
        </tr>
    </thead>
    <?php 
        $grand_total = 0;
        foreach ($donnees as $row): 
            $montant_ligne = $row['quantite'] * $row['prix_mouvement'];
            $grand_total += $montant_ligne;
        ?>
    <tbody>
        
        <tr>
            <td><?= date('d/m/Y H:i', strtotime($row['date_mouvement'])) ?></td>
            <td><?= htmlspecialchars($row['nom_produit']) ?> (<?= $row['unite_mesure'] ?>)</td>
            <td><?= $row['type_mouvement'] ?></td>
            <td align="center"><?= $row['quantite'] ?></td>
            <td align="right"><?= number_format($row['prix_mouvement'], 0, '', ' ') ?></td>
            <td align="right" style="font-weight: bold;"><?= number_format($montant_ligne, 0, '', ' ') ?></td>
        </tr>
        </tbody>
    <tfoot>
        <tr style="background-color: #fff1f2; font-weight: bold;">
            <td colspan="5" align="right">MONTANT TOTAL GÉNÉRAL :</td>
            <td align="right" style="color: #ef4444;"><?= number_format($grand_total, 0, '', ' ') ?> FCFA</td>
        </tr>
    </tfoot>
    <?php endforeach; ?>
</table>