<?php
// 1. CONFIGURATION DU FUSEAU HORAIRE
date_default_timezone_set('Africa/Porto-Novo'); 

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/superviseur_sites.php';

// Sécurité : Pas d'admin ici
if ($_SESSION['role'] === 'ADMIN') {
    header('Location: /admin/gestion_pointages.php');
    exit;
}

function format_retard_duration($minutes) {
    $minutes = max(0, (int) $minutes);
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    if ($h > 0 && $m > 0) return $h . "h " . $m . "min";
    if ($h > 0) return $h . "h";
    return $m . "min";
}

$id_user = $_SESSION['id_user'];
$today = date('Y-m-d');

// 2. RÉCUPÉRATION DES INFOS UTILISATEUR ET DU SITE
$session_site_id = (int)($_SESSION['id_site'] ?? 0);
if (($_SESSION['role'] ?? '') === 'SUPERVISEUR' && $session_site_id > 0) {
    $stmtUser = $pdo->prepare("
        SELECT u.nom, u.prenom, u.role, ? AS id_site, s.nom_site, s.latitude, s.longitude, s.heure_debut_service
        FROM users u
        LEFT JOIN sites s ON s.id_site = ?
        WHERE u.id_user = ?
    ");
    $stmtUser->execute([$session_site_id, $session_site_id, $id_user]);
} else {
    $stmtUser = $pdo->prepare("
        SELECT u.nom, u.prenom, u.role, u.id_site, s.nom_site, s.latitude, s.longitude, s.heure_debut_service
        FROM users u
        LEFT JOIN sites s ON u.id_site = s.id_site
        WHERE u.id_user = ?
    ");
    $stmtUser->execute([$id_user]);
}
$userInfo = $stmtUser->fetch();

$nom = ucfirst($userInfo['nom'] ?? '');
$prenom = ucfirst($userInfo['prenom'] ?? '');
$site = ucfirst($userInfo['nom_site'] ?? 'Site inconnu');
$role_label = (strtoupper($userInfo['role'] ?? '') === 'SUPERVISEUR') ? 'Le superviseur' : "L'agent";

// FONCTIONS UTILES
function getDistance($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    return $earth_radius * 2 * atan2(sqrt($a), sqrt(1-$a));
}

function formatDateLongue($date) {
    $dateObj = new DateTime($date);
    $jours = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
    $mois = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
    return $jours[$dateObj->format('w')] . ' ' . $dateObj->format('d') . ' ' . $mois[$dateObj->format('n')] . ' ' . $dateObj->format('Y');
}

function notify_supervisors_if_possible($pdo, $from_user, $message, $type) {
    try {
        $agentSiteId = null;
        if (($_SESSION['role'] ?? '') === 'SUPERVISEUR' && !empty($_SESSION['id_site'])) {
            $agentSiteId = (int) $_SESSION['id_site'];
        } else {
            $stmtSite = $pdo->prepare("SELECT id_site FROM users WHERE id_user = ?");
            $stmtSite->execute([$from_user]);
            $agentSiteId = $stmtSite->fetchColumn();
        }

        $targets = [];
        if ($agentSiteId) {
            $targets = get_supervisor_ids_for_site($pdo, (int) $agentSiteId);
        }

        $stmtAdmins = $pdo->query("SELECT id_user FROM users WHERE role = 'ADMIN'");
        $admin_ids = $stmtAdmins->fetchAll(PDO::FETCH_COLUMN);

        $targets = array_unique(array_merge($targets, $admin_ids));

        $sqlInsert = "INSERT INTO notifications (id_user, from_user, type, message, created_at) VALUES (?, ?, ?, ?, ?)";
        $ins = $pdo->prepare($sqlInsert);
        foreach ($targets as $t) { 
            $ins->execute([$t, $from_user, $type, $message, date('Y-m-d H:i:s')]); 
        }
    } catch (Exception $e) { error_log('Erreur notification : ' . $e->getMessage()); }
}

// 3. TRAITEMENT DES ACTIONS (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = 'Requête invalide.';
    } else {
        $action = $_POST['action'] ?? '';
        $heure_actuelle = date('H:i:s');

        if ($action === 'arrivee') {
            $u_lat = $_POST['user_lat'] ?? 0;
            $u_lng = $_POST['user_lng'] ?? 0;
            if ($userInfo['latitude'] != 0) {
                $dist = getDistance($u_lat, $u_lng, $userInfo['latitude'], $userInfo['longitude']);
                if ($dist > 200000) { // 200km de test
                    $_SESSION['flash_error'] = "Trop loin du site (".round($dist)."m).";
                    header('Location: pointage.php'); exit;
                }
            }

            $stmtCheck = $pdo->prepare('SELECT id_pointage FROM pointages WHERE id_user = ? AND date_pointage = ? AND type = "NORMAL"');
            $stmtCheck->execute([$id_user, $today]);
            if ($stmtCheck->fetch()) {
                $_SESSION['flash_error'] = 'Arrivée déjà enregistrée.';
            } else {
                $est_en_retard = (!empty($userInfo['heure_debut_service']) && strtotime($heure_actuelle) > strtotime($userInfo['heure_debut_service'])) ? 1 : 0;
                $sql = "INSERT INTO pointages (id_user, date_pointage, heure_arrivee, type, id_site, est_en_retard) VALUES (?, ?, ?, 'NORMAL', ?, ?)";
                $pdo->prepare($sql)->execute([$id_user, $today, $heure_actuelle, $userInfo['id_site'], $est_en_retard]);
                $_SESSION['flash_success'] = "Arrivée enregistrée " . ($est_en_retard ? "(Retard)" : "");
                $msg = $role_label . " " . $nom . " " . $prenom . " est arrive sur le site " . $site . " a " . $heure_actuelle;
                if ($est_en_retard && !empty($userInfo['heure_debut_service'])) {
                    $d1 = new DateTime($userInfo['heure_debut_service']);
                    $d2 = new DateTime($heure_actuelle);
                    $diff = $d1->diff($d2);
                    $retard_minutes = ($diff->h * 60) + $diff->i;
                    $msg .= " et est en retard de " . format_retard_duration($retard_minutes);
                }
                notify_supervisors_if_possible($pdo, $id_user, $msg, 'arrivee');
            }
        } 
        elseif ($action === 'depart') {
            $stmt = $pdo->prepare('SELECT id_pointage FROM pointages WHERE id_user = ? AND date_pointage = ? AND type = "NORMAL" AND heure_depart IS NULL');
            $stmt->execute([$id_user, $today]);
            $pointage = $stmt->fetch();
            if ($pointage) {
                $pdo->prepare("UPDATE pointages SET heure_depart = ? WHERE id_pointage = ?")->execute([$heure_actuelle, $pointage['id_pointage']]);
                $_SESSION['flash_success'] = "Départ enregistré.";
                $msg = $role_label . " " . $nom . " " . $prenom . " a quitte le site de " . $site . " a " . $heure_actuelle;
                notify_supervisors_if_possible($pdo, $id_user, $msg, 'depart');
            }
        } 
        elseif ($action === 'urgence') {
            $raison = trim($_POST['raison'] ?? '');
            $date_debut = $_POST['date_debut'] ?? $today;
            $date_fin = $_POST['date_fin'] ?? $date_debut;
            $h_dep = !empty($_POST['heure_depart']) ? $_POST['heure_depart'] : null;
            $h_arr = !empty($_POST['heure_arrivee']) ? $_POST['heure_arrivee'] : null;

            if (!empty($raison)) {
                $is_longue = ($date_debut !== $date_fin);
                $motif = $raison . (!empty($_POST['commentaire']) ? " - " . trim($_POST['commentaire']) : "");
                
                try {
                    $pdo->beginTransaction();
                    $start = new DateTime($date_debut);
                    $end = new DateTime($date_fin);
                    $interval = new DateInterval('P1D');
                    $period = new DatePeriod($start, $interval, $end->modify('+1 day'));
                    
                    $nb_jours = 0;
                    foreach($period as $d) $nb_jours++;

                    $stmt = $pdo->prepare("INSERT INTO pointages (id_user, date_pointage, type, motif_urgence, heure_depart, heure_arrivee, id_site, commentaire) VALUES (?, ?, 'URGENCE', ?, ?, ?, ?, ?)");
                    
                    foreach ($period as $date) {
                        $info_groupe = $is_longue ? "GROUPED:{$date_debut}:{$date_fin}:{$nb_jours}" : "SINGLE";
                        $stmt->execute([$id_user, $date->format('Y-m-d'), $motif, $h_dep, $h_arr, $userInfo['id_site'], $info_groupe]);
                    }
                    $pdo->commit();

                    if ($is_longue) {
                        $msg_notif = $role_label . " " . $nom . " " . $prenom . " sera absent pour duree " . $nb_jours . " jours, raison : " . $raison;
                    } else {
                        $msg_notif = $role_label . " " . $nom . " " . $prenom . " a demande une permission ; Raison : " . $raison;
                    }
                    notify_supervisors_if_possible($pdo, $id_user, $msg_notif, 'urgence');
                    $_SESSION['flash_success'] = "Absence enregistrée.";
                } catch (Exception $e) { $pdo->rollBack(); $_SESSION['flash_error'] = "Erreur technique."; }
            }
        }
    }
    header('Location: pointage.php'); exit;
}

// 4. RÉCUPÉRATION HISTORIQUE & ÉTAT ACTUEL
$stmtH = $pdo->prepare('SELECT * FROM pointages WHERE id_user = ? AND date_pointage >= DATE_SUB(?, INTERVAL 7 DAY) ORDER BY date_pointage DESC, created_at DESC');
$stmtH->execute([$id_user, $today]);
$historique = $stmtH->fetchAll(PDO::FETCH_ASSOC);

$heure_arrivee = null; $heure_depart = null;
foreach ($historique as $p) {
    if ($p['date_pointage'] == $today && $p['type'] == 'NORMAL') {
        $heure_arrivee = $p['heure_arrivee'];
        $heure_depart = $p['heure_depart'];
    }
}
$urgence_types = ['Absence justifiée', 'Congé maladie', 'Congé personnel', 'Télétravail', 'Formation', 'Réunion externe', 'Autre'];
?>

<?php include_once __DIR__ . '/../includes/header.php'; ?>


<div class="pointage-container">
    <div id="pointage-config" data-site-lat="<?= htmlspecialchars($userInfo['latitude'] ?? 0, ENT_QUOTES, 'UTF-8') ?>" data-site-lng="<?= htmlspecialchars($userInfo['longitude'] ?? 0, ENT_QUOTES, 'UTF-8') ?>" data-has-arrived="<?= $heure_arrivee ? '1' : '0' ?>" style="display:none;"></div>
    <div class="clock-card">
        <div id="date-actuelle" style="color: var(--brand-muted); font-weight: 600;">--/--/----</div>
        <div id="heure-actuelle">00:00:00</div>
        <span class="status-badge status-waiting" id="geo-status">Localisation...</span>
    </div>

    <div class="pointage-display">
        <div class="time-tile <?= $heure_arrivee ? 'active' : '' ?>">
            <span style="font-size:11px; color:var(--brand-muted); font-weight:700;">ENTRÉE</span><br>
            <span style="font-size:24px; font-weight:800;"><?= $heure_arrivee ? substr($heure_arrivee, 0, 5) : '--:--' ?></span>
        </div>
        <div class="time-tile <?= $heure_depart ? 'active' : '' ?>">
            <span style="font-size:11px; color:var(--brand-muted); font-weight:700;">SORTIE</span><br>
            <span style="font-size:24px; font-weight:800;"><?= $heure_depart ? substr($heure_depart, 0, 5) : '--:--' ?></span>
        </div>
    </div>

    <div class="action-grid">
        <form method="post" style="display:contents;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
            <input type="hidden" name="action" value="arrivee">
            <input type="hidden" name="user_lat" class="lat_input"><input type="hidden" name="user_lng" class="lng_input">
            <button class="btn-pointage btn-arrivee" type="submit" disabled id="btn-arrivee"><i class="bi bi-box-arrow-in-right"></i> Arrivée</button>
        </form>
        <form method="post" style="display:contents;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
            <input type="hidden" name="action" value="depart">
            <button class="btn-pointage btn-depart" type="submit" <?= !$heure_arrivee || $heure_depart ? 'disabled' : '' ?>><i class="bi bi-box-arrow-right"></i> Sortie</button>
        </form>
    </div>

    <div style="background: var(--brand-orange-soft); border: 1px solid var(--brand-orange-border); border-radius: 20px; padding: 25px; margin-bottom: 30px;">
        <h3 style="color: var(--brand-orange-dark); margin-bottom: 20px; font-size: 16px;"><i class="bi bi-exclamation-diamond"></i> Signaler une Absence / Sortie</h3>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
            <input type="hidden" name="action" value="urgence">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div class="form-group">
                    <label style="font-size:11px; font-weight:700; color:var(--brand-orange-dark);">DU (DÉBUT)</label>
                    <input type="date" name="date_debut" id="date_debut" value="<?= $today ?>" required style="width:100%; padding:12px; border-radius:10px; border:1px solid var(--brand-orange-border);">
                </div>
                <div class="form-group">
                    <label style="font-size:11px; font-weight:700; color:var(--brand-orange-dark);">AU (FIN)</label>
                    <input type="date" name="date_fin" id="date_fin" value="<?= $today ?>" style="width:100%; padding:12px; border-radius:10px; border:1px solid var(--brand-orange-border);">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div class="form-group">
                    <label style="font-size:11px; font-weight:700; color:var(--brand-orange-dark);">RAISON</label>
                    <select name="raison" required style="width:100%; padding:12px; border-radius:10px; border:1px solid var(--brand-orange-border);">
                        <option value="">Motif...</option>
                        <?php foreach ($urgence_types as $t): ?><option value="<?= $t ?>"><?= $t ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label style="font-size:11px; font-weight:700; color:var(--brand-orange-dark);">H. SORTIE</label>
                    <input type="time" name="heure_depart" value="<?= date('H:i') ?>" style="width:100%; padding:12px; border-radius:10px; border:1px solid var(--brand-orange-border);">
                </div>
                <div class="form-group">
                    <label style="font-size:11px; font-weight:700; color:var(--brand-orange-dark);">H. RETOUR</label>
                    <input type="time" name="heure_arrivee" placeholder="Retour" style="width:100%; padding:12px; border-radius:10px; border:1px solid var(--brand-orange-border);">
                </div>
             </div>
            <textarea name="commentaire" placeholder="Plus de détails..." style="width:100%; padding:12px; border-radius:10px; border:1px solid var(--brand-orange-border); min-height:60px; margin-bottom:15px;"></textarea>
            <button type="submit" style="width:100%; background:var(--brand-orange); color:white; border:none; padding:15px; border-radius:12px; font-weight:800; cursor:pointer;"><i class="bi bi-check-circle"></i> Enregistrer</button>
        </form>
    </div>

    <div class="history-card">
        <h3 style="font-size:16px;"><i class="bi bi-bar-chart"></i> Activité récente (7 jours)</h3>
        <div style="overflow-x:auto;">
            <table class="history-table">
                <thead>
                    <tr><th>Date</th><th>Type / Motif</th><th>Sortie / Entrée</th><th>Durée</th></tr>
                </thead>
                <tbody>
                    <?php 
                    $displayed_groups = []; 
                    foreach ($historique as $h): 
                        $is_grouped = (strpos($h['commentaire'], 'GROUPED:') === 0);
                        if ($is_grouped) {
                            $parts = explode(':', $h['commentaire']);
                            $g_start = $parts[1]; $g_end = $parts[2]; $g_jours = $parts[3];
                            $key = $g_start . $g_end . $h['motif_urgence'];
                            if (in_array($key, $displayed_groups)) continue;
                            $displayed_groups[] = $key;
                        }
                    ?>
                    <tr>
                        <td style="font-weight:700;">
                            <?php if ($is_grouped): ?>
                                <span style="color:var(--brand-orange-dark);">Du <?= date('d/m', strtotime($g_start)) ?> Au <?= date('d/m', strtotime($g_end)) ?></span>
                            <?php else: ?>
                                <?= formatDateLongue($h['date_pointage']) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($h['type'] === 'URGENCE'): ?>
                                <span style="background:var(--brand-orange-soft); color:var(--brand-orange-dark); padding:4px 8px; border-radius:6px; font-size:11px; font-weight:700;"><i class="bi bi-exclamation-diamond"></i> <?= htmlspecialchars($h['motif_urgence']) ?></span>
                            <?php else: ?>
                                <span style="background:var(--brand-green-soft); color:var(--brand-green-dark); padding:4px 8px; border-radius:6px; font-size:11px; font-weight:700;"><i class="bi bi-check-circle"></i> NORMAL</span>
                                <?= $h['est_en_retard'] ? '<span style="color:var(--brand-orange-dark); font-size:10px; margin-left:5px;"><i class="bi bi-exclamation-triangle"></i> RETARD</span>' : '' ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$is_grouped): ?>
                                <?= ($h['heure_depart'] ? substr($h['heure_depart'],0,5) : '--') . ' / ' . ($h['heure_arrivee'] ? substr($h['heure_arrivee'],0,5) : '--') ?>
                            <?php else: ?> -- / -- <?php endif; ?>
                        </td>
                        <td style="font-weight:800; color:#475569;">
                            <?php 
                                if ($is_grouped) echo "<span style='color:var(--brand-orange-dark);'>$g_jours Jours</span>";
                                elseif ($h['heure_arrivee'] && $h['heure_depart']) {
                                    $d = new DateTime($h['heure_arrivee']); $f = new DateTime($h['heure_depart']);
                                    echo $f->diff($d)->format('%hh %im');
                                } else echo "--";
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
