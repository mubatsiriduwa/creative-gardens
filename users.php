<?php
require_once 'config.php';
require_admin();

$user = current_user();
$users = get_users();
$error = '';

// Add user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    csrf_check();

    $name     = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $pass     = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'editor';

    if ($name === '' || $username === '' || $email === '' || $pass === '') {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $result = create_user($name, $username, $email, $pass, $role);
        if (isset($result['error'])) {
            $error = $result['error'];
        } else {
            header('Location: users.php?added=1');
            exit;
        }
    }
}

// Change role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    csrf_check();

    $targetId = (int)($_POST['id'] ?? 0);
    $newRole  = $_POST['role'] ?? '';

    if ($targetId === (int)$user['id']) {
        $error = 'You cannot change your own role.';
    } elseif ($targetId <= 0) {
        $error = 'Invalid user.';
    } else {
        $target = find_user_by_id($targetId);
        if ($target && $target['role'] === 'admin' && $newRole === 'editor' && admin_count() <= 1) {
            $error = 'Cannot demote the last admin.';
        } else {
            $result = update_user_role($targetId, $newRole);
            if (isset($result['error'])) {
                $error = $result['error'];
            } else {
                header('Location: users.php?updated=1');
                exit;
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
    <title>Users · Creative Gardens</title>
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
            <a href="messages.php">Messages</a>
            <a href="users.php" class="active">Users</a>
            <a href="account.php">My account</a>

            <div class="sidebar-divider"></div>

            <a href="index.php" target="_blank">View public</a>
            <a href="logout.php" class="sidebar-signout">Sign out</a>
        </nav>
    </aside>

    <main class="main">
        <div class="topbar">
            <h1>Users</h1>
            <div class="topbar-user">
                <span class="user-name"><?= e($user['name']) ?></span>
                <span class="user-role"><?= ucfirst($user['role']) ?></span>
            </div>
        </div>

        <div class="content">

            <div class="page-header">
                <h2>Manage users</h2>
                <p>Who can access this panel.</p>
            </div>

            <?php if (isset($_GET['added'])): ?>
                <div class="alert alert-success">User added.</div>
            <?php endif; ?>
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success">User removed.</div>
            <?php endif; ?>
            <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-success">User updated.</div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <div class="panel">
                <div class="panel-header">
                    <h3>Add a new user</h3>
                </div>
                <div class="panel-body">
                    <form method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="add_user" value="1">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Full name <span class="required">*</span></label>
                                <input type="text" name="name" required>
                            </div>
                            <div class="form-group">
                                <label>Username <span class="required">*</span></label>
                                <input type="text" name="username" required>
                            </div>
                            <div class="form-group full">
                                <label>Email <span class="required">*</span></label>
                                <input type="email" name="email" required>
                                <p class="hint">Used for password resets.</p>
                            </div>
                            <div class="form-group">
                                <label>Password <span class="required">*</span></label>
                                <input type="password" name="password" required minlength="6">
                            </div>
                            <div class="form-group">
                                <label>Role <span class="required">*</span></label>
                                <select name="role">
                                    <option value="editor">Editor</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn-primary" style="margin-top:20px;">Add user</button>
                    </form>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h3>All users</h3>
                    <span class="panel-meta"><?= count($users) ?> total</span>
                </div>

                <div class="project-list">
                    <?php foreach ($users as $u):
                        $isMe = ((int)$u['id'] === (int)$user['id']);
                        $isLastAdmin = ($u['role'] === 'admin' && admin_count() === 1);
                        $canDelete = !$isMe && !$isLastAdmin;
                    ?>
                        <div class="project-row">
                            <div class="user-avatar"><?= strtoupper(substr($u['name'], 0, 1)) ?></div>

                            <div class="project-row-info">
                                <div class="project-row-title">
                                    <?= e($u['name']) ?><?= $isMe ? ' (you)' : '' ?>
                                </div>
                                <div class="project-row-desc">
                                    @<?= e($u['username']) ?>
                                    <?php if (!empty($u['email'])): ?>
                                        · <?= e($u['email']) ?>
                                    <?php endif; ?>
                                </div>
                                <div class="project-row-meta">
                                    <span class="project-status <?= $u['role'] === 'admin' ? 'complete' : 'pending' ?>">
                                        <?= ucfirst($u['role']) ?>
                                    </span>
                                </div>
                            </div>

                            <div class="project-row-actions">
                                <?php if (!$isMe): ?>
                                    <form method="POST" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="change_role" value="1">
                                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                        <select name="role" onchange="this.form.submit()"
                                                style="padding:6px 10px; border-radius:8px; border:1px solid var(--border-strong); font-size:0.78rem; cursor:pointer;">
                                            <option value="editor" <?= $u['role'] === 'editor' ? 'selected' : '' ?>>Editor</option>
                                            <option value="admin"  <?= $u['role'] === 'admin'  ? 'selected' : '' ?>>Admin</option>
                                        </select>
                                    </form>
                                <?php endif; ?>

                                <?php if ($canDelete): ?>
                                    <form action="delete_user.php" method="POST"
                                          onsubmit="return confirm('Remove this user?');"
                                          style="display:inline;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                        <button type="submit" class="btn-danger btn-sm">Remove</button>
                                    </form>
                                <?php elseif ($isMe): ?>
                                    <span class="btn-secondary btn-sm disabled">—</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
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
