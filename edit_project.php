<?php
require_once 'config.php';
require_login();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
    header('Location: projects.php?error=' . urlencode('Invalid project ID'));
    exit;
}

$project = get_project_by_id($id);
if (!$project) {
    header('Location: projects.php?error=' . urlencode('Project not found'));
    exit;
}

$user        = current_user();
$categories  = get_categories();
$unreadMessages = count(array_filter(json_read(MESSAGES_FILE, []), fn($m) => empty($m['read'])));

$errors = [];
$values = [
    'title'       => $project['title']       ?? '',
    'description' => $project['description'] ?? '',
    'category'    => $project['category']    ?? 'Other',
    'status'      => $project['status']      ?? 'Pending',
    'launchDate'  => $project['launchDate']  ?? '',
    'demoUrl'     => $project['demoUrl']     ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $values['title']       = trim($_POST['title']       ?? '');
    $values['description'] = trim($_POST['description'] ?? '');
    $values['category']    = trim($_POST['category']    ?? '');
    $values['status']      = trim($_POST['status']      ?? '');
    $values['launchDate']  = trim($_POST['launchDate']  ?? '');
    $values['demoUrl']     = trim($_POST['demoUrl']     ?? '');

    if ($values['title'] === '') {
        $errors[] = 'Title is required.';
    }
    if ($values['description'] === '') {
        $errors[] = 'Description is required.';
    }
    if (!in_array($values['category'], $categories, true)) {
        $errors[] = 'Please pick a valid category.';
    }
    if (!in_array($values['status'], ['Pending', 'Complete'], true)) {
        $errors[] = 'Please pick a valid status.';
    }
    if ($values['launchDate'] === '') {
        $errors[] = 'Launch date is required.';
    }
    if ($values['demoUrl'] === '' || !filter_var($values['demoUrl'], FILTER_VALIDATE_URL)) {
        $errors[] = 'A valid project URL is required.';
    }

    $newImage = null;
    if (!empty($_FILES['projectImage']['name'])) {
        $upload = save_uploaded_image('projectImage');
        if (!$upload['ok']) {
            $errors[] = $upload['error'];
        } else {
            $newImage = UPLOAD_URL . $upload['filename'];
        }
    }

    if (empty($errors)) {
        $update = [
            'title'       => $values['title'],
            'description' => $values['description'],
            'category'    => $values['category'],
            'status'      => $values['status'],
            'launchDate'  => $values['launchDate'],
            'demoUrl'     => $values['demoUrl'],
        ];

        if ($newImage !== null) {
            if (!empty($project['imageUrl'])) {
                $oldPath = __DIR__ . '/' . ltrim($project['imageUrl'], '/');
                $real    = realpath($oldPath);
                $base    = realpath(UPLOAD_DIR);
                if ($real && $base && strpos($real, $base) === 0 && is_file($real)) {
                    @unlink($real);
                }
            }
            $update['imageUrl'] = $newImage;
        }

        update_project($id, $update);
        header('Location: projects.php?updated=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit project · Creative Gardens</title>
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
            <h1>Edit project</h1>
            <div class="topbar-user">
                <span class="user-name"><?= e($user['name']) ?></span>
                <span class="user-role"><?= ucfirst($user['role']) ?></span>
            </div>
        </div>

        <div class="content">

            <div class="page-header">
                <h2>Editing: <?= e($project['title']) ?></h2>
                <p>
                    <a href="projects.php" class="btn-link">← Back to projects</a>
                </p>
            </div>

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
                    <h3>Project details</h3>
                    <span class="panel-meta">ID #<?= (int)$project['id'] ?></span>
                </div>
                <div class="panel-body">
                    <form action="edit_project.php?id=<?= (int)$project['id'] ?>" method="POST" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$project['id'] ?>">

                        <div class="form-grid">

                            <div class="form-group full">
                                <label>Project title <span class="required">*</span></label>
                                <input type="text" name="title" required value="<?= e($values['title']) ?>">
                            </div>

                            <div class="form-group full">
                                <label>Description <span class="required">*</span></label>
                                <textarea name="description" required><?= e($values['description']) ?></textarea>
                            </div>

                            <div class="form-group">
                                <label>Category <span class="required">*</span></label>
                                <select name="category" required>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= e($cat) ?>" <?= $values['category'] === $cat ? 'selected' : '' ?>>
                                            <?= e($cat) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Status <span class="required">*</span></label>
                                <select name="status">
                                    <option value="Pending"  <?= $values['status'] === 'Pending'  ? 'selected' : '' ?>>Pending</option>
                                    <option value="Complete" <?= $values['status'] === 'Complete' ? 'selected' : '' ?>>Complete</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Launch date <span class="required">*</span></label>
                                <input type="date" name="launchDate" required value="<?= e($values['launchDate']) ?>">
                            </div>

                            <div class="form-group">
                                <label>Current logo</label>
                                <div style="padding:10px; border:1px solid var(--border); border-radius:8px; background:#f8faf8; display:flex; align-items:center; justify-content:center; height:90px;">
                                    <?php if (!empty($project['imageUrl'])): ?>
                                        <img src="<?= e($project['imageUrl']) ?>" alt="Current logo"
                                             style="max-height:100%; max-width:100%; object-fit:contain;">
                                    <?php else: ?>
                                        <span style="color:var(--muted); font-size:0.82rem;">No logo</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="form-group full">
                                <label>Project URL <span class="required">*</span></label>
                                <input type="url" name="demoUrl" required
                                       value="<?= e($values['demoUrl']) ?>"
                                       placeholder="https://client-site.com">
                                <p class="hint">Full URL where the site is hosted.</p>
                            </div>

                            <div class="form-group full">
                                <label>Replace logo <span style="color:var(--muted); font-weight:400;">(optional)</span></label>
                                <input type="file" name="projectImage" accept="image/*">
                                <p class="hint">Leave empty to keep the current logo. Max 5 MB. JPG, PNG, GIF, or WEBP.</p>
                            </div>

                        </div>

                        <div style="margin-top:24px; display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                            <button type="submit" class="btn-primary">Save changes</button>
                            <a href="projects.php" class="btn-secondary">Cancel</a>
                            <span style="font-size:0.78rem; color:var(--muted); margin-left:auto;">
                                Created <?= e(date('M j, Y', strtotime($project['createdAt'] ?? 'now'))) ?>
                                <?php if (!empty($project['createdBy'])): ?>
                                    by <?= e($project['createdBy']) ?>
                                <?php endif; ?>
                            </span>
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
