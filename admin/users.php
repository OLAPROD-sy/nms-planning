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
/*
<style>
    :root {
        --active: #4CAF50;
        --inactive: #9E9E9E;
    }

    .users-grid { max-width: 1300px; margin: 0 auto; padding: 20px; }

    /* Barre de recherche et Header */
    .controls-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        gap: 15px;
        flex-wrap: wrap;
    }

    .search-box {
        flex: 1;
        min-width: 300px;
        position: relative;
    }

    .search-box input {
        width: 100%;
        padding: 12px 20px 12px 45px;
        border-radius: 12px;
        border: 1px solid #ddd;
        font-size: 14px;
        transition: 0.3s;
    }

    .search-box::before {
        content: '🔍';
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        filter: grayscale(1);
    }

    /* Tableau Modernisé */
    .table-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        overflow: hidden;
    }

    table { width: 100%; border-collapse: collapse; }
    th { background: #f8f9fa; padding: 15px; text-align: left; font-size: 12px; text-transform: uppercase; color: #666; }
    td { padding: 15px; border-bottom: 1px solid #f1f1f1; vertical-align: middle; }

    /* Colonne Statut */
    .status-toggle {
        border: none;
        background: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        transition: 0.3s;
    }

    .status-active { background: #E8F5E9; color: var(--active); }
    .status-inactive { background: #F5F5F5; color: var(--inactive); }

    .dot { width: 8px; height: 8px; border-radius: 50%; background: currentColor; }

    /* Responsive : Transformation en cartes sur mobile */
    @media (max-width: 992px) {
        table, thead, tbody, th, td, tr { display: block; }
        thead { display: none; }
        tr { margin-bottom: 15px; border: 1px solid #eee; border-radius: 12px; padding: 10px; background: white; }
        td { display: flex; justify-content: space-between; align-items: center; border: none; padding: 8px 5px; text-align: right; }
        td::before { content: attr(data-label); font-weight: 700; color: #888; font-size: 12px; text-align: left; }
        .user-avatar { flex-direction: row-reverse; }
        .action-buttons { width: 100%; justify-content: flex-end; }
    }

    .btn-icon {
        padding: 8px; border-radius: 8px; border: none; cursor: pointer; transition: 0.2s;
    }


    /* Conteneur de la liste */
    .users-grid {
        display: grid;
        gap: 15px;
        padding: 15px;
    }

    /* Style de la Carte */
    .user-card {
        background: white;
        border-radius: 16px;
        padding: 15px;
        display: flex;
        align-items: center;
        gap: 15px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        border: 1px solid #f0f0f0;
        transition: transform 0.2s;
    }

    .user-card:active { transform: scale(0.98); }

    /* Avatar (Photo de profil) */
    .user-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        object-fit: cover;
        background: var(--primary);
        border: 2px solid white;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .user-info { flex: 1; }
    .user-name { font-weight: 700; color: var(--dark); font-size: 16px; margin-bottom: 2px; }
    .user-role { font-size: 12px; font-weight: 600; text-transform: uppercase; padding: 3px 8px; border-radius: 20px; display: inline-block; }

    /* Couleurs des badges de rôle */
    .role-admin { background: #ffebee; color: #f44336; }
    .role-superviseur { background: #fff3e0; color: #fb8c00; }
    .role-agent { background: #e8f5e9; color: #4caf50; }

    .user-meta { color: #888; font-size: 13px; margin-top: 4px; }

    /* Actions (Boutons Modifier/Supprimer) */
    .user-actions { display: flex; gap: 8px; }
    .btn-action {
        width: 35px; height: 35px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        text-decoration: none; font-size: 16px;
    }
    .btn-edit { background: #e3f2fd; color: #2196f3; }
    .btn-delete { background: #fff5f5; color: #ff5252; }

    /* Sur ordinateur : on peut mettre 2 ou 3 colonnes */
    @media (min-width: 768px) {
        .users-grid { grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); }
    }
    #userSearch:focus {
        border-color: var(--primary);
        box-shadow: 0 4px 12px rgba(255, 152, 0, 0.1);
        background-color: white;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(5px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* La pilule de statut */
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 20px;
        margin-top: 8px;
    }

    .status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
    }

    /* Couleurs État Actif */
    .status-online { background: #E8F5E9; color: #2E7D32; }
    .status-online .status-dot { background: #4CAF50; box-shadow: 0 0 5px #4CAF50; }

    /* Couleurs État Inactif */
    .status-offline { background: #F5F5F5; color: #757575; }
    .status-offline .status-dot { background: #9E9E9E; }

    /* Petit bouton discret sous Modifier/Supprimer */
    .btn-toggle-status {
        font-size: 10px;
        text-align: center;
        text-decoration: none;
        color: #666;
        background: #EEE;
        padding: 5px;
        border-radius: 6px;
        font-weight: 600;
        transition: 0.2s;
    }
    .btn-toggle-status:active { background: #DDD; }

    /* Header & Bouton Ajouter */
    .btn-add-modern {
        background: var(--dark);
        color: white;
        text-decoration: none;
        padding: 12px 18px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 700;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        transition: 0.3s;
    }
    .btn-add-modern:active { transform: scale(0.95); }

    .search-wrapper { position: relative; width: 100%; }
    .search-icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); opacity: 0.4; }
    #userSearch {
        width: 100%; padding: 15px 15px 15px 45px;
        border-radius: 16px; border: 1px solid #EEE;
        background: #FFF; font-size: 15px; outline: none;
    }

    /* Meta data (Téléphone et Site) */
    .user-details {
        margin-top: 8px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .detail-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        color: #666;
    }



</style>

<div class="page-header" style="padding: 25px 20px 10px 20px;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
        <div>
            <h1 style="font-size: 32px; font-weight: 800; color: var(--dark); letter-spacing: -1px; margin: 0;">Collaborateurs</h1>
            <p style="color: #666; font-size: 14px; margin-top: 5px;">Gérez les accès et les profils de l'équipe</p>
        </div>
        <a href="/admin/add_user.php" class="btn-add-modern">
            <span class="icon">➕</span>
            <span class="text">Ajouter</span>
        </a>
    </div>

    <div class="search-wrapper">
        <span class="search-icon">🔍</span>
        <input type="text" id="userSearch" placeholder="Rechercher un nom, un site ou un rôle...">
    </div>
</div>

<div class="users-grid">
    <?php foreach ($users as $u): ?>
        <div class="user-card">
            <div class="avatar-container">
                <?php if (!empty($u['photo'])): ?>
                    <img src="/<?= htmlspecialchars($u['photo']) ?>" class="user-avatar">
                <?php else: ?>
                    <div class="user-avatar" style="background: #e9ecef; display:flex; align-items:center; justify-content:center; color:#adb5bd;">
                        <?= strtoupper(substr($u['nom'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></div>
                <div class="user-role role-<?= strtolower($u['role']) ?>"><?= $u['role'] ?></div>
                
                <div class="user-details">
                    <div class="detail-item">
                        <span>📍</span> <?= htmlspecialchars($u['nom_site'] ?? 'Non assigné') ?>
                    </div>
                    <div class="detail-item">
                        <span>📞</span> <?= htmlspecialchars($u['contact'] ?? 'Non renseigné') ?>
                    </div>
                </div>

                <?php 
                    $isActive = ((int)$u['actif'] === 1);
                    $statusClass = $isActive ? 'status-online' : 'status-offline';
                    $statusText = $isActive ? 'Actif' : 'Inactif';
                ?>
                <div class="status-pill <?= $statusClass ?>">
                    <span class="status-dot"></span> <?= $statusText ?>
                </div>
            </div>

            <div class="user-actions-container">
                <div class="user-actions">
                    <a href="edit_user.php?id=<?= $u['id_user'] ?>" class="btn-action btn-edit">✏️</a>
                    <a href="delete_user.php?id=<?= $u['id_user'] ?>" 
                       class="btn-action btn-delete" 
                       onclick="return confirm('Supprimer définitivement <?= htmlspecialchars($u['prenom']) ?> ?')">
                       🗑️
                    </a>
                </div>
                <form method="POST" style="margin-top:5px;">
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
<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('userSearch');
    const userCards = document.querySelectorAll('.user-card');

    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();

        userCards.forEach(card => {
            // On récupère tout le texte de la carte (nom, rôle, site)
            const text = card.textContent.toLowerCase();
            
            if (text.includes(searchTerm)) {
                card.style.display = 'flex'; // On affiche
                card.style.animation = 'fadeIn 0.3s ease';
            } else {
                card.style.display = 'none'; // On cache
            }
        });
    });
});
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>