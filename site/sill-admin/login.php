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

        <?php if ($timeout): ?>
        <p class="login-error">Votre session a expiré. Veuillez vous reconnecter.</p>
        <?php endif; ?>

        <?php if ($error !== null): ?>
        <p class="login-error"><?= e($error) ?></p>
        <?php endif; ?>

        <form method="post" action="?page=login">
            <?= csrfField() ?>

            <div class="form-group">
                <label for="username">Identifiant</label>
                <input type="text" id="username" name="username"
                       value="<?= e($_POST['username'] ?? '') ?>"
                       autocomplete="username" autofocus required>
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
