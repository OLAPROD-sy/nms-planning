<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

$id_agent = $_SESSION['id_user'] ?? null;
if (!$id_agent) {
    header('Location: /nms-planning/auth/login.php');
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

<style>
    

    /* Change .content-planning par .container-planning pour correspondre à ton HTML */
.container-planning {
    max-width: 1200px;
    margin: 40px auto; /* Augmenté pour décoller du header */
    padding: 0 30px;    /* Augmenté pour décoller des bords gauche/droite */
}

/* Ajout d'un média query pour les petits écrans */
@media (max-width: 768px) {
    .container-planning {
        padding: 0 20px; /* Un peu moins sur mobile mais garde un espace */
        margin: 20px auto;
    }
    
    .schedule-header {
        flex-direction: column;
        align-items: flex-start;
    }
}

.slot-info { 
    padding: 20px; /* Passé de 15px à 20px pour plus de confort visuel */
}
    .schedule-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        flex-wrap: wrap;
        gap: 15px;
    }
    .filter-box {
        background: white;
        padding: 15px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .filter-box select {
        padding: 8px;
        border-radius: 5px;
        border: 1px solid #ddd;
    }
    .grid-schedule {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }
    .day-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        border-top: 5px solid var(--primary-orange);
        transition: transform 0.2s;
    }
    .day-card:hover { transform: translateY(-5px); }
    .day-card.today { border-top-color: var(--primary-green); background: #f0fff4; }
    
    .day-header {
        padding: 15px;
        background: #fafafa;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .day-name { font-weight: bold; font-size: 1.1em; color: var(--text-dark); }
    .day-date { color: var(--text-light); font-size: 0.9em; }
    
    .slot-info { padding: 15px; }
    .poste-tag {
        display: inline-block;
        background: #e3f2fd;
        color: #1976d2;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.85em;
        font-weight: 600;
        margin-bottom: 10px;
    }
    .time-row {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 1.2em;
        font-weight: bold;
        color: #2c3e50;
    }
    .duration-badge {
        margin-top: 10px;
        font-size: 0.9em;
        color: #666;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .no-data {
        grid-column: 1 / -1;
        text-align: center;
        padding: 50px;
        background: white;
        border-radius: 10px;
        color: #999;
    }
    @media (max-width: 600px) {
        .grid-schedule { grid-template-columns: 1fr; }
    }
</style>

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