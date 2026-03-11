<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_user'])) {
    $_SESSION['flash_error'] = 'Veuillez vous connecter.';
    header('Location: /auth/login.php');
    exit;
}

// Récupérer les infos de l'utilisateur depuis la session
$id_user = $_SESSION['id_user'];
$role = $_SESSION['role'] ?? 'AGENT';
$id_site = $_SESSION['id_site'] ?? NULL;
$nom = $_SESSION['nom'] ?? '';
$prenom = $_SESSION['prenom'] ?? '';

// Forcer la sélection d'un site pour les superviseurs
if ($role === 'SUPERVISEUR') {
    $current_path = $_SERVER['SCRIPT_NAME'] ?? '';
    $allowed_paths = [
        '/planning/choix_site_superviseur.php',
        '/admin/logout.php',
    ];

    if (!in_array($current_path, $allowed_paths, true)) {
        require_once __DIR__ . '/../config/database.php';
        require_once __DIR__ . '/superviseur_sites.php';

        $today = date('Y-m-d');
        $site_ids = get_supervisor_site_ids($pdo, (int) $id_user, $today);
        if (!$id_site && count($site_ids) === 1) {
            $_SESSION['id_site'] = (int) $site_ids[0];
            $id_site = $_SESSION['id_site'];
        }

        if (!$id_site || !supervisor_has_site($pdo, (int) $id_user, (int) $id_site, $today)) {
            unset($_SESSION['id_site']);
            header('Location: /planning/choix_site_superviseur.php');
            exit;
        }
    }
}

// Rediriger vers le login si les infos sont manquantes
if (empty($nom) || empty($prenom)) {
    session_destroy();
    $_SESSION['flash_error'] = 'Données de session invalides.';
    header('Location: /auth/login.php');
    exit;
}
?>
