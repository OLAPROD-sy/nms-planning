<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

if (($_SESSION['role'] ?? '') !== 'ADMIN') {
    exit('Accès refusé');
}

$sql = "
    SELECT
        u.id_user,
        u.nom,
        u.prenom,
        u.username,
        u.email,
        u.role,
        u.contact,
        u.id_site,
        s.nom_site,
        u.date_embauche,
        u.actif,
        u.photo,
        u.cv,
        u.created_at
    FROM users u
    LEFT JOIN sites s ON s.id_site = u.id_site
    ORDER BY
        CASE
            WHEN u.role = 'ADMIN' THEN 0
            WHEN u.role = 'SUPERVISEUR' THEN 1
            ELSE 2
        END,
        s.nom_site ASC,
        u.nom ASC,
        u.prenom ASC
";

$stmt = $pdo->query($sql);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$count_total = count($users);
$count_admin = 0;
$count_superviseur = 0;
$count_agent = 0;
$count_actifs = 0;
$count_inactifs = 0;

foreach ($users as $u) {
    if ($u['role'] === 'ADMIN') {
        $count_admin++;
    } elseif ($u['role'] === 'SUPERVISEUR') {
        $count_superviseur++;
    } else {
        $count_agent++;
    }

    if ((int) $u['actif'] === 1) {
        $count_actifs++;
    } else {
        $count_inactifs++;
    }
}

$filename = 'Export_Utilisateurs_' . date('d-m-Y_H-i') . '.xls';
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style>
<?= file_get_contents(__DIR__ . '/../assets/css/pages/admin/export_users_excel.css'); ?>
</style>

<table border="1" class="title-table">
    <tr>
        <th colspan="14" class="report-title">ANNUAIRE UTILISATEURS - NMS PLANNING</th>
    </tr>
    <tr>
        <td colspan="14" class="report-meta">
            Généré le <?= date('d/m/Y à H:i') ?> | Export administrateur
        </td>
    </tr>
</table>

<br>

<table border="1" class="summary-table">
    <tr>
        <th>Total</th>
        <th>Admins</th>
        <th>Superviseurs</th>
        <th>Agents</th>
        <th>Actifs</th>
        <th>Inactifs</th>
    </tr>
    <tr>
        <td><?= $count_total ?></td>
        <td><?= $count_admin ?></td>
        <td><?= $count_superviseur ?></td>
        <td><?= $count_agent ?></td>
        <td><?= $count_actifs ?></td>
        <td><?= $count_inactifs ?></td>
    </tr>
</table>

<br>

<table border="1" class="users-table">
    <thead>
        <tr>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Username</th>
            <th>Email</th>
            <th>Rôle</th>
            <th>Contact</th>
            <th>Site</th>
            <th>Date embauche</th>
            <th>Créé le</th>
            <th>Statut</th>
            <th>Photo</th>
            <th>CV</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $u): ?>
            <?php
            $status_label = ((int) $u['actif'] === 1) ? 'ACTIF' : 'INACTIF';
            $status_class = ((int) $u['actif'] === 1) ? 'status-active' : 'status-inactive';

            $role_class = 'role-agent';
            if ($u['role'] === 'ADMIN') {
                $role_class = 'role-admin';
            } elseif ($u['role'] === 'SUPERVISEUR') {
                $role_class = 'role-superviseur';
            }
            ?>
            <tr>
                <td><?= htmlspecialchars($u['nom'] ?? '') ?></td>
                <td><?= htmlspecialchars($u['prenom'] ?? '') ?></td>
                <td><?= htmlspecialchars($u['username'] ?? '') ?></td>
                <td><?= htmlspecialchars($u['email'] ?? '') ?></td>
                <td class="<?= $role_class ?> ta-center"><?= htmlspecialchars($u['role'] ?? '') ?></td>
                <td><?= htmlspecialchars((string) ($u['contact'] ?? '')) ?></td>
                <td><?= htmlspecialchars($u['nom_site'] ?? 'Non assigné') ?></td>
                <td class="ta-center">
                    <?= !empty($u['date_embauche']) ? date('d/m/Y', strtotime($u['date_embauche'])) : '-' ?>
                </td>
                <td class="ta-center">
                    <?= !empty($u['created_at']) ? date('d/m/Y H:i', strtotime($u['created_at'])) : '-' ?>
                </td>
                <td class="<?= $status_class ?> ta-center"><?= $status_label ?></td>
                <td><?= !empty($u['photo']) ? 'Oui' : 'Non' ?></td>
                <td><?= !empty($u['cv']) ? 'Oui' : 'Non' ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<br>

<table border="0" class="note-table">
    <tr>
        <td>
            Note sécurité: le hash de mot de passe n'est pas exporté volontairement.
        </td>
    </tr>
</table>
