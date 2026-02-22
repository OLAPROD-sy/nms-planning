<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';

if ($_SESSION['role'] !== 'ADMIN') { exit; }

// On récupère les filtres pour exporter la sélection précise
$where = [];
$params = [];

if (!empty($_GET['f_produit'])) { $where[] = "m.id_produit_admin = ?"; $params[] = $_GET['f_produit']; }
if (!empty($_GET['f_action'])) { $where[] = "m.type_mouvement = ?"; $params[] = $_GET['f_action']; }
if (!empty($_GET['f_date'])) { $where[] = "DATE(m.date_mouvement) = ?"; $params[] = $_GET['f_date']; }

$sql = "SELECT m.date_mouvement, p.nom_produit, m.type_mouvement, m.quantite, m.commentaire 
        FROM mouvements_stock_admin m 
        JOIN produits_admin p ON m.id_produit_admin = p.id_produit_admin";

if (!empty($where)) { $sql .= " WHERE " . implode(" AND ", $where); }
$sql .= " ORDER BY m.date_mouvement DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Configuration des headers pour le téléchargement Excel (CSV)
$filename = "export_stock_" . date('d-m-Y') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

// Création du fichier CSV
$output = fopen('php://output', 'w');
// Correction pour Excel (BOM UTF-8)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Entêtes des colonnes
fputcsv($output, ['Date', 'Produit', 'Action', 'Quantité', 'Commentaire'], ';');

// Données
foreach ($data as $row) {
    fputcsv($output, $row, ';');
}
fclose($output);
exit;