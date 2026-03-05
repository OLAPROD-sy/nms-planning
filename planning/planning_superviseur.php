<?php
require_once "../includes/auth_check.php";
require_once "../config/database.php";

if ($_SESSION['role'] !== 'SUPERVISEUR') {
    header("Location: /auth/login.php");
    exit();
}

$id_site = $_SESSION['id_site'];
$message = "";
$message_type = "success";

// --- 1. CRÉATION D'UNE SEMAINE ---
if (isset($_POST['create_week'])) {
    $date_debut = $_POST['date_debut'];
    $date_fin = date('Y-m-d', strtotime($date_debut . ' +6 days'));
    $check = $pdo->prepare("SELECT id_semaine FROM semaines WHERE date_debut = ? AND id_site = ?");
    $check->execute([$date_debut, $id_site]);

    if ($check->rowCount() == 0) {
        $insert = $pdo->prepare("INSERT INTO semaines (date_debut, date_fin, id_site) VALUES (?, ?, ?)");
        $insert->execute([$date_debut, $date_fin, $id_site]);
        $_SESSION['flash_success'] = 'Semaine créée avec succès.';
    } else {
        $message = "Cette semaine existe déjà.";
        $message_type = "error";
    }
}

if (isset($_POST['add_poste'])) {
    $libelle_poste = trim($_POST['libelle_poste']);
    if (!empty($libelle_poste)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO postes (libelle, id_site, description) VALUES (?, ?, 'Nouveau poste')");
            $stmt->execute([$libelle_poste, $id_site]);
            $_SESSION['flash_success'] = 'Poste ajouté avec succès.';
        } catch (PDOException $e) {
            $message = "Erreur lors de l'ajout du poste : " . $e->getMessage();
            $message_type = "error";
        }
    } else {
        $message = "Le nom du poste ne peut pas être vide.";
        $message_type = "error";
    }
}

// --- 2. ENREGISTREMENT DE LA PROGRAMMATION ---
if (isset($_POST['programmer'])) {
    $id_agent = (int)$_POST['id_agent'];
    $id_poste = $_POST['id_poste'];
    $id_semaine = $_POST['id_semaine'];
    $dates_selectionnees = $_POST['dates_planning'] ?? [];
    $heure_debut = $_POST['heure_debut'];
    $heure_fin = $_POST['heure_fin'];

    if (!empty($dates_selectionnees)) {
        try {
            foreach ($dates_selectionnees as $date_p) {
                // Correction du bug ENUM : On s'assure que le jour correspond aux valeurs attendues
                // Si votre base attend 'LUNDI', on traduit. Si elle attend 'Monday', on adapte.
                $jours_traduction = [
                    'Monday' => 'LUNDI', 'Tuesday' => 'MARDI', 'Wednesday' => 'MERCREDI',
                    'Thursday' => 'JEUDI', 'Friday' => 'VENDREDI', 'Saturday' => 'SAMEDI', 'Sunday' => 'DIMANCHE'
                ];
                $nom_jour_en = date('l', strtotime($date_p));
                $jour_final = $jours_traduction[$nom_jour_en];

                $check = $pdo->prepare("SELECT id_programmation FROM programmations WHERE id_agent = ? AND date_planning = ?");
                $check->execute([$id_agent, $date_p]);

                if ($check->rowCount() == 0) {
                    $insert = $pdo->prepare("INSERT INTO programmations 
                        (id_semaine, id_site, id_agent, id_poste, jour, heure_debut, heure_fin, date_planning, id_superviseur)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $insert->execute([$id_semaine, $id_site, $id_agent, $id_poste, $jour_final, $heure_debut, $heure_fin, $date_p, $_SESSION['id_user']]);
                }
            }
            $message = "Programmation enregistrée !";
        } catch (PDOException $e) {
            $message = "Erreur SQL : " . $e->getMessage();
            $message_type = "error";
        }
    }
}

// --- RÉCUPÉRATION DES DONNÉES ---
$semaines = $pdo->prepare("SELECT * FROM semaines WHERE id_site=? ORDER BY date_debut DESC");
$semaines->execute([$id_site]);
$semaines_list = $semaines->fetchAll(PDO::FETCH_ASSOC);

$agents = $pdo->prepare("SELECT id_user, prenom, nom FROM users WHERE role = 'AGENT' AND id_site = ? ORDER BY nom");
$agents->execute([$id_site]);
$agents = $agents->fetchAll(PDO::FETCH_ASSOC); // Utilisation de FETCH_ASSOC pour plus de clarté

$stmtPostes = $pdo->prepare("SELECT * FROM postes WHERE id_site=?");
$stmtPostes->execute([$id_site]);
$postes = $stmtPostes->fetchAll();

$stmtPlan = $pdo->prepare("SELECT p.*, u.nom, u.prenom, po.libelle as poste_nom 
    FROM programmations p 
    JOIN users u ON p.id_agent = u.id_user 
    JOIN postes po ON p.id_poste = po.id_poste 
    WHERE p.id_site = ? ORDER BY p.date_planning DESC LIMIT 15");
$stmtPlan->execute([$id_site]);
$recent_plannings = $stmtPlan->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include_once __DIR__ . '/../includes/header.php'; ?>


<div class="admin-grid">
    <div class="side-col">
        <div class="card">
            <h3>🆕 Créer une Semaine</h3>
            <form method="post">
                <div class="form-group">
                    <label>Lundi de la semaine</label>
                    <input type="date" name="date_debut" class="form-control" required>
                </div>
                <button name="create_week" class="btn-main btn-week">➕ Ajouter la semaine</button>
            </form>
        </div>

        <div class="card">
            <h3>🏢 Nouveau Poste</h3>
            <form method="post">
                <div class="form-group">
                    <input type="text" name="libelle_poste" class="form-control" placeholder="Nom du poste (ex: Accueil)" required>
                </div>
                <button name="add_poste" class="btn-main btn-week">➕ Ajouter le poste</button>
            </form>
        </div>

        <div class="card">
            <h3>👥 Agents du site</h3>
            <div style="max-height: 200px; overflow-y: auto;">
                <?php foreach($agents as $a): ?>
                    <div style="padding: 5px 0; border-bottom: 1px solid #eee; font-size: 0.9em;">
                        • <?= htmlspecialchars($a['nom'].' '.$a['prenom']) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="main-col">
        <div class="card">
            <h3>📅 Programmer un Agent</h3>
            <form method="post">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label>Semaine</label>
                        <select name="id_semaine" id="semSelect" class="form-control" required>
                            <option value="">-- Choisir --</option>
                            <?php foreach($semaines_list as $s): ?>
                                <option value="<?= $s['id_semaine'] ?>" data-start="<?= $s['date_debut'] ?>">
                                    Du <?= date('d/m', strtotime($s['date_debut'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>L'agent</label>
                        <select name="id_agent" class="form-control" required>
                            <option value="">-- Sélectionner un agent --</option>
                            <?php if (!empty($agents)): ?>
                                <?php foreach($agents as $a): ?>
                                    <option value="<?= $a['id_user'] ?>">
                                        <?= htmlspecialchars(strtoupper($a['nom']) . ' ' . $a['prenom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>Aucun agent trouvé pour ce site</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Poste</label>
                        <select name="id_poste" class="form-control" required>
                            <?php foreach($postes as $p): ?>
                                <option value="<?= $p['id_poste'] ?>"><?= $p['libelle'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <label>Jours de travail (cliquez pour sélectionner) :</label>
                <div class="week-grid" id="daysContainer">
                    <p style="color: #999; font-style: italic; font-size: 0.8em;">Sélectionnez une semaine...</p>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; align-items: end;">
                    <div class="form-group">
                        <label>Début</label>
                        <input type="time" name="heure_debut" class="form-control" value="08:00">
                    </div>
                    <div class="form-group">
                        <label>Fin</label>
                        <input type="time" name="heure_fin" class="form-control" value="18:00">
                    </div>
                    <div class="form-group">
                        <button name="programmer" class="btn-main btn-save">Enregistrer</button>
                    </div>
                </div>
            </form>
        </div>

        

        <div class="card">
            <h3>📋 Programmations Récentes</h3>
            <div class="table-container">
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>Agent</th>
                            <th>Jour & Date</th>
                            <th>Poste</th>
                            <th>Horaire</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_plannings as $p): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($p['nom'].' '.$p['prenom']) ?></strong></td>
                            <td>
                                <span style="color: var(--primary-orange); font-weight: bold;"><?= $p['jour'] ?></span><br>
                                <span class="badge-date"><?= date('d/m/Y', strtotime($p['date_planning'])) ?></span>
                            </td>
                            <td><?= htmlspecialchars($p['poste_nom']) ?></td>
                            <td><?= substr($p['heure_debut'],0,5) ?> - <?= substr($p['heure_fin'],0,5) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('semSelect').addEventListener('change', function() {
    const container = document.getElementById('daysContainer');
    const startStr = this.options[this.selectedIndex].getAttribute('data-start');
    if(!startStr) return;

    container.innerHTML = '';
    const startDate = new Date(startStr);
    const joursFr = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];

    for(let i=0; i<7; i++) {
        let current = new Date(startDate);
        current.setDate(startDate.getDate() + i);
        let iso = current.toISOString().split('T')[0];
        
        const div = document.createElement('div');
        div.className = 'day-option';
        div.innerHTML = `<input type="checkbox" name="dates_planning[]" value="${iso}">
                         <span>${joursFr[i]}</span><br><b>${current.getDate()}/${current.getMonth()+1}</b>`;
        
        div.onclick = function() {
            const cb = this.querySelector('input');
            cb.checked = !cb.checked;
            this.classList.toggle('selected', cb.checked);
        };
        container.appendChild(div);
    }
});
</script>
<?php include_once __DIR__ . '/../includes/footer.php'; ?>