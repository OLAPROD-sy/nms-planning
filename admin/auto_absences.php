<?php
// includes/auto_absences.php

/**
 * Fonction qui génère les absences pour les agents n'ayant pas pointé
 */
function genererAbsencesAutomatiques($pdo) {
    $today = date('Y-m-d');
    $jour_semaine = date('N'); 
    $heure_actuelle = date('H:i:s');

    $sql = "SELECT u.id_user, u.id_site, u.jours_repos, s.heure_debut_service 
        FROM users u
        LEFT JOIN sites s ON u.id_site = s.id_site
        WHERE u.role != 'ADMIN' 
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
        $limite = date('H:i:s', strtotime($heure_debut . ' + 4 hours'));

        if ($heure_actuelle > $limite) {
            try {
                $pdo->beginTransaction();

                $insP = $pdo->prepare("INSERT INTO pointages (id_user, date_pointage, type, id_site, motif_urgence, commentaire) 
                                     VALUES (?, ?, 'URGENCE', ?, 'ABSENCE AUTOMATIQUE', 'Système : Aucun pointage détecté.')");
                $insP->execute([$agent['id_user'], $today, $agent['id_site']]);

                $msg = "⚠️ Absence automatique enregistrée pour le " . date('d/m/Y') . ". Justifiez-vous.";
                $insN = $pdo->prepare("INSERT INTO notifications (id_user, from_user, type, message, created_at) 
                                     VALUES (?, NULL, 'urgence', ?, NOW())");
                $insN->execute([$agent['id_user'], $msg]);

                $pdo->commit();
            } catch (Exception $e) { 
                $pdo->rollBack(); 
            }
        }
    }
}
// Pas d'accolade orpheline ici