<?php
require_once 'config.php';

if (current_user()) {
    header('Location: admin.php');
    exit;
}

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$user  = find_user_by_reset_token($token);

$error   = '';
$success = false;

if (!$user) {
    $error = 'This reset link is invalid or has expired. Please request a new one.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $pass    = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($pass !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        reset_user_password($user['id'], $pass);
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset password · Creative Gardens</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">

<div class="auth-wrap">
    <a href="index.php" class="auth-brand">
        <img src="logo.png" alt="Creative Gardens">
    </a>

    <?php if ($success): ?>
        <h1 class="auth-title">Password updated.</h1>
        <p class="auth-sub">You can now sign in with your new password.</p>
        <a href="login.php?reset=1" class="btn-primary btn-full" style="display:block; text-align:center;">Go to sign in</a>

    <?php elseif (!$user): ?>
        <h1 class="auth-title">Invalid link.</h1>
        <p class="auth-sub"><?= e($error) ?></p>
        <a href="forgot_password.php" class="btn-primary btn-full" style="display:block; text-align:center;">Request a new link</a>

    <?php else: ?>
        <h1 class="auth-title">Set a new password.</h1>
        <p class="auth-sub">Choose a password for <strong><?= e($user['username']) ?></strong>.</p>

        <?php if ($error): ?>
            <div class="auth-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">

            <label>New password
                <input type="password" name="password" required minlength="6" autofocus autocomplete="new-password">
            </label>

            <label>Confirm new password
                <input type="password" name="confirm" required minlength="6" autocomplete="new-password">
            </label>

            <button type="submit" class="btn-primary btn-full">Reset password</button>
        </form>

        <p style="margin-top:24px; text-align:center; font-size:0.88rem;">
            <a href="login.php" class="btn-link">← Back to sign in</a>
        </p>
    <?php endif; ?>
</div>

</body>
</html>
