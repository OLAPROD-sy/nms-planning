<?php
// includes/auto_absences.php

/**
 * Fonction qui génère les absences pour les agents n'ayant pas pointé
 */
function genererAbsencesAutomatiques($pdo) {
    date_default_timezone_set('Africa/Porto-Novo');
    $today = date('Y-m-d');
    $jour_semaine = date('N'); 
    $heure_actuelle = date('H:i:s');

    $sql = "SELECT u.id_user, u.id_site, u.jours_repos, u.nom, u.prenom, u.role, s.nom_site, s.heure_debut_service 
        FROM users u
        LEFT JOIN sites s ON u.id_site = s.id_site
        WHERE u.role = 'AGENT' 
        AND u.actif = 1
        AND u.id_user NOT IN (
            SELECT id_user FROM pointages WHERE date_pointage = ?
        )";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$today]);
    $absents_potentiels = $stmt->fetchAll();

    foreach ($absents_potentiels as $agent) {
        $repos_agent = explode(',', $agent['jours_repos'] ?? '7');
    
        if (in_array($jour_semaine, $repos_agent)) {
            continue; 
        }

        $heure_debut = !empty($agent['heure_debut_service']) ? $agent['heure_debut_service'] : '08:00:00';
        $limite = date('H:i:s', strtotime($heure_debut . ' + 5 hours'));

        if ($heure_actuelle > $limite) {
            try {
                $pdo->beginTransaction();

                $insP = $pdo->prepare("INSERT INTO pointages (id_user, date_pointage, type, id_site, motif_urgence, commentaire) 
                                     VALUES (?, ?, 'URGENCE', ?, 'ABSENCE AUTOMATIQUE', 'Système : Aucun pointage détecté.')");
                $insP->execute([$agent['id_user'], $today, $agent['id_site']]);

                $role_label = (strtoupper($agent['role'] ?? '') === 'SUPERVISEUR') ? 'Le superviseur' : "L'agent";
                $nom_agent = ucfirst($agent['nom'] ?? '');
                $prenom_agent = ucfirst($agent['prenom'] ?? '');
                $nom_site = ucfirst($agent['nom_site'] ?? 'Site inconnu');
                $date_abs = date('d/m/Y', strtotime($today));
                $msg_agent = "Absence automatique : " . $role_label . " " . $nom_agent . " " . $prenom_agent . " du site " . $nom_site . " n'a pas pointe le " . $date_abs . " donc absent.";
                $insN = $pdo->prepare("INSERT INTO notifications (id_user, from_user, type, message, created_at) 
                                     VALUES (?, NULL, 'urgence', ?, NOW())");
                $insN->execute([$agent['id_user'], $msg_agent]);

                require_once __DIR__ . '/../includes/superviseur_sites.php';
                $targets = [];
                if (!empty($agent['id_site'])) {
                    $targets = get_supervisor_ids_for_site($pdo, (int) $agent['id_site']);
                }
                $admins = $pdo->query("SELECT id_user FROM users WHERE role = 'ADMIN'")->fetchAll(PDO::FETCH_COLUMN);
                $targets = array_unique(array_merge($targets, $admins));

                if ($targets) {
                    $msg_admin = "Absence automatique : " . $role_label . " " . $nom_agent . " " . $prenom_agent . " du site " . $nom_site . " n'a pas pointe le " . $date_abs . " donc absent.";
                    foreach ($targets as $target_id) {
                        $insN->execute([$target_id, $msg_admin]);
                    }
                }

                $pdo->commit();
            } catch (Exception $e) { 
                $pdo->rollBack(); 
            }
        }
    }
}
// Pas d'accolade orpheline ici
