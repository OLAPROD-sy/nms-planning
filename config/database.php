<?php
// Récupération des variables d'environnement (Railway) ou valeurs par défaut (Local)
$host = getenv('MYSQLHOST') ?: "localhost";
$port = getenv('MYSQLPORT') ?: "3306";
$dbname = getenv('MYSQLDATABASE') ?: "nms_planning";
$user = getenv('MYSQLUSER') ?: "nms_user";
$pass = getenv('MYSQLPASSWORD') ?: "MonSuperMdp_2024!";

try {
    // Notez l'ajout du port dans le DSN, important pour Railway
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );
} catch (PDOException $e) {
    // En production (Railway), il vaut mieux ne pas afficher les détails d'erreur
    // Mais pour le moment, on garde votre affichage de débogage
    echo "<pre style='background:#fee2e2;padding:20px;border-radius:8px;color:#7f1d1d'>";
    echo "❌ ERREUR CONNEXION DB\n";
    // On cache le message précis en ligne pour la sécurité, ou on l'affiche si on débogue
    echo htmlspecialchars($e->getMessage());
    echo "</pre>";
    exit;
}
?>