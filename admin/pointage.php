<?php
// 1. CONFIGURATION DU FUSEAU HORAIRE
date_default_timezone_set('Africa/Porto-Novo'); 

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';

// Sécurité : Pas d'admin ici
if ($_SESSION['role'] === 'ADMIN') {
    header('Location: /admin/gestion_pointages.php');
    exit;
}

$id_user = $_SESSION['id_user'];
$today = date('Y-m-d');

// 2. RÉCUPÉRATION DES INFOS UTILISATEUR ET DU SITE
$stmtUser = $pdo->prepare("
    SELECT u.nom, u.prenom, u.role, u.id_site, s.nom_site, s.latitude, s.longitude, s.heure_debut_service
    FROM users u
    LEFT JOIN sites s ON u.id_site = s.id_site
    WHERE u.id_user = ?
");
$stmtUser->execute([$id_user]);
$userInfo = $stmtUser->fetch();

$nom = ucfirst($userInfo['nom'] ?? '');
$prenom = ucfirst($userInfo['prenom'] ?? '');
$site = ucfirst($userInfo['nom_site'] ?? 'Site inconnu');

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
        $stmtSite = $pdo->prepare("SELECT id_site FROM users WHERE id_user = ?");
        $stmtSite->execute([$from_user]);
        $agentSiteId = $stmtSite->fetchColumn();

        $sqlTargets = "SELECT id_user FROM users WHERE role = 'ADMIN' OR (role = 'SUPERVISEUR' AND id_site = ?)";
        $stmtTargets = $pdo->prepare($sqlTargets);
        $stmtTargets->execute([$agentSiteId]);
        $targets = $stmtTargets->fetchAll(PDO::FETCH_COLUMN);

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
                if ($dist > 150000) { // 150km de test
                    $_SESSION['flash_error'] = "❌ Trop loin du site (".round($dist)."m).";
                    header('Location: pointage.php'); exit;
                }
            }

            $stmtCheck = $pdo->prepare('SELECT id_pointage FROM pointages WHERE id_user = ? AND date_pointage = ? AND type = "NORMAL"');
            $stmtCheck->execute([$id_user, $today]);
            if ($stmtCheck->fetch()) {
                $_SESSION['flash_error'] = '⚠️ Arrivée déjà enregistrée.';
            } else {
                $est_en_retard = (!empty($userInfo['heure_debut_service']) && strtotime($heure_actuelle) > strtotime($userInfo['heure_debut_service'])) ? 1 : 0;
                $sql = "INSERT INTO pointages (id_user, date_pointage, heure_arrivee, type, id_site, est_en_retard) VALUES (?, ?, ?, 'NORMAL', ?, ?)";
                $pdo->prepare($sql)->execute([$id_user, $today, $heure_actuelle, $userInfo['id_site'], $est_en_retard]);
                $_SESSION['flash_success'] = "✓ Arrivée enregistrée " . ($est_en_retard ? "(Retard)" : "");
                notify_supervisors_if_possible($pdo, $id_user, "$nom $prenom est arrivé" . ($est_en_retard ? " avec RETARD" : ""), 'arrivee');
            }
        } 
        elseif ($action === 'depart') {
            $stmt = $pdo->prepare('SELECT id_pointage FROM pointages WHERE id_user = ? AND date_pointage = ? AND type = "NORMAL" AND heure_depart IS NULL');
            $stmt->execute([$id_user, $today]);
            $pointage = $stmt->fetch();
            if ($pointage) {
                $pdo->prepare("UPDATE pointages SET heure_depart = ? WHERE id_pointage = ?")->execute([$heure_actuelle, $pointage['id_pointage']]);
                $_SESSION['flash_success'] = "✓ Départ enregistré.";
                notify_supervisors_if_possible($pdo, $id_user, "$nom $prenom a quitté le site.", 'depart');
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

                    if($is_longue) {
                        $msg_notif = "📅 LONGUE ABSENCE : $nom $prenom du $date_debut au $date_fin ($raison)";
                    } else {
                        $msg_notif = "🕒 ABSENCE COURTE : $nom $prenom le $date_debut [Sortie: $h_dep | Retour: $h_arr] - $raison";
                    }
                    notify_supervisors_if_possible($pdo, $id_user, $msg_notif, 'urgence');
                    $_SESSION['flash_success'] = "🚀 Absence enregistrée.";
                } catch (Exception $e) { $pdo->rollBack(); $_SESSION['flash_error'] = "Erreur technique."; }
            }
        }
        // --- AJOUT : TRAITEMENT DE LA JUSTIFICATION ---
        elseif ($action === 'justifier_auto') {
            $justif = trim($_POST['justification'] ?? '');
            $date_cible = $_POST['date_concernee'] ?? $today;
            if (!empty($justif)) {
                $sql = "UPDATE pointages 
                        SET commentaire = CONCAT('JUSTIFIÉ : ', ?), type = 'URGENCE' 
                        WHERE id_user = ? AND date_pointage = ? AND motif_urgence = 'ABSENCE AUTOMATIQUE'";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$justif, $id_user, $date_cible]);
                notify_supervisors_if_possible($pdo, $id_user, "Justification reçue de $nom $prenom : $justif", 'info');
                $_SESSION['flash_success'] = "✓ Justification transmise.";
                header('Location: pointage.php'); exit;
            }
        }
        // --- FIN AJOUT ---
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

// --- AJOUT : RÉCUPÉRATION DES ALERTES ---
$stmtNotif = $pdo->prepare("SELECT id_notification, message, created_at FROM notifications WHERE id_user = ? ORDER BY created_at DESC LIMIT 3");
$stmtNotif->execute([$id_user]);
$mes_notifs = $stmtNotif->fetchAll();
// --- FIN AJOUT ---
?>

<?php include_once __DIR__ . '/../includes/header.php'; ?>

<style>
    :root { --primary-green: #4CAF50; --primary-orange: #FF9800; --danger: #ef4444; --bg-soft: #f8fafc; }
    body { background-color: var(--bg-soft); font-family: 'Plus Jakarta Sans', sans-serif; }
    .pointage-container { max-width: 850px; margin: 0 auto; padding: 20px; }
    .clock-card { background: white; border-radius: 20px; padding: 30px; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.05); margin-bottom: 25px; position: relative; overflow: hidden; }
    .clock-card::after { content: ''; position: absolute; bottom: 0; left: 0; width: 100%; height: 4px; background: linear-gradient(90deg, var(--primary-green), var(--primary-orange)); }
    #heure-actuelle { font-size: 56px; font-weight: 800; color: #1e293b; letter-spacing: -2px; }
    .status-badge { display: inline-block; padding: 6px 16px; border-radius: 50px; font-size: 12px; font-weight: 700; text-transform: uppercase; margin-top: 10px; }
    .status-waiting { background: #fee2e2; color: #ef4444; }
    .status-working { background: #dcfce7; color: #16a34a; }
    .pointage-display { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px; }
    .time-tile { background: white; border-radius: 18px; padding: 20px; text-align: center; border: 1px solid #e2e8f0; }
    .time-tile.active { border-color: var(--primary-green); box-shadow: 0 8px 20px rgba(74, 175, 80, 0.1); }
    .action-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 30px; }
    .btn-pointage { border: none; border-radius: 16px; padding: 20px; color: white; font-weight: 700; cursor: pointer; transition: 0.3s; display: flex; flex-direction: column; align-items: center; gap: 10px; }
    .btn-arrivee { background: linear-gradient(135deg, #4CAF50, #2E7D32); }
    .btn-depart { background: linear-gradient(135deg, #FF9800, #EF6C00); }
    .btn-pointage:disabled { opacity: 0.4; cursor: not-allowed; filter: grayscale(1); }
    .history-card { background: white; border-radius: 18px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
    .history-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .history-table th { text-align: left; padding: 12px; font-size: 11px; color: #94a3b8; text-transform: uppercase; border-bottom: 1px solid #f1f5f9; }
    .history-table td { padding: 15px 12px; font-size: 14px; border-bottom: 1px solid #f1f5f9; }
</style>

<div class="pointage-container">
    <?php if (isset($_SESSION['flash_success'])): ?>
        <div style="background:#dcfce7; color:#16a34a; padding:15px; border-radius:12px; margin-bottom:20px; font-weight:600;"><?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['flash_error'])): ?>
        <div style="background:#fee2e2; color:#ef4444; padding:15px; border-radius:12px; margin-bottom:20px; font-weight:600;"><?= $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?></div>
    <?php endif; ?>
    <?php foreach ($mes_notifs as $n): ?>
        <div style="background: #fff5f5; border-left: 5px solid #ef4444; padding: 15px; border-radius: 12px; margin-bottom: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
            <div style="display: flex; justify-content: space-between;">
                <strong style="color: #ef4444; font-size: 11px; text-transform: uppercase;">🚨 Absence Automatique</strong>
                <span style="font-size: 10px; color: #94a3b8;"><?= date('H:i', strtotime($n['created_at'])) ?></span>
            </div>
            <p style="margin: 5px 0; font-size: 13px; color: #1e293b;"><?= htmlspecialchars($n['message']) ?></p>
            
            <form method="post" style="margin-top: 10px; display: flex; gap: 8px;">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="action" value="justifier_auto">
                <input type="hidden" name="date_concernee" value="<?= date('Y-m-d', strtotime($n['created_at'])) ?>">
                <input type="text" name="justification" placeholder="Justifiez votre absence ici..." required 
                       style="flex: 1; padding: 8px; border: 1px solid #fecaca; border-radius: 8px; font-size: 12px;">
                <button type="submit" style="background: #ef4444; color: white; border: none; padding: 8px 15px; border-radius: 8px; cursor: pointer; font-weight: 700; font-size: 11px;">Envoyer</button>
            </form>
        </div>
    <?php endforeach; ?>

    <div class="clock-card">
        <div id="date-actuelle" style="color: #64748b; font-weight: 600;">--/--/----</div>
        <div id="heure-actuelle">00:00:00</div>
        <span class="status-badge status-waiting" id="geo-status">Localisation...</span>
    </div>

    <div class="pointage-display">
        <div class="time-tile <?= $heure_arrivee ? 'active' : '' ?>">
            <span style="font-size:11px; color:#94a3b8; font-weight:700;">ENTRÉE</span><br>
            <span style="font-size:24px; font-weight:800;"><?= $heure_arrivee ? substr($heure_arrivee, 0, 5) : '--:--' ?></span>
        </div>
        <div class="time-tile <?= $heure_depart ? 'active' : '' ?>">
            <span style="font-size:11px; color:#94a3b8; font-weight:700;">SORTIE</span><br>
            <span style="font-size:24px; font-weight:800;"><?= $heure_depart ? substr($heure_depart, 0, 5) : '--:--' ?></span>
        </div>
    </div>

    <div class="action-grid">
        <form method="post" style="display:contents;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
            <input type="hidden" name="action" value="arrivee">
            <input type="hidden" name="user_lat" class="lat_input"><input type="hidden" name="user_lng" class="lng_input">
            <button class="btn-pointage btn-arrivee" type="submit" disabled id="btn-arrivee">📍 Arrivée</button>
        </form>
        <form method="post" style="display:contents;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
            <input type="hidden" name="action" value="depart">
            <button class="btn-pointage btn-depart" type="submit" <?= !$heure_arrivee || $heure_depart ? 'disabled' : '' ?>>🚶 Sortie</button>
        </form>
    </div>

    <div style="background: #fff5f5; border: 1px solid #fecaca; border-radius: 20px; padding: 25px; margin-bottom: 30px;">
        <h3 style="color: #991b1b; margin-bottom: 20px; font-size: 16px;">🚨 Signaler une Absence / Sortie</h3>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
            <input type="hidden" name="action" value="urgence">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div class="form-group">
                    <label style="font-size:11px; font-weight:700; color:#991b1b;">DU (DÉBUT)</label>
                    <input type="date" name="date_debut" id="date_debut" value="<?= $today ?>" required style="width:100%; padding:12px; border-radius:10px; border:1px solid #fecaca;">
                </div>
                <div class="form-group">
                    <label style="font-size:11px; font-weight:700; color:#991b1b;">AU (FIN)</label>
                    <input type="date" name="date_fin" id="date_fin" value="<?= $today ?>" style="width:100%; padding:12px; border-radius:10px; border:1px solid #fecaca;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div class="form-group">
                    <label style="font-size:11px; font-weight:700; color:#991b1b;">RAISON</label>
                    <select name="raison" required style="width:100%; padding:12px; border-radius:10px; border:1px solid #fecaca;">
                        <option value="">Motif...</option>
                        <?php foreach ($urgence_types as $t): ?><option value="<?= $t ?>"><?= $t ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label style="font-size:11px; font-weight:700; color:#991b1b;">H. SORTIE</label>
                    <input type="time" name="heure_depart" value="<?= date('H:i') ?>" style="width:100%; padding:12px; border-radius:10px; border:1px solid #fecaca;">
                </div>
                <div class="form-group">
                    <label style="font-size:11px; font-weight:700; color:#991b1b;">H. RETOUR</label>
                    <input type="time" name="heure_arrivee" placeholder="Retour" style="width:100%; padding:12px; border-radius:10px; border:1px solid #fecaca;">
                </div>
             </div>
            <textarea name="commentaire" placeholder="Plus de détails..." style="width:100%; padding:12px; border-radius:10px; border:1px solid #fecaca; min-height:60px; margin-bottom:15px;"></textarea>
            <button type="submit" style="width:100%; background:#ef4444; color:white; border:none; padding:15px; border-radius:12px; font-weight:800; cursor:pointer;">💾 Enregistrer</button>
        </form>
    </div>

    <div class="history-card">
        <h3 style="font-size:16px;">📊 Activité récente (7 jours)</h3>
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
                                <span style="color:#2563eb;">Du <?= date('d/m', strtotime($g_start)) ?> Au <?= date('d/m', strtotime($g_end)) ?></span>
                            <?php else: ?>
                                <?= formatDateLongue($h['date_pointage']) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($h['type'] === 'URGENCE'): ?>
                                <span style="background:#fff5f5; color:#ef4444; padding:4px 8px; border-radius:6px; font-size:11px; font-weight:700;">🚨 <?= htmlspecialchars($h['motif_urgence']) ?></span>
                            <?php else: ?>
                                <span style="background:#f0fdf4; color:#16a34a; padding:4px 8px; border-radius:6px; font-size:11px; font-weight:700;">✅ NORMAL</span>
                                <?= $h['est_en_retard'] ? '<span style="color:#ea580c; font-size:10px; margin-left:5px;">⚠️ RETARD</span>' : '' ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$is_grouped): ?>
                                <?= ($h['heure_depart'] ? substr($h['heure_depart'],0,5) : '--') . ' / ' . ($h['heure_arrivee'] ? substr($h['heure_arrivee'],0,5) : '--') ?>
                            <?php else: ?> -- / -- <?php endif; ?>
                        </td>
                        <td style="font-weight:800; color:#475569;">
                            <?php 
                                if ($is_grouped) echo "<span style='color:#2563eb;'>$g_jours Jours</span>";
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

<script>
    const siteLat = <?= $userInfo['latitude'] ?? 0 ?>;
    const siteLng = <?= $userInfo['longitude'] ?? 0 ?>;
    const hasArrived = <?= $heure_arrivee ? 'true' : 'false' ?>;

    function calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371e3;
        const p1 = lat1 * Math.PI/180; const p2 = lat2 * Math.PI/180;
        const dp = (lat2-lat1) * Math.PI/180; const dl = (lon2-lon1) * Math.PI/180;
        const a = Math.sin(dp/2)**2 + Math.cos(p1) * Math.cos(p2) * Math.sin(dl/2)**2;
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    }

    // GPS & Heure
    function init() {
        setInterval(() => {
            const now = new Date();
            document.getElementById('heure-actuelle').textContent = now.toLocaleTimeString('fr-FR');
            document.getElementById('date-actuelle').textContent = now.toLocaleDateString('fr-FR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        }, 1000);

        if (navigator.geolocation) {
            navigator.geolocation.watchPosition(pos => {
                const uLat = pos.coords.latitude; const uLng = pos.coords.longitude;
                document.querySelectorAll('.lat_input').forEach(i => i.value = uLat);
                document.querySelectorAll('.lng_input').forEach(i => i.value = uLng);
                
                const dist = calculateDistance(uLat, uLng, siteLat, siteLng);
                const badge = document.getElementById('geo-status');
                if (dist < 150000) { // 150m
                    badge.className = "status-badge status-working"; badge.textContent = "📍 Sur site";
                    if(!hasArrived) document.getElementById('btn-arrivee').disabled = false;
                } else {
                    badge.className = "status-badge status-waiting"; badge.textContent = "🚶 Trop loin";
                }
            });
        }
    }

    document.getElementById('date_debut').addEventListener('change', function() {
        document.getElementById('date_fin').value = this.value;
    });

    init();
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>