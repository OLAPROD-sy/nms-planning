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


<div class="page-wrapper">
    <div class="hero-section">
        <h1 style="font-size: clamp(24px, 5vw, 40px); font-weight: 850; margin: 0;">Gestion des Sites</h1>
        <p style="opacity: 0.7; font-size: 16px; margin-top: 10px;">Centralisez et organisez vos lieux d'activité.</p>
        
        <div class="search-container">
            <span class="search-icon"><i class="bi bi-search"></i></span>
            <input type="text" id="siteSearch" class="search-input" placeholder="Rechercher un site, une ville...">
        </div>
    </div>

    <div class="action-card">
        <h3 style="margin: 0 0 25px 0; font-size: 18px;"><?= $editing ? '<i class="bi bi-pencil-square"></i> Modifier le site' : '<i class="bi bi-plus-circle"></i> Ajouter un site' ?></h3>
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
                                    <i class="bi bi-clock"></i> <?= substr($s['heure_debut_service'], 0, 5) ?>
                                </span>
                            </div>
                            
                            <div class="site-loc"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($s['localisation'] ?? 'Non définie') ?></div>
                            
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
                            <i class="bi bi-pencil-square"></i> Modifier
                        </button>
                    </form>

                    <form method="post" style="flex:1" onsubmit="return confirm('Attention : supprimer ce site peut impacter les pointages liés. Confirmer ?');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                        <input type="hidden" name="delete_site" value="<?= $id ?>">
                        <button type="submit" class="btn-icon" style="background: #fff1f2; color: #e11d48; border:none; width:100%;" title="Supprimer">
                            <i class="bi bi-trash3"></i> Effacer
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
    const term = e.target.value.trim().toLowerCase();
    const cards = document.querySelectorAll('.site-card');
    
    cards.forEach(card => {
        // On récupère le contenu directement dans le texte de la carte si l'attribut fail
        const cardText = card.innerText.toLowerCase();
        const searchableAttr = card.getAttribute('data-searchable') || "";
        
        if (cardText.includes(term) || searchableAttr.includes(term)) {
            // Au lieu de forcer 'flex', on retire juste le masquage
            card.style.display = ''; 
            card.style.opacity = '1';
        } else {
            card.style.display = 'none';
            card.style.opacity = '0';
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
