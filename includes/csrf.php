<?php
// S'assurer que la session est bien active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function generate_csrf_token(): string {
    // Générer un nouveau token s'il n'existe pas
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(string $token): bool {
    // Vérifier que la session et le token existent
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }
    
    if (empty($token)) {
        return false;
    }
    
    // Utiliser hash_equals pour éviter les attaques temporelles
    return hash_equals($_SESSION['csrf_token'], $token);
}

