<?php
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

// Vérifier que seul l'admin peut accéder
if ($_SESSION['role'] !== 'ADMIN') {
    $_SESSION['flash_error'] = 'Accès refusé.';
    header('Location: /nms-planning/index.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    $_SESSION['flash_error'] = 'Utilisateur non trouvé.';
    header('Location: /nms-planning/admin/users.php');
    exit;
}

// Récupérer l'utilisateur
$stmt = $pdo->prepare('SELECT * FROM users WHERE id_user = ?');
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['flash_error'] = 'Utilisateur non trouvé.';
    header('Location: /nms-planning/admin/users.php');
    exit;
}

$error = '';
$UPLOAD_DIR_PHOTOS = __DIR__ . '/../uploads/photos';
$UPLOAD_DIR_CV = __DIR__ . '/../uploads/cv';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Requête invalide (CSRF).';
    } else {
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $id_site = !empty($_POST['id_site']) ? (int)$_POST['id_site'] : null;
        $date_embauche = trim($_POST['date_embauche'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // Validations
        if ($nom === '' || $prenom === '' || $email === '') {
            $error = 'Nom, prénom et email sont requis.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email invalide.';
        } elseif (!in_array($role, ['ADMIN', 'SUPERVISEUR', 'AGENT'])) {
            $error = 'Rôle invalide.';
        } else {
            // Vérifier que l'email n'existe pas pour un autre utilisateur
            $check = $pdo->prepare('SELECT id_user FROM users WHERE email = ? AND id_user != ?');
            $check->execute([$email, $id]);
            if ($check->rowCount() > 0) {
                $error = 'Cet email est déjà utilisé par un autre utilisateur.';
            } else {
                $photo = $user['photo'];
                $cv = $user['cv'];

                // Traiter upload photo
                if (!empty($_FILES['photo']['name'])) {
                    $file = $_FILES['photo'];
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $max_size = 5 * 1024 * 1024; // 5 MB

                    if (!in_array($file['type'], $allowed_types)) {
                        $error = 'La photo doit être au format JPEG, PNG ou GIF.';
                    } elseif ($file['size'] > $max_size) {
                        $error = 'La taille de la photo ne doit pas dépasser 5 MB.';
                    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
                        $error = 'Erreur lors du téléchargement de la photo.';
                    } else {
                        // Supprimer l'ancienne photo
                        if ($user['photo'] && file_exists(__DIR__ . '/../' . $user['photo'])) {
                            unlink(__DIR__ . '/../' . $user['photo']);
                        }
                        
                        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                        $filepath = $UPLOAD_DIR_PHOTOS . '/' . $filename;
                        
                        if (move_uploaded_file($file['tmp_name'], $filepath)) {
                            $photo = 'uploads/photos/' . $filename;
                        } else {
                            $error = 'Impossible de sauvegarder la photo.';
                        }
                    }
                }

                // Traiter upload CV si pas d'erreur
                if ($error === '' && !empty($_FILES['cv']['name'])) {
                    $file = $_FILES['cv'];
                    $allowed_types = ['application/pdf'];
                    $max_size = 10 * 1024 * 1024; // 10 MB

                    if ($file['type'] !== 'application/pdf') {
                        $error = 'Le CV doit être au format PDF.';
                    } elseif ($file['size'] > $max_size) {
                        $error = 'La taille du CV ne doit pas dépasser 10 MB.';
                    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
                        $error = 'Erreur lors du téléchargement du CV.';
                    } else {
                        // Supprimer l'ancien CV
                        if ($user['cv'] && file_exists(__DIR__ . '/../' . $user['cv'])) {
                            unlink(__DIR__ . '/../' . $user['cv']);
                        }
                        
                        $ext = 'pdf';
                        $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                        $filepath = $UPLOAD_DIR_CV . '/' . $filename;
                        
                        if (move_uploaded_file($file['tmp_name'], $filepath)) {
                            $cv = 'uploads/cv/' . $filename;
                        } else {
                            $error = 'Impossible de sauvegarder le CV.';
                        }
                    }
                }

                // Mettre à jour l'utilisateur si pas d'erreur
                if ($error === '') {
                    try {
                        if ($password !== '') {
                            $password_hash = password_hash($password, PASSWORD_BCRYPT);
                            $stmt = $pdo->prepare('
                                UPDATE users 
                                SET nom=?, prenom=?, email=?, password=?, role=?, id_site=?, date_embauche=?, photo=?, cv=?
                                WHERE id_user=?
                            ');
                            $stmt->execute([
                                $nom, $prenom, $email, $password_hash, $role, $id_site,
                                !empty($date_embauche) ? $date_embauche : null,
                                $photo, $cv, $id
                            ]);
                        } else {
                            $stmt = $pdo->prepare('
                                UPDATE users 
                                SET nom=?, prenom=?, email=?, role=?, id_site=?, date_embauche=?, photo=?, cv=?
                                WHERE id_user=?
                            ');
                            $stmt->execute([
                                $nom, $prenom, $email, $role, $id_site,
                                !empty($date_embauche) ? $date_embauche : null,
                                $photo, $cv, $id
                            ]);
                        }
                        
                        $_SESSION['flash_success'] = 'Utilisateur modifié avec succès !';
                        header('Location: /nms-planning/admin/users.php');
                        exit;
                    } catch (PDOException $e) {
                        $error = 'Erreur lors de la modification : ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

// Récupérer les sites pour le formulaire
$sites = $pdo->query('SELECT id_site, nom_site FROM sites ORDER BY nom_site')->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include_once __DIR__ . '/../includes/header.php'; ?>

<div style="max-width:700px;margin:0 auto;padding:20px">
    <h2>Modifier l'utilisateur</h2>

    <?php if ($error): ?>
        <div style="background:#FFEBEE;border-left:4px solid #F44336;color:#C62828;padding:15px;border-radius:6px;margin-bottom:25px;font-weight:500">
            ⚠️ <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" style="background:#f9fafb;padding:30px;border-radius:12px;border:1px solid #FFE0B2;box-shadow:0 4px 20px rgba(255, 152, 0, 0.15)">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">

        <style>
            .form-group {
                margin-bottom: 22px;
            }

            .form-group label {
                display: block;
                margin-bottom: 8px;
                font-weight: 600;
                color: #333;
                font-size: 14px;
            }

            .form-group label .required {
                color: #F44336;
                margin-left: 4px;
            }

            .form-group input[type="text"],
            .form-group input[type="email"],
            .form-group input[type="password"],
            .form-group input[type="date"],
            .form-group input[type="file"],
            .form-group select {
                width: 100%;
                padding: 12px 14px;
                border: 2px solid #E0E0E0;
                border-radius: 8px;
                font-size: 14px;
                transition: all 0.3s ease;
                font-family: inherit;
            }

            .form-group input:focus,
            .form-group select:focus {
                outline: none;
                border-color: #FF9800;
                box-shadow: 0 0 0 3px rgba(255, 152, 0, 0.1);
            }

            .form-group small {
                display: block;
                margin-top: 6px;
                color: #999;
                font-size: 12px;
            }

            .form-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
            }

            @media (max-width: 600px) {
                .form-row {
                    grid-template-columns: 1fr;
                }
            }

            .form-divider {
                border-top: 2px solid #F5F5F5;
                margin: 30px 0 25px;
                padding-top: 25px;
            }

            .form-divider::before {
                content: attr(data-icon);
                display: block;
                color: #666;
                font-weight: 600;
                font-size: 14px;
                margin-bottom: 15px;
            }

            .photo-preview {
                margin-bottom: 15px;
                padding: 15px;
                background: white;
                border-radius: 8px;
                border: 1px solid #E0E0E0;
            }

            .photo-preview img {
                max-width: 150px;
                border-radius: 8px;
                margin-bottom: 10px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }

            .photo-preview p {
                font-size: 12px;
                color: #666;
                margin: 0;
            }

            .btn-primary {
                background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
                color: white;
                padding: 14px 28px;
                border: none;
                border-radius: 8px;
                font-weight: 600;
                font-size: 15px;
                cursor: pointer;
                transition: all 0.3s ease;
                flex: 1;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
            }

            .btn-primary:hover {
                box-shadow: 0 4px 15px rgba(255, 152, 0, 0.4);
                transform: translateY(-2px);
            }

            .btn-secondary {
                background: #E0E0E0;
                color: #333;
                padding: 14px 28px;
                border: none;
                border-radius: 8px;
                font-weight: 600;
                font-size: 15px;
                cursor: pointer;
                text-decoration: none;
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
            }

            .btn-secondary:hover {
                background: #D0D0D0;
            }

            .form-actions {
                display: flex;
                gap: 15px;
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #E0E0E0;
            }

            @media (max-width: 768px) {
                .form-container {
                    padding: 0 15px;
                }

                .form-header {
                    padding: 25px;
                }

                .form-header h2 {
                    font-size: 24px;
                }

                .form-wrapper {
                    padding: 20px;
                }

                .form-row {
                    grid-template-columns: 1fr;
                }

                .photo-preview img {
                    max-width: 120px;
                }

                .btn-primary,
                .btn-secondary {
                    padding: 12px 20px;
                    font-size: 14px;
                }

                .form-actions {
                    flex-direction: column;
                }
            }

            @media (max-width: 480px) {
                .form-container {
                    margin: 15px auto;
                }

                .form-header {
                    padding: 20px;
                }

                .form-header h2 {
                    font-size: 20px;
                }

                .form-wrapper {
                    padding: 15px;
                }

                .form-group {
                    margin-bottom: 18px;
                }

                .form-group label {
                    font-size: 13px;
                }

                .form-group input,
                .form-group select {
                    padding: 10px;
                    font-size: 16px;
                }

                .photo-preview {
                    padding: 12px;
                }

                .photo-preview img {
                    max-width: 100px;
                }

                .btn-primary,
                .btn-secondary {
                    padding: 12px 16px;
                    font-size: 13px;
                }

                .form-actions {
                    gap: 10px;
                    margin-top: 20px;
                }
            }
        </style>

        <!-- Section Informations Personnelles -->
        <div style="margin-bottom: 30px;">
            <div style="display: flex; align-items: center; margin-bottom: 20px; gap: 10px;">
                <span style="font-size: 24px;">👤</span>
                <h3 style="margin: 0; color: #333;">Informations Personnelles</h3>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Nom <span class="required">*</span></label>
                    <input type="text" name="nom" required value="<?= htmlspecialchars($user['nom'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Prénom <span class="required">*</span></label>
                    <input type="text" name="prenom" required value="<?= htmlspecialchars($user['prenom'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Email <span class="required">*</span></label>
                <input type="email" name="email" required value="<?= htmlspecialchars($user['email'] ?? '') ?>">
            </div>
        </div>

        <!-- Section Rôle et Site -->
        <div style="margin-bottom: 30px;">
            <div style="display: flex; align-items: center; margin-bottom: 20px; gap: 10px;">
                <span style="font-size: 24px;">🏢</span>
                <h3 style="margin: 0; color: #333;">Rôle et Affectation</h3>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Rôle <span class="required">*</span></label>
                    <select name="role" required>
                        <option value="ADMIN" <?= ($user['role'] ?? '') === 'ADMIN' ? 'selected' : '' ?>>🔴 Admin</option>
                        <option value="SUPERVISEUR" <?= ($user['role'] ?? '') === 'SUPERVISEUR' ? 'selected' : '' ?>>🟠 Superviseur</option>
                        <option value="AGENT" <?= ($user['role'] ?? '') === 'AGENT' ? 'selected' : '' ?>>🟢 Agent</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Site</label>
                    <select name="id_site">
                        <option value="">-- Aucun site --</option>
                        <?php foreach ($sites as $site): ?>
                            <option value="<?= (int)$site['id_site'] ?>" <?= ($user['id_site'] ?? '') === $site['id_site'] ? 'selected' : '' ?>>
                                📍 <?= htmlspecialchars($site['nom_site']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Date d'embauche</label>
                <input type="date" name="date_embauche" value="<?= htmlspecialchars($user['date_embauche'] ?? '') ?>">
            </div>
        </div>

        <!-- Section Documents -->
        <div style="margin-bottom: 30px;">
            <div style="display: flex; align-items: center; margin-bottom: 20px; gap: 10px;">
                <span style="font-size: 24px;">📄</span>
                <h3 style="margin: 0; color: #333;">Documents</h3>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Photo de profil</label>
                    <?php if ($user['photo']): ?>
                        <div class="photo-preview">
                            <img src="/nms-planning/<?= htmlspecialchars($user['photo']) ?>" alt="Photo de profil">
                            <p>✅ Photo actuelle</p>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/gif">
                    <small>JPG, PNG, GIF • Max 5 MB (remplacera la photo actuelle)</small>
                </div>
                <div class="form-group">
                    <label>CV</label>
                    <?php if ($user['cv']): ?>
                        <div style="margin-bottom: 15px; padding: 12px; background: #E8F5E9; border-radius: 8px; border-left: 4px solid #4CAF50;">
                            <a href="/nms-planning/<?= htmlspecialchars($user['cv']) ?>" target="_blank" style="color: #2E7D32; text-decoration: none; font-weight: 600;">📄 Télécharger le CV actuel</a>
                            <p style="font-size: 12px; color: #666; margin: 5px 0 0;">CV actuel</p>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="cv" accept="application/pdf">
                    <small>PDF • Max 10 MB (remplacera le CV actuel)</small>
                </div>
            </div>
        </div>

        <!-- Section Sécurité -->
        <div style="margin-bottom: 30px;">
            <div style="display: flex; align-items: center; margin-bottom: 20px; gap: 10px;">
                <span style="font-size: 24px;">🔐</span>
                <h3 style="margin: 0; color: #333;">Sécurité</h3>
            </div>

            <div class="form-group">
                <label>Nouveau mot de passe</label>
                <input type="password" name="password" placeholder="Laisser vide pour conserver l'actuel">
                <small>Minimum 8 caractères. Laisser vide si vous ne souhaitez pas modifier</small>
            </div>
        </div>

        <!-- Actions -->
        <div class="form-actions">
            <button type="submit" class="btn-primary">
                ✅ Enregistrer les modifications
            </button>
            <a href="/nms-planning/admin/users.php" class="btn-secondary">
                ⬅️ Annuler
            </a>
        </div>
    </form>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
