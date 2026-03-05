<?php
// S'assurer que la session est démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier que l'utilisateur est connecté
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
                <a href="/">🏠 Accueil</a>
                <?php if ($role === 'ADMIN'): ?>
                    <a href="/admin/gestion_pointages.php">📍 Pointages</a>
                    <a href="/stock/gest_stock.php">📦 Stock</a>
                <?php else: ?>
                    <a href="/admin/pointage.php">📍 Présence</a>
                <?php endif; ?>
            </div>

            <div class="nav-right-group">
                <?php if ($role !== 'AGENT'): ?>
                <div style="position: relative;">
                    <div class="bell-container" id="notifBell">
                        🔔 <?php if ($notifications_count > 0): ?><span class="notif-badge"><?= $notifications_count ?></span><?php endif; ?>
                    </div>
                    <div class="notif-dropdown" id="notifDropdown">
                        <div class="notif-header">Notifications</div>
                        <div class="notif-list">
                            <?php if(empty($recent_notifications)): ?>
                                <div class="notif-item">Aucune notification</div>
                            <?php else: ?>
                                <?php foreach ($recent_notifications as $notif): 
                                $type = strtolower($notif['type'] ?? '');
                                $is_unread = isset($notif['is_read']) && $notif['is_read'] == 0; // Vérifie si non lu
                                $class = 'normal';
                                if (strpos($type, 'arrivee') !== false) $class = 'arrivee';
                                elseif (strpos($type, 'depart') !== false) $class = 'depart';
                                elseif (strpos($type, 'urgence') !== false) $class = 'urgence';
                            ?>
                                <a href="/admin/notification.php?id=<?= $notif['id'] ?>" class="notif-item <?= $class ?> <?= $is_unread ? 'unread' : '' ?>">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                        <strong><?= htmlspecialchars($notif['message']) ?></strong>
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
                <?php endif; ?>

                <a href="/admin/view_profile.php" class="user-profile-btn">👤 <span class="desktop-text"><?= htmlspecialchars($prenom) ?></span></a>
                <a href="/admin/logout.php" class="logout-desktop-btn">🚪Déconnexion</a>
                <div class="hamburger" id="hamburger"><span></span><span></span><span></span></div>
            </div>
        </nav>

        <div class="nav-mobile" id="navMobile">
    <a href="/">🏠 Accueil</a>
    <?php if ($role === 'ADMIN'): ?>
        <a href="/admin/gestion_pointages.php">📍 Pointages</a>
        <a href="/admin/gest_stock.php">📦 Stock</a>
    <?php else: ?>
        <a href="/admin/pointage.php">📍 Présence</a>
    <?php endif; ?>
    <a href="/admin/view_profile.php">👤 Mon Profil</a>
    <a href="/admin/logout.php" style="color: var(--danger);">🚪 Déconnexion</a>
</div>
    </header>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const bell = document.getElementById('notifBell');
            const dropdown = document.getElementById('notifDropdown');
            const hamburger = document.getElementById('hamburger');
            const navMobile = document.getElementById('navMobile');

            // Toggle Notifications
            // Toggle Notifications
            if (bell && dropdown) {
                bell.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const isActive = dropdown.classList.toggle('active');
                    
                    // Si on vient d'ouvrir le menu
                    if (isActive) {
                        if(navMobile) navMobile.classList.remove('active');

                        // Appel AJAX pour marquer comme lu sur le serveur
                        fetch('/admin/mark_notification_read.php')
                            .then(response => response.json())
                            .then(data => {
                                // On fait disparaître le badge rouge visuellement
                                const badge = document.querySelector('.notif-badge');
                                if (badge) badge.style.display = 'none';
                            })
                            .catch(err => console.error('Erreur SQL:', err));
                    }
                });
            }
            // Toggle Hamburger
            if (hamburger && navMobile) {
                hamburger.addEventListener('click', function(e) {
                    e.stopPropagation();
                    navMobile.classList.toggle('active');
                    if(dropdown) dropdown.classList.remove('active');
                });
            }

            // Fermer si clic ailleurs
            document.addEventListener('click', function(e) {
                if (dropdown && !dropdown.contains(e.target)) dropdown.classList.remove('active');
                if (navMobile && !navMobile.contains(e.target) && !hamburger.contains(e.target)) {
                    navMobile.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
