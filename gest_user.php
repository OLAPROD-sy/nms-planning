<?php
require_once 'config/database.php';

try {
    // 1. On récupère la liste RÉELLE des IDs de sites existants
    $stmtSites = $pdo->query("SELECT id_site FROM sites");
    $sites_ids = $stmtSites->fetchAll(PDO::FETCH_COLUMN);

    if (empty($sites_ids)) {
        die("❌ Erreur : Aucun site n'a été trouvé dans la table 'sites'.");
    }

    $produits_noms = [
        'BALAI INTERIEUR', 'BALAI CANTONNIER', 'RACLETTE SOL', 'BALAI INDIGENE',
        'BROSSE A MANCHE', 'FRANGE A ACCESSOIRE', 'GANT', 'CHIFFON',
        'EPONGE GRATTE', 'SAVON LIQUIDE', 'JAVEL', 'PELLE A TIGE',
        'MASQUE', 'LAIT PEAK', 'DETERGENT', 'DESINFECTANT',
        'CAMPHRE SENTEUR', 'SACHET POUBELLE GRAND', 'SACHET POUBELLE PETIT',
        'DESODORISANT', 'LAVE VITRE', 'SERPILLERE', 'PAPIER H', 'NETTOYANT MEUBLE'
    ];

    $pdo->beginTransaction();

    // 2. Nettoyage de la table produits pour éviter les doublons
    $pdo->exec("DELETE FROM produits");

    // 3. Préparation de l'insertion
    $sql = "INSERT INTO produits (nom_produit, id_site, quantite_actuelle, quantite_alerte) VALUES (?, ?, 0, 5)";
    $stmt = $pdo->prepare($sql);

    $total_insertions = 0;
    foreach ($sites_ids as $id_site) {
        foreach ($produits_noms as $nom) {
            $stmt->execute([$nom, (int)$id_site]);
            $total_insertions++;
        }
    }

    $pdo->commit();
    echo "<h1>✅ Succès !</h1>";
    echo "<p>Insertion de <b>$total_insertions</b> produits terminée pour les <b>" . count($sites_ids) . "</b> sites détectés.</p>";

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<h1>❌ Erreur Critique</h1>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}