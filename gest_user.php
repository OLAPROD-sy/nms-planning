<?php
require_once __DIR__ . '/config/database.php';

try {
    echo "<h2>🛠 Mise à jour de la base de données...</h2>";

    // --- 1. MISE À JOUR DE LA TABLE produits_admin ---
    $table_p = $pdo->query("DESCRIBE produits_admin")->fetchAll(PDO::FETCH_COLUMN);
    
    // Ajout prix_unitaire si absent
    if (!in_array('prix_unitaire', $table_p)) {
        $pdo->exec("ALTER TABLE produits_admin ADD prix_unitaire DECIMAL(10, 2) DEFAULT 0");
        echo "✅ Colonne 'prix_unitaire' ajoutée à produits_admin.<br>";
    }

    // Ajout unite_mesure si absent
    if (!in_array('unite_mesure', $table_p)) {
        $pdo->exec("ALTER TABLE produits_admin ADD unite_mesure VARCHAR(20) DEFAULT 'Unité'");
        echo "✅ Colonne 'unite_mesure' ajoutée à produits_admin.<br>";
    }

    // --- 2. MISE À JOUR DE LA TABLE mouvements_stock_admin ---
    $table_m = $pdo->query("DESCRIBE mouvements_stock_admin")->fetchAll(PDO::FETCH_COLUMN);
    
    // Ajout prix_mouvement si absent
    if (!in_array('prix_mouvement', $table_m)) {
        $pdo->exec("ALTER TABLE mouvements_stock_admin ADD prix_mouvement DECIMAL(10, 2) DEFAULT 0");
        echo "✅ Colonne 'prix_mouvement' ajoutée à mouvements_stock_admin.<br>";
    }

    echo "<br>🚀 <b>Toutes les mises à jour ont été effectuées !</b>";
    echo "<br><a href='admin_stock.php' style='display:inline-block; margin-top:20px; padding:10px; background:#FF9800; color:white; text-decoration:none; border-radius:5px;'>Retourner à la Gestion de Stock</a>";

} catch (PDOException $e) {
    die("❌ Erreur critique : " . $e->getMessage());
}
?>