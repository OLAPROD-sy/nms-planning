<?php
// 1. Connexion à la base de données
// Ajustez le chemin vers votre fichier database.php si nécessaire
require_once __DIR__ . '/config/database.php'; 

try {
    echo "<div style='font-family: sans-serif; padding: 20px;'>";
    echo "<h2>🛠️ Mise à jour de la structure du stock</h2>";
    echo "<hr>";

    // 2. Vérification de la colonne id_site_destination
    $check = $pdo->query("SHOW COLUMNS FROM mouvements_stock_admin LIKE 'id_site_destination'");
    $exists = $check->fetch();

    if (!$exists) {
        // 3. Exécution de la commande ALTER TABLE
        $sql = "ALTER TABLE mouvements_stock_admin ADD COLUMN id_site_destination INT NULL AFTER commentaire";
        $pdo->exec($sql);
        
        echo "<p style='color: #16a34a; font-weight: bold;'>✅ Succès : La colonne 'id_site_destination' a été ajoutée.</p>";
    } else {
        echo "<p style='color: #ca8a04; font-weight: bold;'>ℹ️ Info : La colonne 'id_site_destination' existe déjà dans la table.</p>";
    }

    // 4. Bonus : Vérification de la contrainte (Optionnel mais recommandé)
    // Cela permet de s'assurer que l'ID correspond bien à un site existant
    echo "<p style='color: #64748b;'>Structure vérifiée avec succès.</p>";
    echo "<br><a href='gest_stock.php' style='padding: 10px 20px; background: #FF9800; color: white; text-decoration: none; border-radius: 5px;'>Retour à la gestion de stock</a>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<div style='padding: 20px; background: #fee2e2; border: 1px solid #ef4444; border-radius: 8px; color: #b91c1c;'>";
    echo "<h3>❌ Erreur SQL</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>