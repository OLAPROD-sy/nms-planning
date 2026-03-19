<?php
// S'assurer que la session est dÃ©marrÃ©e
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// VÃ©rifier que l'utilisateur est connectÃ©
if (!isset($_SESSION['id_user'])) {
    header('Location: /auth/login.php');
    exit;
}

// Afficher les messages flash
$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$prenom = $_SESSION['prenom'] ?? 'Utilisateur';
$role = $_SESSION['role'] ?? 'AGENT';

// Chargement automatique du CSS de la page courante (assets/css/pages/{route}.css)
$scriptPath = trim(parse_url($_SERVER['SCRIPT_NAME'] ?? '', PHP_URL_PATH), '/');
$scriptFilename = basename($_SERVER['SCRIPT_FILENAME'] ?? '');
if ($scriptPath === '' && $scriptFilename !== '') {
    $scriptPath = $scriptFilename;
}
$pageCssHref = '';
if ($scriptPath !== '') {
    $pageCssPath = preg_replace('/\.php$/', '.css', $scriptPath);
    $pageCssFile = __DIR__ . '/../assets/css/pages/' . $pageCssPath;
    if (is_file($pageCssFile)) {
        $pageCssHref = '/assets/css/pages/' . $pageCssPath;
    }
}

$notifications_count = 0;
$recent_notifications = [];
try {
    if (!isset($pdo)) { require_once __DIR__ . '/../config/database.php'; }
    $res = $pdo->query("SHOW COLUMNS FROM notifications")->fetchAll(PDO::FETCH_COLUMN);
    $colUser = in_array('id_user', $res) ? 'id_user' : (in_array('user_id', $res) ? 'user_id' : null);

    if ($colUser) {
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE $colUser = ? AND is_read = 0");
        $countStmt->execute([$_SESSION['id_user']]);
        $notifications_count = (int) $countStmt->fetchColumn();

        $listStmt = $pdo->prepare("SELECT * FROM notifications WHERE $colUser = ? ORDER BY created_at DESC LIMIT 5");
        $listStmt->execute([$_SESSION['id_user']]);
        $recent_notifications = $listStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) { error_log($e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NMS Planning</title>
    <link rel="icon" type="image/svg+xml" href="/assets/images/favicon.svg">
    
    <link rel="alternate icon" href="/assets/images/logo_nms.png">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/layout/header.css">
    <?php if ($pageCssHref): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($pageCssHref, ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
    
</head>
<body>
    <header>
        <nav>
            <div class="nav-brand"><a href="/"> <img src="/assets/images/logo_nms.png" alt="NMS Planning"> </a></div>
            
            <div class="nav-center">
                <a href="/"><i class="bi bi-house-door nav-link-icon"></i><span>Accueil</span></a>
                <?php if ($role === 'ADMIN'): ?>
                    <a href="/admin/gestion_pointages.php"><i class="bi bi-geo-alt nav-link-icon"></i><span>Pointages</span></a>
                    <a href="/stock/gest_stock.php"><i class="bi bi-box-seam nav-link-icon"></i><span>Stock</span></a>
                <?php else: ?>
                    <a href="/admin/pointage.php"><i class="bi bi-geo-alt nav-link-icon"></i><span>Présence</span></a>
                <?php endif; ?>
            </div>

            <div class="nav-right-group">
                <div style="position: relative;">
                    <div class="bell-container" id="notifBell">
                        <i class="bi bi-bell-fill bell-icon"></i><?php if ($notifications_count > 0): ?><span class="notif-badge"><?= $notifications_count ?></span><?php endif; ?>
                    </div>
                    <div class="notif-dropdown" id="notifDropdown">
                        <div class="notif-header">Notifications</div>
                        <div class="notif-list">
                            <?php if(empty($recent_notifications)): ?>
                                <div class="notif-item">Aucune notification</div>
                            <?php else: ?>
                                <?php foreach ($recent_notifications as $notif): 
                                $type = strtolower($notif['type'] ?? '');
                                $is_unread = isset($notif['is_read']) && $notif['is_read'] == 0; // VÃ©rifie si non lu
                                $msg_raw = $notif['message'] ?? '';
                                $class = 'normal';
                                if (stripos($msg_raw, 'Nouvelle programmation') !== false) {
                                    $class = 'programmation';
                                } elseif (strpos($type, 'arrivee') !== false) $class = 'arrivee';
                                elseif (strpos($type, 'depart') !== false) $class = 'depart';
                                elseif (strpos($type, 'urgence') !== false) $class = 'urgence';
                                $type_label = 'Info';
                                $type_color = '#475569';
                                $type_bg = '#eef2f7';
                                if ($class === 'programmation') { $type_label = 'Programmation'; $type_color = '#b45309'; $type_bg = '#fff7ed'; }
                                elseif ($class === 'arrivee') { $type_label = 'Arrivee'; $type_color = '#166534'; $type_bg = '#e8f5e9'; }
                                elseif ($class === 'depart') { $type_label = 'Depart'; $type_color = '#1d4ed8'; $type_bg = '#e3f2fd'; }
                                elseif ($class === 'urgence') { $type_label = 'Urgence'; $type_color = '#b91c1c'; $type_bg = '#ffebee'; }
                                $role_tag = '';
                                if (stripos($msg_raw, "Le superviseur") === 0) {
                                    $role_tag = "Superviseur";
                                } elseif (stripos($msg_raw, "L'agent") === 0) {
                                    $role_tag = "Agent";
                                }
                            ?>
                                <a href="/admin/notifications.php" class="notif-item <?= $class ?> <?= $is_unread ? 'unread' : '' ?>">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 6px;">
                                        <div>
                                            <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap; margin-bottom:4px;">
                                                <span style="display:inline-block; background:<?= $type_bg ?>; color:<?= $type_color ?>; border:1px solid <?= $type_color ?>; padding:2px 6px; border-radius:10px; font-size:10px; font-weight:800; text-transform:uppercase;">
                                                    <?= htmlspecialchars($type_label) ?>
                                                </span>
                                            <?php if (!empty($role_tag)): ?>
                                                <span style="display:inline-block; background:#eef2f7; color:#334155; border:1px solid #cbd5e1; padding:2px 6px; border-radius:10px; font-size:10px; font-weight:800;">
                                                    <?= htmlspecialchars($role_tag) ?>
                                                </span>
                                            <?php endif; ?>
                                            </div>
                                            <strong>
                                            <?php
                                                $msg = $notif['message'] ?? '';
                                                $limit = 120;
                                                if (mb_strlen($msg) > $limit) {
                                                    $msg = mb_substr($msg, 0, $limit - 1) . '...';
                                                }
                                            ?>
                                            <?= htmlspecialchars($msg) ?>
                                            </strong>
                                        </div>
                                        <?php if ($is_unread): ?>
                                            <span class="unread-dot"></span>
                                        <?php endif; ?>
                                    </div>
                                    <small><?= date('d/m H:i', strtotime($notif['created_at'])) ?></small>
                                </a>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <a href="/admin/notifications.php" class="notif-footer">Voir toutes les notifications</a>
                    </div>
                </div>

                <a href="/admin/view_profile.php" class="user-profile-btn"><i class="bi bi-person-circle"></i> <span class="desktop-text"><?= htmlspecialchars($prenom) ?></span></a>
                <a href="/admin/logout.php" class="logout-desktop-btn"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
                <div class="hamburger" id="hamburger"><span></span><span></span><span></span></div>
            </div>
        </nav>

        <div class="nav-mobile" id="navMobile">
    <a href="/"><i class="bi bi-house-door nav-link-icon"></i><span>Accueil</span></a>
    <?php if ($role === 'ADMIN'): ?>
        <a href="/admin/gestion_pointages.php"><i class="bi bi-geo-alt nav-link-icon"></i><span>Pointages</span></a>
        <a href="/admin/gest_stock.php"><i class="bi bi-box-seam nav-link-icon"></i><span>Stock</span></a>
    <?php else: ?>
        <a href="/admin/pointage.php"><i class="bi bi-geo-alt nav-link-icon"></i><span>Présence</span></a>
    <?php endif; ?>
    <a href="/admin/view_profile.php"><i class="bi bi-person-circle nav-link-icon"></i><span>Mon Profil</span></a>
    <a href="/admin/logout.php" style="color: var(--danger);"><i class="bi bi-box-arrow-right nav-link-icon"></i><span>Déconnexion</span></a>
</div>
    </header>
    <?php if ($flash_success || $flash_error): ?>
        <div class="flash-container">
            <?php if ($flash_success): ?>
                <div class="flash-message flash-success"><i class="bi bi-check-circle"></i><span><?= htmlspecialchars($flash_success, ENT_QUOTES, 'UTF-8') ?></span></div>
            <?php endif; ?>
            <?php if ($flash_error): ?>
                <div class="flash-message flash-error"><i class="bi bi-exclamation-triangle"></i><span><?= htmlspecialchars($flash_error, ENT_QUOTES, 'UTF-8') ?></span></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</body>
</html>






