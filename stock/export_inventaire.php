<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';

// Protection : Seuls Admin et Superviseur peuvent exporter
if ($_SESSION['role'] === 'AGENT') {
    exit('Accès refusé');
}

// 1. Récupération des filtres depuis l'URL (envoyés par le JS)
$id_site = $_GET['id_site'] ?? null;
$date_start = $_GET['date_start'] ?? '';
$date_end = $_GET['date_end'] ?? '';
$filter_type = $_GET['f_type'] ?? '';

if (!$id_site) {
    exit('Site non spécifié');
}

// 2. Construction de la requête SQL (identique à celle de la page historique)
$sql = "SELECT m.date_mouvement, p.nom_produit, m.type_mouvement, m.quantite, m.responsable_nom 
        FROM mouvements_stock m 
        JOIN produits p ON m.id_produit = p.id_produit 
        WHERE m.id_site = ?";
$params = [$id_site];

if($date_start) { $sql .= " AND DATE(m.date_mouvement) >= ?"; $params[] = $date_start; }
if($date_end)   { $sql .= " AND DATE(m.date_mouvement) <= ?"; $params[] = $date_end; }
if($filter_type) { $sql .= " AND m.type_mouvement = ?"; $params[] = $filter_type; }

$sql .= " ORDER BY m.date_mouvement DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Configuration des headers pour forcer le téléchargement Excel
$filename = "historique_stock_site_" . $id_site . "_" . date('Ymd') . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

// 4. Génération du contenu (Format Table HTML compatible Excel)
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<table border="1">
    <thead>
        <tr style="background-color: #3498db; color: white;">
            <th>Date & Heure</th>
            <th>Produit</th>
            <th>Type d'opération</th>
            <th>Quantité</th>
            <th>Responsable</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($data as $row): ?>
            <tr>
                <td><?= date('d/m/Y H:i', strtotime($row['date_mouvement'])) ?></td>
                <td><?= htmlspecialchars($row['nom_produit']) ?></td>
                <td style="text-align:center; font-weight:bold; color: <?= (trim(strtolower($row['type_mouvement'])) == 'entree') ? '#27ae60' : '#e74c3c' ?>;">
                    <?= strtoupper($row['type_mouvement']) ?>
                </td>
                <td style="text-align:center;"><?= $row['quantite'] ?></td>
                <td><?= htmlspecialchars($row['responsable_nom']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>