<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';

if ($_SESSION['role'] !== 'ADMIN') { exit; }

// Récupération de l'état actuel des stocks
$sql = "SELECT nom_produit, quantite_globale, seuil_alerte 
        FROM produits_admin 
        ORDER BY nom_produit ASC";

$stmt = $pdo->query($sql);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Configuration CSV
$filename = "etat_stock_instant_T_" . date('d-m-Y_H-i') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8 pour Excel

// Entêtes
fputcsv($output, ['Désignation Produit', 'Quantité en Réserve', 'Seuil Alerte'], ';');

foreach ($data as $row) {
    fputcsv($output, $row, ';');
}
fclose($output);
exit;