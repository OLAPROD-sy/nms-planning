<?php
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

// Vérifier que seul l'admin peut accéder
if ($_SESSION['role'] !== 'ADMIN') {
    $_SESSION['flash_error'] = 'Accès refusé. Seul l\'admin peut ajouter des utilisateurs.';
    header('Location: /index.php');
    exit;
}

$error = '';
$success = '';
$UPLOAD_DIR_PHOTOS = __DIR__ . '/../uploads/photos';
$UPLOAD_DIR_CV = __DIR__ . '/../uploads/cv';

// Créer les répertoires s'ils n'existent pas
if (!is_dir($UPLOAD_DIR_PHOTOS)) mkdir($UPLOAD_DIR_PHOTOS, 0755, true);
if (!is_dir($UPLOAD_DIR_CV)) mkdir($UPLOAD_DIR_CV, 0755, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Requête invalide (CSRF).';
    } else {
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $contact = trim($_POST['contact'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $password_confirm = trim($_POST['password_confirm'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $id_site = !empty($_POST['id_site']) ? (int)$_POST['id_site'] : null;
        $date_embauche = trim($_POST['date_embauche'] ?? '');

        // Validations
        if ($nom === '' || $prenom === '' || $contact === '' || $username === '' || $email === '' || $password === '') {
            $error = 'Tous les champs obligatoires doivent être remplis.';
        } elseif ($password !== $password_confirm) {
            $error = 'Les mots de passe ne correspondent pas.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email invalide.';
        } elseif (!in_array($role, ['ADMIN', 'SUPERVISEUR', 'AGENT'])) {
            $error = 'Rôle invalide.';
        } else {
            // Vérifier si l'email existe déjà
            $check = $pdo->prepare('SELECT id_user FROM users WHERE email = ?');
            $check->execute([$email]);
            if ($check->rowCount() > 0) {
                $error = 'Cet email est déjà utilisé.';
            } else {
                $photo = null;
                $cv = null;

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

                // Insérer l'utilisateur si pas d'erreur
                if ($error === '') {
                    try {
                        $password_hash = password_hash($password, PASSWORD_BCRYPT);
                        $stmt = $pdo->prepare('
                            INSERT INTO users 
                            (nom, prenom, username, contact, email, password, role, id_site, date_embauche, photo, cv, created_at)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                        ');
                        $stmt->execute([
                            $nom,
                            $prenom,
                            $username,
                            $contact,
                            $email,
                            $password_hash,
                            $role,
                            $id_site,
                            !empty($date_embauche) ? $date_embauche : null,
                            $photo,
                            $cv
                        ]);
                        
                        $_SESSION['flash_success'] = 'Utilisateur ajouté avec succès !';
                        header('Location: /admin/users.php');
                        exit;
                    } catch (PDOException $e) {
                        $error = 'Erreur lors de l\'ajout : ' . $e->getMessage();
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

<style>
    .form-container {
        max-width: 700px;
        margin: 30px auto;
        padding: 0 20px;
    }

    .form-header {
        background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
        color: white;
        padding: 30px;
        border-radius: 12px 12px 0 0;
        margin-bottom: 0;
    }

    .form-header h2 {
        margin: 0;
        font-size: 28px;
        font-weight: 600;
    }

    .form-header p {
        margin: 8px 0 0;
        opacity: 0.9;
    }

    .form-wrapper {
        background: white;
        border-radius: 0 0 12px 12px;
        padding: 30px;
        box-shadow: 0 4px 20px rgba(255, 152, 0, 0.15);
        border: 1px solid #FFE0B2;
    }

    .alert-error {
        background: #FFEBEE;
        border-left: 4px solid #F44336;
        color: #C62828;
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 25px;
        font-weight: 500;
    }

    .alert-error::before {
        content: '⚠️ ';
        margin-right: 8px;
    }

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

    .form-group input[type="file"] {
        padding: 10px;
        cursor: pointer;
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

    @media (max-width: 768px) {
        .form-container {
            padding: 0 15px;
        }

        .form-header {
            padding: 25px;
            border-radius: 8px 8px 0 0;
        }

        .form-header h2 {
            font-size: 24px;
        }

        .form-header p {
            font-size: 14px;
        }

        .form-wrapper {
            padding: 20px;
        }

        .form-group input,
        .form-group select {
            padding: 10px 12px;
            font-size: 16px;
        }

        .form-row {
            grid-template-columns: 1fr;
            gap: 15px;
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
            border-radius: 0 0 8px 8px;
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

        .form-divider {
            margin: 25px 0 20px;
            padding-top: 20px;
        }

        .form-actions {
            gap: 10px;
            margin-top: 20px;
        }

        .btn-primary,
        .btn-secondary {
            padding: 12px 16px;
            font-size: 13px;
            gap: 6px;
        }
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
        content: '🔐 Sécurité';
        display: block;
        color: #666;
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 15px;
    }

    .form-actions {
        display: flex;
        gap: 15px;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #E0E0E0;
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

    .btn-primary:active {
        transform: translateY(0);
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

    /* Masquer les étapes par défaut */
.step-section {
    display: none;
    animation: fadeIn 0.4s ease;
}

.step-section.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Barre de progression */
.progress-container {
    margin-bottom: 30px;
    background: #f0f0f0;
    height: 8px;
    border-radius: 10px;
    position: relative;
}

.progress-bar {
    background: var(--primary);
    height: 100%;
    width: 33%; /* Ajusté par JS */
    border-radius: 10px;
    transition: width 0.4s ease;
}

.steps-indicators {
    display: flex;
    justify-content: space-between;
    position: absolute;
    width: 100%;
    top: -12px;
}

.step-dot {
    width: 30px;
    height: 30px;
    background: white;
    border: 2px solid #ddd;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 14px;
}

.step-dot.active {
    border-color: var(--primary);
    color: var(--primary);
}
</style>

<div class="form-container">
    <div class="form-header">
        <h2>👤 Ajouter un utilisateur</h2>
        <p>Complétez le formulaire pour créer un nouveau compte</p>
    </div>

    <div class="form-wrapper">
        <?php if ($error): ?>
            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" id="multiStepForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">

            <div class="progress-container">
                <div class="progress-bar" id="progressBar"></div>
                <div class="steps-indicators">
                    <span class="step-dot active">1</span>
                    <span class="step-dot">2</span>
                    <span class="step-dot">3</span>
                    <span class="step-dot">4</span>
                </div>
            </div>
                    <!-- Section Informations Personnelles -->
            <div class="step-section active" data-step="1"> 
                <div class="form-divider">👤 Informations Personnelles</div>        
                <div class="form-row">
                    <div class="form-group">
                        <label>Nom <span class="required">*</span></label>
                        <input type="text" name="nom" placeholder="Ex: ALAO" required value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Prénom <span class="required">*</span></label>
                        <input type="text" name="prenom" placeholder="Ex: Ayouba" required value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Identifiant (username) <span class="required">*</span></label>
                        <input type="text" name="username" placeholder="Ex: ayouba_alao" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Contact<span class="required">*</span></label>
                        <input type="text" name="contact" placeholder="0197000000" required value="<?= htmlspecialchars($_POST['contact'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Email <span class="required">*</span></label>
                    <input type="email" name="email" placeholder="exemple@mail.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    <small>L'email doit être unique dans le système</small>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-primary next-step">Suivant ➡️</button>
                </div>
            </div>    

            <div class="step-section" data-step="2">
                <div class="form-divider">👤 Fonction dans l'entreprise</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Rôle <span class="required">*</span></label>
                        <select name="role" required>
                            <option value="">-- Sélectionner un rôle --</option>
                            <option value="ADMIN" <?= ($_POST['role'] ?? '') === 'ADMIN' ? 'selected' : '' ?>>🔴 Admin</option>
                            <option value="SUPERVISEUR" <?= ($_POST['role'] ?? '') === 'SUPERVISEUR' ? 'selected' : '' ?>>🟠 Superviseur</option>
                            <option value="AGENT" <?= ($_POST['role'] ?? '') === 'AGENT' ? 'selected' : '' ?>>🟢 Agent</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Site</label>
                        <select name="id_site">
                            <option value="">-- Aucun site --</option>
                            <?php foreach ($sites as $site): ?>
                                <option value="<?= (int)$site['id_site'] ?>" <?= ($_POST['id_site'] ?? '') === (string)$site['id_site'] ? 'selected' : '' ?>>
                                    📍 <?= htmlspecialchars($site['nom_site']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Date d'embauche</label>
                    <input type="date" name="date_embauche" value="<?= htmlspecialchars($_POST['date_embauche'] ?? '') ?>">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-secondary prev-step">⬅️ Précédent</button>
                    <button type="button" class="btn-primary next-step">Suivant ➡️</button>
                </div>
            </div>

            <!-- Section Documents -->
            <div class="step-section" data-step="3">
                <div class="form-divider">📸 Pièces Jointes</div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Photo de profil</label>
                        <input type="file" name="photo" accept="image/jpeg,image/png,image/gif">
                        <small>JPG, PNG, GIF • Max 5 MB</small>
                    </div>
                    <div class="form-group">
                        <label>CV</label>
                        <input type="file" name="cv" accept="application/pdf">
                        <small>PDF • Max 10 MB</small>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary prev-step">⬅️ Précédent</button>
                    <button type="button" class="btn-primary next-step">Suivant ➡️</button>
                </div>
            </div>    
            <!-- Section Mot de Passe -->
            <div class="step-section" data-step="4">
                <div class="form-divider">🔐 Sécurité</div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Mot de passe <span class="required">*</span></label>
                        <input type="password" name="password" required placeholder="Minimum 8 caractères">
                    </div>
                    <div class="form-group">
                        <label>Confirmer le mot de passe <span class="required">*</span></label>
                        <input type="password" name="password_confirm" required placeholder="Retapez le mot de passe">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary prev-step">⬅️ Précédent</button>
                    <button type="submit" class="btn-primary">✅ Créer l'utilisateur</button>
                </div>

            </div>

        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const steps = document.querySelectorAll('.step-section');
    const dots = document.querySelectorAll('.step-dot');
    const progressBar = document.getElementById('progressBar');
    let currentStep = 1;

    // Fonction pour changer d'étape
    function updateStep(stepNumber) {
        steps.forEach(step => step.classList.remove('active'));
        dots.forEach((dot, index) => {
            dot.classList.toggle('active', index < stepNumber);
        });
        
        document.querySelector(`.step-section[data-step="${stepNumber}"]`).classList.add('active');
        
        // Update progress bar
        const progress = (stepNumber / steps.length) * 100;
        progressBar.style.width = `${progress}%`;
    }

    // Boutons "Suivant"
    document.querySelectorAll('.next-step').forEach(btn => {
        btn.addEventListener('click', () => {
            // Optionnel: Ajoutez ici une validation JS pour vérifier si les champs requis sont remplis
            if (currentStep < steps.length) {
                currentStep++;
                updateStep(currentStep);
            }
        });
    });

    // Boutons "Précédent"
    document.querySelectorAll('.prev-step').forEach(btn => {
        btn.addEventListener('click', () => {
            if (currentStep > 1) {
                currentStep--;
                updateStep(currentStep);
            }
        });
    });
});
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
