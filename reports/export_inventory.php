<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// 1. Récupération et sécurisation des périodes
$date_start = $_GET['date_start'] ?? date('Y-m-d', strtotime('monday this week'));
$date_end = $_GET['date_end'] ?? date('Y-m-d', strtotime('sunday this week'));

// --- PRÉPARATION DU LOGO ---
// On encode le logo en base64 pour qu'il s'affiche sans problème sur Railway/Serveur
$logoPath = __DIR__ . '/../assets/img/logo.png'; // Vérifie bien ce chemin
$logoSrc = '';
if (file_exists($logoPath)) {
    $logoData = base64_encode(file_get_contents($logoPath));
    $logoSrc = 'data:image/png;base64,' . $logoData;
}

// 2. Récupération des données SQL
$stmt = $pdo->prepare('
    SELECT m.*, p.nom_produit
    FROM mouvements_stock m
    JOIN produits p ON m.id_produit = p.id_produit
    WHERE m.date_mouvement BETWEEN ? AND ?
    ORDER BY m.date_mouvement DESC
');
$stmt->execute([$date_start . ' 00:00:00', $date_end . ' 23:59:59']);
$mouvements = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

// 3. Préparation du HTML
$html = '
<html>
<head>
    <style>
        body { font-family: "Helvetica", sans-serif; font-size: 11px; color: #333; line-height: 1.4; }
        .header-table { width: 100%; border: none; margin-bottom: 20px; }
        .logo { width: 100px; }
        .company-info { text-align: right; font-size: 10px; color: #555; }
        
        .report-title { 
            text-align: center; 
            background: #f8f9fa; 
            padding: 15px; 
            border: 1px solid #eee;
            border-bottom: 3px solid #FF9800;
            margin-bottom: 20px;
        }
        .report-title h1 { color: #F57C00; margin: 0; font-size: 18px; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background-color: #f2f2f2; padding: 10px; border: 1px solid #ddd; text-align: left; text-transform: uppercase; font-size: 9px; }
        td { padding: 8px; border: 1px solid #ddd; }
        
        .badge-entree { color: #27ae60; font-weight: bold; }
        .badge-sortie { color: #e74c3c; font-weight: bold; }
        
        .signature-section { margin-top: 50px; width: 100%; }
        .signature-box { width: 45%; display: inline-block; vertical-align: top; text-align: center; }
        
        .footer { position: fixed; bottom: -30px; left: 0; right: 0; text-align: center; font-size: 9px; color: #999; border-top: 1px solid #eee; padding-top: 5px; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="border:none;">
                ' . ($logoSrc ? '<img src="' . $logoSrc . '" class="logo">' : '<h2 style="color:#FF9800">NMS PLANNING</h2>') . '
            </td>
            <td style="border:none;" class="company-info">
                <strong>NMS PLANNING SERVICES</strong><br>
                Gestion d\'Inventaire Centralisée<br>
                Généré le : ' . date('d/m/Y H:i') . '
            </td>
        </tr>
    </table>

    <div class="report-title">
        <h1>RAPPORT D\'INVENTAIRE DES STOCKS</h1>
        <div style="margin-top:5px;">Période : du ' . date('d/m/Y', strtotime($date_start)) . ' au ' . date('d/m/Y', strtotime($date_end)) . '</div>
    </div>

    <h3>1. RÉSUMÉ DES FLUX PAR PRODUIT</h3>
    <table>
        <thead>
            <tr>
                <th>Désignation Produit</th>
                <th>Total Entrées</th>
                <th>Total Sorties</th>
                <th>Bilan Net</th>
            </tr>
        </thead>
        <tbody>';
foreach ($resume as $r) {
    $html .= '<tr>
        <td><strong>' . htmlspecialchars($r['nom_produit']) . '</strong></td>
        <td style="color:green;">+ ' . $r['total_entrees'] . '</td>
        <td style="color:red;">- ' . $r['total_sorties'] . '</td>
        <td style="font-weight:bold; background:#fafafa;">' . ($r['bilan_net'] > 0 ? '+' : '') . $r['bilan_net'] . '</td>
    </tr>';
}
$html .= '</tbody></table>

    <h3>2. JOURNAL DÉTAILLÉ DES MOUVEMENTS</h3>
    <table>
        <thead>
            <tr>
                <th>Date & Heure</th>
                <th>Produit</th>
                <th>Type</th>
                <th>Quantité</th>
                <th>Responsable</th>
            </tr>
        </thead>
        <tbody>';
foreach ($mouvements as $m) {
    $isEntree = (trim(strtolower($m['type_mouvement'])) === 'entree');
    $html .= '<tr>
        <td>' . date('d/m/Y H:i', strtotime($m['date_mouvement'])) . '</td>
        <td>' . htmlspecialchars($m['nom_produit']) . '</td>
        <td class="' . ($isEntree ? 'badge-entree' : 'badge-sortie') . '">' . ($isEntree ? '⬆ ENTRÉE' : '⬇ SORTIE') . '</td>
        <td>' . $m['quantite'] . '</td>
        <td>' . htmlspecialchars($m['responsable_nom']) . '</td>
    </tr>';
}
$html .= '</tbody></table>

    <div class="signature-section">
        <div class="signature-box" style="float:left;">
            <p><strong>Le Responsable de Stock</strong></p>
            <div style="margin-top:40px; border-bottom: 1px solid #333; width: 150px; margin-left: auto; margin-right: auto;"></div>
            <p style="font-size:9px; color:#777;">Cachet et Signature</p>
        </div>
        <div class="signature-box" style="float:right;">
            <p><strong>La Direction / Validation</strong></p>
            <div style="margin-top:40px; border-bottom: 1px solid #333; width: 150px; margin-left: auto; margin-right: auto;"></div>
            <p style="font-size:9px; color:#777;">Signature autorisée</p>
        </div>
    </div>

    <div class="footer">Document officiel NMS Planning - Page 1/1</div>
</body>
</html>';

// 4. Lancer Dompdf avec Options corrigées
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true); // Pour autoriser le chargement d'images
$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// 5. Envoi du flux
$dompdf->stream("NMS_Inventaire_" . date('d_m_Y') . ".pdf", ["Attachment" => true]);