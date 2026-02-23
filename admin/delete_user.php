<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if ($_SESSION['role'] !== 'ADMIN') {
    header('Location: /index.php');
    exit;
}

$id = $_GET['id'] ?? null;

if ($id) {
    try {
        // 1. Optionnel : Supprimer les fichiers physiques (Photo/CV) pour libérer de l'espace sur Railway
        $stmt = $pdo->prepare("SELECT photo, cv FROM users WHERE id_user = ?");
        $stmt->execute([$id]);
        $files = $stmt->fetch();

        if ($files) {
            if ($files['photo'] && file_exists(__DIR__ . '/../' . $files['photo'])) unlink(__DIR__ . '/../' . $files['photo']);
            if ($files['cv'] && file_exists(__DIR__ . '/../' . $files['cv'])) unlink(__DIR__ . '/../' . $files['cv']);
        }

        // 2. Supprimer l'utilisateur de la base
        $delete = $pdo->prepare("DELETE FROM users WHERE id_user = ?");
        $delete->execute([$id]);

        $_SESSION['flash_success'] = "Utilisateur supprimé définitivement.";
    } catch (PDOException $e) {
        $_SESSION['flash_error'] = "Erreur : Impossible de supprimer cet utilisateur (il est probablement lié à des plannings existants).";
    }
}

header('Location: /admin/users.php');
exit;