<?php
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/superviseur_sites.php';

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
        $site_ids = $_POST['site_ids'] ?? [];
        $date_debut = $_POST['date_debut'] ?? [];
        $date_fin = $_POST['date_fin'] ?? [];
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
                    $assignments = [];
                    if ($role === 'SUPERVISEUR') {
                        $count = max(count((array) $site_ids), count((array) $date_debut), count((array) $date_fin));
                        for ($i = 0; $i < $count; $i++) {
                            $sid = (int) ($site_ids[$i] ?? 0);
                            $start = trim((string) ($date_debut[$i] ?? ''));
                            $end = trim((string) ($date_fin[$i] ?? ''));

                            if ($sid > 0 && $start !== '') {
                                if ($end !== '' && $end < $start) {
                                    $error = 'La date de fin doit être supérieure ou égale à la date de début.';
                                    break;
                                }
                                $assignments[] = [
                                    'id_site' => $sid,
                                    'date_debut' => $start,
                                    'date_fin' => $end !== '' ? $end : null,
                                ];
                            }
                        }

                        if ($error === '' && empty($assignments)) {
                            $error = 'Veuillez sélectionner au moins un site avec une date de début pour le superviseur.';
                        }
                        if ($error === '') {
                            $id_site = !empty($assignments) ? (int) $assignments[0]['id_site'] : null;
                        }
                    }
                    try {
                        $sql = "UPDATE users SET nom=?, prenom=?, username=?, contact=?, email=?, role=?, id_site=?, date_embauche=?, photo=?, cv=? " . 
                               ($password !== '' ? ", password=? " : "") . "WHERE id_user=?";
                        
                        $params = [$nom, $prenom, $username, $contact, $email, $role, $id_site, !empty($date_embauche) ? $date_embauche : null, $photo, $cv];
                        if ($password !== '') $params[] = password_hash($password, PASSWORD_BCRYPT);
                        $params[] = $id;

                        $pdo->prepare($sql)->execute($params);
                        if ($role === 'SUPERVISEUR') {
                            set_supervisor_sites($pdo, $id, $assignments ?? []);
                        } else {
                            set_supervisor_sites($pdo, $id, []);
                        }
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
$assignment_rows = [];
$posted_sites = $_POST['site_ids'] ?? [];
$posted_starts = $_POST['date_debut'] ?? [];
$posted_ends = $_POST['date_fin'] ?? [];
if (is_array($posted_sites) && (count($posted_sites) || count($posted_starts) || count($posted_ends))) {
    $count = max(count($posted_sites), count($posted_starts), count($posted_ends));
    for ($i = 0; $i < $count; $i++) {
        $assignment_rows[] = [
            'id_site' => (int) ($posted_sites[$i] ?? 0),
            'date_debut' => $posted_starts[$i] ?? '',
            'date_fin' => $posted_ends[$i] ?? '',
        ];
    }
} elseif (($user['role'] ?? '') === 'SUPERVISEUR') {
    $assignment_rows = get_supervisor_assignments($pdo, (int) $user['id_user']);
}
if (empty($assignment_rows)) {
    $assignment_rows[] = ['id_site' => 0, 'date_debut' => '', 'date_fin' => ''];
}
include_once __DIR__ . '/../includes/header.php';
?>


<div class="form-container">
    <div class="form-header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2><i class="bi bi-pencil-square"></i> Modifier l'utilisateur</h2>
                <p>Mise à jour de : <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></p>
            </div>
            <?php if($user['photo']): ?>
                <img src="/<?= htmlspecialchars($user['photo']) ?>" class="photo-thumb">
            <?php endif; ?>
        </div>
    </div>

    <div class="form-wrapper">
        <?php if ($error): ?>
            <div style="background:#FFEBEE; color:#C62828; padding:15px; border-radius:8px; margin-bottom:20px; display:flex; gap:8px; align-items:center;"><i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
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
                        <input type="text" name="contact" placeholder="0197000000" value="<?= htmlspecialchars($user['contact'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required value="<?= htmlspecialchars($user['email']) ?>">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-primary next-step">Suivant <i class="bi bi-arrow-right"></i></button>
                </div>
            </div>

            <div class="step-section" data-step="2">
                <div class="form-row">
                    <div class="form-group">
                        <label>Rôle</label>
                        <select name="role" required>
                            <option value="ADMIN" <?= $user['role']==='ADMIN'?'selected':'' ?>>Admin</option>
                            <option value="SUPERVISEUR" <?= $user['role']==='SUPERVISEUR'?'selected':'' ?>>Superviseur</option>
                            <option value="AGENT" <?= $user['role']==='AGENT'?'selected':'' ?>>Agent</option>
                        </select>
                    </div>
                    <div class="form-group" id="siteSingleGroup">
                        <label>Site</label>
                        <select name="id_site">
                            <option value="">-- Aucun site --</option>
                            <?php foreach ($sites as $site): ?>
                                <option value="<?= $site['id_site'] ?>" <?= $user['id_site']==$site['id_site']?'selected':'' ?>><?= htmlspecialchars($site['nom_site']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small>Pour les agents, choisissez un seul site.</small>
                    </div>
                    <div class="form-group" id="siteMultiGroup" style="display:none;">
                        <label>Sites du superviseur</label>
                        <div class="assignments-wrapper" id="assignmentsWrapper">
                            <?php foreach ($assignment_rows as $row): ?>
                                <div class="assignment-row">
                                    <select name="site_ids[]">
                                        <option value="">-- Sélectionner un site --</option>
                                        <?php foreach ($sites as $site): ?>
                                            <option value="<?= $site['id_site'] ?>" <?= (int)$site['id_site'] === (int)$row['id_site'] ? 'selected' : '' ?>><?= htmlspecialchars($site['nom_site']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="date" name="date_debut[]" value="<?= htmlspecialchars($row['date_debut'] ?? '') ?>">
                                    <input type="date" name="date_fin[]" value="<?= htmlspecialchars($row['date_fin'] ?? '') ?>" placeholder="Date fin (optionnel)">
                                    <button type="button" class="btn-remove-assignment">Retirer</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="assignment-row assignment-template" id="assignmentTemplate" style="display:none;">
                            <select name="site_ids[]">
                                <option value="">-- Sélectionner un site --</option>
                                <?php foreach ($sites as $site): ?>
                                    <option value="<?= $site['id_site'] ?>"><?= htmlspecialchars($site['nom_site']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="date" name="date_debut[]">
                            <input type="date" name="date_fin[]" placeholder="Date fin (optionnel)">
                            <button type="button" class="btn-remove-assignment">Retirer</button>
                        </div>
                        <button type="button" class="btn-add-assignment" id="addAssignmentBtn"><i class="bi bi-plus-circle"></i> Ajouter un site</button>
                        <small>La date de fin est optionnelle. Laissez vide si l'affectation est en cours.</small>
                    </div>
                </div>
                <div class="form-group">
                    <label>Date d'embauche</label>
                    <input type="date" name="date_embauche" value="<?= $user['date_embauche'] ?>">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-secondary prev-step"><i class="bi bi-arrow-left"></i> Précédent</button>
                    <button type="button" class="btn-primary next-step">Suivant <i class="bi bi-arrow-right"></i></button>
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
                    <button type="button" class="btn-secondary prev-step"><i class="bi bi-arrow-left"></i> Précédent</button>
                    <button type="button" class="btn-primary next-step">Suivant <i class="bi bi-arrow-right"></i></button>
                </div>
            </div>

            <div class="step-section" data-step="4">
                <div class="form-group">
                    <label>Changer le mot de passe</label>
                    <input type="password" name="password" placeholder="Laisser vide pour ne pas changer">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-secondary prev-step"><i class="bi bi-arrow-left"></i> Précédent</button>
                    <button type="submit" class="btn-primary"><i class="bi bi-check-circle"></i> Enregistrer les modifications</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
