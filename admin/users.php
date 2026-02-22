<?php
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';

if ($_SESSION['role'] !== 'ADMIN') {
    $_SESSION['flash_error'] = 'Accès refusé.';
    header('Location: /nms-planning/index.php');
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
</style>

<div class="users-container">
    <div class="controls-row">
        <h1>👥 Collaborateurs</h1>
        <div class="search-box">
            <input type="text" id="userSearch" placeholder="Rechercher un nom, un email ou un rôle...">
        </div>
        <a href="/nms-planning/admin/add_users.php" class="btn-add" style="background: var(--primary); color:white; padding: 12px 20px; border-radius: 10px; text-decoration:none; font-weight:700;">
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
                            <a href="/nms-planning/admin/edit_users.php?id=<?= $u['id_user'] ?>" class="btn-action btn-edit">✏️</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer définitivement ?');">
                                <input type="hidden" name="id_user" value="<?= $u['id_user'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                <button type="submit" name="delete_user" class="btn-action btn-delete">🗑️</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Recherche en temps réel
    document.getElementById('userSearch').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('.user-row');
        
        rows.forEach(row => {
            let text = row.innerText.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>