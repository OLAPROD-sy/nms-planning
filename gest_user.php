<?php
require_once 'config/database.php';

try {
    // 1. Liste des nouveaux agents pour le site 2
    $agents = [
        ['nom' => 'GNIDA', 'prenom' => 'Francis'],
        ['nom' => 'IDRISSOU', 'prenom' => 'Lidy'],
        ['nom' => 'ASSIGNAMEY', 'prenom' => 'Marie'],
        ['nom' => 'CHERIF', 'prenom' => 'Chamssiyath'],
        ['nom' => 'SESSINOU', 'prenom' => 'Sandie'],
        ['nom' => 'AGBOGBA', 'prenom' => 'Mariette']
    ];

    $id_site_cible = 2; // Changement pour le site ID 2
    $role_defaut = 'AGENT';
    // Mot de passe par défaut : Agent@2024
    $password_hashed = password_hash('Agent@2024', PASSWORD_BCRYPT);

    $pdo->beginTransaction();

    $sql = "INSERT INTO users (nom, prenom, username, password, role, id_site, actif) 
            VALUES (:nom, :prenom, :username, :password, :role, :id_site, 1)";
    $stmt = $pdo->prepare($sql);

    $count = 0;
    foreach ($agents as $agent) {
        // Génération du username (ex: francis.g)
        $username = strtolower($agent['prenom'] . '.' . substr($agent['nom'], 0, 1));
        
        $stmt->execute([
            ':nom'      => $agent['nom'],
            ':prenom'   => $agent['prenom'],
            ':username' => $username,
            ':password' => $password_hashed,
            ':role'     => $role_defaut,
            ':id_site'  => $id_site_cible
        ]);
        $count++;
    }

    $pdo->commit();
    echo "<h1>✅ Insertion réussie pour le Site 2 !</h1>";
    echo "<p><strong>$count</strong> agents ont été enregistrés sur le site ID 2.</p>";
    echo "<p>Identifiant : <code>prenom.initialenom</code> | MDP : <code>Agent@2024</code></p>";

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<h1>❌ Erreur</h1>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}