<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

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

// Récupérer le site
$site_name = 'Responsable des sites';
if ($user['id_site']) {
    $stmt = $pdo->prepare('SELECT nom_site FROM sites WHERE id_site = ?');
    $stmt->execute([$user['id_site']]);
    $site_result = $stmt->fetchColumn();
    $site_name = $site_result ? htmlspecialchars($site_result) : 'Responsable des sites';
}
?>
<?php include_once __DIR__ . '/../includes/header.php'; ?>


<style>
    :root {
        --profile-primary: #FF9800;
        --profile-secondary: #f3f4f6;
        --profile-text: #3b3a30;
    }

    .profile-wrapper {
        max-width: 900px;
        margin: 40px auto;
        padding: 0 20px;
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    /* Header Stylisé */
    .profile-hero {
        background: linear-gradient(135deg, #a4f906 0%, #444 100%);
        border-radius: 24px;
        padding: 50px 30px;
        text-align: center;
        color: white;
        position: relative;
        overflow: hidden;
        margin-bottom: -60px; /* Chevauchement */
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    }

    .profile-hero::before {
        content: '';
        position: absolute;
        top: -50%; left: -20%;
        width: 100%; height: 200%;
        background: radial-gradient(circle, rgba(255,152,0,0.2) 0%, transparent 70%);
    }

    .photo-container {
        position: relative;
        display: inline-block;
        z-index: 2;
    }

    .profile-photo {
        width: 140px;
        height: 140px;
        border-radius: 40px; /* Style moderne arrondi */
        border: 4px solid white;
        object-fit: cover;
        background: #eee;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }

    .role-tag {
        display: inline-block;
        margin-top: 15px;
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        padding: 6px 18px;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 1px;
        text-transform: uppercase;
        border: 1px solid rgba(255,255,255,0.2);
    }

    /* Contenu Principal */
    .profile-body {
        background: white;
        border-radius: 24px;
        padding: 80px 40px 40px; /* Padding top élevé pour le chevauchement */
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        z-index: 1;
        position: relative;
    }

    .profile-main-name {
        text-align: center;
        font-size: 32px;
        font-weight: 800;
        color: var(--profile-text);
        margin-bottom: 40px;
    }

    /* Grille d'infos */
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 25px;
        margin-bottom: 40px;
    }

    .info-tile {
        background: var(--profile-secondary);
        padding: 20px;
        border-radius: 18px;
        transition: 0.3s;
        border: 1px solid transparent;
    }

    .info-tile:hover {
        background: white;
        border-color: var(--profile-primary);
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    }

    .tile-icon { font-size: 24px; margin-bottom: 12px; display: block; }
    .tile-label { font-size: 11px; text-transform: uppercase; color: #6b7280; font-weight: 700; letter-spacing: 0.5px; }
    .tile-value { font-size: 15px; color: var(--profile-text); font-weight: 600; margin-top: 4px; display: block; }

    /* CV Section */
    .cv-card {
        background: #FFF9F0;
        border: 2px dashed #FF9800;
        border-radius: 18px;
        padding: 25px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 30px;
    }

    .cv-info { display: flex; align-items: center; gap: 15px; }
    .cv-icon { font-size: 35px; }

    .btn-download {
        background: var(--profile-primary);
        color: white;
        padding: 10px 20px;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 700;
        font-size: 14px;
        transition: 0.3s;
    }

    .btn-download:hover { background: #e68900; box-shadow: 0 5px 15px rgba(255,152,0,0.3); }

    .btn-return {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #6b7280;
        text-decoration: none;
        font-weight: 600;
        font-size: 14px;
        margin-top: 20px;
        transition: 0.3s;
    }

    .btn-return:hover { color: var(--profile-primary); }

    @media (max-width: 600px) {
        .profile-body { padding: 80px 20px 30px; }
        .info-grid { grid-template-columns: 1fr; }
        .cv-card { flex-direction: column; text-align: center; gap: 20px; }
    }
</style>

<div class="profile-wrapper">
    <div class="profile-hero">
        <div class="photo-container">
            <?php if ($user['photo']): ?>
                <img src="/<?= htmlspecialchars($user['photo']) ?>" class="profile-photo" alt="Photo">
            <?php else: ?>
                <div class="profile-photo" style="display:flex;align-items:center;justify-content:center;font-size:50px;background:#555;">👤</div>
            <?php endif; ?>
        </div>
        <br>
        <div class="role-tag"><?= htmlspecialchars($user['role']) ?></div>
    </div>

    <div class="divide"></div>

    <div class="profile-body">
        <h1 class="profile-main-name"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></h1>

        <div class="info-grid">
            <div class="info-tile">
                <span class="tile-icon">📧</span>
                <span class="tile-label">Adresse Email</span>
                <span class="tile-value"><?= htmlspecialchars($user['email']) ?></span>
            </div>

            <div class="info-tile">
                <span class="tile-icon">🏢</span>
                <span class="tile-label">Site Affecté</span>
                <span class="tile-value"><?= $site_name ?></span>
            </div>

            <div class="info-tile">
                <span class="tile-icon">📅</span>
                <span class="tile-label">Membre depuis</span>
                <span class="tile-value"><?= $user['date_embauche'] ? date('d M Y', strtotime($user['date_embauche'])) : '—' ?></span>
            </div>

            <div class="info-tile" style="border-left: 4px solid var(--profile-primary);">
                <span class="tile-icon">⏱️</span>
                <span class="tile-label">Expérience</span>
                <span class="tile-value"><?= $experience ?></span>
            </div>
        </div>

        <div class="cv-card">
            <div class="cv-info">
                <div class="cv-icon">📄</div>
                <div>
                    <h4 style="margin:0; color: #855b00;">Document Professionnel</h4>
                    <p style="margin:0; font-size:12px; color: #b38d42;">
                        <?= $user['cv'] ? 'Curriculum Vitae disponible' : 'Aucun CV enregistré' ?>
                    </p>
                </div>
            </div>
            <?php if ($user['cv']): ?>
                <a href="/<?= htmlspecialchars($user['cv']) ?>" target="_blank" class="btn-download">
                    👁️ Consulter le CV
                </a>
            <?php endif; ?>
        </div>

        <div style="text-align: center;">
            <a href="javascript:history.back()" class="btn-return">
                <span>←</span> Retour au panneau de contrôle
            </a>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>