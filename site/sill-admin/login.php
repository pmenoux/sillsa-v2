<?php
// sill-admin/login.php — Standalone login page (no layout)
// SILL SA v2 — PHP 8.2 vanilla

// Redirect if already authenticated
if (!empty($_SESSION['admin_user_id'])) {
    header('Location: ?page=dashboard');
    exit;
}

$error   = null;
$timeout = isset($_GET['timeout']) && $_GET['timeout'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Veuillez saisir votre identifiant et votre mot de passe.';
    } elseif (isRateLimited($username)) {
        $error = 'Trop de tentatives. Veuillez réessayer dans 15 minutes.';
    } elseif (!attemptLogin($username, $password)) {
        $error = 'Identifiant ou mot de passe incorrect.';
    } else {
        flash('success', 'Connexion réussie. Bienvenue, ' . $_SESSION['admin_username'] . '.');
        header('Location: ?page=dashboard');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SILL Admin — Connexion</title>
    <link rel="stylesheet" href="assets/admin.css">
</head>
<body>

<div class="login-container">
    <div class="login-box">
        <h1>SILL Admin</h1>

        <?php $flash = getFlash(); if ($flash): ?>
        <p class="login-<?= e($flash['type']) ?>" style="color:<?= $flash['type']==='success'?'#2E7D32':'#CC0000' ?>; font-size:13px; text-align:center; margin-bottom:16px;">
            <?= e($flash['message']) ?>
        </p>
        <?php endif; ?>

        <?php if ($timeout): ?>
        <p class="login-error">Votre session a expiré. Veuillez vous reconnecter.</p>
        <?php endif; ?>

        <?php if ($error !== null): ?>
        <p class="login-error"><?= e($error) ?></p>
        <?php endif; ?>

        <?php if (defined('AZURE_CLIENT_ID')): ?>
        <a href="?page=azure-login" class="btn-microsoft">
            <svg xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 21 21">
                <rect x="1" y="1" width="9" height="9" fill="#f25022"/>
                <rect x="11" y="1" width="9" height="9" fill="#7fba00"/>
                <rect x="1" y="11" width="9" height="9" fill="#00a4ef"/>
                <rect x="11" y="11" width="9" height="9" fill="#ffb900"/>
            </svg>
            Se connecter avec Microsoft
        </a>

        <div class="login-separator">
            <span>ou</span>
        </div>
        <?php endif; ?>

        <form method="post" action="?page=login">
            <?= csrfField() ?>

            <div class="form-group">
                <label for="username">Identifiant</label>
                <input type="text" id="username" name="username"
                       value="<?= e($_POST['username'] ?? '') ?>"
                       autocomplete="username" required>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password"
                       autocomplete="current-password" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%; padding:10px;">
                Se connecter
            </button>
        </form>
    </div>
</div>

</body>
</html>
