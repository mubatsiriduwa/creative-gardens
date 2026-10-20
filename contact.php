<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

csrf_check();

$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $email === '' || $message === '') {
    header('Location: index.php?error=' . urlencode('Please fill in all fields'));
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: index.php?error=' . urlencode('Please enter a valid email'));
    exit;
}

if (strlen($name) > 100 || strlen($email) > 150 || strlen($message) > 2000) {
    header('Location: index.php?error=' . urlencode('One of your fields is too long'));
    exit;
}

// ---------- Rate limit: max 5 messages per hour per IP ----------
$ip     = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$bucket = sys_get_temp_dir() . '/cg_contact_' . md5($ip);
$now    = time();
$hits   = [];

if (is_file($bucket)) {
    $hits = json_decode(file_get_contents($bucket), true) ?: [];
    $hits = array_values(array_filter($hits, fn($t) => is_int($t) && $t > $now - 3600));
}

if (count($hits) >= 5) {
    header('Location: index.php?error=' . urlencode('Too many messages. Please try again later.'));
    exit;
}

$hits[] = $now;
file_put_contents($bucket, json_encode($hits), LOCK_EX);

// ---------- Save to local inbox ----------
$messages = json_read(MESSAGES_FILE, []);

array_unshift($messages, [
    'id'        => (string) generate_id(),
    'name'      => $name,
    'email'     => $email,
    'message'   => $message,
    'read'      => false,
    'createdAt' => date('c'),
    'ip'        => $ip
]);

json_write(MESSAGES_FILE, $messages);

// ---------- Forward to Formspree ----------
$formspreeUrl = 'https://formspree.io/f/mdeawrgb';

$payload = [
    'name'     => $name,
    'email'    => $email,
    'message'  => $message,
    '_subject' => 'New enquiry from Creative Gardens website',
    '_replyto' => $email,
];

$ch = curl_init($formspreeUrl);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query($payload),
    CURLOPT_HTTPHEADER     => [
        'Accept: application/json',
        'Content-Type: application/x-www-form-urlencoded',
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

// Log failures for debugging (won't block the user)
if ($httpCode !== 200) {
    error_log(sprintf(
        'Formspree forward failed: http=%d err=%s response=%s',
        $httpCode,
        $curlErr,
        substr((string)$response, 0, 500)
    ));
}

header('Location: index.php?sent=1#garden');
exit;
