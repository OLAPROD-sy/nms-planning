<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SESSION['role'] !== 'ADMIN') { exit; }

$date_debut = $_GET['date_debut'] ?? date('Y-m-01');
$date_fin = $_GET['date_fin'] ?? date('Y-m-d');
$filtre_site = $_GET['site'] ?? '';
$filtre_type = $_GET['f_type'] ?? '';

$sql = "SELECT p.*, u.prenom, u.nom, s.nom_site FROM pointages p 
        LEFT JOIN users u ON p.id_user = u.id_user 
        LEFT JOIN sites s ON p.id_site = s.id_site 
        WHERE p.date_pointage BETWEEN ? AND ?";
$params = [$date_debut, $date_fin];

if ($filtre_type) { $sql .= " AND p.type = ?"; $params[] = $filtre_type; }
if ($filtre_site) { $sql .= " AND p.id_site = ?"; $params[] = intval($filtre_site); }

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=export_pointages.xls");

echo "<table border='1'><tr><th>Collaborateur</th><th>Site</th><th>Date</th><th>Type</th><th>Horaire/Periode</th><th>Duree</th></tr>";
foreach($rows as $r) {
    $is_abs = (strpos($r['commentaire'], 'GROUPED:') === 0);
    $d = $is_abs ? explode(':', $r['commentaire']) : [];
    echo "<tr><td>{$r['prenom']} {$r['nom']}</td><td>{$r['nom_site']}</td><td>{$r['date_pointage']}</td>";
    echo "<td>" . ($is_abs ? 'ABSENCE' : $r['type']) . "</td>";
    echo "<td>" . ($is_abs ? "Du $d[1] au $d[2]" : $r['heure_arrivee']." - ".$r['heure_depart']) . "</td>";
    echo "<td>" . ($is_abs ? $d[3]." Jours" : "") . "</td></tr>";
}
echo "</table>";