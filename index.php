<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/database.php';

// Récupérer les statistiques selon le rôle
if ($_SESSION['role'] === 'ADMIN') {
    $totalAgents = $pdo->query("SELECT COUNT(*) FROM users WHERE role='AGENT' AND actif=1")->fetchColumn();
    $totalSupervisors = $pdo->query("SELECT COUNT(*) FROM users WHERE role='SUPERVISEUR' AND actif=1")->fetchColumn();
    $totalSites = $pdo->query("SELECT COUNT(*) FROM sites")->fetchColumn();
    
    // Produits en alerte
    $produits_alertes = $pdo->query("SELECT COUNT(*) FROM produits WHERE quantite_actuelle < quantite_alerte")->fetchColumn();
    
    // Pointages du jour
    $pointages_today = $pdo->prepare('SELECT COUNT(*) FROM pointages WHERE date_pointage = ? AND heure_arrivee IS NOT NULL');
    $pointages_today->execute([date('Y-m-d')]);
    $pointages_count = $pointages_today->fetchColumn();

} elseif ($_SESSION['role'] === 'SUPERVISEUR') {
    $id_site = $_SESSION['id_site'];
    $agents_site = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role='AGENT' AND id_site = ?");
    $agents_site->execute([$id_site]);
    $agents_site_count = $agents_site->fetchColumn();
    
    // Plannings cette semaine
    $plannings_week = $pdo->prepare('
        SELECT COUNT(*) FROM programmations 
        WHERE id_site = ? 
        AND date_planning BETWEEN ? AND ?
    ');
    $plannings_week->execute([$id_site, date('Y-m-d', strtotime('monday this week')), date('Y-m-d', strtotime('sunday this week'))]);
    $plannings_count = $plannings_week->fetchColumn();

} else { // AGENT
    // Ses programmations
    $stmt = $pdo->prepare('
        SELECT COUNT(*) FROM programmations 
        WHERE id_agent = ? 
        AND date_planning >= ?
    ');
    $stmt->execute([$_SESSION['id_user'], date('Y-m-d')]);
    $mes_plannings = $stmt->fetchColumn();

    $pointages_today = $pdo->prepare('SELECT COUNT(*) FROM pointages WHERE  heure_arrivee IS NOT NULL AND id_user = ?');
    $pointages_today->execute([$_SESSION['id_user']]);
    $pointages_count = $pointages_today->fetchColumn();
}
?>

<?php include_once __DIR__ . '/includes/header.php'; ?>
<style>
    :root {
        --primary-grad: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
        --success-grad: linear-gradient(135deg, #4CAF50 0%, #388E3C 100%);
        --info-grad: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
        --danger-grad: linear-gradient(135deg, #FF5252 0%, #D32F2F 100%);
        --purple-grad: linear-gradient(135deg, #9C27B0 0%, #7B1FA2 100%);
        --glass-white: rgba(255, 255, 255, 0.9);
    }

    .dashboard-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    /* Header Moderne */
    .dashboard-header {
        background: var(--primary-grad);
        color: white;
        padding: 35px;
        border-radius: 20px;
        margin-bottom: 30px;
        box-shadow: 0 10px 20px rgba(245, 124, 0, 0.2);
        position: relative;
        overflow: hidden;
    }

    .dashboard-header::after {
        content: '🏢';
        position: absolute;
        right: -10px;
        bottom: -20px;
        font-size: 150px;
        opacity: 0.1;
    }

    .dashboard-header h1 {
        font-size: 28px;
        margin-bottom: 5px;
        font-weight: 800;
    }

    .user-badge {
        display: inline-flex;
        align-items: center;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(5px);
        padding: 8px 16px;
        border-radius: 50px;
        margin-top: 15px;
        font-weight: 600;
        font-size: 14px;
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    /* Grilles */
    .section-title {
        font-size: 18px;
        font-weight: 700;
        margin: 30px 0 15px;
        color: #444;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
    }

    /* Cartes de Stats */
    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 16px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
        gap: 20px;
        transition: transform 0.3s;
        border: 1px solid #eee;
    }

    .stat-card:hover { transform: translateY(-5px); }

    .stat-icon-box {
        width: 55px;
        height: 55px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .bg-orange { background: #FFF3E0; color: #FF9800; }
    .bg-green { background: #E8F5E9; color: #4CAF50; }
    .bg-blue { background: #E3F2FD; color: #2196F3; }
    .bg-red { background: #FFEBEE; color: #F44336; }

    .stat-info h3 { font-size: 24px; font-weight: 800; color: #333; margin: 0; }
    .stat-info p { font-size: 12px; color: #777; text-transform: uppercase; font-weight: 600; margin: 0; }

    /* Actions Rapides */
    .quick-actions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
    }

    .action-tile {
        background: white;
        padding: 20px;
        border-radius: 16px;
        text-align: center;
        text-decoration: none;
        color: #444;
        font-weight: 600;
        font-size: 13px;
        transition: 0.3s;
        border: 1px solid #eee;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
    }

    .action-tile i {
        font-size: 30px;
        transition: transform 0.3s;
    }

    .action-tile:hover {
        background: #fafafa;
        border-color: var(--primary);
        color: var(--primary-dark);
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }

    .action-tile:hover i { transform: scale(1.2); }

    /* Bouton Retour */
    .btn-back-container {
        margin-top: 40px;
        text-align: center;
    }

    .btn-back {
        background: #f1f1f1;
        color: #666;
        padding: 12px 30px;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        transition: 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }

    .btn-back:hover { background: #e0e0e0; color: #333; }

    /* Responsivité Mobile */
    @media (max-width: 600px) {
        .dashboard-header { padding: 20px; border-radius: 0; margin: -20px -20px 20px -20px; }
        .stats-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
        .stat-card { flex-direction: column; text-align: center; padding: 15px; gap: 10px; }
        .stat-icon-box { width: 45px; height: 45px; font-size: 20px; }
        .quick-actions-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
    }
</style>

<div class="dashboard-container">
    
    <div class="dashboard-header">
        <h1>Bonjour, <?= htmlspecialchars($_SESSION['prenom']) ?> <?= htmlspecialchars($_SESSION['nom']) ?> !</h1>
        <p>Heureux de vous revoir sur votre espace NMS Planning.</p>
        <div class="user-badge">
            <?php
                $badges = ['ADMIN' => '🔴 Administrateur', 'SUPERVISEUR' => '🟠 Superviseur', 'AGENT' => '🟢 Agent'];
                echo $badges[$_SESSION['role']] ?? '👤 Utilisateur';
            ?>
        </div>
    </div>

    <div class="section-title">⚡ Accès Rapide</div>
    <div class="quick-actions-grid">
        <?php if ($_SESSION['role'] === 'AGENT'): ?>
            <a href="/admin/pointage.php" class="action-tile"><i>📍</i><span>Présence</span></a>
            <a href="/planning/agent_schedule.php" class="action-tile"><i>📅</i><span>Mon Planning</span></a>
        <?php endif; ?>

        <?php if ($_SESSION['role'] === 'SUPERVISEUR'): ?>
            <a href="/admin/postes.php" class="action-tile"><i>👔</i><span>Gérer les Postes</span></a>
            <a href="/admin/pointage.php" class="action-tile"><i>📍</i><span>Présence</span></a>
            <a href="/planning/planning_superviseur.php" class="action-tile"><i>📅</i><span>Planning</span></a>
            <a href="/stock/manage_stock.php" class="action-tile"><i>📦</i><span>Stocks</span></a>
        <?php endif; ?>

        <?php if ($_SESSION['role'] === 'ADMIN'): ?>
            <a href="/admin/sites.php" class="action-tile"><i>🏢</i><span>Gérer les Sites</span></a>
            <a href="/admin/users.php" class="action-tile"><i>👥</i><span>Utilisateurs</span></a>
            <a href="/admin/gestion_pointages.php" class="action-tile"><i>📍</i><span>Pointages</span></a>
            <a href="/stock/gest_stock.php" class="action-tile"><i>📦</i><span>Stocks</span></a>
            <a href="/stock/alerts.php" class="action-tile"><i>⚠️</i><span>Alertes</span></a>
            <a href="/reports/inventory.php" class="action-tile"><i>📊</i><span>Rapports</span></a>
        <?php endif; ?>
        
        <a href="/admin/view_profile.php" class="action-tile"><i>👤</i><span>Mon Profil</span></a>
    </div>

    <div class="section-title">📊 Vos Statistiques</div>
    <div class="stats-grid">
        <?php if ($_SESSION['role'] === 'ADMIN'): ?>
            <div class="stat-card">
                <div class="stat-icon-box bg-orange">👥</div>
                <div class="stat-info"><p>Agents actifs</p><h3><?= $totalAgents ?></h3></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon-box bg-orange">👥</div>
                <div class="stat-info"><p>Superviseurs actifs</p><h3><?= $totalSupervisors ?></h3></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon-box bg-blue">🏢</div>
                <div class="stat-info"><p>Sites</p><h3><?= $totalSites ?></h3></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon-box bg-red">⚠️</div>
                <div class="stat-info"><p>Alertes</p><h3><?= $produits_alertes ?></h3></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon-box bg-green">✅</div>
                <div class="stat-info"><p>Pointages</p><h3><?= $pointages_count ?></h3></div>
            </div>

        <?php elseif ($_SESSION['role'] === 'SUPERVISEUR'): ?>
            <div class="stat-card">
                <div class="stat-icon-box bg-orange">👥</div>
                <div class="stat-info"><p>Mes Agents actifs</p><h3><?= $agents_site_count ?></h3></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon-box bg-blue">📅</div>
                <div class="stat-info"><p>Plannings/Sem.</p><h3><?= $plannings_count ?></h3></div>
            </div>

        <?php else: ?>
            <div class="stat-card">
                <div class="stat-icon-box bg-blue">📅</div>
                <div class="stat-info"><p>Mes Missions</p><h3><?= $mes_plannings ?></h3></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon-box bg-green">🕐</div>
                <div class="stat-info"><p>Pointages</p><h3><?= $pointages_count ?></h3></div>
            </div>
        <?php endif; ?>
    </div>

    <div class="btn-back-container">
        <a href="javascript:history.back()" class="btn-back">⬅️ Retour en arrière</a>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>