<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';

if ($_SESSION['role'] !== 'ADMIN') {
    $_SESSION['flash_error'] = 'Accès réservé aux administrateurs.';
    header('Location: /nms-planning/'); exit;
}

function formatDateLongue($date) {
    $dateObj = new DateTime($date);
    $jours = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
    $mois = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
    return $jours[$dateObj->format('w')] . ' ' . $dateObj->format('d') . ' ' . $mois[$dateObj->format('n')] . ' ' . $dateObj->format('Y');
}


// 1. Logique de récupération identique pour préserver les fonctionnalités
$filtre_site = $_GET['site'] ?? '';
$filtre_type = $_GET['type'] ?? '';
$filtre_date = $_GET['date'] ?? date('Y-m-d');
$filtre_user = $_GET['user'] ?? '';

$sql = "SELECT p.*, u.prenom, u.nom, u.role, s.nom_site as site_nom 
        FROM pointages p
        LEFT JOIN users u ON p.id_user = u.id_user
        LEFT JOIN sites s ON p.id_site = s.id_site
        WHERE 1=1";
$params = [];

if ($filtre_date) { $sql .= " AND DATE(p.date_pointage) = ?"; $params[] = $filtre_date; }
if ($filtre_type) { $sql .= " AND p.type = ?"; $params[] = $filtre_type; }
if ($filtre_site) { $sql .= " AND p.id_site = ?"; $params[] = intval($filtre_site); }
if ($filtre_user) { $sql .= " AND (u.prenom LIKE ? OR u.nom LIKE ?)"; $params[] = '%' . $filtre_user . '%'; $params[] = '%' . $filtre_user . '%'; }

$sql .= " ORDER BY p.date_pointage DESC, p.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pointages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sites = $pdo->query("SELECT id_site, nom_site FROM sites ORDER BY nom_site")->fetchAll(PDO::FETCH_ASSOC);

// Calcul des statistiques pour les KPIs
$stats_normal = array_filter($pointages, fn($p) => $p['type'] === 'NORMAL');
$stats_urgence = array_filter($pointages, fn($p) => $p['type'] === 'URGENCE');
$total_minutes = 0;
foreach ($pointages as $p) {
    if ($p['heure_arrivee'] && $p['heure_depart']) {
        $d = new DateTime($p['heure_arrivee']); $f = new DateTime($p['heure_depart']);
        $total_minutes += ($f->diff($d)->h * 60) + $f->diff($d)->i;
    }
}
?>

<?php include_once __DIR__ . '/../includes/header.php'; ?>

<style>
    :root { --p-orange: #f39c12; --p-green: #27ae60; --p-red: #e74c3c; --p-blue: #3498db; --bg: #f8f9fa; }
    .page-wrapper { max-width: 1300px; margin: 0 auto; padding: 25px; font-family: 'Inter', sans-serif; background: var(--bg); }
    
    /* KPI Cards */
    .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border-bottom: 4px solid #ddd; transition: 0.3s; }
    .stat-card:hover { transform: translateY(-5px); }
    .stat-card.green { border-bottom-color: var(--p-green); }
    .stat-card.orange { border-bottom-color: var(--p-orange); }
    .stat-card.blue { border-bottom-color: var(--p-blue); }
    .stat-label { font-size: 0.85rem; color: #7f8c8d; text-transform: uppercase; font-weight: 600; }
    .stat-value { font-size: 1.8rem; font-weight: 800; color: #2c3e50; margin-top: 5px; }

    /* Filtres */
    .filter-box { background: white; padding: 20px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; align-items: flex-end; }
    .input-group label { display: block; font-size: 0.75rem; font-weight: 700; color: #34495e; margin-bottom: 6px; }
    .custom-input { width: 100%; padding: 10px; border: 1px solid #dfe6e9; border-radius: 8px; font-size: 0.9rem; }

    /* Tableau Stylisé */
    .table-container { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
    .modern-table { width: 100%; border-collapse: collapse; }
    .modern-table th { background: #f1f3f5; padding: 15px; text-align: left; font-size: 0.75rem; color: #4b5563; text-transform: uppercase; letter-spacing: 0.5px; }
    .modern-table td { padding: 16px 15px; border-bottom: 1px solid #f1f1f1; vertical-align: middle; font-size: 0.9rem; }
    .modern-table tr:hover { background-color: #fcfcfc; }

    /* Badges */
    .badge { padding: 5px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; }
    .badge-normal { background: #e8f5e9; color: var(--p-green); }
    .badge-urgence { background: #fff3e0; color: var(--p-orange); animation: pulse 2s infinite; }
    .role-tag { background: #f1f2f6; color: #576574; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; }
    
    @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.7; } 100% { opacity: 1; } }

    /* Conteneur de scroll pour mobile */
    .table-responsive-wrapper {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch; /* Scroll fluide sur iOS */
        border-radius: 12px;
    }

    /* Fixer une largeur minimale pour empêcher l'écrasement des colonnes */
    .modern-table {
        min-width: 800px; /* Force l'apparition du scroll si l'écran est plus petit */
        width: 100%;
    }

    /* Ajustements pour les écrans mobiles */
    @media (max-width: 768px) {
        .page-wrapper {
            padding: 10px;
        }
        
        h1 {
            font-size: 1.5rem;
        }
        .stats-row {
            grid-template-columns: 1fr; /* Les KPIs s'empilent sur mobile */
        }

        .filter-grid {
            grid-template-columns: 1fr; /* Les filtres s'empilent sur mobile */
        }
    }

    /* Conteneur pour le défilement horizontal sur mobile */
.table-container {
    background: white;
    border-radius: 12px;
    overflow-x: auto; /* Active le scroll horizontal */
    -webkit-overflow-scrolling: touch;
    box-shadow: 0 10px 25px rgba(0,0,0,0.05);
}

.modern-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 850px; /* Empêche les colonnes de s'écraser trop */
}

/* Ajustement de la grille de filtres pour mobile */
@media (max-width: 768px) {
    .filter-grid {
        grid-template-columns: 1fr !important; /* Une seule colonne sur petit écran */
    }
    
    .stats-row {
        grid-template-columns: 1fr !important;
    }
}
</style>

<div class="page-wrapper">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
        <h1 style="font-weight:800; color:#2c3e50; margin:0;">📊 Suivi Présence</h1>
        <span style="color:#95a5a6; font-size:0.9rem;"><?= date('d F Y') ?></span>
    </div>

    <div class="stats-row">
        <div class="stat-card green">
            <div class="stat-label">Pointages Normaux</div>
            <div class="stat-value"><?= count($stats_normal) ?></div>
        </div>
        <div class="stat-card orange">
            <div class="stat-label">Urgences Déclarées</div>
            <div class="stat-value"><?= count($stats_urgence) ?></div>
        </div>
        <div class="stat-card blue">
            <div class="stat-label">Heures Totales</div>
            <div class="stat-value"><?= intdiv($total_minutes, 60) ?>h <?= ($total_minutes % 60) ?>m</div>
        </div>
    </div>

    <div class="filter-box">
    <form method="get" class="filter-grid">
        <div class="input-group">
            <label>Date du jour</label>
            <input type="date" name="date" value="<?= htmlspecialchars($filtre_date) ?>" class="custom-input">
        </div>

        <div class="input-group">
            <label>Type de Pointage</label>
            <select name="type" class="custom-input">
                <option value="">Tous les types</option>
                <option value="NORMAL" <?= $filtre_type === 'NORMAL' ? 'selected' : '' ?>>Normal</option>
                <option value="URGENCE" <?= $filtre_type === 'URGENCE' ? 'selected' : '' ?>>Urgence</option>
            </select>
        </div>

        <div class="input-group">
            <label>Site</label>
            <select name="site" class="custom-input">
                <option value="">Tous les sites</option>
                <?php foreach ($sites as $site): ?>
                    <option value="<?= $site['id_site'] ?>" <?= $filtre_site == $site['id_site'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($site['nom_site']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="input-group">
            <label>Utilisateur</label>
            <input type="text" name="user" placeholder="Rechercher..." value="<?= htmlspecialchars($filtre_user) ?>" class="custom-input">
        </div>

        <div style="display:flex; gap:10px;">
            <button type="submit" style="flex:2; background:var(--p-blue); color:white; border:none; padding:10px; border-radius:8px; cursor:pointer; font-weight:bold;">Filtrer</button>
            <a href="/nms-planning/admin/gestion_pointages.php" style="flex:1; background:#eee; color:#666; text-align:center; padding:10px; border-radius:8px; text-decoration:none;">🔄</a>
        </div>
    </form>
</div>

    <div class="table-responsive-wrapper">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Collaborateur</th>
                    <th>Site & Mission</th>
                    <th>Type</th>
                    <th>Horaires</th>
                    <th>Durée</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pointages)): ?>
                    <tr><td colspan="6" style="text-align:center; padding:40px; color:#95a5a6;">Aucun enregistrement trouvé pour ces critères.</td></tr>
                <?php else: ?>
                    <?php foreach ($pointages as $p): ?>
                        <tr>
                            <td>
                                <div style="font-weight:700; color:#2c3e50;"><?= htmlspecialchars($p['prenom'] . ' ' . $p['nom']) ?></div>
                                <span class="role-tag"><?= htmlspecialchars($p['role']) ?></span>
                            </td>
                            <td>
                                <div style="font-size:0.85rem; font-weight:600;">📍 <?= htmlspecialchars($p['site_nom'] ?? 'N/A') ?></div>
                                <div style="font-size:0.75rem; color:#95a5a6;"><?= date('d/m/Y', strtotime($p['date_pointage'])) ?></div>
                            </td>
                            <td>
                                <?php if ($p['type'] === 'NORMAL'): ?>
                                    <span class="badge badge-normal">● Normal</span>
                                <?php else: ?>
                                    <span class="badge badge-urgence">🚨 Urgence</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex; gap:10px; font-family:monospace;">
                                    <span style="color:var(--p-green)">Arrivé: <?= $p['heure_arrivee'] ? substr($p['heure_arrivee'], 0, 5) : '--:--' ?></span>
                                    <span style="color:var(--p-red)">Sortie: <?= $p['heure_depart'] ? substr($p['heure_depart'], 0, 5) : '--:--' ?></span>
                                </div>
                            </td>
                            <td style="font-weight:700; color:#34495e;">
                                <?php 
                                if ($p['heure_arrivee'] && $p['heure_depart']) {
                                    $debut = new DateTime($p['heure_arrivee']); $fin = new DateTime($p['heure_depart']);
                                    $diff = $fin->diff($debut); echo $diff->h . 'h ' . $diff->i . 'm';
                                } else { echo '-'; }
                                ?>
                            </td>
                            <td>
                                <?php if ($p['type'] === 'URGENCE' && $p['motif_urgence']): ?>
                                    <button onclick="alert('Motif: <?= addslashes($p['motif_urgence']) ?>')" style="background:none; border:1px solid #ddd; padding:5px 10px; border-radius:5px; cursor:pointer; font-size:0.7rem;">Détails</button>
                                <?php else: ?>
                                    <span style="color:#ced4da;">---</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>