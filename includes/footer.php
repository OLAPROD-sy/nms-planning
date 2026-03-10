<div style="height:40px"></div>
</div> <footer style="
    background: white; 
    border-top: 1px solid #e2e8f0; 
    padding: 40px 20px; 
    margin-top: 60px; 
    text-align: center;
    box-shadow: 0 -5px 15px rgba(0,0,0,0.02);
">
    <div style="max-width: 800px; margin: 0 auto;">
        <div style="
            font-weight: 800; 
            letter-spacing: -0.5px; 
            margin-bottom: 10px; 
            color: #1e293b;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        ">
            <span style="color: green;">NMS</span> 
            <span style="color: orange;">Planning</span>
        </div>
        
        <p style="
            color: #94a3b8; 
            font-size: 13px; 
            margin: 0;
            font-weight: 500;
        ">
            &copy; 2026 Tous droits réservés 
            <span style="margin: 0 8px; color: #e2e8f0;">|</span> 
            Système de Gestion Interne
        </p>

        <div style="
            margin-top: 20px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f8fafc;
            padding: 4px 12px;
            border-radius: 50px;
            border: 1px solid #f1f5f9;
        ">
            <span style="width: 8px; height: 8px; background: #10b981; border-radius: 50%;"></span>
            <span style="font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase;">Serveur Opérationnel</span>
        </div>
    </div>
</footer>

<?php
// Chargement automatique du JS de la page courante (assets/js/pages/{route}.js)
$scriptPath = trim(parse_url($_SERVER['SCRIPT_NAME'] ?? '', PHP_URL_PATH), '/');
$scriptFilename = basename($_SERVER['SCRIPT_FILENAME'] ?? '');
if ($scriptPath === '' && $scriptFilename !== '') {
    $scriptPath = $scriptFilename;
}
$pageJsSrc = '';
if ($scriptPath !== '') {
    $pageJsPath = preg_replace('/\.php$/', '.js', $scriptPath);
    $pageJsFile = __DIR__ . '/../assets/js/pages/' . $pageJsPath;
    if (is_file($pageJsFile)) {
        $pageJsSrc = '/assets/js/pages/' . $pageJsPath;
    }
}
?>
<?php if ($pageJsSrc): ?>
<script src="<?= htmlspecialchars($pageJsSrc, ENT_QUOTES, 'UTF-8') ?>"></script>
<?php endif; ?>
<script src="/assets/js/main.js"></script>
</body>
</html>
