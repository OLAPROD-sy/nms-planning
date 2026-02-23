<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

// Sécurité : Seul l'admin peut changer les statuts
if ($_SESSION['role'] !== 'ADMIN') {
    header('Location: /index.php');
    exit;
}

$id = $_GET['id'] ?? null;

if ($id) {
    // On récupère le statut actuel pour l'inverser
    $stmt = $pdo->prepare("SELECT status FROM users WHERE id_user = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if ($user) {
        $newStatus = ($user['status'] === 'actif') ? 'inactif' : 'actif';
        
        $update = $pdo->prepare("UPDATE users SET status = ? WHERE id_user = ?");
        $update->execute([$newStatus, $id]);
        
        $_SESSION['flash_success'] = "Statut mis à jour avec succès.";
    }
}

header('Location: /admin/users.php');
exit;