<?php
require_once 'config.php';
require_admin();
csrf_check();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: messages.php');
    exit;
}

$action = $_POST['action'] ?? '';
$id     = $_POST['id'] ?? '';

if ($id === '') {
    header('Location: messages.php?error=' . urlencode('Invalid message'));
    exit;
}

$messages = json_read(MESSAGES_FILE, []);

if ($action === 'mark_read') {
    foreach ($messages as &$m) {
        if ((string)$m['id'] === (string)$id) {
            $m['read'] = true;
            break;
        }
    }
    unset($m);
    json_write(MESSAGES_FILE, $messages);
    header('Location: messages.php?marked=1');
    exit;
}

if ($action === 'delete') {
    $messages = array_values(array_filter($messages, fn($m) => (string)$m['id'] !== (string)$id));
    json_write(MESSAGES_FILE, $messages);
    header('Location: messages.php?deleted=1');
    exit;
}

header('Location: messages.php');
exit;
