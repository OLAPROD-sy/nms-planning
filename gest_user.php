<?php
require_once __DIR__ . '/config/database.php';

if ($_SESSION['role'] !== 'ADMIN') {
    die("Accès refusé.");
}

// Liste complète extraite de l'image
$liste_produits = [
    ['nom' => 'Papier Hygiénique', 'unite' => 'Paquet de 10', 'min_prix' => 2500, 'max_prix' => 3500],
    ['nom' => 'Détergent (Omo)', 'unite' => 'Sachet', 'min_prix' => 500, 'max_prix' => 1500],
    ['nom' => 'Savon Liquide', 'unite' => 'Bidon 5L', 'min_prix' => 4500, 'max_prix' => 6000],
    ['nom' => 'Gel à main', 'unite' => 'Flacon', 'min_prix' => 1500, 'max_prix' => 2500],
    ['nom' => 'Lave vitre', 'unite' => 'Flacon spray', 'min_prix' => 1200, 'max_prix' => 2000],
    ['nom' => 'Désodorisant', 'unite' => 'Aérosol', 'min_prix' => 1500, 'max_prix' => 3000],
    ['nom' => 'Nettoyant meuble', 'unite' => 'Spray', 'min_prix' => 2000, 'max_prix' => 3500],
    ['nom' => 'Serpillères', 'unite' => 'Unité', 'min_prix' => 1000, 'max_prix' => 2000],
    ['nom' => 'Eau de Javel', 'unite' => 'Flacon 1L', 'min_prix' => 800, 'max_prix' => 1500],
    ['nom' => 'Balais intérieur', 'unite' => 'Unité avec manche', 'min_prix' => 2500, 'max_prix' => 4500],
    ['nom' => 'Raclettes', 'unite' => 'Unité', 'min_prix' => 3000, 'max_prix' => 5000],
    ['nom' => 'Lait Peak', 'unite' => 'Boite', 'min_prix' => 500, 'max_prix' => 800],
    ['nom' => 'Eponge gratte', 'unite' => 'Unité', 'min_prix' => 200, 'max_prix' => 500],
    ['nom' => 'Sachet poubelle 30kg', 'unite' => 'Rouleau', 'min_prix' => 1500, 'max_prix' => 2500],
    ['nom' => 'Frange', 'unite' => 'Unité', 'min_prix' => 1500, 'max_prix' => 3000]
];

try {
    $pdo->beginTransaction();

    // 1. SUPPRESSION DES ANCIENS PRODUITS (Nettoyage complet)
    $pdo->exec("DELETE FROM produits_admin");

    // 2. INSERTION DES NOUVEAUX PRODUITS
    $stmt = $pdo->prepare("INSERT INTO produits_admin (nom_produit, unite_mesure, quantite_globale, prix_unitaire, seuil_alerte) 
                           VALUES (:nom, :unite, :qty, :prix, :seuil)");

    $compteur = 0;
    foreach ($liste_produits as $item) {
        $prix_aleatoire = rand($item['min_prix'], $item['max_prix']);
        $prix_aleatoire = round($prix_aleatoire / 50) * 50; 

        $stmt->execute([
            ':nom'   => $item['nom'],
            ':unite' => $item['unite'],
            ':qty'   => 15,
            ':prix'  => $prix_aleatoire,
            ':seuil' => 5
        ]);
        $compteur++;
    }

    $pdo->commit();
    $message = "🧹 Table vidée et ✅ $compteur nouveaux produits insérés avec succès !";
} catch (Exception $e) {
    $pdo->rollBack();
    $message = "❌ Erreur lors de la mise à jour : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réinitialisation Stock</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; background: #f8fafc; margin: 0; }
        .card { background: white; padding: 40px; border-radius: 24px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); text-align: center; max-width: 400px; }
        .icon { font-size: 50px; margin-bottom: 20px; }
        h1 { color: #1e293b; font-size: 22px; margin-bottom: 10px; }
        p { color: #64748b; line-height: 1.6; }
        .btn { background: #FF9800; color: white; padding: 14px 28px; border-radius: 12px; text-decoration: none; font-weight: bold; display: inline-block; margin-top: 25px; transition: 0.3s; }
        .btn:hover { background: #E68900; transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">🚀</div>
        <h1>Mise à jour du Stock</h1>
        <p><?= $message ?></p>
        <a href="/stock/gest_stock.php" class="btn">Voir le nouveau stock</a>
    </div>
</body>
</html>