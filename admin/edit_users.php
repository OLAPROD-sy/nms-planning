<?php
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

// Vérifier que seul l'admin peut accéder
if ($_SESSION['role'] !== 'ADMIN') {
    $_SESSION['flash_error'] = 'Accès refusé.';
    header('Location: /index.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    $_SESSION['flash_error'] = 'Utilisateur non trouvé.';
    header('Location: /admin/users.php');
    exit;
}

// Récupérer l'utilisateur
$stmt = $pdo->prepare('SELECT * FROM users WHERE id_user = ?');
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['flash_error'] = 'Utilisateur non trouvé.';
    header('Location: /admin/users.php');
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
        $username = trim($_POST['username'] ?? '');
        $contact = trim($_POST['contact'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $id_site = !empty($_POST['id_site']) ? (int)$_POST['id_site'] : null;
        $date_embauche = trim($_POST['date_embauche'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($nom === '' || $prenom === '' || $email === '' || $username === '') {
            $error = 'Nom, prénom, identifiant et email sont requis.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email invalide.';
        } else {
            // Vérifier email unique
            $check = $pdo->prepare('SELECT id_user FROM users WHERE email = ? AND id_user != ?');
            $check->execute([$email, $id]);
            if ($check->rowCount() > 0) {
                $error = 'Cet email est déjà utilisé.';
            } else {
                $photo = $user['photo'];
                $cv = $user['cv'];

                // Traitement Photo
                if (!empty($_FILES['photo']['name'])) {
                    if ($user['photo'] && file_exists(__DIR__ . '/../' . $user['photo'])) unlink(__DIR__ . '/../' . $user['photo']);
                    $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                    $filename = time() . '_p' . bin2hex(random_bytes(4)) . '.' . $ext;
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $UPLOAD_DIR_PHOTOS . '/' . $filename)) {
                        $photo = 'uploads/photos/' . $filename;
                    }
                }

                // Traitement CV
                if ($error === '' && !empty($_FILES['cv']['name'])) {
                    if ($user['cv'] && file_exists(__DIR__ . '/../' . $user['cv'])) unlink(__DIR__ . '/../' . $user['cv']);
                    $filename = time() . '_cv' . bin2hex(random_bytes(4)) . '.pdf';
                    if (move_uploaded_file($_FILES['cv']['tmp_name'], $UPLOAD_DIR_CV . '/' . $filename)) {
                        $cv = 'uploads/cv/' . $filename;
                    }
                }

                if ($error === '') {
                    try {
                        $sql = "UPDATE users SET nom=?, prenom=?, username=?, contact=?, email=?, role=?, id_site=?, date_embauche=?, photo=?, cv=? " . 
                               ($password !== '' ? ", password=? " : "") . "WHERE id_user=?";
                        
                        $params = [$nom, $prenom, $username, $contact, $email, $role, $id_site, !empty($date_embauche) ? $date_embauche : null, $photo, $cv];
                        if ($password !== '') $params[] = password_hash($password, PASSWORD_BCRYPT);
                        $params[] = $id;

                        $pdo->prepare($sql)->execute($params);
                        $_SESSION['flash_success'] = 'Utilisateur mis à jour !';
                        header('Location: /admin/users.php');
                        exit;
                    } catch (PDOException $e) { $error = $e->getMessage(); }
                }
            }
        }
    }
}
$sites = $pdo->query('SELECT id_site, nom_site FROM sites ORDER BY nom_site')->fetchAll(PDO::FETCH_ASSOC);
include_once __DIR__ . '/../includes/header.php';
?>

<style>
    :root { --primary: #FF9800; }
    .form-container { max-width: 700px; margin: 30px auto; padding: 0 20px; }
    .form-header { background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%); color: white; padding: 30px; border-radius: 12px 12px 0 0; }
    .form-wrapper { background: white; border-radius: 0 0 12px 12px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); border: 1px solid #FFE0B2; }
    .form-group { margin-bottom: 22px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; }
    .form-group input, .form-group select { width: 100%; padding: 12px; border: 2px solid #E0E0E0; border-radius: 8px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .step-section { display: none; animation: fadeIn 0.4s ease; }
    .step-section.active { display: block; }
    .progress-container { margin-bottom: 30px; background: #f0f0f0; height: 8px; border-radius: 10px; position: relative; }
    .progress-bar { background: var(--primary); height: 100%; width: 25%; border-radius: 10px; transition: width 0.4s ease; }
    .steps-indicators { display: flex; justify-content: space-between; position: absolute; width: 100%; top: -12px; }
    .step-dot { width: 30px; height: 30px; background: white; border: 2px solid #ddd; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }
    .step-dot.active { border-color: var(--primary); color: var(--primary); }
    .btn-primary { background: var(--primary); color: white; padding: 12px 25px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
    .btn-secondary { background: #E0E0E0; color: #333; padding: 12px 25px; border-radius: 8px; text-decoration: none; font-weight: 600; }
    .form-actions { display: flex; gap: 15px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .photo-thumb { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary); }
</style>

<div class="form-container">
    <div class="form-header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2>✏️ Modifier l'utilisateur</h2>
                <p>Mise à jour de : <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></p>
            </div>
            <?php if($user['photo']): ?>
                <img src="/<?= htmlspecialchars($user['photo']) ?>" class="photo-thumb">
            <?php endif; ?>
        </div>
    </div>

    <div class="form-wrapper">
        <?php if ($error): ?>
            <div style="background:#FFEBEE; color:#C62828; padding:15px; border-radius:8px; margin-bottom:20px;">⚠️ <?= htmlspecialchars($error) ?></div>
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

            <div class="step-section active" data-step="1">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nom</label>
                        <input type="text" name="nom" required value="<?= htmlspecialchars($user['nom']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Prénom</label>
                        <input type="text" name="prenom" required value="<?= htmlspecialchars($user['prenom']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Identifiant</label>
                        <input type="text" name="username" required value="<?= htmlspecialchars($user['username']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Contact</label>
                        <input type="text" name="contact" value="<?= htmlspecialchars($user['contact'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required value="<?= htmlspecialchars($user['email']) ?>">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-primary next-step">Suivant ➡️</button>
                </div>
            </div>

            <div class="step-section" data-step="2">
                <div class="form-row">
                    <div class="form-group">
                        <label>Rôle</label>
                        <select name="role" required>
                            <option value="ADMIN" <?= $user['role']==='ADMIN'?'selected':'' ?>>🔴 Admin</option>
                            <option value="SUPERVISEUR" <?= $user['role']==='SUPERVISEUR'?'selected':'' ?>>🟠 Superviseur</option>
                            <option value="AGENT" <?= $user['role']==='AGENT'?'selected':'' ?>>🟢 Agent</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Site</label>
                        <select name="id_site">
                            <option value="">-- Aucun site --</option>
                            <?php foreach ($sites as $site): ?>
                                <option value="<?= $site['id_site'] ?>" <?= $user['id_site']==$site['id_site']?'selected':'' ?>>📍 <?= htmlspecialchars($site['nom_site']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Date d'embauche</label>
                    <input type="date" name="date_embauche" value="<?= $user['date_embauche'] ?>">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-secondary prev-step">⬅️ Précédent</button>
                    <button type="button" class="btn-primary next-step">Suivant ➡️</button>
                </div>
            </div>

            <div class="step-section" data-step="3">
                <div class="form-row">
                    <div class="form-group">
                        <label>Photo de profil</label>
                        <input type="file" name="photo" accept="image/*">
                        <small>Laissez vide pour conserver l'actuelle</small>
                    </div>
                    <div class="form-group">
                        <label>CV (PDF)</label>
                        <input type="file" name="cv" accept="application/pdf">
                        <small>Laissez vide pour conserver l'actuel</small>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-secondary prev-step">⬅️ Précédent</button>
                    <button type="button" class="btn-primary next-step">Suivant ➡️</button>
                </div>
            </div>

            <div class="step-section" data-step="4">
                <div class="form-group">
                    <label>Changer le mot de passe</label>
                    <input type="password" name="password" placeholder="Laisser vide pour ne pas changer">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-secondary prev-step">⬅️ Précédent</button>
                    <button type="submit" class="btn-primary">✅ Enregistrer les modifications</button>
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

    function updateStep(stepNumber) {
        steps.forEach(s => s.classList.remove('active'));
        dots.forEach((d, i) => d.classList.toggle('active', i < stepNumber));
        document.querySelector(`.step-section[data-step="${stepNumber}"]`).classList.add('active');
        progressBar.style.width = `${(stepNumber / steps.length) * 100}%`;
    }

    document.querySelectorAll('.next-step').forEach(btn => {
        btn.addEventListener('click', () => { if(currentStep < 4) { currentStep++; updateStep(currentStep); } });
    });

    document.querySelectorAll('.prev-step').forEach(btn => {
        btn.addEventListener('click', () => { if(currentStep > 1) { currentStep--; updateStep(currentStep); } });
    });
});
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>