<?php
require_once 'config/database.php';

try {
    // 1. Liste des noms et prénoms fournis
    $agents = [
        ['nom' => 'KODJO', 'prenom' => 'Marcel'],
        ['nom' => 'HOTEKPO', 'prenom' => 'Athanase'],
        ['nom' => 'TOISSI', 'prenom' => 'Ulrich'],
        ['nom' => 'GANKPIN', 'prenom' => 'Ezéchiel'],
        ['nom' => 'MACAULEY', 'prenom' => 'Gloria'],
        ['nom' => 'ALAVO', 'prenom' => 'Carlos'],
        ['nom' => 'SODJI', 'prenom' => 'Plastide'],
        ['nom' => 'TOHOUBI', 'prenom' => 'Adrien'],
        ['nom' => 'ZINHOUDJO', 'prenom' => 'Edmond'],
        ['nom' => 'TOUGAN', 'prenom' => 'Prudence'],
        ['nom' => 'ADONON', 'prenom' => 'Brigitte'],
        ['nom' => 'LOKOSSOU', 'prenom' => 'Louise'],
        ['nom' => 'GBENOU', 'prenom' => 'Robert'],
        ['nom' => 'DEYO', 'prenom' => 'Amélie'],
        ['nom' => 'HONVOU', 'prenom' => 'François'],
        ['nom' => 'AHOUANDJINOU', 'prenom' => 'Félix'],
        ['nom' => 'HOUNNA', 'prenom' => 'Delcripia']
    ];

    $id_site_fixe = 1;
    $role_defaut = 'AGENT';
    // Mot de passe par défaut : Agent@2024 (hashé pour la sécurité)
    $password_hashed = password_hash('Agent@2024', PASSWORD_BCRYPT);

    $pdo->beginTransaction();

    $sql = "INSERT INTO users (nom, prenom, username, password, role, id_site, actif) 
            VALUES (:nom, :prenom, :username, :password, :role, :id_site, 1)";
    $stmt = $pdo->prepare($sql);

    $count = 0;
    foreach ($agents as $agent) {
        $username = strtolower($agent['prenom'] . '.' . substr($agent['nom'], 0, 1));
        
        $stmt->execute([
            ':nom'      => $agent['nom'],
            ':prenom'   => $agent['prenom'],
            ':username' => $username,
            ':password' => $password_hashed,
            ':role'     => $role_defaut,
            ':id_site'  => $id_site_fixe
        ]);
        $count++;
    }

    $pdo->commit();
    echo "<h1>✅ Succès !</h1>";
    echo "<p><strong>$count</strong> agents ont été enregistrés avec succès pour le site ID 1.</p>";
    echo "<p>Identifiant par défaut : <code>prenom.initialenom</code><br>Mot de passe par défaut : <code>Agent@2024</code></p>";

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<h1>❌ Erreur lors de l'insertion</h1>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}