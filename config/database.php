<?php
// Affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$dbname = "nms_planning";
$user = "nms_user";
$pass = "MonSuperMdp_2024!";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );
} catch (PDOException $e) {
    echo "<pre style='background:#fee2e2;padding:20px;border-radius:8px;color:#7f1d1d'>";
    echo "❌ ERREUR CONNEXION DB :\n";
    echo htmlspecialchars($e->getMessage());
    echo "\n\nDétails:\n";
    echo "Host: " . htmlspecialchars($host) . "\n";
    echo "DB: " . htmlspecialchars($dbname) . "\n";
    echo "User: " . htmlspecialchars($user) . "\n";
    echo "</pre>";
    exit;
}
?>
