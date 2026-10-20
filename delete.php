<?php
require_once 'config.php';
require_login();
csrf_check();

if (!is_admin()) {
    header('Location: projects.php?error=' . urlencode('Admin access required'));
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    header('Location: projects.php?error=' . urlencode('Invalid ID'));
    exit;
}

$deleteFolder = isset($_POST['deleteFolder']) && $_POST['deleteFolder'] === '1';
$projects = get_projects();

foreach ($projects as $p) {
    if ((int)$p['id'] === $id) {
        // Remove the uploaded logo (path stored relative to this dir)
        if (!empty($p['imageUrl'])) {
            $thumbPath = __DIR__ . '/' . ltrim($p['imageUrl'], '/');
            $real = realpath($thumbPath);
            $base = realpath(UPLOAD_DIR);
            if ($real && $base && strpos($real, $base) === 0 && is_file($real)) {
                @unlink($real);
            }
        }

        if ($deleteFolder && !empty($p['demoFolder'])) {
            $folderPath = __DIR__ . '/projects/' . basename($p['demoFolder']);
            if (is_dir($folderPath)) delete_folder($folderPath);
        }
        break;
    }
}

$projects = array_values(array_filter($projects, fn($p) => (int)$p['id'] !== $id));
save_projects($projects);

header('Location: projects.php?deleted=1');
exit;
