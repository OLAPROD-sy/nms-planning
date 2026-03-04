<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SESSION['role'] !== 'ADMIN') { exit('Accès refusé'); }

$date_debut = $_GET['date_debut'] ?? date('Y-m-01');
$date_fin = $_GET['date_fin'] ?? date('Y-m-d');
$filtre_site = $_GET['site'] ?? '';
$filtre_type = $_GET['f_type'] ?? '';

$sql = "SELECT p.*, u.prenom, u.nom, u.role, s.nom_site 
        FROM pointages p
        LEFT JOIN users u ON p.id_user = u.id_user
        LEFT JOIN sites s ON p.id_site = s.id_site
        WHERE p.date_pointage BETWEEN ? AND ?";

$params = [$date_debut, $date_fin];

if ($filtre_type === 'ABSENCE') {
    $sql .= " AND (p.type = 'ABSENCE' OR p.commentaire LIKE 'GROUPED:%')";
} elseif ($filtre_type === 'URGENCE') {
    $sql .= " AND p.type = 'URGENCE' AND (p.commentaire NOT LIKE 'GROUPED:%' OR p.commentaire IS NULL)";
} elseif ($filtre_type === 'RETARD') {
    $sql .= " AND p.est_en_retard = 1";
} elseif ($filtre_type === 'NORMAL') {
    $sql .= " AND p.type = 'NORMAL' AND p.est_en_retard = 0";
}

if ($filtre_site !== '') { 
    $sql .= " AND p.id_site = ?"; 
    $params[] = intval($filtre_site); 
}

$sql .= " ORDER BY p.date_pointage DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pointages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Variables pour le résumé final
$tot_retards = 0;
$tot_absences = 0;
$tot_urgences = 0;
$min_travail = 0;

$filename = "Export_Pointages_" . date('d-m-Y') . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
?>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<table border="1">
    <tr style="background-color: #2c3e50; color: white; font-weight: bold;">
        <th>Collaborateur</th>
        <th>Rôle</th>
        <th>Site</th>
        <th>Date</th>
        <th>Statut / Type</th>
        <th>Horaire / Période</th>
        <th>Durée</th>
        <th>Motif / Note</th>
    </tr>

    <?php foreach ($pointages as $p): 
        $is_grouped = (strpos($p['commentaire'] ?? '', 'GROUPED:') === 0);
        $abs_data = $is_grouped ? explode(':', $p['commentaire']) : [];
        
        $statut_texte = "NORMAL";
        if ($is_grouped || $p['type'] === 'ABSENCE') {
            $statut_texte = "ABSENCE";
            $tot_absences++;
        } elseif ($p['est_en_retard'] == 1) {
            $statut_texte = "RETARD";
            $tot_retards++;
        } elseif ($p['type'] === 'URGENCE') {
            $statut_texte = "URGENCE COURTE";
            $tot_urgences++;
        }

        // Calcul du temps de travail (hors absences groupées)
        if (!$is_grouped && $p['heure_arrivee'] && $p['heure_depart']) {
            $d1 = new DateTime($p['heure_arrivee']);
            $d2 = new DateTime($p['heure_depart']);
            $diff = $d1->diff($d2);
            $min_travail += ($diff->h * 60) + $diff->i;
        }
    ?>
    <tr>
        <td><?= htmlspecialchars($p['prenom'] . ' ' . $p['nom']) ?></td>
        <td><?= htmlspecialchars($p['role']) ?></td>
        <td><?= htmlspecialchars($p['nom_site'] ?? 'N/A') ?></td>
        <td><?= date('d/m/Y', strtotime($p['date_pointage'])) ?></td>
        <td style="font-weight: bold;"><?= $statut_texte ?></td>
        <td><?= $is_grouped ? "Du ".$abs_data[1]." au ".$abs_data[2] : substr($p['heure_arrivee'], 0, 5)." - ".substr($p['heure_depart'], 0, 5) ?></td>
        <td>
            <?php 
                if ($is_grouped) echo $abs_data[3] . " Jours";
                elseif ($p['heure_arrivee'] && $p['heure_depart']) {
                    $d1 = new DateTime($p['heure_arrivee']); $d2 = new DateTime($p['heure_depart']);
                    echo $d1->diff($d2)->format('%hh %im');
                } else echo "-";
            ?>
        </td>
        <td><?= htmlspecialchars($p['motif_urgence'] ?: ($is_grouped ? ($abs_data[4] ?? '') : '')) ?></td>
    </tr>
    <?php endforeach; ?>

    <tr style="background-color: #f1f3f5; font-weight: bold;">
        <td colspan="4" style="text-align: right;">TOTAL SUR LA PÉRIODE :</td>
        <td>
            Retards: <?= $tot_retards ?><br>
            Absences: <?= $tot_absences ?><br>
            Urgences: <?= $tot_urgences ?>
        </td>
        <td style="text-align: right;">Temps Travail Total :</td>
        <td><?= intdiv($min_travail, 60) ?>h <?= ($min_travail % 60) ?>m</td>
        <td></td>
    </tr>
</table>