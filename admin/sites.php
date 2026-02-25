<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';

if ($_SESSION['role'] !== 'ADMIN') {
    $_SESSION['flash_error'] = 'Accès réservé aux administrateurs.';
    header('Location: /'); exit;
}

// Ajout d'un site
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_site'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = 'Requête invalide.';
        header('Location: sites.php'); exit;
    }
    
    $nom = trim($_POST['nom_site'] ?? '');
    $localisation = trim($_POST['localisation'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $lat = $_POST['latitude'] ?: 0;
    $lng = $_POST['longitude'] ?: 0;
    $heure = $_POST['heure_debut_service'] ?: '08:00:00';

    if ($nom !== '') {
        $stmt = $pdo->prepare('INSERT INTO sites (nom_site, localisation, description, latitude, longitude, heure_debut_service) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$nom, $localisation, $description, $lat, $lng, $heure]);
        
        $_SESSION['flash_success'] = 'Site ajouté avec succès.';
        header('Location: sites.php'); exit;
    }
}

// Mise à jour d'un site
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_site'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = 'Requête invalide.';
        header('Location: sites.php'); exit;
    }

    $id = (int)($_POST['id_site'] ?? 0);
    $nom = trim($_POST['nom_site'] ?? '');
    $localisation = trim($_POST['localisation'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $lat = $_POST['latitude'] ?: 0;
    $lng = $_POST['longitude'] ?: 0;
    $heure = $_POST['heure_debut_service'] ?: '08:00:00';

    if ($id > 0 && $nom !== '') {
        $stmt = $pdo->prepare('UPDATE sites SET nom_site = ?, localisation = ?, description = ?, latitude = ?, longitude = ?, heure_debut_service = ? WHERE id_site = ?');
        $stmt->execute([$nom, $localisation, $description, $lat, $lng, $heure, $id]);
        
        $_SESSION['flash_success'] = 'Site mis à jour avec succès.';
        header('Location: sites.php'); exit;
    }
}

// ... (Garder le reste de la logique de suppression et d'édition inchangé) ...
// Suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_site'])) {
	if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
		$_SESSION['flash_error'] = 'Requête invalide.';
		header('Location: sites.php'); exit;
	}
	$id = (int)($_POST['delete_site'] ?? 0);
	if ($id > 0) {
		$stmt = $pdo->prepare('DELETE FROM sites WHERE id_site = ?');
		$stmt->execute([$id]);
		$_SESSION['flash_success'] = 'Site supprimé.';
	}
	header('Location: sites.php');
	exit;
}

// Edition via POST -> set session then redirect (évite id en GET)
$editing = false;
$edit_site = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_site'])) {
	if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
		$_SESSION['flash_error'] = 'Requête invalide.';
		header('Location: sites.php'); exit;
	}
	$id = (int)($_POST['edit_site'] ?? 0);
	if ($id > 0) {
		$_SESSION['edit_site_id'] = $id;
		header('Location: sites.php');
		exit;
	}
}


if (isset($_SESSION['edit_site_id'])) {
    $id = (int)$_SESSION['edit_site_id'];
    unset($_SESSION['edit_site_id']);
    $stmt = $pdo->prepare('SELECT * FROM sites WHERE id_site = ?');
    $stmt->execute([$id]);
    $edit_site = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($edit_site) $editing = true;
}

$stmt = $pdo->query('SELECT * FROM sites ORDER BY nom_site');
$sites = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include_once __DIR__ . '/../includes/header.php'; ?>

<style>
    /* Tes styles existants ici... */
    :root {
        --primary: #6366f1;
        --primary-dark: #4f46e5;
        --glass: rgba(255, 255, 255, 0.9);
    }

    .page-wrapper {
        max-width: 1100px;
        margin: 40px auto;
        padding: 0 20px;
        font-family: 'Inter', sans-serif;
    }

    /* --- HEADER ÉLÉGANT --- */
    .hero-section {
        background: linear-gradient(135deg, #ff7403 0%, #bfff00 100%);
        border-radius: 24px;
        padding: 60px 40px;
        color: white;
        margin-bottom: -50px; /* Chevauchement */
        position: relative;
        overflow: hidden;
        box-shadow: 0 20px 50px rgba(0,0,0,0.2);
    }

    .hero-section::after {
        content: "";
        position: absolute;
        top: -50%; right: -10%;
        width: 400px; height: 400px;
        background: radial-gradient(circle, rgba(99,102,241,0.2) 0%, transparent 70%);
        border-radius: 50%;
    }

    /* --- BARRE DE RECHERCHE --- */
    .search-container {
        position: relative;
        max-width: 500px;
        margin-top: 25px;
    }

    .search-input {
        width: 100%;
        padding: 15px 20px 15px 50px;
        border-radius: 14px;
        border: none;
        background: rgba(255,255,255,0.1);
        backdrop-filter: blur(10px);
        color: white;
        font-size: 16px;
        border: 1px solid rgba(255,255,255,0.1);
        transition: all 0.3s;
    }

    .search-input:focus {
        background: rgba(255,255,255,0.2);
        outline: none;
        box-shadow: 0 0 0 4px rgba(99,102,241,0.3);
    }

    .search-icon {
        position: absolute;
        left: 18px; top: 50%;
        transform: translateY(-50%);
        opacity: 0.5;
    }

    /* --- FORMULAIRE OVERLAY --- */
    .action-card {
        background: white;
        border-radius: 20px;
        padding: 35px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.08);
        border: 1px solid #f1f5f9;
        position: relative;
        z-index: 10;
        margin-bottom: 40px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
    }

    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .form-group label { font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; }

    .modern-input {
        padding: 12px;
        border-radius: 10px;
        border: 2px solid #f1f5f9;
        background: #f8fafc;
        transition: 0.3s;
    }

    .modern-input:focus { border-color: var(--primary); outline: none; background: white; }

    /* --- CARTES DE SITES --- */
    .sites-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 25px;
    }

    .site-card {
        background: white;
        border-radius: 20px;
        padding: 25px;
        border: 1px solid #f1f5f9;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .site-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
        border-color: var(--primary);
    }

    .site-header { display: flex; gap: 15px; margin-bottom: 15px; }
    .site-icon {
        width: 50px; height: 50px;
        background: #eef2ff;
        color: var(--primary);
        border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px; font-weight: 800;
    }

    .site-loc { font-size: 13px; color: #64748b; display: flex; align-items: center; gap: 5px; margin-top: 4px;}
    .site-desc { font-size: 14px; color: #475569; margin: 15px 0; line-height: 1.5; }

    .btn-group { display: flex; gap: 10px; border-top: 1px solid #f1f5f9; padding-top: 15px; }
    .btn-icon {
        padding: 10px; border-radius: 10px; border: none; cursor: pointer;
        transition: 0.2s; flex: 1; display: flex; justify-content: center;
    }

    @media (max-width: 768px) {
        .hero-section { padding: 40px 20px; text-align: center; }
        .search-container { margin: 20px auto 0; }
    }
    .geo-badge { font-size: 11px; background: #f1f5f9; padding: 2px 8px; border-radius: 6px; color: #475569; font-family: monospace; }
</style>

<div class="page-wrapper">
    <div class="hero-section">
        <h1 style="font-size: clamp(24px, 5vw, 40px); font-weight: 850; margin: 0;">Gestion des Sites</h1>
        <p style="opacity: 0.7; font-size: 16px; margin-top: 10px;">Centralisez et organisez vos lieux d'activité.</p>
        
        <div class="search-container">
            <span class="search-icon">🔍</span>
            <input type="text" id="siteSearch" class="search-input" placeholder="Rechercher un site, une ville...">
        </div>
    </div>

    <div class="action-card">
        <h3 style="margin: 0 0 25px 0; font-size: 18px;"><?= $editing ? '✍️ Modifier le site' : '➕ Ajouter un site' ?></h3>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
            <?php if($editing): ?><input type="hidden" name="id_site" value="<?= $edit_site['id_site'] ?>"><?php endif; ?>
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Nom du site</label>
                    <input type="text" name="nom_site" class="modern-input" placeholder="Ex: Bureau Central" value="<?= $editing ? htmlspecialchars($edit_site['nom_site']) : '' ?>" required>
                </div>
                <div class="form-group">
                    <label>Localisation</label>
                    <input type="text" name="localisation" class="modern-input" placeholder="Ex: Paris, FR" value="<?= $editing ? htmlspecialchars($edit_site['localisation'] ?? '') : '' ?>">
                </div>
                <div class="form-group">
                    <label>Début de Service</label>
                    <input type="time" name="heure_debut_service" class="modern-input" placeholder="08:00" value="<?= $editing ? htmlspecialchars($edit_site['heure_debut_service'] ?? '') : '' ?>">
                </div>
            </div>

            <div class="form-grid" style="margin-top: 20px;">
                <div class="form-group">
                    <label>Latitude</label>
                    <input type="number" step="any" name="latitude" class="modern-input" placeholder="Ex: 6.3702" value="<?= $editing ? htmlspecialchars($edit_site['latitude'] ?? '') : '' ?>">
                </div>
                <div class="form-group">
                    <label>Longitude</label>
                    <input type="number" step="any" name="longitude" class="modern-input" placeholder="Ex: 2.4407" value="<?= $editing ? htmlspecialchars($edit_site['longitude'] ?? '') : '' ?>">
                </div>
            </div>
            
            <div class="form-group" style="margin-top: 20px;">
                <label>Description</label>
                <textarea name="description" class="modern-input" style="height: 80px; resize: none;" placeholder="Bref descriptif..."><?= $editing ? htmlspecialchars($edit_site['description'] ?? '') : '' ?></textarea>
            </div>

            <div style="margin-top: 25px; display: flex; gap: 12px;">
                <button name="<?= $editing ? 'update_site' : 'add_site' ?>" class="btn-icon" style="background: var(--primary); color: white; font-weight: 700; flex: none; padding: 12px 30px;">
                    <?= $editing ? 'Mettre à jour' : 'Enregistrer le site' ?>
                </button>
                <?php if($editing): ?>
                    <a href="sites.php" class="btn-icon" style="background: #f1f5f9; color: #64748b; text-decoration: none; flex: none; padding: 12px 30px;">Annuler</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

   <div class="sites-grid" id="sitesList">
        <?php foreach ($sites as $s): 
            $id = (int)($s['id_site'] ?? $s['id']);
        ?>
            <div class="site-card" data-searchable="<?= strtolower(htmlspecialchars($s['nom_site'] . ' ' . ($s['localisation'] ?? '') . ' ' . ($s['description'] ?? ''))) ?>">
                <div>
                    <div class="site-header">
                        <div class="site-icon"><?= strtoupper(substr($s['nom_site'], 0, 1)) ?></div>
                        
                        <div style="flex:1">
                            <div style="font-weight: 800; color: #0f172a; font-size: 18px; display: flex; justify-content: space-between; align-items: center;">
                                <?= htmlspecialchars($s['nom_site']) ?>
                                <span style="font-size: 12px; color: var(--primary); background: #eef2ff; padding: 2px 8px; border-radius: 5px; font-weight: 700;">
                                    🕒 <?= substr($s['heure_debut_service'], 0, 5) ?>
                                </span>
                            </div>
                            
                            <div class="site-loc">📍 <?= htmlspecialchars($s['localisation'] ?? 'Non définie') ?></div>
                            
                            <div style="margin-top: 8px; display: flex; gap: 5px; flex-wrap: wrap;">
                                <span class="geo-badge">Lat: <?= htmlspecialchars($s['latitude'] ?? '0') ?></span>
                                <span class="geo-badge">Lng: <?= htmlspecialchars($s['longitude'] ?? '0') ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <p class="site-desc"><?= nl2br(htmlspecialchars($s['description'] ?? 'Aucune description disponible.')) ?></p>
                </div>

                <div class="btn-group">
                    <form method="post" style="flex:1">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                        <input type="hidden" name="edit_site" value="<?= $id ?>">
                        <button type="submit" class="btn-icon" style="background: #eef2ff; color: var(--primary); border:none; width:100%; font-weight:600;" title="Modifier">
                            ✏️ Modifier
                        </button>
                    </form>

                    <form method="post" style="flex:1" onsubmit="return confirm('Attention : supprimer ce site peut impacter les pointages liés. Confirmer ?');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                        <input type="hidden" name="delete_site" value="<?= $id ?>">
                        <button type="submit" class="btn-icon" style="background: #fff1f2; color: #e11d48; border:none; width:100%;" title="Supprimer">
                            🗑️ Effacer
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<script>
    // --- RECHERCHE TEMPS RÉEL ---
    document.getElementById('siteSearch').addEventListener('input', function(e) {
        const term = e.target.value.toLowerCase();
        const cards = document.querySelectorAll('.site-card');
        
        cards.forEach(card => {
            const content = card.getAttribute('data-searchable');
            if(content.includes(term)) {
                card.style.display = 'flex';
                card.style.animation = 'fadeIn 0.3s ease';
            } else {
                card.style.display = 'none';
            }
        });
    });

    // Animation de toast (si présent)
    const toast = document.querySelector('.alert-toast');
    if(toast) {
        setTimeout(() => toast.remove(), 4000);
    }
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>