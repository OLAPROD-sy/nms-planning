<?php
// nettoyage.php
require_once __DIR__ . '/config/database.php';

// Optionnel : Ajoute une sécurité pour que toi seul puisses l'ouvrir
// if ($_GET['key'] !== 'ton_code_secret') die('Accès refusé');

try {
    // On désactive les contraintes de clés étrangères pour éviter les blocages
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // Liste des tables à VIDER (garde la structure mais efface le contenu)
    $tablesToEmpty = [
        'mouvements_stock',
        'mouvements_stock_admin',
        'notifications',
        'pointages',
        'programmations',
        'semaines'
    ];

    foreach ($tablesToEmpty as $table) {
        // On utilise TRUNCATE pour remettre les compteurs (ID) à zéro
        $pdo->exec("TRUNCATE TABLE `$table` ");
        echo "✅ Table '$table' vidée.<br>";
    }

    // SUPPRIMER définitivement la table utilisateurs
    $pdo->exec("DROP TABLE IF EXISTS `users` "); // Adapte le nom si ta table s'appelle 'utilisateurs'
    echo "🗑️ Table 'users' supprimée définitivement.<br>";

    // On réactive les clés étrangères
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    echo "---<br>✨ Nettoyage terminé avec succès !";

} catch (PDOException $e) {
    die("❌ Erreur lors du nettoyage : " . $e->getMessage());
}
?>