<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_user'])) {
    $_SESSION['flash_error'] = 'Veuillez vous connecter.';
    // Utilisation d'un chemin relatif pour éviter les erreurs de racine sur Railway
    header('Location: ../auth/login.php'); 
    exit;
}

// 2. Récupérer les infos de l'utilisateur
$id_user = $_SESSION['id_user'];
$role = $_SESSION['role'] ?? 'AGENT';
$id_site = $_SESSION['id_site'] ?? NULL;
$nom = $_SESSION['nom'] ?? '';
$prenom = $_SESSION['prenom'] ?? '';

// 3. Test de sécurité : Ne détruis la session que si c'est vraiment critique
// Sur Railway, si la session est vide, c'est souvent un problème de cookie.
if (empty($nom) && empty($prenom)) {
    // Au lieu de détruire, on redirige simplement pour forcer le login
    header('Location: ../auth/login.php');
    exit;
}