<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/superviseur_sites.php';

if (($_SESSION['role'] ?? '') !== 'SUPERVISEUR') {
    header('Location: /index.php');
    exit;
}

$error = '';
$sites = get_supervisor_sites($pdo, (int) $_SESSION['id_user'], date('Y-m-d'));
$site_ids = array_column($sites, 'id_site');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Requête invalide (CSRF).';
    } else {
        $selected = (int) ($_POST['id_site'] ?? 0);
        if ($selected && in_array($selected, $site_ids, true)) {
            $_SESSION['id_site'] = $selected;
            header('Location: /index.php');
            exit;
        }
        $error = 'Sélection invalide. Veuillez choisir un site autorisé.';
    }
}
?>
<?php include_once __DIR__ . '/../includes/header.php'; ?>

<div class="site-select-page">
    <div class="site-select-hero">
        <div class="hero-text">
            <p class="hero-kicker">Espace superviseur</p>
            <h1>Choisissez le site à gérer</h1>
            <p class="hero-subtitle">Vous pouvez superviser plusieurs sites. Sélectionnez celui sur lequel vous souhaitez travailler maintenant.</p>
        </div>
        <div class="hero-actions">
            <a class="quick-link" href="/admin/view_profile.php"><i class="bi bi-person-circle"></i> Mon profil</a>
            <a class="quick-link quick-link-alt" href="/admin/pointage.php"><i class="bi bi-geo-alt"></i> Accès pointage</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="site-alert site-alert-error"><i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (empty($sites)): ?>
        <div class="site-empty">
            <div class="site-empty-icon"><i class="bi bi-buildings"></i></div>
            <h2>Aucun site attribué</h2>
            <p>Contactez l'administrateur pour vous attribuer un ou plusieurs sites.</p>
        </div>
    <?php else: ?>
        <div class="sites-grid">
            <?php foreach ($sites as $site): ?>
                <form method="post" class="site-card">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                    <input type="hidden" name="id_site" value="<?= (int) $site['id_site'] ?>">
                    <button class="site-card-btn" type="submit">
                        <div class="site-card-top">
                            <div class="site-icon"><?= strtoupper(substr($site['nom_site'], 0, 1)) ?></div>
                            <div>
                                <div class="site-name"><?= htmlspecialchars($site['nom_site']) ?></div>
                                <div class="site-meta"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($site['localisation'] ?? 'Localisation non définie') ?></div>
                            </div>
                        </div>
                        <p class="site-desc"><?= htmlspecialchars($site['description'] ?? 'Aucune description disponible.') ?></p>
                        <?php if (!empty($site['date_debut']) || !empty($site['date_fin'])): ?>
                            <div class="site-period">
                                <i class="bi bi-calendar-event"></i>
                                <span>
                                    <?= !empty($site['date_debut']) ? date('d/m/Y', strtotime($site['date_debut'])) : '—' ?>
                                    →
                                    <?= !empty($site['date_fin']) ? date('d/m/Y', strtotime($site['date_fin'])) : 'en cours' ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        <div class="site-cta">Gérer ce site <i class="bi bi-arrow-right"></i></div>
                    </button>
                </form>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
