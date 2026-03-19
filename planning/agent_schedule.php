<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$id_agent = $_SESSION['id_user'] ?? null;
if (!$id_agent) {
    header('Location: /auth/login.php');
    exit;
}

// 1. RÃ©cupÃ©rer le site de l'agent (utile pour filtrer les semaines)
$id_site = (int)($_SESSION['id_site'] ?? 0);
if ($id_site === 0) {
    $stmtSite = $pdo->prepare("SELECT id_site FROM users WHERE id_user = ?");
    $stmtSite->execute([$id_agent]);
    $id_site = (int)$stmtSite->fetchColumn();
}

// 2. DÃ©terminer la semaine par dÃ©faut
// Si aucune semaine n'est choisie, on prend la derniÃ¨re programmÃ©e pour l'agent,
// sinon la semaine actuelle.
$stmtLast = $pdo->prepare("SELECT date_planning FROM programmations WHERE id_agent = ? ORDER BY date_planning DESC LIMIT 1");
$stmtLast->execute([$id_agent]);
$last_date = $stmtLast->fetchColumn();
$date_selectionnee = isset($_GET['semaine']) ? $_GET['semaine'] : ($last_date ?: date('Y-m-d'));
$debut_semaine = date('Y-m-d', strtotime('monday this week', strtotime($date_selectionnee)));
$fin_semaine = date('Y-m-d', strtotime('sunday this week', strtotime($date_selectionnee)));

// 3. RÃ©cupÃ©rer toutes les semaines disponibles (pour le filtre)
if ($id_site > 0) {
    $stmtSemaines = $pdo->prepare("SELECT DISTINCT date_debut, date_fin FROM semaines WHERE id_site = ? ORDER BY date_debut DESC");
    $stmtSemaines->execute([$id_site]);
    $liste_semaines = $stmtSemaines->fetchAll();
} else {
    $stmtSemaines = $pdo->query("SELECT DISTINCT date_debut, date_fin FROM semaines ORDER BY date_debut DESC");
    $liste_semaines = $stmtSemaines->fetchAll();
}

if (empty($liste_semaines)) {
    $liste_semaines = [[
        'date_debut' => $debut_semaine,
        'date_fin' => $fin_semaine
    ]];
}

// 4. RÃ©cupÃ©rer le planning dÃ©taillÃ© de l'agent pour la pÃ©riode choisie
$stmt = $pdo->prepare("
    SELECT p.*, po.libelle as poste_nom, s.nom_site 
    FROM programmations p
    LEFT JOIN postes po ON p.id_poste = po.id_poste
    LEFT JOIN sites s ON p.id_site = s.id_site
    WHERE p.id_agent = ? AND p.date_planning BETWEEN ? AND ?
    ORDER BY p.date_planning ASC, p.heure_debut ASC
");
$stmt->execute([$id_agent, $debut_semaine, $fin_semaine]);
$plannings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fonction pour calculer la durÃ©e joliment
function calculerDuree($debut, $fin) {
    $d1 = new DateTime($debut);
    $d2 = new DateTime($fin);
    $interval = $d1->diff($d2);
    return $interval->format('%hh %im');
}

// Traduction des jours en FranÃ§ais
$jours_fr = [
    'Monday' => 'Lundi', 'Tuesday' => 'Mardi', 'Wednesday' => 'Mercredi',
    'Thursday' => 'Jeudi', 'Friday' => 'Vendredi', 'Saturday' => 'Samedi', 'Sunday' => 'Dimanche'
];
?>

<?php include_once __DIR__ . '/../includes/header.php'; ?>


<div class="container-planning">
    <div class="schedule-header">
        <h1><i class="bi bi-calendar3"></i> Mon Planning</h1>
        
        <form method="GET" class="filter-box">
            <label>Semaine du :</label>
            <select name="semaine" onchange="this.form.submit()">
                <?php foreach($liste_semaines as $s): ?>
                    <option value="<?= $s['date_debut'] ?>" <?= ($s['date_debut'] == $debut_semaine) ? 'selected' : '' ?> >
                        <?= date('d M', strtotime($s['date_debut'])) ?> au <?= date('d M Y', strtotime($s['date_fin'])) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="grid-schedule">
        <?php if (empty($plannings)): ?>
            <div class="no-data">
                <img src="https://cdn-icons-png.flaticon.com/512/4076/4076549.png" width="80" style="opacity: 0.3; margin-bottom: 15px;"><br>
                Aucune programmation trouvÃ©e pour cette semaine.
            </div>
        <?php else: ?>
            <?php foreach ($plannings as $p): 
                $date_p = $p['date_planning'];
                $is_today = ($date_p == date('Y-m-d'));
                $nom_jour = $jours_fr[date('l', strtotime($date_p))];
            ?>
                <div class="day-card <?= $is_today ? 'today' : '' ?>">
                    <div class="day-header">
                        <span class="day-name"><?= $nom_jour ?></span>
                        <span class="day-date"><?= date('d/m/Y', strtotime($date_p)) ?></span>
                    </div>
                    <div class="slot-info">
                        <span class="poste-tag"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($p['poste_nom'] ?? 'Poste standard') ?></span>
                        <div class="time-row">
                            <span><?= substr($p['heure_debut'], 0, 5) ?></span>
                            <span style="color:#ccc"><i class="bi bi-arrow-right"></i></span>
                            <span><?= substr($p['heure_fin'], 0, 5) ?></span>
                        </div>
                        <div class="duration-badge">
                            <i class="bi bi-clock"></i> DurÃ©e : <strong><?= calculerDuree($p['heure_debut'], $p['heure_fin']) ?></strong>
                        </div>
                        <div style="margin-top:10px; font-size: 0.85em; color: #888;">
                            <i class="bi bi-buildings"></i> Site : <?= htmlspecialchars($p['nom_site'] ?? 'Non spÃ©cifiÃ©') ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
