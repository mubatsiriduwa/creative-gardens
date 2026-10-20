<?php
require_once 'config.php';
require_admin();

$user = current_user();
$messages = json_read(MESSAGES_FILE, []);

usort($messages, fn($a, $b) => strtotime($b['createdAt']) - strtotime($a['createdAt']));

$unreadCount = count(array_filter($messages, fn($m) => empty($m['read'])));

function format_datetime($iso) {
    $ts = strtotime($iso);
    return $ts ? date('M j, Y · g:ia', $ts) : $iso;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages · Creative Gardens</title>
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
            <a href="messages.php" class="active">
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
            <h1>Messages</h1>
            <div class="topbar-user">
                <span class="user-name"><?= e($user['name']) ?></span>
                <span class="user-role"><?= ucfirst($user['role']) ?></span>
            </div>
        </div>

        <div class="content">

            <div class="page-header">
                <h2>Messages</h2>
                <p>
                    <?= count($messages) ?> total
                    <?php if ($unreadCount > 0): ?>
                        · <strong><?= $unreadCount ?> unread</strong>
                    <?php endif; ?>
                </p>
            </div>

            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success">Message deleted.</div>
            <?php endif; ?>
            <?php if (isset($_GET['marked'])): ?>
                <div class="alert alert-success">Marked as read.</div>
            <?php endif; ?>

            <div class="panel">
                <?php if (empty($messages)): ?>
                    <div class="empty-block">
                        <p>No messages yet.</p>
                    </div>
                <?php else: ?>
                    <div class="message-list">
                        <?php foreach ($messages as $m):
                            $isRead = !empty($m['read']);
                        ?>
                            <div class="message-row <?= $isRead ? '' : 'unread' ?>">
                                <div class="message-avatar">
                                    <?= strtoupper(substr($m['name'], 0, 1)) ?>
                                </div>

                                <div class="message-body">
                                    <div class="message-top">
                                        <div>
                                            <span class="message-name"><?= e($m['name']) ?></span>
                                            <span class="message-email">
                                                &lt;<?= e($m['email']) ?>&gt;
                                            </span>
                                        </div>
                                        <span class="message-date">
                                            <?= e(format_datetime($m['createdAt'] ?? '')) ?>
                                        </span>
                                    </div>

                                    <p class="message-text"><?= nl2br(e($m['message'])) ?></p>

                                    <div class="message-actions">
                                        <a href="mailto:<?= e($m['email']) ?>?subject=Re: Your message to Creative Gardens" class="btn-secondary btn-sm">
                                            Reply
                                        </a>

                                        <?php if (!$isRead): ?>
                                            <form action="message_action.php" method="POST" style="display:inline;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="mark_read">
                                                <input type="hidden" name="id" value="<?= e($m['id']) ?>">
                                                <button type="submit" class="btn-secondary btn-sm">Mark as read</button>
                                            </form>
                                        <?php endif; ?>

                                        <form action="message_action.php" method="POST"
                                              onsubmit="return confirm('Delete this message?');"
                                              style="display:inline;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= e($m['id']) ?>">
                                            <button type="submit" class="btn-danger btn-sm">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
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
