<?php
require_once 'config.php';
require_login();

$user = current_user();
$unreadMessages = count(array_filter(json_read(MESSAGES_FILE, []), fn($m) => empty($m['read'])));

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $name         = trim($_POST['name'] ?? '');
    $username     = trim($_POST['username'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $currentPass  = $_POST['current_password'] ?? '';
    $newPass      = $_POST['new_password'] ?? '';
    $confirmPass  = $_POST['confirm_password'] ?? '';

    if ($name === '') {
        $errors[] = 'Name cannot be empty.';
    } elseif (strlen($name) > 100) {
        $errors[] = 'Name is too long.';
    }

    if ($username === '') {
        $errors[] = 'Username cannot be empty.';
    } elseif (strlen($username) > 50) {
        $errors[] = 'Username is too long.';
    } elseif (!preg_match('/^[a-zA-Z0-9_\.\-]+$/', $username)) {
        $errors[] = 'Username may only contain letters, numbers, dots, dashes, and underscores.';
    }

    if ($email === '') {
        $errors[] = 'Email cannot be empty.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    $wantsPasswordChange = ($newPass !== '' || $confirmPass !== '');

    if ($wantsPasswordChange) {
        if ($currentPass === '') {
            $errors[] = 'Please enter your current password to change it.';
        } elseif (!password_verify($currentPass, $user['password'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($newPass) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        } elseif ($newPass !== $confirmPass) {
            $errors[] = 'New passwords do not match.';
        }
    }

    if (empty($errors)) {
        $profileFields = [
            'name'     => $name,
            'username' => $username,
            'email'    => $email,
        ];

        if ($wantsPasswordChange) {
            $profileFields['password'] = password_hash($newPass, PASSWORD_DEFAULT);
        }

        $result = update_user_profile($user['id'], $profileFields);

        if (isset($result['error'])) {
            $errors[] = $result['error'];
        } else {
            $success = true;
            $user = current_user();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My account · Creative Gardens</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="admin-body">

<div class="layout">

    <aside class="sidebar">
        <div class="sidebar-brand">
            <img src="logo.png" alt="Creative Gardens">
        </div>

        <nav class="sidebar-nav">
            <a href="admin.php">Overview</a>
            <a href="projects.php">Projects</a>
            <a href="admin.php#upload">Upload</a>
            <a href="categories.php">Categories</a>
            <a href="messages.php">
                Messages
                <?php if ($unreadMessages > 0): ?>
                    <span class="nav-badge"><?= $unreadMessages ?></span>
                <?php endif; ?>
            </a>

            <?php if (is_admin()): ?>
                <a href="users.php">Users</a>
            <?php endif; ?>

            <a href="account.php" class="active">My account</a>

            <div class="sidebar-divider"></div>

            <a href="index.php" target="_blank">View public</a>
            <a href="logout.php" class="sidebar-signout">Sign out</a>
        </nav>
    </aside>

    <main class="main">
        <div class="topbar">
            <h1>My account</h1>
            <div class="topbar-user">
                <span class="user-name"><?= e($user['name']) ?></span>
                <span class="user-role"><?= ucfirst($user['role']) ?></span>
            </div>
        </div>

        <div class="content">

            <div class="page-header">
                <h2>Account settings</h2>
                <p>Update your name, username, email, and password.</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">Your account has been updated.</div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <strong>Please fix the following:</strong>
                    <ul style="margin:8px 0 0 18px;">
                        <?php foreach ($errors as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="panel">
                <div class="panel-header">
                    <h3>Profile</h3>
                    <span class="panel-meta">
                        Role: <?= ucfirst($user['role']) ?>
                        · Joined <?= e(date('M j, Y', strtotime($user['createdAt'] ?? 'now'))) ?>
                    </span>
                </div>
                <div class="panel-body">
                    <form method="POST">
                        <?= csrf_field() ?>

                        <div class="form-grid">

                            <div class="form-group">
                                <label>Full name <span class="required">*</span></label>
                                <input type="text" name="name" required maxlength="100"
                                       value="<?= e($user['name']) ?>">
                            </div>

                            <div class="form-group">
                                <label>Username <span class="required">*</span></label>
                                <input type="text" name="username" required maxlength="50"
                                       value="<?= e($user['username']) ?>">
                                <p class="hint">Letters, numbers, dots, dashes, and underscores only.</p>
                            </div>

                            <div class="form-group full">
                                <label>Email <span class="required">*</span></label>
                                <input type="email" name="email" required maxlength="150"
                                       value="<?= e($user['email'] ?? '') ?>">
                                <p class="hint">Used for password resets.</p>
                            </div>

                        </div>

                        <div class="panel-header" style="padding:24px 0 8px; border:none; background:transparent;">
                            <h3 style="font-size:0.95rem;">Change password</h3>
                        </div>
                        <p class="hint" style="margin-bottom:18px;">
                            Leave blank to keep your current password.
                        </p>

                        <div class="form-grid">

                            <div class="form-group full">
                                <label>Current password</label>
                                <input type="password" name="current_password" autocomplete="current-password">
                            </div>

                            <div class="form-group">
                                <label>New password</label>
                                <input type="password" name="new_password" minlength="6" autocomplete="new-password">
                            </div>

                            <div class="form-group">
                                <label>Confirm new password</label>
                                <input type="password" name="confirm_password" minlength="6" autocomplete="new-password">
                            </div>

                        </div>

                        <div style="margin-top:24px; display:flex; gap:12px; align-items:center;">
                            <button type="submit" class="btn-primary">Save changes</button>
                            <a href="admin.php" class="btn-secondary">Cancel</a>
                        </div>

                    </form>
                </div>
            </div>

        </div>

        <footer class="admin-footer">
            <span>Creative Gardens · Admin</span>
            <span>&copy; <?= date('Y') ?> · Made in Harare, Zimbabwe</span>
        </footer>
    </main>

</div>

</body>
</html>
