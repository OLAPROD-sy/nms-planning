<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/superviseur_sites.php';

$id_user = (int)($_GET['id'] ?? $_SESSION['id_user']);
$current_user = $_SESSION['id_user'];

// Vérification des droits d'accès
$stmt = $pdo->prepare('SELECT role FROM users WHERE id_user = ?');
$stmt->execute([$id_user]);
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);

// Un agent ne peut voir que son profil sauf superviseur/admin
if ($_SESSION['role'] === 'AGENT' && $id_user !== $current_user) {
    $_SESSION['flash_error'] = 'Accès refusé.';
    header('Location: /nms-planning/'); exit;
}

$stmt = $pdo->prepare('SELECT * FROM users WHERE id_user = ?');
$stmt->execute([$id_user]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['flash_error'] = 'Utilisateur introuvable.';
    header('Location: /'); exit;
}

// Calcul années d'expérience
$annees = '—';
$mois = '';
$experience = '—';
if (!empty($user['date_embauche'])) {
    try {
        $date_embauche = new DateTime($user['date_embauche']);
        $today = new DateTime();
        $interval = $today->diff($date_embauche);
        $annees = $interval->y;
        $mois = $interval->m;
        $experience = $annees . ' ans ' . $mois . ' mois';
    } catch (Exception $e) {
        $experience = '—';
    }
}

// Récupérer le(s) site(s)
$site_name = 'Non assigné';
if ($user['role'] === 'SUPERVISEUR') {
    $sites = get_supervisor_sites($pdo, (int) $user['id_user']);
    if (!empty($sites)) {
        $site_names = [];
        foreach ($sites as $s) {
            $site_names[] = htmlspecialchars($s['nom_site']);
        }
        $site_name = implode(', ', $site_names);
    } else {
        $site_name = 'Responsable des sites';
    }
} elseif ($user['id_site']) {
    $stmt = $pdo->prepare('SELECT nom_site FROM sites WHERE id_site = ?');
    $stmt->execute([$user['id_site']]);
    $site_result = $stmt->fetchColumn();
    $site_name = $site_result ? htmlspecialchars($site_result) : 'Non assigné';
}
?>
<?php include_once __DIR__ . '/../includes/header.php'; ?>



<div class="profile-wrapper">
    <div class="profile-hero">
        <div class="photo-container">
            <?php if ($user['photo']): ?>
                <img src="/<?= htmlspecialchars($user['photo']) ?>" class="profile-photo" alt="Photo">
            <?php else: ?>
                <div class="profile-photo" style="display:flex;align-items:center;justify-content:center;font-size:50px;background:#555;"><i class="bi bi-person"></i></div>
            <?php endif; ?>
        </div>
        <br>
        <div class="role-tag"><?= htmlspecialchars($user['role']) ?></div>
        <div></div>
    </div>

    <div class="divide"></div>

    <div class="profile-body">
        <h1 class="profile-main-name"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></h1>

        <div class="info-grid">
            <div class="info-tile">
                <span class="tile-icon"><i class="bi bi-envelope"></i></span>
                <span class="tile-label">Adresse Email</span>
                <span class="tile-value"><?= htmlspecialchars($user['email']) ?></span>
            </div>

            <div class="info-tile">
                <span class="tile-icon"><i class="bi bi-buildings"></i></span>
                <span class="tile-label"><?= $user['role'] === 'SUPERVISEUR' ? 'Sites supervisés' : 'Site Affecté' ?></span>
                <span class="tile-value"><?= $site_name ?></span>
            </div>

            <div class="info-tile">
                <span class="tile-icon"><i class="bi bi-calendar3"></i></span>
                <span class="tile-label">Membre depuis</span>
                <span class="tile-value"><?= $user['date_embauche'] ? date('d M Y', strtotime($user['date_embauche'])) : '—' ?></span>
            </div>

            <div class="info-tile" style="border-left: 4px solid var(--profile-primary);">
                <span class="tile-icon"><i class="bi bi-clock"></i></span>
                <span class="tile-label">Expérience</span>
                <span class="tile-value"><?= $experience ?></span>
            </div>
        </div>

        <div class="cv-card">
            <div class="cv-info">
                <div class="cv-icon"><i class="bi bi-file-earmark-text"></i></div>
                <div>
                    <h4 style="margin:0; color: #855b00;">Document Professionnel</h4>
                    <p style="margin:0; font-size:12px; color: #b38d42;">
                        <?= $user['cv'] ? 'Curriculum Vitae disponible' : 'Aucun CV enregistré' ?>
                    </p>
                </div>
            </div>
            <?php if ($user['cv']): ?>
                <a href="/<?= htmlspecialchars($user['cv']) ?>" target="_blank" class="btn-download">
                    <i class="bi bi-eye"></i> Consulter le CV
                </a>
            <?php endif; ?>
        </div>

        <div style="text-align: center;">
            <a href="javascript:history.back()" class="btn-return">
                <span><i class="bi bi-arrow-left"></i></span> Retour au panneau de contrôle
            </a>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
