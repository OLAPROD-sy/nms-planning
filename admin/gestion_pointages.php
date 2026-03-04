<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';

if ($_SESSION['role'] !== 'ADMIN') {
    $_SESSION['flash_error'] = 'Accès réservé aux administrateurs.';
    header('Location: /'); exit;
}

function formatDateLongue($date) {
    $dateObj = new DateTime($date);
    $jours = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
    $mois = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
    return $jours[$dateObj->format('w')] . ' ' . $dateObj->format('d') . ' ' . $mois[$dateObj->format('n')] . ' ' . $dateObj->format('Y');
}

// 1. Logique de récupération
$date_debut = $_GET['date_debut'] ?? date('Y-m-01');
$date_fin = $_GET['date_fin'] ?? date('Y-m-d');
$filtre_site = isset($_GET['site']) && $_GET['site'] !== '' ? $_GET['site'] : ''; 
$filtre_type = $_GET['f_type'] ?? ''; 
$filtre_user = $_GET['user'] ?? '';
$filtre_retard = isset($_GET['only_retard']) && $_GET['only_retard'] == '1' ? 1 : 0;

$sql = "SELECT p.*, u.prenom, u.nom, u.role, s.nom_site as site_nom 
        FROM pointages p
        LEFT JOIN users u ON p.id_user = u.id_user
        LEFT JOIN sites s ON p.id_site = s.id_site
        WHERE p.date_pointage BETWEEN ? AND ?";

$params = [$date_debut, $date_fin];

if ($filtre_type) { $sql .= " AND p.type = ?"; $params[] = $filtre_type; }
if ($filtre_site !== '') { $sql .= " AND p.id_site = ?"; $params[] = intval($filtre_site); }
if ($filtre_user) { 
    $sql .= " AND (u.prenom LIKE ? OR u.nom LIKE ?)"; 
    $params[] = '%' . $filtre_user . '%'; 
    $params[] = '%' . $filtre_user . '%'; 
}
if ($filtre_retard) { $sql .= " AND p.est_en_retard = 1"; }

$sql .= " ORDER BY p.date_pointage DESC, p.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pointages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sites = $pdo->query("SELECT id_site, nom_site FROM sites ORDER BY nom_site")->fetchAll(PDO::FETCH_ASSOC);

// --- CALCUL DES STATS ---
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

<style>
    :root { --p-orange: #f39c12; --p-green: #27ae60; --p-red: #e74c3c; --p-blue: #3498db; --p-purple: #8e44ad; --bg: #f8f9fa; }
    .page-wrapper { max-width: 1300px; margin: 0 auto; padding: 25px; font-family: 'Inter', sans-serif; background: var(--bg); }
    .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 25px; }
    .stat-card { background: white; padding: 18px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border-bottom: 4px solid #ddd; }
    .stat-card.red { border-bottom-color: var(--p-red); }
    .stat-card.purple { border-bottom-color: var(--p-purple); }
    .stat-card.orange { border-bottom-color: var(--p-orange); }
    .stat-card.blue { border-bottom-color: var(--p-blue); }
    .stat-label { font-size: 0.75rem; color: #7f8c8d; text-transform: uppercase; font-weight: 700; }
    .stat-value { font-size: 1.6rem; font-weight: 800; color: #2c3e50; margin-top: 5px; }

    .filter-box { background: white; padding: 20px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; align-items: flex-end; }
    .custom-input { width: 100%; padding: 10px; border: 1px solid #dfe6e9; border-radius: 8px; font-size: 0.85rem; }

    .table-container { background: white; border-radius: 12px; overflow-x: auto; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
    .modern-table { width: 100%; border-collapse: collapse; min-width: 950px; }
    .modern-table th { background: #f8fafc; padding: 15px; text-align: left; font-size: 0.7rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #eee; }
    .modern-table td { padding: 15px; border-bottom: 1px solid #f1f1f1; vertical-align: middle; font-size: 0.9rem; }

    .badge { padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 800; display: inline-flex; align-items: center; gap: 4px; }
    .b-normal { background: #e8f5e9; color: #1b5e20; }
    .b-retard { background: #ffebee; color: #b71c1c; border: 1px solid #ffcdd2; }
    .b-urgence { background: #fff3e0; color: #e65100; }
    .b-absence { background: #f3e5f5; color: #4a148c; border: 1px solid #e1bee7; }
</style>

<div class="page-wrapper">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
        <h1 style="font-weight:800; color:#2c3e50; margin:0;">📊 Suivi Présence Admin</h1>
        <button onclick="exportExcel()" style="background:#27ae60; color:white; padding:10px 18px; border-radius:8px; border:none; cursor:pointer; font-weight:bold; font-size:0.85rem;">📥 Exporter Excel</button>
    </div>

    <div class="stats-row">
        <div class="stat-card red"><div class="stat-label">Retards</div><div class="stat-value"><?= $nb_retards ?></div></div>
        <div class="stat-card purple"><div class="stat-label">Absences</div><div class="stat-value"><?= $nb_absences ?></div></div>
        <div class="stat-card orange"><div class="stat-label">Urgences Courtes</div><div class="stat-value"><?= $nb_urgences ?></div></div>
        <div class="stat-card blue"><div class="stat-label">Heures Travail</div><div class="stat-value"><?= intdiv($total_minutes, 60) ?>h <?= ($total_minutes % 60) ?>m</div></div>
    </div>

    <div class="filter-box">
        <form method="get" id="filterForm" class="filter-grid">
            <div><label style="font-size:0.7rem;font-weight:bold;">DÉBUT</label><input type="date" name="date_debut" value="<?= $date_debut ?>" class="custom-input"></div>
            <div><label style="font-size:0.7rem;font-weight:bold;">FIN</label><input type="date" name="date_fin" value="<?= $date_fin ?>" class="custom-input"></div>
            <div>
                <label style="font-size:0.7rem;font-weight:bold;">TYPE</label>
                <select name="f_type" class="custom-input">
                    <option value="">Tous les types</option>
                    <option value="NORMAL" <?= $filtre_type == 'NORMAL' ? 'selected' : '' ?>>Normal</option>
                    <option value="URGENCE" <?= $filtre_type == 'URGENCE' ? 'selected' : '' ?>>Urgence Courte</option>
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
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pointages as $p): 
                    $is_grouped = (strpos($p['commentaire'] ?? '', 'GROUPED:') === 0);
                    $abs_data = $is_grouped ? explode(':', $p['commentaire']) : [];
                ?>
                <tr>
                    <td>
                        <div style="font-weight:700; color:#2c3e50;"><?= htmlspecialchars($p['prenom'].' '.$p['nom']) ?></div>
                        <span style="font-size:0.7rem; color:#94a3b8;"><?= htmlspecialchars($p['role']) ?></span>
                    </td>
                    <td>
                        <div style="font-weight:600; color:var(--p-blue);">📍 <?= htmlspecialchars($p['site_nom']) ?></div>
                        <div style="font-size:0.75rem; color:#94a3b8;"><?= date('d/m/Y', strtotime($p['date_pointage'])) ?></div>
                    </td>
                    <td>
                        <?php 
                        // LOGIQUE DE DÉTECTION STRICTE
                        if ($is_grouped || $p['type'] === 'ABSENCE'): ?>
                            <span class="badge b-absence">📁 ABSENCE</span>
                        <?php elseif ($p['type'] === 'URGENCE'): ?>
                            <span class="badge b-urgence">🚨 URGENCE COURTE</span>
                        <?php elseif ($p['est_en_retard'] == 1): ?>
                            <span class="badge b-retard">⏰ EN RETARD</span>
                        <?php else: ?>
                            <span class="badge b-normal">✅ NORMAL</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($is_grouped): ?>
                            <span style="color:var(--p-purple); font-weight:800; font-size:0.85rem;">Du <?= $abs_data[1] ?> au <?= $abs_data[2] ?></span>
                        <?php else: ?>
                            <div style="font-family:monospace; font-size:0.8rem;">
                                <span style="color:var(--p-green)">Arrivé: <?= substr($p['heure_arrivee'], 0, 5) ?: '--:--' ?></span><br>
                                <span style="color:var(--p-red)">Sortie: <?= substr($p['heure_depart'], 0, 5) ?: '--:--' ?></span>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="font-weight:700; color:#334155;">
                        <?php 
                        if ($is_grouped) {
                            echo $abs_data[3] . ' Jours';
                        } elseif ($p['heure_arrivee'] && $p['heure_depart']) {
                            $d1 = new DateTime($p['heure_arrivee']); $d2 = new DateTime($p['heure_depart']);
                            $diff = $d1->diff($d2); echo $diff->h . 'h ' . $diff->i . 'm';
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                    <td>
                        <?php $note = $p['motif_urgence'] ?: ($is_grouped ? ($abs_data[4] ?? '') : ''); ?>
                        <?php if(!empty($note)): ?>
                            <button onclick="alert('Note: <?= addslashes($note) ?>')" style="background:none; border:1px solid #e2e8f0; padding:5px 8px; border-radius:6px; cursor:pointer;">👁️</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function exportExcel() {
    const form = document.getElementById('filterForm');
    const params = new URLSearchParams(new FormData(form)).toString();
    window.location.href = 'export_pointages_excel.php?' + params;
}
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>