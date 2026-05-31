<?php
/**
 * Ruffian Target Admin — Login Page
 * Passcode-only authentication with PHP sessions.
 */

session_start();

// Already logged in? Go to dashboard.
if (isset($_SESSION['ruffian_admin_auth']) && $_SESSION['ruffian_admin_auth'] === true) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$passcode = 'ruff4444';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted = isset($_POST['passcode']) ? trim($_POST['passcode']) : '';
    if ($submitted === $passcode) {
        $_SESSION['ruffian_admin_auth'] = true;
        header('Location: dashboard.php');
        exit();
    } else {
        $error = 'Parol noto\'g\'ri. Qaytadan urinib ko\'ring.';
    }
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RUFFIAN — Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600&family=Lora:ital,wght@0,400;1,400&family=Montserrat:wght@300;400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="login-wrapper">

    <div class="brand-wordmark">RUFFIAN</div>
    <hr class="brand-rule">

    <div class="login-card">
        <h1 class="login-title">Admin Panel</h1>
        <p class="login-subtitle">Kirish uchun parolni kiriting</p>

        <?php if ($error): ?>
            <div class="login-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="" autocomplete="off">
            <div class="input-group">
                <input
                    type="password"
                    name="passcode"
                    class="form-input"
                    placeholder="Parol"
                    autocomplete="off"
                    required
                    autofocus
                >
            </div>
            <button type="submit" class="btn-primary">Kirish</button>
        </form>
    </div>

    <footer class="admin-footer">
        <hr class="footer-rule">
        <p class="footer-text">ruffian.uz &middot; admin</p>
    </footer>

</div>

</body>
</html>
