<?php
require_once 'config.php';
require_login();

$user = current_user();
$projects = get_projects();
$messages = json_read(MESSAGES_FILE, []);
$categories = get_categories();

$totalProjects = count($projects);
$pendingCount  = count(array_filter($projects, fn($p) => !is_project_complete($p['status'] ?? '')));
$completeCount = count(array_filter($projects, fn($p) => is_project_complete($p['status'] ?? '')));
$unreadCount   = count(array_filter($messages, fn($m) => empty($m['read'])));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overview · Creative Gardens</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="admin-body">

<div class="layout">

    <aside class="sidebar">
        <div class="sidebar-brand">
            <img src="logo.png" alt="Creative Gardens">
        </div>

        <nav class="sidebar-nav">
            <a href="admin.php" class="active">Overview</a>
            <a href="projects.php">Projects</a>
            <a href="admin.php#upload">Upload</a>
            <a href="categories.php">Categories</a>
            <a href="messages.php">
                Messages
                <?php if ($unreadCount > 0): ?>
                    <span class="nav-badge"><?= $unreadCount ?></span>
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
            <h1>Overview</h1>
            <div class="topbar-user">
                <span class="user-name"><?= e($user['name']) ?></span>
                <span class="user-role"><?= ucfirst($user['role']) ?></span>
            </div>
        </div>

        <div class="content">

            <div class="page-header">
                <h2>Welcome back, <?= e($user['name']) ?>.</h2>
                <p>Here's what's growing today.</p>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">Project added successfully.</div>
            <?php endif; ?>
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success">Project removed.</div>
            <?php endif; ?>
            <?php if (isset($_GET['warning'])): ?>
                <div class="alert alert-warning"><?= e($_GET['warning']) ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error"><?= e($_GET['error']) ?></div>
            <?php endif; ?>

            <div class="stats-grid">
                <a href="projects.php" class="stat-card">
                    <div class="stat-label">Projects</div>
                    <div class="stat-value"><?= $totalProjects ?></div>
                    <div class="stat-hint">View all →</div>
                </a>
                <a href="projects.php?filter=pending" class="stat-card">
                    <div class="stat-label">Pending</div>
                    <div class="stat-value"><?= $pendingCount ?></div>
                    <div class="stat-hint">Not yet published</div>
                </a>
                <a href="projects.php?filter=complete" class="stat-card">
                    <div class="stat-label">Complete</div>
                    <div class="stat-value"><?= $completeCount ?></div>
                    <div class="stat-hint">Live on the site</div>
                </a>
                <a href="messages.php" class="stat-card">
                    <div class="stat-label">Messages</div>
                    <div class="stat-value"><?= count($messages) ?></div>
                    <div class="stat-hint">
                        <?= $unreadCount > 0 ? $unreadCount . ' unread' : 'All read' ?>
                    </div>
                </a>
            </div>

            <div class="panel" id="upload">
                <div class="panel-header">
                    <h3>Add a new project</h3>
                    <span class="panel-meta">URL, logo and category required</span>
                </div>
                <div class="panel-body">
                    <form action="upload.php" method="POST" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <div class="form-grid">

                            <div class="form-group full">
                                <label>Project title <span class="required">*</span></label>
                                <input type="text" name="title" required>
                            </div>

                            <div class="form-group full">
                                <label>Description <span class="required">*</span></label>
                                <textarea name="description" required></textarea>
                            </div>

                            <div class="form-group">
                                <label>Category <span class="required">*</span></label>
                                <select name="category" required>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= e($cat) ?>"><?= e($cat) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Status <span class="required">*</span></label>
                                <select name="status">
                                    <option value="Pending">Pending</option>
                                    <option value="Complete">Complete</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Launch date <span class="required">*</span></label>
                                <input type="date" name="launchDate" required>
                            </div>

                            <div class="form-group full">
                                <label>Project URL <span class="required">*</span></label>
                                <input type="url" name="projectUrl" placeholder="https://client-site.com" required>
                                <p class="hint">Full URL where the site is hosted — e.g. <code>https://lytton.co.zw</code>.</p>
                            </div>

                            <div class="form-group full">
                                <label>Company logo <span class="required">*</span></label>
                                <input type="file" name="projectImage" accept="image/*" required>
                                <p class="hint">Upload the client's logo. PNG with transparent background looks best. Max 5 MB.</p>
                            </div>

                        </div>

                        <button type="submit" class="btn-primary" style="margin-top:20px;">Save project</button>
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

<script>
if (window.history.replaceState && window.location.search) {
    window.history.replaceState({}, '', window.location.pathname);
}
</script>

</body>
</html>
