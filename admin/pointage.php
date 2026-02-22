<?php
// 1. AJOUT DU FUSEAU HORAIRE GMT+1 (Bénin/France)
date_default_timezone_set('Africa/Porto-Novo'); 

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';

// Vérifier que l'utilisateur n'est pas ADMIN
if ($_SESSION['role'] === 'ADMIN') {
    $_SESSION['flash_error'] = 'Les administrateurs consultent les pointages via la gestion.';
    header('Location: /nms-planning/admin/gestion_pointages.php');
    exit;
}

// Variables de contexte
$id_user = $_SESSION['id_user'] ?? null;
$today = date('Y-m-d');

// Synchroniser MySQL en GMT+1 pour cette session
if (isset($pdo)) {
    $pdo->exec("SET time_zone = '+01:00'");
}

// Vérifier utilisateur
if (!$id_user) {
    header('Location: /nms-planning/auth/login.php');
    exit;
}

// Récupérer infos utilisateur + site
$stmtUser = $pdo->prepare("
    SELECT u.nom, u.prenom, u.role, s.nom_site
    FROM users u
    LEFT JOIN sites s ON u.id_site = s.id_site
    WHERE u.id_user = ?
");
$stmtUser->execute([$id_user]);
$userInfo = $stmtUser->fetch();

$role = strtolower($userInfo['role'] ?? 'agent');
$nom = ucfirst($userInfo['nom'] ?? '');
$prenom = ucfirst($userInfo['prenom'] ?? '');
$site = ucfirst($userInfo['nom_site'] ?? 'Site inconnu');

// FONCTIONS DE FORMATAGE
function formatHeure($time) {
    return date('H\hi\m\i\n', strtotime($time));
}

function formatDateFr($date) {
    return date('d/m/Y', strtotime($date));
}

// AJUSTEMENT : FONCTION POUR LA DATE LONGUE
function formatDateLongue($date) {
    $dateObj = new DateTime($date);
    $jours = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
    $mois = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
    return $jours[$dateObj->format('w')] . ' ' . $dateObj->format('d') . ' ' . $mois[$dateObj->format('n')] . ' ' . $dateObj->format('Y');
}

/**
 * Notifie les superviseurs du site concerné et les administrateurs.
 */
function notify_supervisors_if_possible($pdo, $from_user, $message, $type)
{
    try {
        $stmtSite = $pdo->prepare("SELECT id_site FROM users WHERE id_user = ?");
        $stmtSite->execute([$from_user]);
        $agentSiteId = $stmtSite->fetchColumn();

        $sqlTargets = "SELECT id_user FROM users WHERE role = 'ADMIN' OR (role = 'SUPERVISEUR' AND id_site = ?)";
        $stmtTargets = $pdo->prepare($sqlTargets);
        $stmtTargets->execute([$agentSiteId]);
        $targets = $stmtTargets->fetchAll(PDO::FETCH_COLUMN);

        if (empty($targets)) return;

        $check = $pdo->query("SHOW TABLES LIKE 'notifications'")->fetchColumn();
        if ($check) {
            $cols = $pdo->query("SHOW COLUMNS FROM notifications")->fetchAll(PDO::FETCH_COLUMN);
            $colUser = in_array('id_user', $cols) ? 'id_user' : (in_array('user_id', $cols) ? 'user_id' : null);

            if ($colUser) {
                $fields = [$colUser];
                $placeholders = ['?'];
                $valuesTemplate = [];

                if (in_array('from_user', $cols)) { $fields[] = 'from_user'; $placeholders[] = '?'; $valuesTemplate[] = $from_user; }
                if (in_array('type', $cols)) { $fields[] = 'type'; $placeholders[] = '?'; $valuesTemplate[] = $type; }
                if (in_array('message', $cols)) { $fields[] = 'message'; $placeholders[] = '?'; $valuesTemplate[] = $message; }
                if (in_array('created_at', $cols)) { $fields[] = 'created_at'; $placeholders[] = '?'; $valuesTemplate[] = date('Y-m-d H:i:s'); }

                $sqlInsert = "INSERT INTO notifications (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
                $ins = $pdo->prepare($sqlInsert);
                foreach ($targets as $t) { $ins->execute(array_merge([$t], $valuesTemplate)); }
            }
        }
    } catch (Exception $e) { error_log('Erreur notification : ' . $e->getMessage()); }
}

// TRAITEMENT DU POINTAGE (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = 'Requête invalide.';
    } else {
        $action = $_POST['action'] ?? '';
        $heure_actuelle = date('H:i:s');

        if ($action === 'arrivee') {
            $stmt = $pdo->prepare('SELECT * FROM pointages WHERE id_user = ? AND date_pointage = ? AND type = "NORMAL" AND heure_arrivee IS NOT NULL');
            $stmt->execute([$id_user, $today]);
            if ($stmt->fetch()) {
                $_SESSION['flash_error'] = '⚠️ Vous avez déjà enregistré votre arrivée aujourd\'hui.';
            } else {
                $sql = "INSERT INTO pointages (id_user, date_pointage, heure_arrivee, type, id_site) VALUES (?, ?, ?, ?, ?)";
                $pdo->prepare($sql)->execute([$id_user, $today, $heure_actuelle, 'NORMAL', $_SESSION['id_site'] ?? NULL]);
                $_SESSION['flash_success'] = '✓ Arrivée enregistrée à ' . substr($heure_actuelle, 0, 5);
                notify_supervisors_if_possible($pdo, $id_user, "$role $nom $prenom est arrivé sur le site de $site le " . formatDateFr($today) . " à " . date('H:i'), 'arrivee');
            }
        } elseif ($action === 'depart') {
            $stmt = $pdo->prepare('SELECT * FROM pointages WHERE id_user = ? AND date_pointage = ? AND type = "NORMAL" AND heure_arrivee IS NOT NULL AND heure_depart IS NULL');
            $stmt->execute([$id_user, $today]);
            $arrivee = $stmt->fetch();
            if (!$arrivee) {
                $_SESSION['flash_error'] = '❌ Aucun pointage d\'arrivée trouvé.';
            } else {
                $sql = "UPDATE pointages SET heure_depart = ? WHERE id_pointage = ?";
                $pdo->prepare($sql)->execute([$heure_actuelle, $arrivee['id_pointage']]);
                $_SESSION['flash_success'] = '✓ Départ enregistré.';
                notify_supervisors_if_possible($pdo, $id_user, "$role $nom $prenom a quitté le site de $site à " . date('H:i'), 'depart');
            }
        } elseif ($action === 'urgence') {
            $raison = trim($_POST['raison'] ?? '');
            if (!empty($raison)) {
                $motif = $raison . (!empty($_POST['commentaire']) ? " - " . trim($_POST['commentaire']) : "");
                $sql = "INSERT INTO pointages (id_user, date_pointage, type, motif_urgence, heure_arrivee, heure_depart, id_site) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $pdo->prepare($sql)->execute([$id_user, $today, 'URGENCE', $motif, $_POST['heure_arrivee'] ?: null, $_POST['heure_depart'] ?: null, $_SESSION['id_site'] ?? NULL]);
                $_SESSION['flash_success'] = '🚨 Urgence signalée.';
                notify_supervisors_if_possible($pdo, $id_user, "🚨 URGENCE : $nom $prenom ($motif)", 'urgence');
            }
        }
    }
    header('Location: /nms-planning/admin/pointage.php'); exit;
}

// RECUPERATION DONNEES
$stmt = $pdo->prepare('SELECT * FROM pointages WHERE id_user = ? AND date_pointage = ? ORDER BY created_at ASC');
$stmt->execute([$id_user, $today]);
$pointages_today = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pointage_normal = null;
foreach ($pointages_today as $p) { if ($p['type'] === 'NORMAL') $pointage_normal = $p; }
$heure_arrivee = $pointage_normal['heure_arrivee'] ?? null;
$heure_depart = $pointage_normal['heure_depart'] ?? null;

$stmtH = $pdo->prepare('SELECT * FROM pointages WHERE id_user = ? AND date_pointage >= DATE_SUB(?, INTERVAL 7 DAY) ORDER BY date_pointage DESC, created_at DESC');
$stmtH->execute([$id_user, $today]);
$historique = $stmtH->fetchAll(PDO::FETCH_ASSOC);

$urgence_types = ['Absence justifiée', 'Congé maladie', 'Congé personnel', 'Télétravail', 'Formation', 'Réunion externe', 'Autre'];
?>

<?php include_once __DIR__ . '/../includes/header.php'; ?>

<style>
    :root {
        --primary-green: #4CAF50;
        --dark-green: #2E7D32;
        --primary-orange: #FF9800;
        --dark-orange: #EF6C00;
        --danger: #FF5252;
        --bg-soft: #f8fafc;
    }
    body { background-color: var(--bg-soft); font-family: 'Plus Jakarta Sans', sans-serif; }
    .pointage-container { max-width: 800px; margin: 0 auto; padding: 20px; }
    .clock-card {
        background: white; border-radius: 20px; padding: 30px; text-align: center;
        box-shadow: 0 10px 25px rgba(0,0,0,0.05); margin-bottom: 25px; position: relative; overflow: hidden;
    }
    .clock-card::after {
        content: ''; position: absolute; bottom: 0; left: 0; width: 100%; height: 4px;
        background: linear-gradient(90deg, var(--primary-green), var(--primary-orange));
    }
    #heure-actuelle { font-size: 56px; font-weight: 800; color: #1e293b; letter-spacing: -2px; line-height: 1; }
    .status-badge { display: inline-block; padding: 6px 16px; border-radius: 50px; font-size: 12px; font-weight: 700; text-transform: uppercase; margin-top: 10px; }
    .status-waiting { background: #fee2e2; color: #ef4444; }
    .status-working { background: #dcfce7; color: #16a34a; animation: pulse 2s infinite; }
    .status-done { background: #f1f5f9; color: #64748b; }
    @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.6; } 100% { opacity: 1; } }
    .pointage-display { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px; }
    .time-tile { background: white; border-radius: 18px; padding: 20px; text-align: center; border: 1px solid #e2e8f0; transition: 0.3s; }
    .time-tile.active { border-color: var(--primary-green); box-shadow: 0 8px 20px rgba(74, 175, 80, 0.1); }
    .time-tile.orange { border-color: var(--primary-orange); }
    .tile-label { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 5px; display: block; }
    .tile-value { font-size: 24px; font-weight: 800; color: #1e293b; }
    .action-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 30px; }
    .btn-pointage {
        border: none; border-radius: 16px; padding: 20px; color: white; font-weight: 700;
        cursor: pointer; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        display: flex; flex-direction: column; align-items: center; gap: 10px; font-size: 15px;
    }
    .btn-arrivee { background: linear-gradient(135deg, #4CAF50, #2E7D32); box-shadow: 0 8px 20px rgba(76, 175, 80, 0.3); }
    .btn-depart { background: linear-gradient(135deg, #FF9800, #EF6C00); box-shadow: 0 8px 20px rgba(255, 152, 0, 0.3); }
    .btn-pointage:hover:not(:disabled) { transform: translateY(-5px); filter: brightness(1.1); }
    .btn-pointage:disabled { opacity: 0.4; cursor: not-allowed; filter: grayscale(1); }
    .urgency-section { background: #fff5f5; border-radius: 18px; border: 1px solid #fee2e2; padding: 20px; margin-bottom: 30px; }
    .urgency-title { color: #991b1b; display: flex; align-items: center; gap: 10px; font-weight: 800; font-size: 18px; margin-bottom: 15px; }
    .history-card { background: white; border-radius: 18px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
    .history-table { width: 100%; border-collapse: collapse; }
    .history-table th { text-align: left; padding: 12px; font-size: 12px; color: #94a3b8; border-bottom: 1px solid #f1f5f9; }
    .history-table td { padding: 12px; font-size: 14px; border-bottom: 1px solid #f1f5f9; }
</style>

<div class="pointage-container">
    <div class="clock-card">
        <div id="date-actuelle" style="color: #64748b; font-weight: 600; margin-bottom: 5px;">--/--/----</div>
        <div id="heure-actuelle">00:00:00</div>
        <?php if (!$heure_arrivee): ?>
            <span class="status-badge status-waiting">Hors service</span>
        <?php elseif ($heure_arrivee && !$heure_depart): ?>
            <span class="status-badge status-working">En poste sur site</span>
        <?php else: ?>
            <span class="status-badge status-done">Service terminé</span>
        <?php endif; ?>
    </div>

    <div class="pointage-display">
        <div class="time-tile <?= $heure_arrivee ? 'active' : '' ?>">
            <span class="tile-label">Entrée</span>
            <span class="tile-value"><?= $heure_arrivee ? substr($heure_arrivee, 0, 5) : '--:--' ?></span>
        </div>
        <div class="time-tile <?= $heure_depart ? 'orange' : '' ?>">
            <span class="tile-label">Sortie</span>
            <span class="tile-value"><?= $heure_depart ? substr($heure_depart, 0, 5) : '--:--' ?></span>
        </div>
    </div>

    <div class="action-grid">
        <form method="post" style="display: contents;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
            <input type="hidden" name="action" value="arrivee">
            <button class="btn-pointage btn-arrivee" type="submit" <?= $heure_arrivee ? 'disabled' : '' ?>>
                <span style="font-size: 24px;">📍</span> Pointer l'Arrivée
            </button>
        </form>
        <form method="post" style="display: contents;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
            <input type="hidden" name="action" value="depart">
            <button class="btn-pointage btn-depart" type="submit" <?= !$heure_arrivee || $heure_depart ? 'disabled' : '' ?>>
                <span style="font-size: 24px;">🚶</span> Pointer la Sortie
            </button>
        </form>
    </div>

    <div class="urgency-section">
        <div class="urgency-title">🚨 Signaler une Urgence</div>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
            <input type="hidden" name="action" value="urgence">
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                <div class="form-group">
                    <label style="font-size: 11px; font-weight: 700; color: #991b1b;">RAISON *</label>
                    <select name="raison" required style="width:100%; padding:10px; border-radius:10px; border:1px solid #fecaca;">
                        <option value="">Sélectionner...</option>
                        <?php foreach ($urgence_types as $type): ?>
                            <option value="<?= $type ?>"><?= $type ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label style="font-size: 11px; font-weight: 700; color: #991b1b;">HEURE DÉPART</label>
                    <input type="time" name="heure_depart" style="width:100%; padding:10px; border-radius:10px; border:1px solid #fecaca;">
                </div>
                <div class="form-group">
                    <label style="font-size: 11px; font-weight: 700; color: #991b1b;">HEURE ARRIVÉE</label>
                    <input type="time" name="heure_arrivee" style="width:100%; padding:10px; border-radius:10px; border:1px solid #fecaca;">
                </div>
            </div>
            <textarea name="commentaire" placeholder="Expliquez brièvement la situation..." style="width:100%; border-radius:10px; border:1px solid #fecaca; padding:10px; font-size:14px; margin-bottom:15px;"></textarea>
            <button type="submit" style="width:100%; background: #ef4444; color:white; border:none; padding:12px; border-radius:10px; font-weight:800; cursor:pointer;">Envoyer l'alerte</button>
        </form>
    </div>

    <div class="history-card">
        <h3 style="margin-bottom: 15px; font-size: 16px;">📊 Activité récente (7 jours)</h3>
        <div style="overflow-x: auto;">
            <table class="history-table">
                <thead>
                    <tr>
                        <th>DATE</th>
                        <th>TYPE / MOTIF</th>
                        <th>ENTRÉE</th>
                        <th>SORTIE</th>
                        <th>DURÉE</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historique as $h): ?>
                    <tr>
                        <td style="font-weight: 700; color: #1e293b;"><?= formatDateLongue($h['date_pointage']) ?></td>
                        
                        <td>
                            <?php if ($h['type'] === 'URGENCE'): ?>
                                <span style="background: #fff5f5; color: #ef4444; padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; border: 1px solid #fee2e2;">
                                    🚨 <?= htmlspecialchars($h['motif_urgence']) ?>
                                </span>
                            <?php else: ?>
                                <span style="background: #f0fdf4; color: #16a34a; padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; border: 1px solid #dcfce7;">
                                    ✅ NORMAL
                                </span>
                            <?php endif; ?>
                        </td>

                        <td><?= $h['heure_arrivee'] ? substr($h['heure_arrivee'], 0, 5) : '--' ?></td>
                        <td><?= $h['heure_depart'] ? substr($h['heure_depart'], 0, 5) : '--' ?></td>
                        <td>
                            <?php if($h['heure_arrivee'] && $h['heure_depart']): ?>
                                <span style="background: #f1f5f9; padding: 2px 8px; border-radius: 4px; font-weight: 600;">
                                    <?php
                                        $d = new DateTime($h['heure_arrivee']);
                                        $f = new DateTime($h['heure_depart']);
                                        echo $f->diff($d)->format('%hh %im');
                                    ?>
                                </span>
                            <?php else: ?> -- <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function updateClock() {
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        // AJUSTEMENT 3 : HEURE LOCALE GMT+1
        document.getElementById('heure-actuelle').textContent = now.toLocaleTimeString('fr-FR');
        document.getElementById('date-actuelle').textContent = now.toLocaleDateString('fr-FR', options);
    }
    setInterval(updateClock, 1000);
    updateClock();
</script>
<?php include_once __DIR__ . '/../includes/footer.php'; ?>