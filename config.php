<?php
// =====================================================
// CREATIVE GARDENS — CONFIGURATION & HELPERS
// =====================================================

define('PROJECTS_FILE',    __DIR__ . '/projects.json');
define('USERS_FILE',       __DIR__ . '/users.json');
define('MESSAGES_FILE',    __DIR__ . '/messages.json');
define('CATEGORIES_FILE',  __DIR__ . '/categories.json');
define('UPLOAD_DIR',       __DIR__ . '/uploads/');
define('UPLOAD_URL',       'uploads/');

// Upload limits
define('MAX_IMAGE_SIZE', 5 * 1024 * 1024);
define('ALLOWED_MIMES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// MIME → safe extension map
define('MIME_EXT', [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
]);

// Password reset link lifetime (seconds)
define('RESET_LINK_TTL', 3600); // 1 hour

// -----------------------------------------------------
// MAIL (SMTP) — password reset delivery
// -----------------------------------------------------
// Set MAIL_ENABLED to true when hosting for real.
// Leave it false on localhost (no mail server available).
define('MAIL_ENABLED', false);

define('MAIL_HOST',      'smtp.gmail.com');       // see notes in comments
define('MAIL_PORT',      587);
define('MAIL_USER',      'youremail@gmail.com');  // your SMTP login
define('MAIL_PASS',      'your-app-password');    // NOT your normal password
define('MAIL_SECURE',    'tls');                  // 'tls' for 587, 'ssl' for 465
define('MAIL_FROM',      'no-reply@creativegardens.co.zw');
define('MAIL_FROM_NAME', 'Creative Gardens');

// -----------------------------------------------------
// SESSION — hardened for localhost (XAMPP/WAMP)
// -----------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {

    $sessPath = session_save_path();

    if (strpos($sessPath, ';') !== false && preg_match('/^\d+;(.+)$/', $sessPath, $m)) {
        $sessPath = $m[1];
    }

    if ($sessPath === '' || !is_dir($sessPath) || !is_writable($sessPath)) {
        $fallback = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cg_sessions';
        if (!is_dir($fallback)) {
            @mkdir($fallback, 0777, true);
        }
        if (is_dir($fallback) && is_writable($fallback)) {
            session_save_path($fallback);
        }
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

// ---------- Generic ----------

function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function json_read($file, $default = []) {
    if (!file_exists($file)) return $default;
    $raw = file_get_contents($file);
    if ($raw === false || $raw === '') return $default;
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $default;
}

function json_write($file, $data) {
    file_put_contents(
        $file,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        LOCK_EX
    );
}

function generate_id() {
    return (int) (microtime(true) * 1000) * 1000 + random_int(0, 999);
}

// ---------- CSRF ----------

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check() {
    $sent = $_POST['_csrf'] ?? '';
    $real = $_SESSION['csrf_token'] ?? '';

    if ($real === '' || !hash_equals($real, $sent)) {
        http_response_code(403);
        exit(
            'Invalid CSRF token.<br><br>' .
            'This usually means your browser isn\'t sending the session cookie.<br>' .
            'Try: <a href="logout.php">logout</a>, clear your browser cookies for localhost, ' .
            'then <a href="login.php">login again</a>.'
        );
    }
}

// ---------- Projects ----------

function get_projects() {
    return json_read(PROJECTS_FILE, []);
}

function save_projects($projects) {
    json_write(PROJECTS_FILE, $projects);
}

function get_project_by_id($id) {
    foreach (get_projects() as $p) {
        if ((int)$p['id'] === (int)$id) return $p;
    }
    return null;
}

function update_project($id, $fields) {
    $projects = get_projects();
    $changed  = false;
    foreach ($projects as &$p) {
        if ((int)$p['id'] === (int)$id) {
            foreach ($fields as $k => $v) {
                $p[$k] = $v;
            }
            $p['updatedAt'] = date('c');
            $p['updatedBy'] = current_user()['username'] ?? '';
            $changed = true;
            break;
        }
    }
    unset($p);
    if ($changed) save_projects($projects);
    return $changed;
}

function is_project_complete($status) {
    return in_array($status, ['Complete', 'Completed'], true);
}

// ---------- Categories ----------

function get_categories() {
    $cats = json_read(CATEGORIES_FILE, []);
    if (empty($cats)) {
        $cats = ['Construction', 'Electrical', 'Engineering', 'Agriculture', 'Other'];
        json_write(CATEGORIES_FILE, $cats);
    }
    return $cats;
}

function save_categories($cats) {
    json_write(CATEGORIES_FILE, array_values($cats));
}

function add_category($name) {
    $name = trim($name);
    if ($name === '') return ['error' => 'Category name is required.'];
    if (strlen($name) > 40) return ['error' => 'Category name is too long.'];

    $cats = get_categories();
    foreach ($cats as $c) {
        if (strcasecmp($c, $name) === 0) {
            return ['error' => 'That category already exists.'];
        }
    }
    $cats[] = $name;
    save_categories($cats);
    return ['success' => true];
}

function delete_category($name) {
    if ($name === 'Other') {
        return ['error' => 'The default category cannot be deleted.'];
    }

    $cats = get_categories();
    $cats = array_values(array_filter($cats, fn($c) => $c !== $name));
    save_categories($cats);

    $projects = get_projects();
    $changed = false;
    foreach ($projects as &$p) {
        if (($p['category'] ?? '') === $name) {
            $p['category'] = 'Other';
            $changed = true;
        }
    }
    unset($p);
    if ($changed) save_projects($projects);

    return ['success' => true];
}

// ---------- Users ----------

function get_users() {
    return json_read(USERS_FILE, []);
}

function save_users($users) {
    json_write(USERS_FILE, $users);
}

function has_any_user() {
    return count(get_users()) > 0;
}

function find_user($username) {
    foreach (get_users() as $u) {
        if (strcasecmp($u['username'], $username) === 0) return $u;
    }
    return null;
}

function find_user_by_id($id) {
    foreach (get_users() as $u) {
        if ((int)$u['id'] === (int)$id) return $u;
    }
    return null;
}

function create_user($name, $username, $email, $password, $role = 'editor') {
    $users = get_users();
    foreach ($users as $u) {
        if (strcasecmp($u['username'], $username) === 0) {
            return ['error' => 'That username is already taken.'];
        }
    }
    $maxId = 0;
    foreach ($users as $u) $maxId = max($maxId, (int)$u['id']);

    $users[] = [
        'id'        => $maxId + 1,
        'name'      => trim($name),
        'username'  => trim($username),
        'email'     => trim($email),
        'password'  => password_hash($password, PASSWORD_DEFAULT),
        'role'      => in_array($role, ['admin', 'editor'], true) ? $role : 'editor',
        'createdAt' => date('c')
    ];
    save_users($users);
    return ['success' => true];
}

function update_user_password($id, $newPassword) {
    $users = get_users();
    $changed = false;
    foreach ($users as &$u) {
        if ((int)$u['id'] === (int)$id) {
            $u['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
            $changed = true;
            break;
        }
    }
    unset($u);
    if ($changed) save_users($users);
    return $changed;
}

function update_user_profile($id, $fields) {
    $users = get_users();
    $changed = false;

    // Username uniqueness (excluding self)
    if (isset($fields['username'])) {
        foreach ($users as $u) {
            if ((int)$u['id'] !== (int)$id &&
                strcasecmp($u['username'], $fields['username']) === 0) {
                return ['error' => 'That username is already taken.'];
            }
        }
    }

    foreach ($users as &$u) {
        if ((int)$u['id'] === (int)$id) {
            foreach ($fields as $k => $v) {
                $u[$k] = $v;
            }
            $u['updatedAt'] = date('c');
            $changed = true;
            break;
        }
    }
    unset($u);

    if ($changed) save_users($users);
    return ['success' => true];
}

function update_user_role($id, $role) {
    if (!in_array($role, ['admin', 'editor'], true)) {
        return ['error' => 'Invalid role.'];
    }
    $users = get_users();
    $changed = false;
    foreach ($users as &$u) {
        if ((int)$u['id'] === (int)$id) {
            $u['role'] = $role;
            $changed = true;
            break;
        }
    }
    unset($u);
    if ($changed) save_users($users);
    return ['success' => true];
}

function delete_user($id) {
    $users = get_users();
    save_users(array_values(array_filter($users, fn($u) => (int)$u['id'] !== (int)$id)));
}

function current_user() {
    if (empty($_SESSION['user_id'])) return null;
    return find_user_by_id($_SESSION['user_id']);
}

function require_login() {
    if (!current_user()) {
        header('Location: login.php');
        exit;
    }
}

function require_admin() {
    require_login();
    $u = current_user();
    if (!$u || $u['role'] !== 'admin') {
        header('Location: admin.php?error=' . urlencode('Admin access required'));
        exit;
    }
}

function is_admin() {
    $u = current_user();
    return $u && $u['role'] === 'admin';
}

function admin_count() {
    return count(array_filter(get_users(), fn($u) => $u['role'] === 'admin'));
}

// ---------- Password Reset ----------

function reset_log_path() {
    return __DIR__ . '/password_resets.log';
}

function create_reset_token($userId) {
    $users = get_users();
    $found = false;
    $token = null;

    foreach ($users as &$u) {
        if ((int)$u['id'] === (int)$userId) {
            $u['resetToken']   = bin2hex(random_bytes(32));
            $u['resetExpires'] = time() + RESET_LINK_TTL;
            $token = $u['resetToken'];
            $found = true;
            break;
        }
    }
    unset($u);

    if (!$found) return null;
    save_users($users);
    return $token;
}

function find_user_by_reset_token($token) {
    if ($token === '') return null;
    foreach (get_users() as $u) {
        if (!empty($u['resetToken']) &&
            hash_equals($u['resetToken'], $token) &&
            !empty($u['resetExpires']) &&
            $u['resetExpires'] > time()) {
            return $u;
        }
    }
    return null;
}

function reset_user_password($userId, $newPassword) {
    $users = get_users();
    $changed = false;

    foreach ($users as &$u) {
        if ((int)$u['id'] === (int)$userId) {
            $u['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
            unset($u['resetToken'], $u['resetExpires']);
            $changed = true;
            break;
        }
    }
    unset($u);

    if ($changed) save_users($users);
    return $changed;
}

// ---------- Mail ----------

function send_reset_email($toEmail, $username, $resetLink) {
    if (!MAIL_ENABLED) return false;
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) return false;

    $phpMailerPath = __DIR__ . '/lib/';
    if (!file_exists($phpMailerPath . 'PHPMailer.php')) {
        error_log('PHPMailer not found in ' . $phpMailerPath);
        return false;
    }

    require_once $phpMailerPath . 'PHPMailer.php';
    require_once $phpMailerPath . 'SMTP.php';
    require_once $phpMailerPath . 'Exception.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USER;
        $mail->Password   = MAIL_PASS;
        $mail->SMTPSecure = MAIL_SECURE;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $username);

        $mail->Subject = 'Reset your Creative Gardens password';

        $mail->isHTML(false);
        $mail->Body =
            "Hi " . $username . ",\n\n" .
            "Someone requested a password reset for your Creative Gardens account.\n\n" .
            "Click this link to set a new password (expires in 1 hour):\n\n" .
            $resetLink . "\n\n" .
            "If you didn't request this, you can safely ignore this email — your password won't change.\n\n" .
            "— Creative Gardens\n" .
            "Harare, Zimbabwe";

        $mail->send();
        return true;
    } catch (\Throwable $e) {
        error_log('Reset email failed: ' . $e->getMessage());
        return false;
    }
}

// ---------- Uploads ----------

function save_uploaded_image($fileField) {
    if (!isset($_FILES[$fileField]) || $_FILES[$fileField]['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Upload failed.'];
    }
    $tmp = $_FILES[$fileField]['tmp_name'];

    if ($_FILES[$fileField]['size'] > MAX_IMAGE_SIZE) {
        return ['ok' => false, 'error' => 'Image too large (max 5 MB).'];
    }

    $info = @getimagesize($tmp);
    if ($info === false) {
        return ['ok' => false, 'error' => 'File is not a valid image.'];
    }
    $mime = $info['mime'] ?? '';
    if (!in_array($mime, ALLOWED_MIMES, true)) {
        return ['ok' => false, 'error' => 'Image must be JPG, PNG, GIF, or WEBP.'];
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $ext  = MIME_EXT[$mime];
    $name = generate_id() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = UPLOAD_DIR . $name;

    if (!move_uploaded_file($tmp, $dest)) {
        return ['ok' => false, 'error' => 'Could not save image.'];
    }
    @chmod($dest, 0644);

    return ['ok' => true, 'filename' => $name];
}

// ---------- Misc ----------

function delete_folder($dir) {
    if (!is_dir($dir)) return;
    foreach (glob($dir . '/*') ?: [] as $f) {
        is_dir($f) ? delete_folder($f) : @unlink($f);
    }
    @rmdir($dir);
}
