<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$id_agent = $_SESSION['id_user'] ?? null;
if (!$id_agent) {
    header('Location: /auth/login.php');
    exit;
}

// 1. Gérer le filtrage par semaine
// Si aucune semaine n'est choisie, on prend la semaine actuelle
$date_selectionnee = isset($_GET['semaine']) ? $_GET['semaine'] : date('Y-m-d', strtotime('monday this week'));
$debut_semaine = date('Y-m-d', strtotime('monday this week', strtotime($date_selectionnee)));
$fin_semaine = date('Y-m-d', strtotime('sunday this week', strtotime($date_selectionnee)));

// 2. Récupérer toutes les semaines disponibles (pour le filtre)
$stmtSemaines = $pdo->query("SELECT DISTINCT date_debut, date_fin FROM semaines ORDER BY date_debut DESC");
$liste_semaines = $stmtSemaines->fetchAll();

// 3. Récupérer le planning détaillé de l'agent pour la période choisie
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

// Fonction pour calculer la durée joliment
function calculerDuree($debut, $fin) {
    $d1 = new DateTime($debut);
    $d2 = new DateTime($fin);
    $interval = $d1->diff($d2);
    return $interval->format('%hh %im');
}

// Traduction des jours en Français
$jours_fr = [
    'Monday' => 'Lundi', 'Tuesday' => 'Mardi', 'Wednesday' => 'Mercredi',
    'Thursday' => 'Jeudi', 'Friday' => 'Vendredi', 'Saturday' => 'Samedi', 'Sunday' => 'Dimanche'
];
?>

<?php include_once __DIR__ . '/../includes/header.php'; ?>


<div class="container-planning">
    <div class="schedule-header">
        <h1>📅 Mon Planning</h1>
        
        <form method="GET" class="filter-box">
            <label>Semaine du :</label>
            <select name="semaine" onchange="this.form.submit()">
                <?php foreach($liste_semaines as $s): ?>
                    <option value="<?= $s['date_debut'] ?>" <?= ($s['date_debut'] == $debut_semaine) ? 'selected' : '' ?>>
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
                Aucune programmation trouvée pour cette semaine.
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
                        <span class="poste-tag">📍 <?= htmlspecialchars($p['poste_nom'] ?? 'Poste standard') ?></span>
                        <div class="time-row">
                            <span><?= substr($p['heure_debut'], 0, 5) ?></span>
                            <span style="color:#ccc">➔</span>
                            <span><?= substr($p['heure_fin'], 0, 5) ?></span>
                        </div>
                        <div class="duration-badge">
                            ⏱️ Durée : <strong><?= calculerDuree($p['heure_debut'], $p['heure_fin']) ?></strong>
                        </div>
                        <div style="margin-top:10px; font-size: 0.85em; color: #888;">
                            🏢 Site : <?= htmlspecialchars($p['nom_site'] ?? 'Non spécifié') ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>