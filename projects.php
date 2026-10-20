<?php
require_once 'config.php';
require_login();

$user = current_user();
$projects = get_projects();
$unreadMessages = count(array_filter(json_read(MESSAGES_FILE, []), fn($m) => empty($m['read'])));

$filter = $_GET['filter'] ?? 'all';
if ($filter === 'pending') {
    $projects = array_values(array_filter($projects, fn($p) => !is_project_complete($p['status'] ?? '')));
} elseif ($filter === 'complete') {
    $projects = array_values(array_filter($projects, fn($p) => is_project_complete($p['status'] ?? '')));
}

$heading = 'All projects';
if ($filter === 'pending')  $heading = 'Pending projects';
if ($filter === 'complete') $heading = 'Complete projects';

function format_date($dateStr) {
    if (!$dateStr) return '';
    $ts = strtotime($dateStr);
    return $ts ? date('M j, Y', $ts) : $dateStr;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects · Creative Gardens</title>
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
            <a href="projects.php" class="active">Projects</a>
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

            <a href="account.php">My account</a>

            <div class="sidebar-divider"></div>

            <a href="index.php" target="_blank">View public</a>
            <a href="logout.php" class="sidebar-signout">Sign out</a>
        </nav>
    </aside>

    <main class="main">
        <div class="topbar">
            <h1>Projects</h1>
            <div class="topbar-user">
                <span class="user-name"><?= e($user['name']) ?></span>
                <span class="user-role"><?= ucfirst($user['role']) ?></span>
            </div>
        </div>

        <div class="content">

            <div class="page-header">
                <h2><?= e($heading) ?></h2>
                <p><?= count($projects) ?> total</p>
            </div>

            <div class="filter-tabs">
                <a href="projects.php" class="<?= $filter === 'all' ? 'active' : '' ?>">All</a>
                <a href="projects.php?filter=pending" class="<?= $filter === 'pending' ? 'active' : '' ?>">Pending</a>
                <a href="projects.php?filter=complete" class="<?= $filter === 'complete' ? 'active' : '' ?>">Complete</a>
            </div>

            <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-success">Project updated.</div>
            <?php endif; ?>
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success">Project removed.</div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error"><?= e($_GET['error']) ?></div>
            <?php endif; ?>

            <div class="panel">
                <?php if (empty($projects)): ?>
                    <div class="empty-block">
                        <p>No projects found.</p>
                        <a href="admin.php#upload" class="btn-primary">Add a project</a>
                    </div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th class="col-logo">Logo</th>
                                    <th>Title</th>
                                    <th class="col-category">Category</th>
                                    <th class="col-status">Status</th>
                                    <th class="col-date">Launch</th>
                                    <th class="col-actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projects as $p):
                                    $isComplete = is_project_complete($p['status'] ?? '');
                                    $hasLink    = ($isComplete && !empty($p['demoUrl']));
                                    $category   = $p['category'] ?? 'Other';
                                ?>
                                    <tr class="data-row">
                                        <td class="col-logo">
                                            <div class="table-logo">
                                                <img src="<?= e($p['imageUrl']) ?>" alt="<?= e($p['title']) ?>">
                                            </div>
                                        </td>

                                        <td>
                                            <div class="table-title"><?= e($p['title']) ?></div>
                                            <div class="table-desc"><?= e($p['description']) ?></div>
                                        </td>

                                        <td class="col-category">
                                            <span class="table-category"><?= e($category) ?></span>
                                        </td>

                                        <td class="col-status">
                                            <span class="project-status <?= $isComplete ? 'complete' : 'pending' ?>">
                                                <?= $isComplete ? 'Complete' : 'Pending' ?>
                                            </span>
                                        </td>

                                        <td class="col-date">
                                            <?= e(format_date($p['launchDate'] ?? '')) ?>
                                        </td>

                                        <td class="col-actions">
                                            <div class="table-actions">
                                                <?php if ($hasLink): ?>
                                                    <a href="<?= e($p['demoUrl']) ?>" target="_blank" rel="noopener" class="btn-secondary btn-sm">Open</a>
                                                <?php else: ?>
                                                    <span class="btn-secondary btn-sm disabled">—</span>
                                                <?php endif; ?>

                                                <a href="edit_project.php?id=<?= (int)$p['id'] ?>" class="btn-secondary btn-sm">Edit</a>

                                                <?php if (is_admin()): ?>
                                                    <form action="delete.php" method="POST"
                                                          onsubmit="return confirm('Delete this project?');"
                                                          style="display:inline;">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                                        <button type="submit" class="btn-danger btn-sm">Delete</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
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
