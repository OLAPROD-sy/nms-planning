<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Sécurité Admin
if ($_SESSION['role'] !== 'ADMIN') { header('Location: /'); exit; }

// Récupérer les sites avec le nombre d'agents par site
$sql = "SELECT s.*, COUNT(u.id_user) as nb_agents 
        FROM sites s 
        LEFT JOIN users u ON s.id_site = u.id_site 
        GROUP BY s.id_site";
$sites = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include_once __DIR__ . '/../includes/header.php'; ?>

<style>
    .sites-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    .site-card {
        background: white;
        border-radius: 15px;
        padding: 20px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        border-top: 5px solid #FF9800;
        transition: transform 0.3s ease;
    }
    .site-card:hover { transform: translateY(-5px); }
    .badge-agents {
        background: #E3F2FD;
        color: #1976D2;
        padding: 5px 12px;
        border-radius: 20px;
        font-weight: bold;
        font-size: 12px;
    }
</style>

<div class="admin-container" style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
        <h2>📍 Gestion des Sites</h2>
        <button class="user_profile_btn" style="width: auto; padding: 10px 20px;">+ Ajouter un site</button>
    </div>

    <div class="sites-grid">
        <?php foreach($sites as $site): ?>
            <div class="site-card">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <h3 style="margin: 0; color: #333;">Site ID: <?= $site['id_site'] ?></h3>
                    <span class="badge-agents"><?= $site['nb_agents'] ?> Agents</span>
                </div>
                <p style="color: #666; font-size: 14px; margin: 10px 0;">
                    <strong>Nom :</strong> <?= htmlspecialchars($site['nom_site'] ?? 'Non défini') ?>
                </p>
                <hr style="border: 0; border-top: 1px solid #eee; margin: 15px 0;">
                <div style="display: flex; gap: 10px;">
                    <a href="view_site.php?id=<?= $site['id_site'] ?>" style="flex: 1; text-align: center; font-size: 12px; color: #FF9800; text-decoration: none; font-weight: bold; border: 1px solid #FF9800; padding: 8px; border-radius: 8px;">Voir l'équipe</a>
                    <a href="edit_site.php?id=<?= $site['id_site'] ?>" style="flex: 1; text-align: center; font-size: 12px; color: #666; text-decoration: none; font-weight: bold; border: 1px solid #ddd; padding: 8px; border-radius: 8px;">Paramètres</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>