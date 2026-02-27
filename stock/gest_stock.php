<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SESSION['role'] !== 'ADMIN') {
    header('Location: /');
    exit;
}

$message = "";

// --- LOGIQUE DE TRAITEMENT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // A. Ajouter un nouveau produit au catalogue Admin
    if ($action === 'creer_produit') {
        $nom = trim($_POST['nom_produit']);
        $seuil = intval($_POST['seuil'] ?? 5);
        
        $stmt = $pdo->prepare("INSERT INTO produits_admin (nom_produit, seuil_alerte) VALUES (?, ?)");
        $stmt->execute([$nom, $seuil]);
        $message = "Produit ajouté au catalogue personnel.";
    }

    // B. Gérer une Entrée ou une Sortie
    if ($action === 'mouvement') {
        $id_p = intval($_POST['id_produit']);
        $type = $_POST['type'];
        $qte = intval($_POST['quantite']);
        
        $operateur = ($type === 'ENTREE') ? "+" : "-";
        
        // Mise à jour du stock admin
        $pdo->prepare("UPDATE produits_admin SET quantite_globale = quantite_globale $operateur ? WHERE id_produit_admin = ?")
            ->execute([$qte, $id_p]);

        // Enregistrement du mouvement
        $pdo->prepare("INSERT INTO mouvements_stock_admin (id_produit_admin, type_mouvement, quantite, commentaire) VALUES (?, ?, ?, ?)")
            ->execute([$id_p, $type, $qte, $_POST['commentaire'] ?? '']);
            
        $message = "Stock mis à jour avec succès.";
    }
}


// --- LOGIQUE DE FILTRAGE (CORRIGÉE) ---
$where_clauses = [];
$params = [];

// Filtre Produit
if (isset($_GET['f_produit']) && $_GET['f_produit'] !== '') {
    $where_clauses[] = "m.id_produit_admin = ?";
    $params[] = (int)$_GET['f_produit'];
}

// Filtre Action (ENTREE / SORTIE)
if (isset($_GET['f_action']) && $_GET['f_action'] !== '') {
    $where_clauses[] = "m.type_mouvement = ?";
    $params[] = $_GET['f_action'];
}

// Filtre Date
if (isset($_GET['f_date']) && $_GET['f_date'] !== '') {
    $where_clauses[] = "DATE(m.date_mouvement) = ?";
    $params[] = $_GET['f_date'];
}

// Construction de la requête de base
$sql_flux = "SELECT m.*, p.nom_produit 
             FROM mouvements_stock_admin m 
             JOIN produits_admin p ON m.id_produit_admin = p.id_produit_admin";

// Ajout des conditions si elles existent
if (!empty($where_clauses)) {
    $sql_flux .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql_flux .= " ORDER BY m.date_mouvement DESC LIMIT 30";

// Exécution sécurisée
$stmt_flux = $pdo->prepare($sql_flux);
$stmt_flux->execute($params);
$flux = $stmt_flux->fetchAll(PDO::FETCH_ASSOC);

// --- LECTURE DES DONNÉES ---
$inventaire = $pdo->query("SELECT * FROM produits_admin ORDER BY nom_produit ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include_once __DIR__ . '/../includes/header.php'; ?>

<?php if ($message): ?>
<div id="temp-alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; background: #10b981; color: white; padding: 15px 25px; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); font-weight: bold; animation: slideIn 0.5s ease-out;">
    ✅ <?= $message ?>
</div>
<script>setTimeout(() => { document.getElementById('temp-alert').style.display = 'none'; }, 3000);</script>
<style>@keyframes slideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }</style>
<?php endif; ?>

<style>
    :root {
        --glass-bg: rgba(255, 255, 255, 0.95);
        --accent-gradient: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
        --shadow: 0 8px 30px rgba(0,0,0,0.08);
    }

    .admin-container { max-width: 1000px; margin: 20px auto; padding: 0 15px; }


    
    

    .grid-admin { display: grid; grid-template-columns: 1fr; gap: 20px; }

    @media (min-width: 1024px) { 
        .grid-admin { grid-template-columns: 350px 1fr; gap: 20px; }
    }

    .stock-card { background: white; border-radius: 18px; padding: 25px; box-shadow: var(--shadow); border: 1px solid #edf2f7; }

    /* --- CORRECTION DES BOUTONS --- */
    .user_profile_btn, .logout_desktop_btn {
        display: block !important;
        width: 100% !important;
        padding: 12px 20px !important;
        border-radius: 10px !important; /* Force un arrondi propre, pas ovale */
        font-weight: 700 !important;
        text-align: center !important;
        text-decoration: none !important;
        transition: all 0.3s ease;
        border: none !important;
        cursor: pointer;
    }

    /* --- AMÉLIORATION DES STATUTS --- */
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 50px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
    }
    .status-pill.ok { background: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; }
    .status-pill.low { 
        background: #fff1f2; color: #e11d48; border: 1px solid #fecaca; 
        animation: pulse-border 2s infinite; 
    }

    @keyframes pulse-border {
        0% { box-shadow: 0 0 0 0 rgba(225, 29, 72, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(225, 29, 72, 0); }
        100% { box-shadow: 0 0 0 0 rgba(225, 29, 72, 0); }
    }

    
        .table-nms tr:hover { background-color: #fcfcfd; }
    
        /* Boutons d'action */
        .user_profile_btn { background: var(--accent-gradient); color: white; }
        .user_profile_btn:hover { background: #f57c00; }
        .logout_desktop_btn { background: #e53e3e; color: white; }
        .logout_desktop_btn:hover { background: #c53030; }
    
        /* Amélioration de la barre de filtres */
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            background: #f8fafc;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            align-items: flex-end;
        }

    /* Header adaptable */
    .header-section {
        background: var(--accent-gradient);
        padding: 30px 20px;
        border-radius: 20px;
        color: white;
        margin-bottom: 25px;
        text-align: center; /* Centré par défaut pour mobile */
        box-shadow: 0 10px 20px rgba(255, 152, 0, 0.2);
    }



    /* Utilitaires de visibilité */
    .hide-mobile { display: none; }
    @media (min-width: 768px) { .hide-mobile { display: inline; } }

    /* Animation de l'alerte */
    .status-alert { background: #fee2e2; color: #b91c1c; padding: 6px 12px; border-radius: 50px; font-size: 11px; font-weight: 700; }
    .admin-container { max-width: 1400px; margin: 30px auto; padding: 0 20px; }
    .stock-card { background: white; border-radius: 18px; padding: 25px; box-shadow: var(--shadow); border: 1px solid #edf2f7; }
    .grid-admin { display: grid; grid-template-columns: 350px 1fr; gap: 25px; }
    
    .alert-low { background: #fff1f2; border: 1px solid #fda4af; color: #e11d48; padding: 2px 8px; border-radius: 6px; font-weight: 800; animation: pulse-alert 2s infinite; }
    @keyframes pulse-alert { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }

    .table-nms { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .table-nms th { text-align: left; padding: 12px; color: #94a3b8; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid #eee; }
    .table-nms td { padding: 15px 12px; border-bottom: 1px solid #f8fafc; }

    /* Style pour les cercles d'icônes de flux */
    .flow-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }
    .icon-entree { background: #dcfce7; color: #16a34a; }
    .icon-sortie { background: #fee2e2; color: #ef4444; }

    /* Badge de type épuré */
    .type-badge {
        font-size: 10px;
        font-weight: 800;
        padding: 3px 8px;
        border-radius: 4px;
        letter-spacing: 0.5px;
    }
    .type-entree { border: 1px solid #16a34a; color: #16a34a; }
    .type-sortie { border: 1px solid #ef4444; color: #ef4444; }

    /* Amélioration de la ligne au survol */
    .table-nms tr:hover { background-color: #fcfcfd; }

    @media (max-width: 1024px) { .grid-admin { grid-template-columns: 1fr; } }

    .filter-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    background: #f8fafc;
    padding: 15px;
    border-radius: 12px;
    margin-bottom: 20px;
    align-items: flex-end;
    }
    .filter-group { flex: 1; min-width: 150px; }
    .filter-group label { 
        display: block; font-size: 10px; font-weight: 800; color: #94a3b8; 
        margin-bottom: 5px; text-transform: uppercase; 
    }
    .filter-input {
        width: 100%; padding: 8px 12px; border-radius: 8px; 
        border: 1px solid #e2e8f0; font-size: 13px; font-weight: 600;
    }
    .btn-filter {
        background: var(--primary); color: white; border: none; 
        padding: 9px 15px; border-radius: 8px; cursor: pointer; font-weight: 700;
    }
    .btn-reset {
        background: #e2e8f0; color: #64748b; text-decoration: none;
        padding: 9px 15px; border-radius: 8px; font-size: 13px; font-weight: 700;
    }

    /* Conteneur de titre flexible */
    .card-header-flex {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
    }

    /* Adaptation mobile de la barre de filtres */
    @media (max-width: 768px) {
        .filter-bar {
            flex-direction: column; /* On empile les filtres */
            align-items: stretch;
        }
        
        .filter-group {
            width: 100%;
        }

        /* Groupe de boutons d'action sur mobile */
        .button-group-responsive {
            display: grid;
            grid-template-columns: 1fr 1fr; /* Deux colonnes pour les boutons */
            gap: 8px;
            width: 100%;
            margin-top: 10px;
        }

        .card-header-flex {
            flex-direction: column;
            align-items: flex-start;
        }

        .btn-export-mobile {
            width: 100%;
            justify-content: center;
        }

        
    }
</style>

<div class="admin-container">
    <div class="header-section">
        <h1 style="font-size: clamp(20px, 5vw, 32px); font-weight: 800; margin-bottom: 8px;">
            🛡️ Réserve Admin
        </h1>
        <p class="hide-mobile" style="opacity: 0.9; font-weight: 600;">
            Gestion confidentielle des fournitures.
        </p>
    </div>

    <div class="grid-admin">
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <div class="stock-card">
                <h3 style="margin-bottom:15px; font-size:16px;">✨ Nouveau au Catalogue</h3>
                <form method="post">
                    <input type="hidden" name="action" value="creer_produit">
                    <input type="text" name="nom_produit" placeholder="Nom du produit" required style="width:100%; padding:10px; margin-bottom:10px; border-radius:8px; border:1px solid #ddd;">
                    <input type="number" name="seuil" placeholder="Seuil d'alerte (ex: 5)" style="width:100%; padding:10px; margin-bottom:10px; border-radius:8px; border:1px solid #ddd;">
                    <button type="submit" class="user_profile_btn" style="width:100%; border:none; cursor:pointer;">Ajouter au catalogue</button>
                </form>
            </div>

            <div class="stock-card">
                <h3 style="margin-bottom:15px; font-size:16px;">🔄 Entrée / Sortie</h3>
                <form method="post">
                    <input type="hidden" name="action" value="mouvement">
                    <select name="id_produit" required style="width:100%; padding:10px; margin-bottom:10px; border-radius:8px; border:1px solid #ddd;">
                        <?php foreach($inventaire as $i): ?>
                            <option value="<?= $i['id_produit_admin'] ?>"><?= htmlspecialchars($i['nom_produit']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="type" style="width:100%; padding:10px; margin-bottom:10px; border-radius:8px; border:1px solid #ddd;">
                        <option value="ENTREE">Encaisser Stock (+)</option>
                        <option value="SORTIE">Décaisser Stock (-)</option>
                    </select>
                    <input type="number" name="quantite" placeholder="Quantité" required style="width:100%; padding:10px; margin-bottom:10px; border-radius:8px; border:1px solid #ddd;">
                    <button type="submit" class="logout_desktop_btn" style="width:100%; border:none; cursor:pointer; background:var(--info); color:white;">Valider l'opération</button>
                </form>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 20px;">
            <div class="stock-card">
                <div class="card-header-flex">
                    <h3 style="font-size: 16px; font-weight: 800; color: #1e293b;">📊 État actuel du stock</h3>
                    <a href="export_inventaire.php" class="btn-reset btn-export-mobile" style="background: #6366f1; color: white; padding: 8px 15px; font-size: 12px; border-radius: 8px; display: flex; align-items: center; gap: 8px; white-space: nowrap;">
                        <span>📥</span> <span class="hide-mobile">Exporter l'état</span><span class="show-mobile-only">Inventaire</span>
                    </a>
                </div>
                <div class="table-container">
                </div> 

                <table class="table-nms">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Quantité</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($inventaire as $i): 
                            $is_low = ($i['quantite_globale'] <= $i['seuil_alerte']);
                        ?>
                        <tr>
                            <td style="font-weight:700; color: #1e293b;"><?= htmlspecialchars($i['nom_produit']) ?></td>
                            <td>
                                <span style="font-size:18px; font-weight:800; background: #f1f5f9; padding: 4px 10px; border-radius: 8px;">
                                    <?= $i['quantite_globale'] ?>
                                </span>
                            </td>
                            <td>
                                <?php if($is_low): ?>
                                    <span class="status-pill low">⚠️ Stock Faible</span>
                                <?php else: ?>
                                    <span class="status-pill ok">✅ En Stock</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="stock-card">
                <h3 style="margin-bottom:15px;">
                    📜 Historique des flux
                    <span style="font-size:11px; font-weight:400; color:#94a3b8; background:#f1f5f9; padding:2px 8px; border-radius:10px;">Top 15</span>
                </h3>
    
                <form method="GET" action="" class="filter-bar">
                    <div class="filter-group">
                        <label>Produit</label>
                        <select name="f_produit" class="filter-input">
                            <option value="">Tous les produits</option>
                            <?php foreach($inventaire as $i): ?>
                                <option value="<?= $i['id_produit_admin'] ?>" <?= (isset($_GET['f_produit']) && $_GET['f_produit'] == $i['id_produit_admin']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($i['nom_produit']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Action</label>
                        <select name="f_action" class="filter-input">
                            <option value="">Toutes</option>
                            <option value="ENTREE" <?= (isset($_GET['f_action']) && $_GET['f_action'] == 'ENTREE') ? 'selected' : '' ?>>📥 Entrées</option>
                            <option value="SORTIE" <?= (isset($_GET['f_action']) && $_GET['f_action'] == 'SORTIE') ? 'selected' : '' ?>>📤 Sorties</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Date</label>
                        <input type="date" name="f_date" class="filter-input" value="<?= htmlspecialchars($_GET['f_date'] ?? '') ?>">
                    </div>

                    <div class="button-group-responsive">
                        <button type="submit" class="btn-filter" style="grid-column: span 2;">🔍 Filtrer</button>
                        <a href="admin_stock.php" class="btn-reset" style="text-align: center;">🧹 Vider</a>
                        <a href="export_stock.php?<?= $_SERVER['QUERY_STRING'] ?>" class="btn-reset" style="background: #10b981; color: white; text-align: center;">
                            📥 Excel
                        </a>
                    </div>
                </form>
                <div style="overflow-x: auto;">
                    <table class="table-nms">
                        <thead>
                            <tr>
                                <th>Date & Heure</th>
                                <th>Produit</th>
                                <th>Action</th>
                                <th style="text-align:right;">Quantité</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($flux as $f): 
                                $is_entree = ($f['type_mouvement'] == 'ENTREE');
                            ?>
                            <tr>
                                <td>
                                    <div style="font-weight:600; color:#1e293b; font-size:13px;"><?= date('d M Y', strtotime($f['date_mouvement'])) ?></div>
                                    <div style="font-size:11px; color:#94a3b8;"><?= date('H:i', strtotime($f['date_mouvement'])) ?></div>
                                </td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <div class="flow-icon <?= $is_entree ? 'icon-entree' : 'icon-sortie' ?>">
                                            <?= $is_entree ? '↓' : '↑' ?>
                                        </div>
                                        <span style="font-weight:700; color:#334155;"><?= htmlspecialchars($f['nom_produit']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="type-badge <?= $is_entree ? 'type-entree' : 'type-sortie' ?>">
                                        <?= $is_entree ? 'ENTRÉEE' : 'SORTIE' ?>
                                    </span>
                                </td>
                                <td style="text-align:right;">
                                    <span style="font-size:16px; font-weight:800; color: <?= $is_entree ? '#16a34a' : '#ef4444' ?>;">
                                        <?= $is_entree ? '+' : '-' ?><?= $f['quantite'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>