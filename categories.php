<?php
require_once 'config.php';
require_admin();

$user       = current_user();
$categories = get_categories();
$projects   = get_projects();
$messages   = json_read(MESSAGES_FILE, []);
$unreadMessages = count(array_filter($messages, fn($m) => empty($m['read'])));

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $result = add_category($_POST['name'] ?? '');
        if (isset($result['error'])) {
            $error = $result['error'];
        } else {
            header('Location: categories.php?added=1');
            exit;
        }
    }

    if ($action === 'delete') {
        $name = $_POST['name'] ?? '';
        if ($name !== '') {
            $result = delete_category($name);
            if (isset($result['error'])) {
                $error = $result['error'];
            } else {
                header('Location: categories.php?deleted=1');
                exit;
            }
        }
    }
}

$counts = [];
foreach ($projects as $p) {
    $c = $p['category'] ?? 'Other';
    $counts[$c] = ($counts[$c] ?? 0) + 1;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories · Creative Gardens</title>
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
            <a href="categories.php" class="active">Categories</a>
            <a href="messages.php">
                Messages
                <?php if ($unreadMessages > 0): ?>
                    <span class="nav-badge"><?= $unreadMessages ?></span>
                <?php endif; ?>
            </a>

            <?php if (is_admin()): ?>
                <a href="users.php">Users</a>
            <?php endif; ?>

            <a href="account.php">My account</a>

            <div class="sidebar-divider"></div>

            <a href="index.php" target="_blank">View public</a>
            <a href="logout.php" class="sidebar-signout">Sign out</a>
        </nav>
    </aside>

    <main class="main">
        <div class="topbar">
            <h1>Categories</h1>
            <div class="topbar-user">
                <span class="user-name"><?= e($user['name']) ?></span>
                <span class="user-role"><?= ucfirst($user['role']) ?></span>
            </div>
        </div>

        <div class="content">

            <div class="page-header">
                <h2>Manage categories</h2>
                <p>These appear in the upload form and the public filter.</p>
            </div>

            <?php if (isset($_GET['added'])): ?>
                <div class="alert alert-success">Category added.</div>
            <?php endif; ?>
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success">Category removed. Any projects in it were moved to "Other".</div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <div class="panel">
                <div class="panel-header">
                    <h3>Add a category</h3>
                </div>
                <div class="panel-body">
                    <form method="POST" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="add">
                        <input type="text" name="name" required placeholder="e.g. Construction" maxlength="40"
                               style="flex:1; min-width:240px; padding:10px 14px; border:1px solid var(--border-strong); border-radius:8px; font-family:inherit; font-size:0.92rem;">
                        <button type="submit" class="btn-primary">Add category</button>
                    </form>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h3>All categories</h3>
                    <span class="panel-meta"><?= count($categories) ?> total</span>
                </div>

                <div class="project-list">
                    <?php if (empty($categories)): ?>
                        <div class="empty-block"><p>No categories yet.</p></div>
                    <?php else: ?>
                        <?php foreach ($categories as $cat):
                            $usage = $counts[$cat] ?? 0;
                            $isProtected = ($cat === 'Other');
                        ?>
                            <div class="project-row">
                                <div class="category-icon"><?= strtoupper(substr($cat, 0, 1)) ?></div>

                                <div class="project-row-info">
                                    <div class="project-row-title"><?= e($cat) ?></div>
                                    <div class="project-row-desc">
                                        <?= $usage ?> project<?= $usage === 1 ? '' : 's' ?>
                                    </div>
                                </div>

                                <div class="project-row-actions">
                                    <?php if (!$isProtected): ?>
                                        <form method="POST" style="display:inline;"
                                              onsubmit="return confirm('Delete this category? Projects will be moved to Other.');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="name" value="<?= e($cat) ?>">
                                            <button type="submit" class="btn-danger btn-sm">Delete</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="btn-secondary btn-sm disabled">Default</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
