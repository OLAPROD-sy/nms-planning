<?php
require_once __DIR__ . '/../config/database.php';

$date_start = $_GET['date_start'];
$date_end = $_GET['date_end'];

// 1. Entêtes pour forcer le téléchargement Excel
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Inventaire_NMS_'.date('Y-m-d').'.csv');

// 2. Ouvrir le flux de sortie
$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // Fix pour les accents (UTF-8)

// 3. Titre des colonnes
fputcsv($output, ['Date', 'Produit', 'Type', 'Quantité', 'Responsable']);

// 4. Récupération des données
$stmt = $pdo->prepare('SELECT m.*, p.nom_produit FROM mouvements_stock m JOIN produits p ON m.id_produit = p.id_produit WHERE m.date_mouvement BETWEEN ? AND ? ORDER BY m.date_mouvement DESC');
$stmt->execute([$date_start . ' 00:00:00', $date_end . ' 23:59:59']);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        date('d/m/Y H:i', strtotime($row['date_mouvement'])),
        $row['nom_produit'],
        strtoupper($row['type_mouvement']),
        $row['quantite'],
        $row['responsable_nom']
    ]);
}
fclose($output);
exit;