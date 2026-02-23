<?php
// 1. Diagnostic (À enlever une fois que ça marche)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 2. Chargement des dépendances
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

// On définit les alias pour ne plus avoir à mettre de \ partout
use Dompdf\Dompdf;
use Dompdf\Options;

// 3. Initialisation de Dompdf
try {
    $options = new Options(); 
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true); 
    
    $dompdf = new Dompdf($options);
} catch (\Exception $e) {
    die("❌ Erreur d'initialisation Dompdf : " . $e->getMessage());
}

// 4. Récupération des dates
$date_start = $_GET['date_start'] ?? date('Y-m-d', strtotime('monday this week'));
$date_end = $_GET['date_end'] ?? date('Y-m-d', strtotime('sunday this week'));

// 5. Préparation du LOGO
$logoPath = __DIR__ . '/../assets/img/logo.png'; 
$logoSrc = '';
if (file_exists($logoPath)) {
    $logoData = base64_encode(file_get_contents($logoPath));
    $logoSrc = 'data:image/png;base64,' . $logoData;
}

// 6. SQL - Journal
$stmt = $pdo->prepare('
    SELECT m.*, p.nom_produit
    FROM mouvements_stock m
    JOIN produits p ON m.id_produit = p.id_produit
    WHERE m.date_mouvement BETWEEN ? AND ?
    ORDER BY m.date_mouvement DESC
');
$stmt->execute([$date_start . ' 00:00:00', $date_end . ' 23:59:59']);
$mouvements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 7. SQL - Résumé
$stmt = $pdo->prepare('
    SELECT 
        p.nom_produit,
        SUM(CASE WHEN m.type_mouvement = "entree" THEN m.quantite ELSE 0 END) as total_entrees,
        SUM(CASE WHEN m.type_mouvement = "sortie" THEN m.quantite ELSE 0 END) as total_sorties,
        (SUM(CASE WHEN m.type_mouvement = "entree" THEN m.quantite ELSE 0 END) - 
         SUM(CASE WHEN m.type_mouvement = "sortie" THEN m.quantite ELSE 0 END)) as bilan_net
    FROM mouvements_stock m
    JOIN produits p ON m.id_produit = p.id_produit
    WHERE m.date_mouvement BETWEEN ? AND ?
    GROUP BY p.id_produit
    ORDER BY p.nom_produit
');
$stmt->execute([$date_start . ' 00:00:00', $date_end . ' 23:59:59']);
$resume = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 8. Génération du HTML
$html = '
<html>
<head>
    <style>
        body { font-family: "Helvetica", sans-serif; font-size: 11px; color: #333; line-height: 1.4; }
        .header-table { width: 100%; border: none; margin-bottom: 20px; }
        .logo { width: 100px; }
        .company-info { text-align: right; font-size: 10px; color: #555; }
        .report-title { text-align: center; background: #f8f9fa; padding: 15px; border-bottom: 3px solid #FF9800; margin-bottom: 20px; }
        .report-title h1 { color: #F57C00; margin: 0; font-size: 18px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background-color: #f2f2f2; padding: 10px; border: 1px solid #ddd; text-align: left; text-transform: uppercase; font-size: 9px; }
        td { padding: 8px; border: 1px solid #ddd; }
        .badge-entree { color: #27ae60; font-weight: bold; }
        .badge-sortie { color: #e74c3c; font-weight: bold; }
        .footer { position: fixed; bottom: -30px; left: 0; right: 0; text-align: center; font-size: 9px; color: #999; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="border:none;">' . ($logoSrc ? '<img src="' . $logoSrc . '" class="logo">' : '<h2 style="color:#FF9800">NMS PLANNING</h2>') . '</td>
            <td style="border:none;" class="company-info"><strong>NMS PLANNING SERVICES</strong><br>Généré le : ' . date('d/m/Y H:i') . '</td>
        </tr>
    </table>
    <div class="report-title">
        <h1>RAPPORT D\'INVENTAIRE</h1>
        <div>Période : du ' . date('d/m/Y', strtotime($date_start)) . ' au ' . date('d/m/Y', strtotime($date_end)) . '</div>
    </div>

    <h3>1. RÉSUMÉ PAR PRODUIT</h3>
    <table>
        <thead><tr><th>Produit</th><th>Entrées</th><th>Sorties</th><th>Bilan</th></tr></thead>
        <tbody>';
foreach ($resume as $r) {
    $html .= '<tr>
        <td><strong>' . htmlspecialchars($r['nom_produit']) . '</strong></td>
        <td style="color:green;">+ ' . $r['total_entrees'] . '</td>
        <td style="color:red;">- ' . $r['total_sorties'] . '</td>
        <td>' . ($r['bilan_net'] > 0 ? '+' : '') . $r['bilan_net'] . '</td>
    </tr>';
}
$html .= '</tbody></table>

    <h3>2. JOURNAL DES MOUVEMENTS</h3>
    <table>
        <thead><tr><th>Date</th><th>Produit</th><th>Type</th><th>Qté</th><th>Responsable</th></tr></thead>
        <tbody>';
foreach ($mouvements as $m) {
    $isEntree = (trim(strtolower($m['type_mouvement'])) === 'entree');
    $html .= '<tr>
        <td>' . date('d/m/Y H:i', strtotime($m['date_mouvement'])) . '</td>
        <td>' . htmlspecialchars($m['nom_produit']) . '</td>
        <td class="' . ($isEntree ? 'badge-entree' : 'badge-sortie') . '">' . ($isEntree ? 'ENTRÉE' : 'SORTIE') . '</td>
        <td>' . $m['quantite'] . '</td>
        <td>' . htmlspecialchars($m['responsable_nom']) . '</td>
    </tr>';
}
$html .= '</tbody></table>
    <div class="footer">NMS Planning - Page 1/1</div>
</body>
</html>';

// 9. Rendu final (Pas besoin de recréer $options ici !)
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// 10. Téléchargement
$dompdf->stream("NMS_Inventaire_" . date('d_m_Y') . ".pdf", ["Attachment" => true]);