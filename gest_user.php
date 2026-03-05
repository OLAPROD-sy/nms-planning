<?php
require_once __DIR__ . '/config/database.php';


try {
    // Requête pour ajouter la colonne jours_repos si elle n'existe pas
    $sql = "ALTER TABLE users ADD COLUMN  jours_repos VARCHAR(50) DEFAULT '7'";
    
    $pdo->exec($sql);
    
    echo "<div style='color:green; font-weight:bold; padding:20px; border:2px solid green; border-radius:10px; text-align:center;'>
            ✅ La colonne 'jours_repos' a été ajoutée avec succès !<br>
            Par défaut, le repos est fixé au Dimanche (7).
          </div>";
          
} catch (PDOException $e) {
    echo "<div style='color:red; font-weight:bold; padding:20px; border:2px solid red; border-radius:10px;'>
            ❌ Erreur lors de la modification : " . $e->getMessage() . "
          </div>";
}
?>