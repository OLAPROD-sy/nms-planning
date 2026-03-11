<?php
// Helpers pour la gestion des superviseurs multi-sites
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function superviseur_sites_table_exists(PDO $pdo): bool {
    static $exists = null;
    if ($exists !== null) {
        return $exists;
    }
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'superviseur_sites'");
        $exists = (bool) $stmt->fetchColumn();
    } catch (Exception $e) {
        $exists = false;
    }
    return $exists;
}

function get_supervisor_site_ids(PDO $pdo, int $user_id, ?string $on_date = null): array {
    $ids = [];
    $on_date = $on_date ?: date('Y-m-d');
    if (superviseur_sites_table_exists($pdo)) {
        $stmt = $pdo->prepare('
            SELECT id_site
            FROM superviseur_sites
            WHERE id_user = ?
              AND (date_debut IS NULL OR date_debut <= ?)
              AND (date_fin IS NULL OR date_fin >= ?)
            ORDER BY id_site
        ');
        $stmt->execute([$user_id, $on_date, $on_date]);
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    if (!$ids) {
        $stmt = $pdo->prepare('SELECT id_site FROM users WHERE id_user = ?');
        $stmt->execute([$user_id]);
        $single = $stmt->fetchColumn();
        if (!empty($single)) {
            $ids = [(int) $single];
        }
    }

    $clean = [];
    foreach ($ids as $id) {
        $id = (int) $id;
        if ($id > 0) {
            $clean[$id] = true;
        }
    }
    return array_values(array_keys($clean));
}

function get_supervisor_sites(PDO $pdo, int $user_id, ?string $on_date = null): array {
    $sites = [];
    $on_date = $on_date ?: date('Y-m-d');
    if (superviseur_sites_table_exists($pdo)) {
        $stmt = $pdo->prepare('
            SELECT s.id_site, s.nom_site, s.localisation, s.description, ss.date_debut, ss.date_fin
            FROM superviseur_sites ss
            JOIN sites s ON s.id_site = ss.id_site
            WHERE ss.id_user = ?
              AND (ss.date_debut IS NULL OR ss.date_debut <= ?)
              AND (ss.date_fin IS NULL OR ss.date_fin >= ?)
            ORDER BY s.nom_site
        ');
        $stmt->execute([$user_id, $on_date, $on_date]);
        $sites = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if (!$sites) {
        $stmt = $pdo->prepare('
            SELECT s.id_site, s.nom_site, s.localisation, s.description, NULL AS date_debut, NULL AS date_fin
            FROM users u
            JOIN sites s ON s.id_site = u.id_site
            WHERE u.id_user = ?
        ');
        $stmt->execute([$user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $sites = [$row];
        }
    }

    return $sites;
}

function supervisor_has_site(PDO $pdo, int $user_id, int $site_id, ?string $on_date = null): bool {
    if ($site_id <= 0) {
        return false;
    }
    $on_date = $on_date ?: date('Y-m-d');

    if (superviseur_sites_table_exists($pdo)) {
        $stmt = $pdo->prepare('
            SELECT 1
            FROM superviseur_sites
            WHERE id_user = ?
              AND id_site = ?
              AND (date_debut IS NULL OR date_debut <= ?)
              AND (date_fin IS NULL OR date_fin >= ?)
            LIMIT 1
        ');
        $stmt->execute([$user_id, $site_id, $on_date, $on_date]);
        if ($stmt->fetchColumn()) {
            return true;
        }
    }

    $stmt = $pdo->prepare('SELECT 1 FROM users WHERE id_user = ? AND id_site = ? LIMIT 1');
    $stmt->execute([$user_id, $site_id]);
    return (bool) $stmt->fetchColumn();
}

function set_supervisor_sites(PDO $pdo, int $user_id, array $assignments): void {
    if (!superviseur_sites_table_exists($pdo)) {
        return;
    }

    $pdo->prepare('DELETE FROM superviseur_sites WHERE id_user = ?')->execute([$user_id]);
    if (!$assignments) {
        return;
    }

    $ins = $pdo->prepare('
        INSERT INTO superviseur_sites (id_user, id_site, date_debut, date_fin)
        VALUES (?, ?, ?, ?)
    ');
    foreach ($assignments as $assignment) {
        if (is_array($assignment)) {
            $id_site = (int) ($assignment['id_site'] ?? 0);
            $date_debut = $assignment['date_debut'] ?? null;
            $date_fin = $assignment['date_fin'] ?? null;
        } else {
            $id_site = (int) $assignment;
            $date_debut = date('Y-m-d');
            $date_fin = null;
        }

        if ($id_site > 0) {
            $ins->execute([$user_id, $id_site, $date_debut, $date_fin]);
        }
    }
}

function get_supervisor_ids_for_site(PDO $pdo, int $site_id): array {
    $ids = [];

    if (superviseur_sites_table_exists($pdo)) {
        $today = date('Y-m-d');
        $stmt = $pdo->prepare('
            SELECT id_user FROM superviseur_sites
            WHERE id_site = ?
              AND (date_debut IS NULL OR date_debut <= ?)
              AND (date_fin IS NULL OR date_fin >= ?)
        ');
        $stmt->execute([$site_id, $today, $today]);
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    $stmt = $pdo->prepare("SELECT id_user FROM users WHERE role = 'SUPERVISEUR' AND id_site = ?");
    $stmt->execute([$site_id]);
    $fallback = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $all = array_unique(array_merge($ids, $fallback));
    return array_values(array_map('intval', $all));
}

function get_supervisor_assignments(PDO $pdo, int $user_id): array {
    if (!superviseur_sites_table_exists($pdo)) {
        $stmt = $pdo->prepare('SELECT id_site, date_embauche FROM users WHERE id_user = ?');
        $stmt->execute([$user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['id_site'])) {
            return [[
                'id_site' => (int) $row['id_site'],
                'date_debut' => $row['date_embauche'] ?: date('Y-m-d'),
                'date_fin' => null,
            ]];
        }
        return [];
    }

    $stmt = $pdo->prepare('
        SELECT id_site, date_debut, date_fin
        FROM superviseur_sites
        WHERE id_user = ?
        ORDER BY date_debut IS NULL DESC, date_debut ASC, id_site ASC
    ');
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
