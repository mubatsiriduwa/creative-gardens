<?php
require_once 'config.php';

if (current_user()) {
    header('Location: admin.php');
    exit;
}

$error = '';
$firstRun = !has_any_user();

// ---------- First-run account creation ----------
if ($firstRun && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_first'])) {
    csrf_check();

    $name     = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $pass     = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if ($name === '' || $username === '' || $email === '' || $pass === '') {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($pass !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $result = create_user($name, $username, $email, $pass, 'admin');
        if (isset($result['error'])) {
            $error = $result['error'];
        } else {
            header('Location: login.php?created=1');
            exit;
        }
    }
}

// ---------- Login ----------
if (!$firstRun && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    csrf_check();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $user = find_user($username);
    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        header('Location: admin.php');
        exit;
    } else {
        $error = 'Incorrect username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $firstRun ? 'Set up' : 'Sign in' ?> · Creative Gardens</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">

<div class="auth-wrap">
    <a href="index.php" class="auth-brand">
        <img src="logo.png" alt="Creative Gardens">
    </a>

    <?php if ($firstRun): ?>
        <h1 class="auth-title">Welcome.</h1>
        <p class="auth-sub">Set up your admin account to begin.</p>

        <?php if ($error): ?>
            <div class="auth-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <?= csrf_field() ?>
            <input type="hidden" name="create_first" value="1">

            <label>Name
                <input type="text" name="name" required autofocus>
            </label>

            <label>Username
                <input type="text" name="username" required>
            </label>

            <label>Email
                <input type="email" name="email" required>
            </label>

            <label>Password
                <input type="password" name="password" required minlength="6">
            </label>

            <label>Confirm password
                <input type="password" name="confirm" required minlength="6">
            </label>

            <button type="submit" class="btn-primary btn-full">Create account</button>
        </form>

    <?php else: ?>
        <h1 class="auth-title">Welcome back.</h1>
        <p class="auth-sub">Sign in to manage your projects.</p>

        <?php if (isset($_GET['created'])): ?>
            <div class="auth-success">Account created. Please sign in.</div>
        <?php endif; ?>

        <?php if (isset($_GET['reset'])): ?>
            <div class="auth-success">Password updated. You can sign in now.</div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="auth-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <?= csrf_field() ?>
            <input type="hidden" name="login" value="1">

            <label>Username
                <input type="text" name="username" required autofocus>
            </label>

            <label>Password
                <input type="password" name="password" required>
            </label>

            <button type="submit" class="btn-primary btn-full">Sign in</button>

            <p style="margin-top:18px; text-align:center; font-size:0.88rem;">
                <a href="forgot_password.php" class="btn-link">Forgot password?</a>
            </p>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
