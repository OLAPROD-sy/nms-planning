<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_user'])) {
    $_SESSION['flash_error'] = 'Veuillez vous connecter.';
    header('Location: /nms-planning/auth/login.php');
    exit;
}

// Récupérer les infos de l'utilisateur depuis la session
$id_user = $_SESSION['id_user'];
$role = $_SESSION['role'] ?? 'AGENT';
$id_site = $_SESSION['id_site'] ?? NULL;
$nom = $_SESSION['nom'] ?? '';
$prenom = $_SESSION['prenom'] ?? '';

// Rediriger vers le login si les infos sont manquantes
if (empty($nom) || empty($prenom)) {
    session_destroy();
    $_SESSION['flash_error'] = 'Données de session invalides.';
    header('Location: /nms-planning/auth/login.php');
    exit;
}
?>
