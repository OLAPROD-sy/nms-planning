<?php
require_once __DIR__ . '/../config/database.php';

$messages = [];

function table_exists(PDO $pdo, string $name): bool {
    $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
    $stmt->execute([$name]);
    return (bool) $stmt->fetchColumn();
}

function column_exists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->execute([$column]);
    return (bool) $stmt->fetchColumn();
}

try {
    if (!table_exists($pdo, 'superviseur_sites')) {
        $pdo->exec("CREATE TABLE superviseur_sites (
            id INT NOT NULL AUTO_INCREMENT,
            id_user INT NOT NULL,
            id_site INT NOT NULL,
            date_debut DATE DEFAULT NULL,
            date_fin DATE DEFAULT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_superviseur_site (id_user, id_site),
            KEY idx_superviseur_site_site (id_site),
            CONSTRAINT fk_superviseur_sites_site FOREIGN KEY (id_site) REFERENCES sites (id_site) ON DELETE CASCADE,
            CONSTRAINT fk_superviseur_sites_user FOREIGN KEY (id_user) REFERENCES users (id_user) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
        $messages[] = 'Table superviseur_sites créée.';
    } else {
        if (!column_exists($pdo, 'superviseur_sites', 'date_debut')) {
            $pdo->exec("ALTER TABLE superviseur_sites ADD COLUMN date_debut DATE DEFAULT NULL");
            $messages[] = 'Colonne date_debut ajoutée.';
        }
        if (!column_exists($pdo, 'superviseur_sites', 'date_fin')) {
            $pdo->exec("ALTER TABLE superviseur_sites ADD COLUMN date_fin DATE DEFAULT NULL");
            $messages[] = 'Colonne date_fin ajoutée.';
        }
    }

    $pdo->exec("INSERT IGNORE INTO superviseur_sites (id_user, id_site, date_debut)
        SELECT id_user, id_site, IFNULL(date_embauche, CURDATE())
        FROM users
        WHERE role = 'SUPERVISEUR' AND id_site IS NOT NULL");
    $messages[] = 'Migration des superviseurs existants terminée.';

    $pdo->exec("UPDATE superviseur_sites SET date_debut = IFNULL(date_debut, CURDATE())");
    $messages[] = 'Dates de début normalisées.';

} catch (Exception $e) {
    $messages[] = 'Erreur: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migration Superviseur Sites</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f8fafc; padding:40px; }
        .card { background:#fff; border-radius:12px; padding:24px; max-width:720px; margin:0 auto; box-shadow:0 10px 30px rgba(0,0,0,0.08); }
        h1 { margin-top:0; }
        ul { padding-left:20px; }
        .note { margin-top:20px; font-size:13px; color:#64748b; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Migration superviseur_sites (Good)</h1>
        <ul>
            <?php foreach ($messages as $msg): ?>
                <li><?= htmlspecialchars($msg) ?></li>
            <?php endforeach; ?>
        </ul>
        <div class="note">Vous pouvez supprimer cette page après exécution.</div>
    </div>
</body>
</html>
