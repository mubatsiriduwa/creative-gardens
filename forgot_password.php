<?php
require_once 'config.php';

if (current_user()) {
    header('Location: admin.php');
    exit;
}

$error   = '';
$message = '';
$link    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $username = trim($_POST['username'] ?? '');

    if ($username === '') {
        $error = 'Please enter your username.';
    } else {
        $user = find_user($username);

        // Always show the same message, whether the user exists or not
        $message = 'If that username exists, a reset link has been ' .
                   (MAIL_ENABLED ? 'sent to the email on file.' : 'generated.');

        if ($user) {
            $token = create_reset_token($user['id']);

            if ($token) {
                // Build reset URL
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $dir    = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
                $resetUrl = $scheme . '://' . $host . $dir . '/reset_password.php?token=' . urlencode($token);

                // Audit log
                $logLine = sprintf(
                    "[%s] user=%s id=%d ip=%s mail=%s link=%s\n",
                    date('c'),
                    $user['username'],
                    $user['id'],
                    $_SERVER['REMOTE_ADDR'] ?? '-',
                    (!empty($user['email']) && MAIL_ENABLED) ? 'attempt' : 'off',
                    $resetUrl
                );
                @file_put_contents(reset_log_path(), $logLine, FILE_APPEND | LOCK_EX);

                // Try email
                $sent = false;
                if (!empty($user['email']) && MAIL_ENABLED) {
                    $sent = send_reset_email($user['email'], $user['username'], $resetUrl);
                }

                // Show on screen only if we couldn't email (localhost, or failure)
                if (!$sent) {
                    $link = $resetUrl;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot password · Creative Gardens</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">

<div class="auth-wrap">
    <a href="index.php" class="auth-brand">
        <img src="logo.png" alt="Creative Gardens">
    </a>

    <h1 class="auth-title">Forgot password?</h1>
    <p class="auth-sub">Enter your username and we'll generate a reset link.</p>

    <?php if ($error): ?>
        <div class="auth-error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($message): ?>
        <div class="auth-success"><?= e($message) ?></div>
    <?php endif; ?>

    <?php if ($link): ?>
        <div class="auth-success" style="word-break:break-all;">
            <strong>Reset link (copy this):</strong><br>
            <a href="<?= e($link) ?>" style="color:var(--forest); text-decoration:underline;"><?= e($link) ?></a>
            <br><br>
            <small style="color:var(--ink-soft);">This link expires in 1 hour.</small>
        </div>
    <?php endif; ?>

    <?php if (!$message): ?>
        <form method="POST" class="auth-form">
            <?= csrf_field() ?>

            <label>Username
                <input type="text" name="username" required autofocus>
            </label>

            <button type="submit" class="btn-primary btn-full">Generate reset link</button>
        </form>
    <?php endif; ?>

    <p style="margin-top:24px; text-align:center; font-size:0.88rem;">
        <a href="login.php" class="btn-link">← Back to sign in</a>
    </p>
</div>

</body>
</html>
