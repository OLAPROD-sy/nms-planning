<?php
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if ($_SESSION['role'] !== 'ADMIN') {
    $_SESSION['flash_error'] = 'Accès refusé.';
    header('Location: /index.php');
    exit;
}

// Traitement de l'activation/désactivation (AJAX ou POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $id = (int)$_POST['id_user'];
    $new_status = (int)$_POST['current_status'] === 1 ? 0 : 1;
    
    $stmt = $pdo->prepare('UPDATE users SET actif = ? WHERE id_user = ?');
    $stmt->execute([$new_status, $id]);
    $_SESSION['flash_success'] = 'Statut mis à jour.';
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Récupération des utilisateurs classés par site
$stmt = $pdo->query('
    SELECT u.*, s.nom_site 
    FROM users u 
    LEFT JOIN sites s ON u.id_site = s.id_site 
    ORDER BY 
        CASE WHEN u.role = "ADMIN" THEN 0 ELSE 1 END, -- Admin en premier
        s.nom_site ASC, 
        u.nom ASC
');
$all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// On groupe les utilisateurs par "Entête de section"
$grouped_users = [];
foreach ($all_users as $u) {
    if ($u['role'] === 'ADMIN') {
        $section_name = "DIRECTION / RESPONSABLES";
    } else {
        $section_name = ($u['nom_site'] ?? 'SANS SITE ASSIGNE');
    }
    $grouped_users[$section_name][] = $u;
}

// ... (Gardez votre logique de suppression ici) ...

// Récupération avec colonne is_active
$users = $pdo->query('
    SELECT u.*, s.nom_site 
    FROM users u 
    LEFT JOIN sites s ON u.id_site = s.id_site 
    ORDER BY u.actif DESC, u.nom ASC
')->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include_once __DIR__ . '/../includes/header.php'; ?>


<div class="page-header" style="padding: 25px 20px 10px 20px;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
        <div>
            <h1 style="font-size: 32px; font-weight: 800; color: var(--dark); letter-spacing: -1px; margin: 0;">Collaborateurs</h1>
            <p style="color: #666; font-size: 14px; margin-top: 5px;">Gérez les accès et les profils de l'équipe</p>
        </div>
        <div class="header-actions">
            <a href="/admin/export_users_excel.php" class="btn-export-modern">
                <span class="icon"><i class="bi bi-file-earmark-excel"></i></span>
                <span class="text">Exporter Excel</span>
            </a>
            <a href="/admin/add_users.php" class="btn-add-modern">
                <span class="icon"><i class="bi bi-plus-circle"></i></span>
                <span class="text">Ajouter</span>
            </a>
        </div>
    </div>

    <div class="search-wrapper">
        <span class="search-icon"><i class="bi bi-search"></i></span>
        <input type="text" id="userSearch" placeholder="Rechercher un nom, un site ou un rôle...">
    </div>
</div>

<div class="users-grid">
    <div class="users-container">
        <?php foreach ($grouped_users as $section => $members): ?>
            <div class="site-section-wrapper" data-section-name="<?= htmlspecialchars($section) ?>">
                <div class="site-section-title">
                    <h2 style="font-size: 1.2rem; color: #444;">
                        <?php if ($section === 'DIRECTION / RESPONSABLES'): ?>
                            <i class="bi bi-star-fill"></i>
                        <?php else: ?>
                            <i class="bi bi-geo-alt"></i>
                        <?php endif; ?>
                        <?= htmlspecialchars($section) ?>
                    </h2>
                    <span style="background: #ddd; padding: 2px 8px; border-radius: 10px; font-size: 12px;"><?= count($members) ?> membre(s)</span>
                </div>

                <div class="users-grid">
                    <?php foreach ($members as $u): ?>
                        <div class="user-card" data-search="<?= strtolower($u['prenom'].' '.$u['nom'].' '.$u['role'].' '.($u['nom_site']??'')) ?>">
                            <div class="avatar-zone">
                                <?php if (!empty($u['photo'])): ?>
                                    <img src="/<?= htmlspecialchars($u['photo']) ?>" class="user-avatar">
                                <?php else: ?>
                                    <div class="user-avatar" style="display:flex; align-items:center; justify-content:center; background:#eee; color:#999; font-weight:bold;">
                                        <?= strtoupper(substr($u['nom'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="user-info">
                                <div class="user-name"><?= htmlspecialchars($u['prenom'].' '.$u['nom']) ?></div>
                                <div class="role-badge role-<?= strtolower($u['role']) ?>"><?= $u['role'] ?></div>
                                
                                <div class="user-details">
                                    <div class="detail-item"><span><i class="bi bi-geo-alt"></i></span> <?= htmlspecialchars($u['nom_site'] ?? 'Non assigné') ?></div>
                                    <div class="detail-item"><span><i class="bi bi-telephone"></i></span> <?= htmlspecialchars($u['contact'] ?? 'N/A') ?></div>
                                </div>

                                <?php $isActive = ((int)$u['actif'] === 1); ?>
                                <div class="status-pill <?= $isActive ? 'status-online' : 'status-offline' ?>">
                                    <span class="status-dot"></span> <?= $isActive ? 'Actif' : 'Inactif' ?>
                                </div>
                            </div>

                            <div class="user-actions-container">
                                <div class="action-group">
                                    <a href="edit_users.php?id=<?= $u['id_user'] ?>" class="btn-action btn-edit" title="Modifier"><i class="bi bi-pencil-square"></i></a>
                                    <a href="delete_user.php?id=<?= $u['id_user'] ?>" class="btn-action btn-delete" onclick="return confirm('Supprimer ?')" title="Supprimer"><i class="bi bi-trash3"></i></a>
                                </div>
                                <form method="POST">
                                    <input type="hidden" name="id_user" value="<?= $u['id_user'] ?>">
                                    <input type="hidden" name="current_status" value="<?= $u['actif'] ?>">
                                    <button type="submit" name="toggle_status" class="btn-toggle-status">
                                        <?= $isActive ? 'Désactiver' : 'Activer' ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('userSearch');
    const sections = document.querySelectorAll('.site-section-header');

    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();

        document.querySelectorAll('.site-section-header').forEach(section => {
            const grid = section.nextElementSibling; // La grille .users-grid
            const cards = grid.querySelectorAll('.user-card');
            let hasVisibleCards = false;

            cards.forEach(card => {
                const content = card.getAttribute('data-search-content');
                if (content.includes(searchTerm)) {
                    card.style.display = 'flex';
                    hasVisibleCards = true;
                } else {
                    card.style.display = 'none';
                }
            });

            // Si aucune carte n'est visible dans ce site, on cache aussi le titre du site
            section.style.display = hasVisibleCards ? 'flex' : 'none';
            grid.style.display = hasVisibleCards ? 'grid' : 'none';
        });
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('userSearch');
    
    // On cible les conteneurs de sections (Titre + Grille)
    const sections = document.querySelectorAll('.site-section-wrapper');

    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();

        sections.forEach(section => {
            const cards = section.querySelectorAll('.user-card');
            let hasVisibleCards = false;

            cards.forEach(card => {
                // On récupère l'attribut data-search que nous avons mis dans le HTML
                const searchText = card.getAttribute('data-search');
                
                if (searchText.includes(searchTerm)) {
                    card.style.display = 'flex'; // On affiche la carte
                    hasVisibleCards = true;
                } else {
                    card.style.display = 'none'; // On cache la carte
                }
            });

            // Si le site contient au moins un employé qui correspond, on affiche le titre
            // Sinon, on cache toute la section du site
            if (hasVisibleCards) {
                section.style.display = 'block';
            } else {
                section.style.display = 'none';
            }
        });
    });
});
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
