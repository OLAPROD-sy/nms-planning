<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';

// --- LOGIQUE PHP CONSERVÉE ---

// Ajout poste
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_poste'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = 'Requête invalide.';
        header('Location: postes.php'); exit;
    }
    $lib = trim($_POST['libelle'] ?? '');
    if ($lib !== '') {
        $stmt = $pdo->prepare('INSERT INTO postes (libelle) VALUES (?)');
        $stmt->execute([$lib]);
        $_SESSION['flash_success'] = 'Le poste a été créé avec succès.';
        header('Location: postes.php'); exit;
    }
}

// Mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_poste'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = 'Requête invalide.';
        header('Location: postes.php'); exit;
    }
    $id = (int)($_POST['id_poste'] ?? 0);
    $lib = trim($_POST['libelle'] ?? '');
    if ($id > 0 && $lib !== '') {
        $stmt = $pdo->prepare('UPDATE postes SET libelle = ? WHERE id_poste = ?');
        $stmt->execute([$lib, $id]);
        $_SESSION['flash_success'] = 'Le poste a été mis à jour.';
        header('Location: postes.php'); exit;
    }
}

// Suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_poste'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = 'Requête invalide.';
        header('Location: postes.php'); exit;
    }
    $id = (int)($_POST['delete_poste'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare('DELETE FROM postes WHERE id_poste = ?');
        $stmt->execute([$id]);
        $_SESSION['flash_success'] = 'Poste supprimé définitivement.';
    }
    header('Location: postes.php'); exit;
}

$editing = false; $edit_poste = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_poste'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = 'Requête invalide.';
        header('Location: postes.php'); exit;
    }
    $id = (int)($_POST['edit_poste'] ?? 0);
    if ($id > 0) { $_SESSION['edit_poste_id'] = $id; header('Location: postes.php'); exit; }
}
if (isset($_SESSION['edit_poste_id'])) {
    $id = (int)$_SESSION['edit_poste_id']; unset($_SESSION['edit_poste_id']);
    $stmt = $pdo->prepare('SELECT * FROM postes WHERE id_poste = ?');
    $stmt->execute([$id]);
    $edit_poste = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($edit_poste) $editing = true;
}

$stmt = $pdo->query('SELECT * FROM postes ORDER BY libelle');
$postes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include_once __DIR__ . '/../includes/header.php'; ?>


<div class="page-wrapper">
    
    <?php if(isset($_SESSION['flash_success'])): ?>
        <div class="alert-toast" id="toast">✅ <?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?></div>
    <?php endif; ?>

    <div class="hero-admin">
        <h1 style="margin:0; font-weight: 850;">Gestion des Postes</h1>
        <p style="opacity: 0.7;">Définissez les rôles et positions de votre structure.</p>
        <div class="search-box">
            <span style="position:absolute; left:15px; top:12px; opacity:0.5;">🔍</span>
            <input type="text" id="searchInput" placeholder="Rechercher un poste...">
        </div>
    </div>

    <div class="action-card">
        <h3 style="margin:0 0 20px 0;"><?= $editing ? '✍️ Modifier le poste' : '➕ Nouveau poste' ?></h3>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
            <?php if($editing): ?>
                <input type="hidden" name="id_poste" value="<?= (int)$edit_poste['id_poste'] ?>">
            <?php endif; ?>

            <div style="display: flex; gap: 15px; align-items: flex-end;">
                <div style="flex-grow: 1;">
                    <label class="form-label">Libellé du poste</label>
                    <input type="text" name="libelle" class="input-modern" placeholder="Ex: Agent de Sécurité, Superviseur..." value="<?= $editing ? htmlspecialchars($edit_poste['libelle']) : '' ?>" required autofocus>
                </div>
                <button name="<?= $editing ? 'update_poste' : 'add_poste' ?>" class="btn-icon" style="background: var(--primary); color: white; padding: 12px 25px; font-weight: 700;">
                    <?= $editing ? 'Mettre à jour' : 'Enregistrer' ?>
                </button>
                <?php if($editing): ?>
                    <a href="postes.php" style="padding: 12px; color: #64748b; text-decoration:none; font-weight:600;">Annuler</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="postes-grid" id="postesContainer">
        <?php foreach ($postes as $p): 
             $id = (int)($p['id_poste'] ?? $p['id']);
        ?>
            <div class="poste-item" data-name="<?= strtolower(htmlspecialchars($p['libelle'])) ?>">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div class="poste-icon"><?= strtoupper(substr($p['libelle'], 0, 1)) ?></div>
                    <div>
                        <div style="font-weight: 700; color: #1e293b;"><?= htmlspecialchars($p['libelle']) ?></div>
                        <div style="font-size: 11px; color: #94a3b8;">ID: #<?= $id ?></div>
                    </div>
                </div>

                <div style="display: flex; gap: 5px;">
                    <form method="post" style="margin:0">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                        <input type="hidden" name="edit_poste" value="<?= $id ?>">
                        <button class="btn-icon" style="background: var(--primary-light); color: var(--primary);" title="Modifier">✏️</button>
                    </form>
                    <form method="post" style="margin:0" onsubmit="return confirm('Supprimer ce poste ?');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                        <input type="hidden" name="delete_poste" value="<?= $id ?>">
                        <button class="btn-icon" style="background: #fff1f2; color: var(--danger);" title="Supprimer">🗑️</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    // Recherche en temps réel
    document.getElementById('searchInput').addEventListener('input', function(e) {
        const val = e.target.value.toLowerCase();
        document.querySelectorAll('.poste-item').forEach(item => {
            const name = item.getAttribute('data-name');
            item.style.display = name.includes(val) ? 'flex' : 'none';
        });
    });

    // Auto-suppression alerte
    const toast = document.getElementById('toast');
    if(toast) {
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = '0.5s';
            setTimeout(() => toast.remove(), 500);
        }, 4000);
    }
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>