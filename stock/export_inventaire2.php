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

// 2. Requête SQL (Jointures avec Sites, Users et inclusion du commentaire)
$sql = "SELECT m.*, p.nom_produit, p.unite_mesure, s.nom_site, u.nom as sup_nom, u.prenom as sup_prenom 
        FROM mouvements_stock_admin m 
        JOIN produits_admin p ON m.id_produit_admin = p.id_produit_admin
        LEFT JOIN sites s ON m.id_site_destination = s.id_site
        LEFT JOIN users u ON s.id_site = u.id_site AND u.role = 'SUPERVISEUR'";

if (!empty($where_clauses)) { 
    $sql .= " WHERE " . implode(" AND ", $where_clauses); 
}
$sql .= " ORDER BY m.date_mouvement DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$donnees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Configuration des headers Excel
$filename = "Inventaire_Detaille_" . date('d-m-Y') . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

// Initialisation des compteurs
$grand_total = 0;
$total_quantite = 0;
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<table border="1">
    <thead>
        <tr>
            <th colspan="9" style="background-color: #F57C00; color: white; font-size: 18px; height: 45px;">
                RAPPORT D'INVENTAIRE DÉTAILLÉ - <?= htmlspecialchars($_GET['f_action'] ?? 'GLOBAL') ?>
            </th>
        </tr>
        <tr style="background-color: #f1f5f9; font-weight: bold; text-align: center;">
            <th width="120">Date & Heure</th>
            <th width="180">Désignation</th>
            <th width="80">Action</th>
            <th width="130">Destination</th>
            <th width="150">Superviseur</th>
            <th width="180">Commentaire / Justification</th> <th width="60">Qté</th>
            <th width="100">P.U (FCFA)</th>
            <th width="130">Total (FCFA)</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        foreach ($donnees as $row): 
            $montant_ligne = (float)$row['quantite'] * (float)$row['prix_mouvement'];
            $grand_total += $montant_ligne;
            $total_quantite += (int)$row['quantite'];
            
            $site = ($row['type_mouvement'] == 'SORTIE' && $row['nom_site']) ? htmlspecialchars($row['nom_site']) : '---';
            $sup = ($row['type_mouvement'] == 'SORTIE' && $row['sup_nom']) ? htmlspecialchars($row['sup_nom'].' '.$row['sup_prenom']) : '---';
            $comm = !empty($row['commentaire']) ? htmlspecialchars($row['commentaire']) : 'Aucune précision';
        ?>
        <tr>
            <td align="center"><?= date('d/m/Y H:i', strtotime($row['date_mouvement'])) ?></td>
            <td><?= htmlspecialchars($row['nom_produit']) ?> (<?= $row['unite_mesure'] ?>)</td>
            <td align="center" style="font-weight:bold; color: <?= ($row['type_mouvement']=='ENTREE') ? '#16a34a' : '#ef4444' ?>;">
                <?= $row['type_mouvement'] ?>
            </td>
            <td align="center"><?= $site ?></td>
            <td align="center"><?= $sup ?></td>
            <td style="font-style: italic; color: #475569; font-size: 11px;"><?= $comm ?></td>
            <td align="center"><?= $row['quantite'] ?></td>
            <td align="right"><?= number_format($row['prix_mouvement'], 0, '', ' ') ?></td>
            <td align="right" style="font-weight: bold;"><?= number_format($montant_ligne, 0, '', ' ') ?></td>
        </tr>
        <?php endforeach; ?>

        <tr><td colspan="9" style="height: 10px; border:none;"></td></tr>

        <tr style="background-color: #f8fafc; font-weight: bold;">
            <td colspan="6" align="right">QUANTITÉ TOTALE SORTIE/ENTRÉE :</td>
            <td align="center" style="background-color: #e2e8f0;"><?= $total_quantite ?></td>
            <td colspan="2"></td>
        </tr>
        <tr style="background-color: #fff1f2; font-weight: bold; height: 35px;">
            <td colspan="8" align="right" style="font-size: 13px;">VALEUR TOTALE DES MOUVEMENTS (FCFA) :</td>
            <td align="right" style="color: #ef4444; font-size: 14px;"><?= number_format($grand_total, 0, '', ' ') ?></td>
        </tr>
    </tbody>
</table>

<br>
<table border="0" style="width: 100%; margin-top: 20px;">
    <tr>
        <td colspan="3" style="font-style: italic; color: #64748b;">
            Extraction réalisée le <?= date('d/m/Y à H:i') ?> par <?= htmlspecialchars($_SESSION['nom'] ?? 'Admin') ?>.
        </td>
    </tr>
    <tr style="height: 120px; vertical-align: top;">
        <td width="40%" style="text-align: center; border: 1pt solid #ccc; padding: 10px;">
            <strong>Cachet de l'Entrepôt / Magasin</strong><br><br><br><br>................................
        </td>
        <td width="20%"></td>
        <td width="40%" style="text-align: center; border: 1pt solid #ccc; padding: 10px;">
            <strong>Visa Direction Générale</strong><br><br><br><br>.................................
        </td>
    </tr>
</table>