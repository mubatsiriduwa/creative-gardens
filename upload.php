<?php
require_once 'config.php';
require_login();
csrf_check();

foreach (['title','description','category','status','launchDate','projectUrl'] as $f) {
    if (empty($_POST[$f])) {
        header('Location: admin.php?error=' . urlencode("Missing field: $f"));
        exit;
    }
}

$status = $_POST['status'];
if (!in_array($status, ['Pending', 'Complete'], true)) {
    header('Location: admin.php?error=' . urlencode('Invalid status'));
    exit;
}

$category = $_POST['category'];
if (!in_array($category, get_categories(), true)) {
    header('Location: admin.php?error=' . urlencode('Invalid category'));
    exit;
}

$projectUrl = trim($_POST['projectUrl']);
if (!filter_var($projectUrl, FILTER_VALIDATE_URL)) {
    header('Location: admin.php?error=' . urlencode('Project URL is not valid'));
    exit;
}

$upload = save_uploaded_image('projectImage');
if (!$upload['ok']) {
    header('Location: admin.php?error=' . urlencode($upload['error']));
    exit;
}

$projects = get_projects();

array_unshift($projects, [
    'id'          => generate_id(),
    'title'       => trim($_POST['title']),
    'description' => trim($_POST['description']),
    'category'    => $category,
    'status'      => $status,
    'launchDate'  => trim($_POST['launchDate']),
    'demoUrl'     => $projectUrl,
    'imageUrl'    => UPLOAD_URL . $upload['filename'],
    'createdAt'   => date('c'),
    'createdBy'   => current_user()['username'] ?? ''
]);

save_projects($projects);

header('Location: admin.php?success=1');
exit;
