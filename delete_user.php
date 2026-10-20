<?php
require_once 'config.php';
require_admin();
csrf_check();

$id = (int)($_POST['id'] ?? 0);
$me = current_user();

if ($id <= 0) {
    header('Location: users.php?error=' . urlencode('Invalid user'));
    exit;
}

if ($id === (int)$me['id']) {
    header('Location: users.php?error=' . urlencode('You cannot delete yourself'));
    exit;
}

$target = find_user_by_id($id);
if (!$target) {
    header('Location: users.php?error=' . urlencode('User not found'));
    exit;
}

if ($target['role'] === 'admin' && admin_count() <= 1) {
    header('Location: users.php?error=' . urlencode('Cannot delete the last admin'));
    exit;
}

delete_user($id);
header('Location: users.php?deleted=1');
exit;
