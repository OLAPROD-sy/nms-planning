<?php
require_once __DIR__ . '/config/database.php';

try {
    // 1. Ajout des colonnes au catalogue
    $pdo->exec("ALTER TABLE produits_admin ADD COLUMN IF NOT EXISTS prix_unitaire DECIMAL(10, 2) DEFAULT 0");
    $pdo->exec("ALTER TABLE produits_admin ADD COLUMN IF NOT EXISTS unite_mesure VARCHAR(20) DEFAULT 'Unité'");

    // 2. Ajout du prix à l'historique des mouvements
    $pdo->exec("ALTER TABLE mouvements_stock_admin ADD COLUMN IF NOT EXISTS prix_mouvement DECIMAL(10, 2) DEFAULT 0");

    echo "✅ Base de données mise à jour avec succès ! Les colonnes Prix et Unité sont prêtes.";
    echo "<br><a href='admin_stock.php'>Retour au stock</a>";
} catch (PDOException $e) {
    die("❌ Erreur lors de la mise à jour : " . $e->getMessage());
}
?>