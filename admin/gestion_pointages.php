<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';

if ($_SESSION['role'] !== 'ADMIN') {
    $_SESSION['flash_error'] = 'Accès réservé aux administrateurs.';
    header('Location: /'); exit;
}


// 1. Récupération des filtres
$date_debut = $_GET['date_debut'] ?? date('Y-m-01');
$date_fin = $_GET['date_fin'] ?? date('Y-m-d');
$filtre_site = isset($_GET['site']) && $_GET['site'] !== '' ? $_GET['site'] : ''; 
$filtre_type = $_GET['f_type'] ?? ''; 
$filtre_user = $_GET['user'] ?? '';

// 2. Construction de la requête SQL
$sql = "SELECT p.*, u.prenom, u.nom, u.role, s.nom_site as site_nom 
        FROM pointages p
        LEFT JOIN users u ON p.id_user = u.id_user
        LEFT JOIN sites s ON p.id_site = s.id_site
        WHERE p.date_pointage BETWEEN ? AND ?";

$params = [$date_debut, $date_fin];

// --- LOGIQUE DE FILTRAGE ---
if ($filtre_type === 'ABSENCE') {
    $sql .= " AND (p.type = 'ABSENCE' OR p.commentaire LIKE 'GROUPED:%')";
} elseif ($filtre_type === 'URGENCE') {
    $sql .= " AND p.type = 'URGENCE' AND (p.commentaire NOT LIKE 'GROUPED:%' OR p.commentaire IS NULL)";
} elseif ($filtre_type === 'RETARD') {
    $sql .= " AND p.est_en_retard = 1";
} elseif ($filtre_type === 'NORMAL') {
    $sql .= " AND p.type = 'NORMAL' AND p.est_en_retard = 0";
}

if ($filtre_site !== '') { $sql .= " AND p.id_site = ?"; $params[] = intval($filtre_site); }
if ($filtre_user) { 
    $sql .= " AND (u.prenom LIKE ? OR u.nom LIKE ?)"; 
    $params[] = '%' . $filtre_user . '%'; $params[] = '%' . $filtre_user . '%'; 
}

$sql .= " ORDER BY p.date_pointage DESC, p.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pointages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sites = $pdo->query("SELECT id_site, nom_site FROM sites ORDER BY nom_site")->fetchAll(PDO::FETCH_ASSOC);

// 3. Calcul des compteurs
$nb_retards = 0; $nb_absences = 0; $nb_urgences = 0; $total_minutes = 0;
foreach ($pointages as $p) {
    $is_grouped = (strpos($p['commentaire'] ?? '', 'GROUPED:') === 0);
    if ($p['est_en_retard'] == 1) $nb_retards++;
    if ($is_grouped || $p['type'] === 'ABSENCE') $nb_absences++;
    if (!$is_grouped && $p['type'] === 'URGENCE') $nb_urgences++;
    if ($p['heure_arrivee'] && $p['heure_depart'] && !$is_grouped) {
        $d = new DateTime($p['heure_arrivee']); $f = new DateTime($p['heure_depart']);
        $total_minutes += ($f->diff($d)->h * 60) + $f->diff($d)->i;
    }
}
?>

<?php include_once __DIR__ . '/../includes/header.php'; ?>


<div class="page-wrapper">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
        <h1 style="font-weight:800; color:#2c3e50; margin:0;"><i class="bi bi-bar-chart"></i> Suivi Présence Admin</h1>
        <button onclick="exportExcel()" style="background:#27ae60; color:white; padding:10px 18px; border-radius:8px; border:none; cursor:pointer; font-weight:bold; font-size:0.85rem;"><i class="bi bi-file-earmark-excel"></i> Exporter Excel</button>
    </div>

    <div class="stats-row">
        <div class="stat-card red"><div class="stat-label">Retards</div><div class="stat-value" style="color:var(--p-red)"><?= $nb_retards ?></div></div>
        <div class="stat-card purple"><div class="stat-label">Absences</div><div class="stat-value" style="color:var(--p-purple)"><?= $nb_absences ?></div></div>
        <div class="stat-card orange"><div class="stat-label">Urgences</div><div class="stat-value" style="color:var(--p-orange)"><?= $nb_urgences ?></div></div>
    </div>

    <div class="filter-box">
        <form method="get" id="filterForm" class="filter-grid">
            <div><label style="font-size:0.7rem;font-weight:bold;">DÉBUT</label><input type="date" name="date_debut" value="<?= $date_debut ?>" class="custom-input"></div>
            <div><label style="font-size:0.7rem;font-weight:bold;">FIN</label><input type="date" name="date_fin" value="<?= $date_fin ?>" class="custom-input"></div>
            <div>
                <label style="font-size:0.7rem;font-weight:bold;">STATUT PRÉSENCE</label>
                <select name="f_type" class="custom-input">
                    <option value="">Tout afficher</option>
                    <option value="NORMAL" <?= $filtre_type == 'NORMAL' ? 'selected' : '' ?>>Ponctuel (Normal)</option>
                    <option value="RETARD" <?= $filtre_type == 'RETARD' ? 'selected' : '' ?>>En Retard</option>
                    <option value="URGENCE" <?= $filtre_type == 'URGENCE' ? 'selected' : '' ?>>Urgence/Permission</option>
                    <option value="ABSENCE" <?= $filtre_type == 'ABSENCE' ? 'selected' : '' ?>>Absence Longue</option>
                </select>
            </div>
            <div>
                <label style="font-size:0.7rem;font-weight:bold;">SITE</label>
                <select name="site" class="custom-input">
                    <option value="">Tous les sites</option>
                    <?php foreach ($sites as $s): ?>
                        <option value="<?= $s['id_site'] ?>" <?= (string)$filtre_site === (string)$s['id_site'] ? 'selected' : '' ?>><?= $s['nom_site'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" style="background:var(--p-blue); color:white; border:none; padding:10px; border-radius:8px; cursor:pointer; font-weight:bold;">Filtrer</button>
        </form>
    </div>

    <div class="table-container">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Collaborateur</th>
                    <th>Site / Date</th>
                    <th>Type Exact</th>
                    <th>Horaire / Période</th>
                    <th>Durée</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pointages as $p): 
                    $is_grouped = (strpos($p['commentaire'] ?? '', 'GROUPED:') === 0);
                    $abs_data = $is_grouped ? explode(':', $p['commentaire']) : [];
                ?>
                <tr>
                    <td>
                        <div style="font-weight:700;"><?= htmlspecialchars($p['prenom'].' '.$p['nom']) ?></div>
                        <span style="font-size:0.7rem; color:#94a3b8;"><?= htmlspecialchars($p['role']) ?></span>
                    </td>
                    <td>
                        <div style="font-weight:600; color:var(--p-blue);"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($p['site_nom']) ?></div>
                        <div style="font-size:0.75rem; color:#94a3b8;"><?= date('d/m/Y', strtotime($p['date_pointage'])) ?></div>
                    </td>
                    <td>
                        <?php if ($is_grouped || $p['type'] === 'ABSENCE'): ?>
                            <span class="badge b-absence"><i class="bi bi-folder"></i> ABSENCE</span>
                        <?php elseif ($p['est_en_retard'] == 1): ?>
                            <span class="badge b-retard"><i class="bi bi-alarm"></i> EN RETARD</span>
                        <?php elseif ($p['type'] === 'URGENCE'): ?>
                            <span class="badge b-urgence"><i class="bi bi-exclamation-diamond"></i> URGENCE</span>
                        <?php else: ?>
                            <span class="badge b-normal"><i class="bi bi-check-circle"></i> NORMAL</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($is_grouped): ?>
                            <span style="color:var(--p-purple); font-weight:800;">Du <?= $abs_data[1] ?> au <?= $abs_data[2] ?></span>
                        <?php else: ?>
                            <div style="font-family:monospace; font-size:0.8rem;">
                                In: <?= substr($p['heure_arrivee'], 0, 5) ?: '--:--' ?><br>
                                Out: <?= substr($p['heure_depart'], 0, 5) ?: '--:--' ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="font-weight:700;">
                        <?php 
                        if ($is_grouped) echo $abs_data[3] . ' Jours';
                        elseif ($p['heure_arrivee'] && $p['heure_depart']) {
                            $d1 = new DateTime($p['heure_arrivee']); $d2 = new DateTime($p['heure_depart']);
                            echo $d1->diff($d2)->format('%hh %im');
                        } else echo '-';
                        ?>
                    </td>
                    <td>
                        <?php $note = $p['motif_urgence'] ?: ($is_grouped ? ($abs_data[4] ?? '') : ''); ?>
                        <?php if(!empty($note)): ?>
                            <button onclick="alert('Note: <?= addslashes($note) ?>')" style="border:1px solid #ddd; background:white; border-radius:4px; cursor:pointer;"><i class="bi bi-eye"></i></button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
