<?php
session_start();

// Si on vient de cliquer sur le bouton de test
if (isset($_GET['test'])) {
    $_SESSION['test_value'] = "La session fonctionne !";
    header('Location: test_session.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head><title>Diagnostic Session Railway</title></head>
<body style="font-family: sans-serif; padding: 50px; text-align: center;">
    <h1>🔍 Diagnostic de Session</h1>
    
    <div style="padding: 20px; border: 2px solid #ccc; display: inline-block;">
        <?php if (isset($_SESSION['test_value'])): ?>
            <h2 style="color: green;">✅ Succès !</h2>
            <p>Valeur en session : <strong><?php echo $_SESSION['test_value']; ?></strong></p>
            <p>ID de session : <code><?php echo session_id(); ?></code></p>
            <a href="?clear=1">Effacer et recommencer</a>
        <?php else: ?>
            <h2 style="color: orange;">En attente...</h2>
            <p>Appuyez sur le bouton pour tester si Railway garde les données.</p>
            <a href="?test=1" style="padding: 10px 20px; background: blue; color: white; text-decoration: none; border-radius: 5px;">Tester la Session</a>
        <?php endif; ?>
    </div>

    <?php
    if (isset($_GET['clear'])) {
        session_destroy();
        header('Location: test_session.php');
    }
    ?>
</body>
</html>