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

    .users-container { max-width: 1300px; margin: 0 auto; padding: 20px; }

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


</style>

<div class="users-container">
    <div class="controls-row">
        <h1>👥 Collaborateurs</h1>
        <div class="search-box">
            <input type="text" id="userSearch" placeholder="Rechercher un nom, un email ou un rôle...">
        </div>
        <a href="/admin/add_users.php" class="btn-add" style="background: var(--primary); color:white; padding: 12px 20px; border-radius: 10px; text-decoration:none; font-weight:700;">
            + Nouveau
        </a>
    </div>

    <div class="table-card">
        <table id="userTable">
            <thead>
                <tr>
                    <th>Utilisateur</th>
                    <th>Rôle & Site</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): 
                    $isActive = (int)($u['actif'] ?? 1);
                    $initials = strtoupper(substr($u['prenom'], 0, 1) . substr($u['nom'], 0, 1));
                ?>
                <tr class="user-row">
                    <td data-label="Utilisateur">
                        <div class="user-avatar">
                            <div class="avatar-circle"><?= $initials ?></div>
                            <div>
                                <div class="user-full-name" style="font-weight:700;"><?= htmlspecialchars($u['prenom'].' '.$u['nom']) ?></div>
                                <div style="font-size:12px; color:#888;"><?= htmlspecialchars($u['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td data-label="Rôle & Site">
                        <span class="role-badge role-<?= strtolower($u['role']) ?>"><?= $u['role'] ?></span>
                        <div style="font-size:11px; margin-top:4px; color:#666;">📍 <?= $u['nom_site'] ?: 'Non assigné' ?></div>
                    </td>
                    <td data-label="Statut">
                        <form method="POST">
                            <input type="hidden" name="id_user" value="<?= $u['id_user'] ?>">
                            <input type="hidden" name="current_status" value="<?= $isActive ?>">
                            <button type="submit" name="toggle_status" class="status-toggle <?= $isActive ? 'status-active' : 'status-inactive' ?>">
                                <span class="dot"></span>
                                <?= $isActive ? 'ACTIF' : 'INACTIF' ?>
                            </button>
                        </form>
                    </td>
                    <td data-label="Actions">
                        <div class="action-buttons">
                            <a href="/edit_users.php?id=<?= $u['id_user'] ?>" class="btn-action btn-edit btn-icon">✏️</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer définitivement ?');">
                                <input type="hidden" name="id_user" value="<?= $u['id_user'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                <button type="submit" name="delete_user" class="btn-action btn-delete btn-icon">🗑️</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="controls-row">
        <h1>👥 Collaborateurs</h1>
        <div class="search-container" style="padding: 15px 20px;">
            <div style="position: relative;">
                <span style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); opacity: 0.5;">🔍</span>
                <input type="text" id="userSearch" placeholder="Rechercher un agent, un site ou un rôle..." 
                    style="width: 100%; padding: 14px 15px 14px 45px; border-radius: 14px; border: 1.5px solid #EEE; font-size: 16px; outline: none; transition: 0.3s;">
            </div>
        </div>
        <a href="/admin/add_users.php" class="btn-add" style="background: var(--primary); color:white; padding: 12px 20px; border-radius: 10px; text-decoration:none; font-weight:700;">
            + Nouveau
        </a>
</div>
<div class="users-grid">
    <?php foreach ($users as $u): ?>
        <div class="user-card">
            <?php if (!empty($u['photo'])): ?>
                <img src="/<?= htmlspecialchars($u['photo']) ?>" class="user-avatar">
            <?php else: ?>
                <div class="user-avatar" style="background: #CCC; display:flex; align-items:center; justify-content:center; color:white;">
                    <?= strtoupper(substr($u['nom'], 0, 1)) ?>
                </div>
            <?php endif; ?>

            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></div>
                <div class="user-role role-<?= strtolower($u['role']) ?>"><?= $u['role'] ?></div>
                
                <?php 
                    $isActive = ($u['actif'] === 1); // Adaptez selon votre colonne SQL
                    $statusClass = $isActive ? 'status-online' : 'status-offline';
                    $statusText = $isActive ? 'En service' : 'Inactif';
                ?>
                <div class="status-pill <?= $statusClass ?>">
                    <span class="status-dot"></span> <?= $statusText ?>
                </div>
            </div>

            <div class="user-actions-container" style="display:flex; flex-direction:column; gap:8px;">
                <div class="user-actions">
                    <a href="edit_user.php?id=<?= $u['id_user'] ?>" class="btn-action btn-edit">✏️</a>
                    <a href="delete_user.php?id=<?= $u['id_user'] ?>" 
                        class="btn-action btn-delete" 
                        onclick="return confirm('❗ Attention : Cette action est irréversible. Supprimer définitivement <?= htmlspecialchars($u['prenom']) ?> ?')">
                        🗑️
                    </a>
                </div>
                <a href="toggle_status.php?id=<?= $u['id_user'] ?>" class="btn-toggle-status">
                    <?= $isActive ? 'Désactiver' : 'Activer' ?>
                </a>
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