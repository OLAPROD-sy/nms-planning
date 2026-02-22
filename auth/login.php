<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Si déjà connecté, rediriger
if (isset($_SESSION['id_user'])) {
    header('Location: /nms-planning/index.php');
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

            header('Location: /nms-planning/index.php');
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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #FF9800;
            --secondary: #4CAF50;
            --dark: #1A1C1E;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        
        body { 
            background: #f4f7f6;
            background-image: 
                radial-gradient(at 0% 0%, rgba(255, 152, 0, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(76, 175, 80, 0.15) 0px, transparent 50%);
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh;
            padding: 20px;
        }
        
        .login-card { 
            background: white; 
            padding: 40px; 
            border-radius: 24px; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.05); 
            width: 100%; 
            max-width: 420px;
            position: relative;
            z-index: 1;
        }

        /* Petit cercle décoratif */
        .login-card::before {
            content: '';
            position: absolute;
            top: -10px;
            right: -10px;
            width: 50px;
            height: 50px;
            background: var(--primary);
            border-radius: 15px;
            z-index: -1;
            opacity: 0.2;
            transform: rotate(15deg);
        }

        .login-header { text-align: center; margin-bottom: 35px; }
        .login-header .logo-icon { font-size: 50px; margin-bottom: 10px; display: block; }
        .login-header h1 { font-size: 26px; font-weight: 800; color: var(--dark); letter-spacing: -0.5px; }
        .login-header p { color: #6c757d; font-size: 14px; margin-top: 5px; }
        
        .form-group { margin-bottom: 22px; position: relative; }
        .form-group label { 
            display: block; margin-bottom: 8px; font-weight: 600; 
            font-size: 13px; color: var(--dark); transition: 0.3s;
        }
        
        .form-group input { 
            width: 100%; padding: 14px 16px; 
            border: 2px solid #f1f3f5; border-radius: 12px; 
            font-size: 14px; background: #f8f9fa; transition: all 0.3s ease;
        }
        
        .form-group input:focus { 
            outline: none; border-color: var(--secondary);
            background-color: #fff; box-shadow: 0 8px 20px rgba(76, 175, 80, 0.1);
        }

        .btn-login { 
            width: 100%; padding: 16px; 
            background: var(--dark); color: white; 
            border: none; border-radius: 12px; 
            font-size: 15px; font-weight: 700; 
            cursor: pointer; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            margin-top: 10px; box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .btn-login:hover { 
            background: var(--secondary);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(76, 175, 80, 0.3);
        }

        .error-msg { 
            background: #fff5f5; color: #e03131; 
            padding: 12px 16px; border-radius: 10px; 
            margin-bottom: 25px; border: 1px solid #ffc9c9;
            font-size: 13px; font-weight: 600;
            display: flex; align-items: center; gap: 10px;
            animation: shake 0.4s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .footer { 
            text-align: center; color: #adb5bd; 
            font-size: 12px; margin-top: 30px;
            padding-top: 20px; border-top: 1px solid #f1f3f5;
        }

        /* Séparateur visuel */
        .info-pill {
            display: inline-block;
            background: #e7f5ff; color: #228be6;
            padding: 5px 12px; border-radius: 20px;
            font-size: 11px; font-weight: 700;
            margin-bottom: 20px; width: 100%; text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <span class="logo-icon">🏢</span>
            <h1>NMS Planning</h1>
            <p>Heureux de vous revoir !</p>
        </div>
        
        <div class="info-pill">🔑 Accès sécurisé à votre espace</div>

        <?php if ($error): ?>
            <div class="error-msg">⚠️ <?= htmlspecialchars($error) ?></div>
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
                <input 
                    type="password" id="password" name="password" 
                    placeholder="••••••••" required
                >
            </div>

            <button class="btn-login" type="submit">Se connecter maintenant</button>
        </form>

        <div class="footer">
            <p>&copy; 2026 NMS Planning — Plateforme de gestion</p>
        </div>
    </div>

    <script>
        // Micro-interaction : Focus visuel
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('focus', () => {
                input.parentElement.querySelector('label').style.color = 'var(--secondary)';
            });
            input.addEventListener('blur', () => {
                input.parentElement.querySelector('label').style.color = 'var(--dark)';
            });
        });
    </script>
</body>
</html>