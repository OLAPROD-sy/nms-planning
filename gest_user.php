<?php
require_once __DIR__ . '/../config/database.php';

try {
    // 1. Définition des données
    $sites_ids = [1, 2, 3, 4, 5, 6, 7, 8, 10, 11, 12, 13];
    
    $produits_noms = [
        'BALAI INTERIEUR', 'BALAI CANTONNIER', 'RACLETTE SOL', 'BALAI INDIGENE',
        'BROSSE A MANCHE', 'FRANGE A ACCESSOIRE', 'GANT', 'CHIFFON',
        'EPONGE GRATTE', 'SAVON LIQUIDE', 'JAVEL', 'PELLE A TIGE',
        'MASQUE', 'LAIT PEAK', 'DETERGENT', 'DESINFECTANT',
        'CAMPHRE SENTEUR', 'SACHET POUBELLE GRAND', 'SACHET POUBELLE PETIT',
        'DESODORISANT', 'LAVE VITRE', 'SERPILLERE', 'PAPIER H', 'NETTOYANT MEUBLE'
    ];

    // Début de la transaction pour garantir que tout est fait ou rien du tout
    $pdo->beginTransaction();

    // 2. Nettoyage de la table
    $pdo->exec("TRUNCATE TABLE produits");

    // 3. Correction de l'index UNIQUE
    // On supprime l'ancien index sur le nom seul et on en crée un combiné (nom + site)
    try {
        $pdo->exec("ALTER TABLE produits DROP INDEX nom_produit");
    } catch (Exception $e) {
        // L'index n'existe peut-être déjà plus, on continue
    }
    $pdo->exec("ALTER TABLE produits ADD UNIQUE KEY unique_produit_par_site (nom_produit, id_site)");

    // 4. Préparation de l'insertion massive
    $sql = "INSERT INTO produits (nom_produit, id_site, quantite_actuelle, quantite_alerte) VALUES (?, ?, 0, 5)";
    $stmt = $pdo->prepare($sql);

    $total_insertions = 0;
    foreach ($sites_ids as $id_site) {
        foreach ($produits_noms as $nom) {
            $stmt->execute([$nom, $id_site]);
            $total_insertions++;
        }
    }

    $pdo->commit();
    echo "✅ Succès ! " . $total_insertions . " produits ont été insérés (24 produits pour 12 sites).";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("❌ Erreur lors de l'insertion : " . $e->getMessage());
}