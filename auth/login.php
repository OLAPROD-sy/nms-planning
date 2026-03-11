<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/superviseur_sites.php';

// Si déjà connecté, rediriger
if (isset($_SESSION['id_user'])) {
    header('Location: /index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['nom'] = $user['nom'];
            $_SESSION['prenom'] = $user['prenom'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['id_site'] = $user['id_site'];
            $_SESSION['contact'] = $user['contact'];

            if ($user['role'] === 'SUPERVISEUR') {
                $sites_ids = get_supervisor_site_ids($pdo, (int) $user['id_user']);
                if (count($sites_ids) === 1) {
                    $_SESSION['id_site'] = (int) $sites_ids[0];
                    header('Location: /index.php');
                    exit;
                }
                unset($_SESSION['id_site']);
                header('Location: /planning/choix_site_superviseur.php');
                exit;
            }

            header('Location: /index.php');
            exit;
        } else {
            $error = 'Identifiants invalides. Veuillez réessayer.';
        }
    } else {
        $error = 'Veuillez remplir tous les champs.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion | NMS Planning</title>
    <link rel="icon" type="image/svg+xml" href="/assets/images/favicon.svg">
    
    <link rel="alternate icon" href="/assets/images/logo_nms.png">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/pages/auth/login.css">
    
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <span class="logo-icon"><i class="bi bi-buildings"></i></span>
            <h1>NMS Planning</h1>
            <p>Heureux de vous revoir !</p>
        </div>
        
        <div class="info-pill"><i class="bi bi-key"></i> Accès sécurisé à votre espace</div>

        <?php if ($error): ?>
            <div class="error-msg"><i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="username">Identifiant ou Email</label>
                <input 
                    type="text" id="username" name="username" 
                    placeholder="ex: jean.dupont" required autofocus
                >
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <div style="position: relative;">
                    <input 
                        type="password" id="password" name="password" 
                        placeholder="••••••••" required
                    >
                    <span id="togglePassword" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 18px; opacity: 0.6; transition: 0.3s;">
                        <i class="bi bi-eye" id="togglePasswordIcon"></i>
                    </span>
                </div>
            </div>

            <button class="btn-login" type="submit">Se connecter maintenant</button>
        </form>

        <div class="footer">
            <p>&copy; 2026 NMS Planning — Plateforme de gestion</p>
        </div>
    </div>

    <script src="/assets/js/pages/auth/login.js"></script>
</body>
</html>
