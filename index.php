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