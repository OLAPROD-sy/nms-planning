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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
    :root {
        --primary: #FF9800; 
        --primary-dark: #E68900;
        --danger: #FF5252; 
        --success: #4CAF50; 
        --info: #2196F3;
        --text-main: #1A1C1E; 
        --shadow: 0 4px 12px rgba(0,0,0,0.05);
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F8F9FA; }

    header { 
        background: #FFFFFF; 
        position: sticky; 
        top: 0; 
        z-index: 1000; 
        height: 70px; 
        box-shadow: var(--shadow);
        border-bottom: 1px solid #edf2f7;
    }

    nav { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        max-width: 1400px; 
        margin: 0 auto; 
        padding: 0 20px; 
        height: 100%; 
    }


     /* NOTIFICATIONS DROPDOWN */

.bell-container { position: relative; cursor: pointer; font-size: 20px; padding: 8px; background: rgba(255,255,255,0.2); border-radius: 12px; }

.notif-badge { position: absolute; top: -5px; right: -5px; background: var(--danger); color: white; font-size: 10px; padding: 2px 6px; border-radius: 20px; border: 2px solid var(--primary); }


.notif-dropdown {

display: none; position: absolute; right: 0; top: 55px; width: 320px;

background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);

z-index: 2000; border: 1px solid #eee; overflow: hidden;

}
/* Optionnel : ajoute un léger flou au reste du site quand les notifs sont ouvertes */
body.notif-open {
    overflow: hidden; /* Empêche de scroller le site derrière */
}

.notif-list {
    max-height: 300px; /* Hauteur maximale avant de commencer à défiler */
    overflow-y: auto;  /* Active le défilement vertical si nécessaire */
    background: #fff;
}

/* Optionnel : Rendre la barre de défilement plus fine et élégante */
.notif-list::-webkit-scrollbar {
    width: 6px;
}

.notif-list::-webkit-scrollbar-thumb {
    background-color: #ddd;
    border-radius: 10px;
}

@media (max-width: 480px) {
    .notif-dropdown {
        position: fixed;
        top: 80px;
        left: 5%;
        right: 5%;
        width: 90%;
        margin: 0 auto;
        max-height: 70vh; /* L'appli de notifs peut prendre jusqu'à 70% de la hauteur de l'écran */
    }

    .notif-list {
        max-height: 60vh; /* La liste défilante s'adapte à l'écran mobile */
    }
}
.notif-dropdown.active { display: block !important; }

.notif-header { padding: 12px; background: #f8f9fa; font-weight: bold; border-bottom: 1px solid #eee; }


/* Styles par types : Arrivée, Départ, Urgence */

.notif-item { padding: 12px 15px; border-bottom: 1px solid #f5f5f5; display: block; text-decoration: none; color: #444; font-size: 13px; border-left: 5px solid #ccc; }

.notif-item.arrivee { border-left-color: var(--success); }

.notif-item.depart { border-left-color: var(--info); }

.notif-item.urgence { border-left-color: var(--danger); background: #fff5f5; } 
    /* LOGO RESPONSIVE */
    .nav-brand img { 
        height: 45px; /* Taille par défaut */
        width: auto; 
        display: block;
    }

    /* NAVIGATION CENTRALE */
    .nav-center { display: flex; gap: 5px; background: #f1f5f9; padding: 5px; border-radius: 12px; }
    .nav-center a { 
        color: #475569; 
        text-decoration: none; 
        padding: 8px 15px; 
        border-radius: 8px; 
        font-size: 13px; 
        font-weight: 700; 
    }
    .nav-center a:hover { background: white; color: var(--primary); }

    .nav-right-group { display: flex; align-items: center; gap: 10px; }

    /* BOUTONS */
    .user-profile-btn { 
        background: var(--primary); 
        color: white; 
        padding: 8px 12px; 
        border-radius: 10px; 
        text-decoration: none; 
        font-weight: 700; 
        font-size: 13px; 
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .logout-desktop-btn { 
        background: #fee2e2; 
        color: #ef4444; 
        padding: 8px 12px; 
        border-radius: 10px; 
        text-decoration: none; 
        font-weight: 700; 
        font-size: 13px; 
    }

    .hamburger { 
        display: none; 
        flex-direction: column; 
        gap: 4px; 
        cursor: pointer; 
        padding: 10px;
    }
    .hamburger span { width: 22px; height: 2px; background: var(--text-main); border-radius: 2px; }

    /* --- MEDIA QUERIES (LA RÉPONSE À TON PROBLÈME) --- */

    @media (max-width: 1024px) {
        .nav-center { display: none; } /* Cache le menu centre sur tablette/mobile */
        .logout-desktop-btn { display: none; } /* Cache déconnexion sur mobile (sera dans le menu burger) */
        .hamburger { display: flex; } /* Affiche le burger */
        
        .user-profile-btn .desktop-text { display: none; } /* Garde juste l'icône sur mobile */
        .user-profile-btn { padding: 8px; border-radius: 50%; }
    }

    @media (max-width: 480px) {
        .nav-brand img { height: 35px; } /* Logo plus petit sur mini écrans */
        nav { padding: 0 15px; }
    }


    .notif-footer { display: block; text-align: center; padding: 10px; background: #f8f9fa; color: var(--primary-dark); font-weight: bold; text-decoration: none; font-size: 13px; } 
    /* MENU MOBILE */
    .nav-mobile { 
        position: fixed; 
        top: 70px; 
        left: 0; 
        width: 100%; 
        background: white; 
        max-height: 0; 
        overflow: hidden; 
        transition: 0.3s ease-in-out; 
        display: flex; 
        flex-direction: column; 
        z-index: 999;
        box-shadow: 0 10px 15px rgba(0,0,0,0.05);
    }
    .nav-mobile.active { max-height: 400px; padding: 10px 0; }
    .nav-mobile a { 
        padding: 15px 25px; 
        text-decoration: none; 
        color: var(--text-main); 
        font-weight: 600; 
        border-bottom: 1px solid #f1f5f9; 
    }

    /* Style pour les notifications non lues */
.notif-item.unread {
    background-color: #f0f7ff; /* Bleu très clair */
    font-weight: 600;
}

/* Le petit point bleu indicateur */
.unread-dot {
    width: 8px;
    height: 8px;
    background-color: var(--info);
    border-radius: 50%;
    margin-left: 10px;
    flex-shrink: 0;
    margin-top: 5px;
}

/* Changement au survol */
.notif-item:hover {
    background-color: #f8f9fa;
}
</style>
</head>
<body>
    <header>
        <nav>
            <div class="nav-brand"><a href="/"> <img src="/assets/images/logo_nms.png" alt="NMS Planning"> </a></div>
            
            <div class="nav-center">
                <a href="/">🏠 Accueil</a>
                <?php if ($role === 'ADMIN'): ?>
                    <a href="/admin/gestion_pointages.php">📍 Pointages</a>
                    <a href="/admin/gest_stock.php">📦 Stock</a>
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
                        fetch('/admin/mark_notifications_read.php')
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