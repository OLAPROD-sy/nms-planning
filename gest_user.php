<?php
require_once __DIR__ . '/config/database.php';

try {
    echo "<h2>🛠 Mise à jour de la base de données (Mode compatible)...</h2>";

    // 1. Mise à jour de la table SITES
    // On ajoute les colonnes une par une pour éviter de bloquer si l'une existe déjà
    $queries_sites = [
        "ALTER TABLE sites ADD latitude DECIMAL(10, 8) DEFAULT 0",
        "ALTER TABLE sites ADD longitude DECIMAL(11, 8) DEFAULT 0",
        "ALTER TABLE sites ADD heure_debut_service TIME DEFAULT '08:00:00'"
    ];

    foreach ($queries_sites as $query) {
        try {
            $pdo->exec($query);
            echo "✅ Colonne ajoutée à 'sites'.<br>";
        } catch (PDOException $e) {
            echo "ℹ️ Note : Une colonne de 'sites' existe déjà ou n'a pu être ajoutée.<br>";
        }
    }

    // 2. Mise à jour de la table POINTAGES
    try {
        $pdo->exec("ALTER TABLE pointages ADD est_en_retard TINYINT(1) DEFAULT 0");
        echo "✅ Colonne 'est_en_retard' ajoutée à 'pointages'.<br>";
    } catch (PDOException $e) {
        echo "ℹ️ Note : La colonne 'est_en_retard' existe déjà.<br>";
    }

    // 3. Configuration de ton site de test (Sèmè-Kpodji)
    $sql3 = "UPDATE sites 
            SET latitude = 6.364985, 
                longitude = 2.526574, 
                heure_debut_service = '00:00:00' 
            WHERE latitude = 0 OR latitude IS NULL LIMIT 1";
            
    $pdo->exec($sql3);
    echo "✅ Site de test configuré avec les coordonnées : 6.364985, 2.526574.<br>";

    echo "<br><strong style='color:green;'>Terminé ! Vérifie ta base de données maintenant.</strong>";

} catch (PDOException $e) {
    echo "<strong style='color:red;'>Erreur critique : </strong>" . $e->getMessage();
}
?>