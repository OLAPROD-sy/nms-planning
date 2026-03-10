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


<div class="form-container">
    <div class="form-header">
        <h2><i class="bi bi-person-plus"></i> Ajouter un utilisateur</h2>
        <p>Complétez le formulaire pour créer un nouveau compte</p>
    </div>

    <div class="form-wrapper">
        <?php if ($error): ?>
            <div class="alert-error"><i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
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
                <div class="form-divider"><i class="bi bi-person"></i> Informations Personnelles</div>        
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
                    <button type="button" class="btn-primary next-step">Suivant <i class="bi bi-arrow-right"></i></button>
                </div>
            </div>    

            <div class="step-section" data-step="2">
                <div class="form-divider"><i class="bi bi-briefcase"></i> Fonction dans l'entreprise</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Rôle <span class="required">*</span></label>
                        <select name="role" required>
                            <option value="">-- Sélectionner un rôle --</option>
                            <option value="ADMIN" <?= ($_POST['role'] ?? '') === 'ADMIN' ? 'selected' : '' ?>>Admin</option>
                            <option value="SUPERVISEUR" <?= ($_POST['role'] ?? '') === 'SUPERVISEUR' ? 'selected' : '' ?>>Superviseur</option>
                            <option value="AGENT" <?= ($_POST['role'] ?? '') === 'AGENT' ? 'selected' : '' ?>>Agent</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Site</label>
                        <select name="id_site">
                            <option value="">-- Aucun site --</option>
                            <?php foreach ($sites as $site): ?>
                                <option value="<?= (int)$site['id_site'] ?>" <?= ($_POST['id_site'] ?? '') === (string)$site['id_site'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($site['nom_site']) ?>
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
                    <button type="button" class="btn-secondary prev-step"><i class="bi bi-arrow-left"></i> Précédent</button>
                    <button type="button" class="btn-primary next-step">Suivant <i class="bi bi-arrow-right"></i></button>
                </div>
            </div>

            <!-- Section Documents -->
            <div class="step-section" data-step="3">
                <div class="form-divider"><i class="bi bi-paperclip"></i> Pièces Jointes</div>

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
                    <button type="button" class="btn-secondary prev-step"><i class="bi bi-arrow-left"></i> Précédent</button>
                    <button type="button" class="btn-primary next-step">Suivant <i class="bi bi-arrow-right"></i></button>
                </div>
            </div>    
            <!-- Section Mot de Passe -->
            <div class="step-section" data-step="4">
                <div class="form-divider"><i class="bi bi-lock"></i> Sécurité</div>

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
                    <button type="button" class="btn-secondary prev-step"><i class="bi bi-arrow-left"></i> Précédent</button>
                    <button type="submit" class="btn-primary"><i class="bi bi-check-circle"></i> Créer l'utilisateur</button>
                </div>

            </div>

        </form>
    </div>
</div>
<?php include_once __DIR__ . '/../includes/footer.php'; ?>
