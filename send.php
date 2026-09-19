<?php

require __DIR__ . '/vendor/autoload.php';

const RATE_LIMIT_MAX = 5;          // submissions allowed ...
const RATE_LIMIT_WINDOW = 3600;    // ... per IP per this many seconds

header('Content-Type: application/json; charset=utf-8');

function respond(int $status, bool $ok, string $message): void
{
    http_response_code($status);
    echo json_encode(['ok' => $ok, 'message' => $message]);
    exit;
}

function renderTemplate(string $file, array $vars): string
{
    extract($vars);
    ob_start();
    require $file;
    return ob_get_clean();
}

/**
 * Returns false once an IP has used up its submissions for the current window.
 */
function withinRateLimit(string $ip): bool
{
    $dir = __DIR__ . '/storage/ratelimit';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    $file = $dir . '/' . hash('sha256', $ip) . '.json';
    $now  = time();

    $fp = @fopen($file, 'c+');
    if ($fp === false) {
        error_log("send.php: cannot open rate limit file {$file}");
        return true;
    }
    flock($fp, LOCK_EX);
    $hits = json_decode(stream_get_contents($fp) ?: '[]', true) ?: [];
    $hits = array_values(array_filter($hits, fn ($t) => $t > $now - RATE_LIMIT_WINDOW));

    $allowed = count($hits) < RATE_LIMIT_MAX;
    if ($allowed) {
        $hits[] = $now;
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($hits));
    }
    flock($fp, LOCK_UN);
    fclose($fp);

    return $allowed;
}

function loadEnv(string $path): array
{
    if (!is_readable($path)) {
        return [];
    }
    $env = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $env[trim($key)] = trim(trim($value), "\"'");
    }
    return $env;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, false, 'Method not allowed.');
}

// Only accept AJAX submissions coming from our own page
if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest') {
    respond(403, false, 'Forbidden.');
}

session_start();
$token = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
    respond(403, false, 'Your session has expired. Please refresh the page and try again.');
}
session_write_close();

// Honeypot: real users never fill this hidden field
if (!empty($_POST['website'])) {
    respond(200, true, 'Thank you. We will be in touch shortly.');
}

$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $email === '' || $message === '') {
    respond(422, false, 'Please fill in all fields.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(422, false, 'Please enter a valid email address.');
}
if (mb_strlen($name) > 100 || mb_strlen($message) > 5000) {
    respond(422, false, 'Your message is too long.');
}
// Block header injection via the name field
$name = preg_replace('/[\r\n]+/', ' ', $name);

if (!withinRateLimit($_SERVER['REMOTE_ADDR'] ?? 'unknown')) {
    respond(429, false, 'Too many requests. Please try again later.');
}

$env    = loadEnv(__DIR__ . '/.env');
$apiKey = $env['RESEND_MAIL_SERVER_KEY'] ?? '';
$fromEmail = $env['MAIL_FROM'] ?? '';
$fromName  = $env['MAIL_FROM_NAME'] ?? 'ALLZERVE Website';
$mailTo    = $env['MAIL_TO'] ?? '';

if ($apiKey === '') {
    error_log('send.php: RESEND_MAIL_SERVER_KEY is not set in .env');
    respond(500, false, 'Mail service is not configured.');
}
if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL) || !filter_var($mailTo, FILTER_VALIDATE_EMAIL)) {
    error_log('send.php: MAIL_FROM or MAIL_TO in .env is missing or not a valid email');
    respond(500, false, 'Mail service is not configured.');
}

$safeName    = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$safeEmail   = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
$replyUrl    = htmlspecialchars(
    'mailto:' . $email . '?subject=' . rawurlencode('Re: Your inquiry to ALLZERVE'),
    ENT_QUOTES,
    'UTF-8'
);
$sentAt = (new DateTime('now', new DateTimeZone('Asia/Bangkok')))->format('j F Y, H:i') . ' (ICT)';

try {
    $resend = Resend::client($apiKey);
    $resend->emails->send([
        'from'     => $fromName . ' <' . $fromEmail . '>',
        'to'       => [$mailTo],
        'reply_to' => str_replace(['"', '<', '>', ','], '', $name) . ' <' . $email . '>',
        'subject'  => 'New inquiry from ' . $name,
        'html'     => renderTemplate(__DIR__ . '/templates/inquiry-email.php', compact(
            'safeName',
            'safeEmail',
            'safeMessage',
            'replyUrl',
            'sentAt'
        )),
        'text'     => "New inquiry from the ALLZERVE website\nReceived {$sentAt}\n\nName: {$name}\nEmail: {$email}\n\nMessage:\n{$message}",
    ]);
    respond(200, true, 'Thank you. We will be in touch shortly.');
} catch (Throwable $e) {
    error_log('send.php: Resend error: ' . $e->getMessage());
    respond(500, false, 'Sorry, your message could not be sent. Please try again later.');
}
