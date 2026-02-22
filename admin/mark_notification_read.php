<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if (isset($_SESSION['id_user'])) {
    // On met à jour toutes les notifs de l'utilisateur d'un coup
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id_user = ? AND is_read = 0");
    $stmt->execute([$_SESSION['id_user']]);
    echo json_encode(['status' => 'success']);
}