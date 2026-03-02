<?php
require_once __DIR__ . '/config/database.php'; // Assurez-vous que le chemin est correct

try {
    echo "<h2>Mise à jour de la base de données en cours...</h2>";
    
    // 1. Vérification de la connexion
    if (!$pdo) {
        throw new Exception("La connexion à la base de données a échoué.");
    }

    // 2. Ajout de la colonne commentaire si elle n'existe pas
    $checkColumn = $pdo->query("SHOW COLUMNS FROM pointages LIKE 'commentaire'");
    $columnExists = $checkColumn->fetch();

    if (!$columnExists) {
        $pdo->exec("ALTER TABLE pointages ADD COLUMN commentaire TEXT NULL AFTER id_site");
        echo "<p style='color:green;'>✅ Colonne 'commentaire' ajoutée avec succès !</p>";
    } else {
        echo "<p style='color:orange;'>ℹ️ La colonne 'commentaire' existe déjà.</p>";
    }

    // 3. Mise à jour des anciennes données pour éviter les erreurs d'affichage
    // On met 'SINGLE' par défaut pour les anciens enregistrements
    $updateOld = $pdo->exec("UPDATE pointages SET commentaire = 'SINGLE' WHERE commentaire IS NULL");
    echo "<p style='color:blue;'>✅ $updateOld anciens enregistrements mis à jour.</p>";

    // 4. Vérification de la colonne 'est_en_retard' (utilisée dans votre code précédent)
    $checkRetard = $pdo->query("SHOW COLUMNS FROM pointages LIKE 'est_en_retard'");
    if (!$checkRetard->fetch()) {
        $pdo->exec("ALTER TABLE pointages ADD COLUMN est_en_retard TINYINT(1) DEFAULT 0 AFTER type");
        echo "<p style='color:green;'>✅ Colonne 'est_en_retard' ajoutée avec succès !</p>";
    }

    echo "<hr><p><strong>Terminé !</strong> Vous pouvez maintenant supprimer ce fichier et tester votre formulaire d'urgence.</p>";

} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Erreur : " . $e->getMessage() . "</p>";
}
?>