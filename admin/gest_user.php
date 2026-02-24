<?php
require_once __DIR__ . '/config/database.php';

try {
    echo "<h2>🛠 Mise à jour de la base de données...</h2>";

    // 1. Mise à jour de la table SITES
    $sql1 = "ALTER TABLE sites 
            ADD COLUMN IF NOT EXISTS latitude DECIMAL(10, 8) DEFAULT 0,
            ADD COLUMN IF NOT EXISTS longitude DECIMAL(11, 8) DEFAULT 0,
            ADD COLUMN IF NOT EXISTS heure_debut_service TIME DEFAULT '08:00:00'";
    
    $pdo->exec($sql1);
    echo "✅ Table 'sites' mise à jour (colonnes latitude, longitude, heure_debut_service).<br>";

    // 2. Mise à jour de la table POINTAGES
    $sql2 = "ALTER TABLE pointages 
            ADD COLUMN IF NOT EXISTS est_en_retard TINYINT(1) DEFAULT 0";
    
    $pdo->exec($sql2);
    echo "✅ Table 'pointages' mise à jour (colonne est_en_retard).<br>";

    // 3. Configuration de ton site de test (Sèmè-Kpodji)
    // On va configurer le premier site trouvé ou tu peux mettre un ID spécifique
    $sql3 = "UPDATE sites 
            SET latitude = 6.364985, 
                longitude = 2.526574, 
                heure_debut_service = '08:00:00' 
            LIMIT 1"; // Modifie LIMIT 1 par WHERE id_site = X si besoin
            
    $pdo->exec($sql3);
    echo "✅ Site de test configuré avec les coordonnées : 6.364985, 2.526574.<br>";

    echo "<br><strong style='color:green;'>Terminé ! Tu peux maintenant supprimer ce fichier.</strong>";

} catch (PDOException $e) {
    echo "<strong style='color:red;'>Erreur : </strong>" . $e->getMessage();
}
?>