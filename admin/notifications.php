<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

// Détecter schéma de la table notifications
$cols = $pdo->query("SHOW COLUMNS FROM notifications")->fetchAll(PDO::FETCH_COLUMN);
$colUser = in_array('id_user', $cols) ? 'id_user' : (in_array('user_id', $cols) ? 'user_id' : null);
$colId = in_array('id_notify', $cols) ? 'id_notify' : (in_array('id', $cols) ? 'id' : null);

if (!$colUser || !$colId) {
    $_SESSION['flash_error'] = 'Table notifications mal configurée.';
    header('Location: /nms-planning/');
    exit;
}

// Marquer comme lu si demandé
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read']) && is_numeric($_POST['mark_read'])) {
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE $colId = ? AND $colUser = ?");
        $stmt->execute([intval($_POST['mark_read']), $_SESSION['id_user']]);
        $_SESSION['flash_success'] = 'Notification marquée comme lue.';
    } catch (Exception $e) {
        error_log('notifications.php mark_read: ' . $e->getMessage());
        $_SESSION['flash_error'] = 'Impossible de marquer la notification.';
    }
    header('Location: /admin/notifications.php');
    exit;
}

// Récupérer les notifications de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE $colUser = ? ORDER BY created_at DESC LIMIT 100");
$stmt->execute([$_SESSION['id_user']]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<?php include_once __DIR__ . '/../includes/header.php'; ?>

<div style="max-width:900px;margin:20px auto;padding:20px">
    <h1 style="margin-bottom: 30px;">🔔 Notifications</h1>

    <?php if (empty($notifications)): ?>
        <div class="card" style="text-align:center;padding:40px">Aucune notification.</div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <?php foreach ($notifications as $n): 
                // Configuration selon le type
                $type_clean = strtolower($n['type'] ?? '');
                $config = [
                    'arrivee' => ['icon' => '🟢', 'label' => 'Arrivée', 'color' => '#2e7d32', 'bg' => '#e8f5e9'],
                    'depart'  => ['icon' => '🔵', 'label' => 'Départ',  'color' => '#1565c0', 'bg' => '#e3f2fd'],
                    'urgence' => ['icon' => '🚨', 'label' => 'Urgence', 'color' => '#c62828', 'bg' => '#ffebee'],
                ];
                $style = $config[$type_clean] ?? $config['default'];
            ?>
                <div class="card" style="margin:0; border-left: 5px solid <?= $style['color'] ?>; background: white; padding: 15px; position: relative;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div style="flex: 1;">
                            <div style="color: <?= $style['color'] ?>; font-weight: 800; text-transform: uppercase; font-size: 0.9em; margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                                <span><?= $style['icon'] ?></span> <?= $style['label'] ?>
                                <?php if (empty($n['is_read'])): ?>
                                    <span style="background: #ff5252; color: white; padding: 2px 6px; border-radius: 10px; font-size: 10px;">NOUVEAU</span>
                                <?php endif; ?>
                            </div>
                            
                            <div style="color: #2c3e50; font-size: 1.05em; line-height: 1.4; margin-bottom: 10px;">
                                <strong><?= $style['icon'] ?></strong> <?= nl2br(htmlspecialchars($n['message'] ?? '')) ?>
                            </div>

                            <div style="font-size: 12px; color: #95a5a6;">
                                🕒 <?= date('d/m/Y à H:i', strtotime($n['created_at'])) ?>
                            </div>
                        </div>

                        <div style="margin-left: 15px;">
                            <form method="post" style="margin:0">
                                <input type="hidden" name="mark_read" value="<?= htmlspecialchars($n[$colId]) ?>">
                                <?php if (empty($n['is_read'])): ?>
                                    <button class="btn" style="background: <?= $style['bg'] ?>; color: <?= $style['color'] ?>; border: 1px solid <?= $style['color'] ?>; padding: 5px 10px; font-size: 12px; font-weight: bold; cursor: pointer; border-radius: 4px;" type="submit">
                                        Marquer lu
                                    </button>
                                <?php else: ?>
                                    <span style="color: #4caf50; font-size: 18px;">✔</span>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>