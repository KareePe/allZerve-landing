<?php

declare(strict_types=1);

const RATE_LIMIT_MAX = 5;          // submissions allowed ...
const RATE_LIMIT_WINDOW = 3600;    // ... per IP per this many seconds

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function respond(int $status, bool $ok, string $message): void
{
    http_response_code($status);
    echo json_encode(['ok' => $ok, 'message' => $message], JSON_UNESCAPED_UNICODE);
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

/** getenv() returns false — not null — when a variable is unset. */
function env(string $key, string $default = ''): string
{
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
}

// Everything above this point is cheap. The Composer autoloader (Resend +
// Guzzle) is only required once a request has earned the right to send mail.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, false, 'Method not allowed.');
}

// Only accept AJAX submissions coming from our own page
if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest') {
    respond(403, false, 'Forbidden.');
}

session_start();
$token = $_POST['csrf_token'] ?? '';
$valid = !empty($_SESSION['csrf_token'])
    && is_string($token)
    && hash_equals($_SESSION['csrf_token'], $token);
session_write_close();
if (!$valid) {
    respond(403, false, 'Your session has expired. Please refresh the page and try again.');
}

// Honeypot: real users never fill this hidden field. Answer exactly like a real
// success so bots learn nothing — but leave a trace, because a password manager
// filling this field looks identical to a delivered email from the outside.
if (!empty($_POST['website'])) {
    error_log('send.php: honeypot triggered, no mail sent (ip=' . ($_SERVER['REMOTE_ADDR'] ?? '?') . ')');
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

$apiKey    = env('RESEND_MAIL_SERVER_KEY');
$fromEmail = env('MAIL_FROM');
$fromName  = env('MAIL_FROM_NAME', 'ALLZERVE Website');
$mailTo    = env('MAIL_TO');

if ($apiKey === '') {
    error_log('send.php: RESEND_MAIL_SERVER_KEY is not set');
    respond(500, false, 'Mail service is not configured.');
}
if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL) || !filter_var($mailTo, FILTER_VALIDATE_EMAIL)) {
    error_log('send.php: MAIL_FROM or MAIL_TO is missing or not a valid email');
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

require __DIR__ . '/vendor/autoload.php';

try {
    $resend = Resend::client($apiKey);
    $sent = $resend->emails->send([
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
    // The id is the handle to look the message up in the Resend dashboard.
    error_log('send.php: accepted by Resend, id=' . ($sent->id ?? 'unknown') . ' to=' . $mailTo);
    respond(200, true, 'Thank you. We will be in touch shortly.');
} catch (Throwable $e) {
    error_log('send.php: Resend error: ' . $e->getMessage());
    respond(500, false, 'Sorry, your message could not be sent. Please try again later.');
}
