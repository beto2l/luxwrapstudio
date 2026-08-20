<?php
/**
 * LuxWrap Studio - Contact Form Processor
 * Envía formularios mediante Web3Forms usando una clave guardada en .env.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/env-loader.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    jsonResponse(false, 'Method not allowed');
}

// ===== ANTI-SPAM: Honeypot =====
if (!empty($_POST['website_url']) || !empty($_POST['botcheck'])) {
    jsonResponse(true, 'Message sent successfully');
}

// ===== RATE LIMITING =====
session_start();
$now = time();
if (!isset($_SESSION['contact_submissions'])) {
    $_SESSION['contact_submissions'] = [];
}

$_SESSION['contact_submissions'] = array_filter(
    $_SESSION['contact_submissions'],
    function($timestamp) use ($now) {
        return ($now - $timestamp) < CONTACT_RATE_WINDOW;
    }
);

if (count($_SESSION['contact_submissions']) >= CONTACT_RATE_LIMIT) {
    http_response_code(429);
    jsonResponse(false, 'Too many submissions. Please try again later.');
}

// ===== VALIDATE INPUTS =====
$name = sanitizeInput($_POST['name'] ?? '');
$email = sanitizeInput($_POST['email'] ?? '');
$phone = sanitizeInput($_POST['phone'] ?? '');
$vehicle = sanitizeInput($_POST['vehicle'] ?? '');
$service = sanitizeInput($_POST['service'] ?? '');
$message = sanitizeInput($_POST['message'] ?? '');

$errors = [];

if (empty($name) || strlen($name) < 2) {
    $errors[] = 'Name is required (minimum 2 characters)';
}
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Valid email is required';
}
if (empty($phone) || strlen($phone) < 7) {
    $errors[] = 'Phone number is required';
}
if (empty($vehicle)) {
    $errors[] = 'Vehicle type is required';
}
if (empty($service)) {
    $errors[] = 'Service type is required';
}

if (!empty($errors)) {
    http_response_code(400);
    jsonResponse(false, implode('. ', $errors));
}

$accessKey = luxwrap_env('WEB3FORMS_ACCESS_KEY');
if (!$accessKey) {
    error_log('LuxWrap contact form error: WEB3FORMS_ACCESS_KEY is missing from .env');
    http_response_code(500);
    jsonResponse(false, 'Email service is not configured. Please call us at (859) 636-7294.');
}

$debugEnabled = isset($_GET['debug']) && hash_equals((string) luxwrap_env('DEPLOY_SECRET', ''), (string) $_GET['debug']);

// ===== SEND EMAIL THROUGH WEB3FORMS =====
$payload = [
    'access_key' => $accessKey,
    'subject' => 'New Quote Request — LuxWrap Studio Website',
    'from_name' => 'LuxWrap Studio Website',
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'vehicle' => $vehicle,
    'service' => $service,
    'message' => $message,
    'site' => SITE_URL,
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'submitted_at' => date('Y-m-d H:i:s')
];

$sent = false;
$responseBody = '';
$errorDetail = '';

if (function_exists('curl_init')) {
    $ch = curl_init('https://api.web3forms.com/submit');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => ['Accept: application/json']
    ]);
    $responseBody = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($responseBody, true);
    $sent = ($httpCode >= 200 && $httpCode < 300 && (!isset($decoded['success']) || $decoded['success'] !== false));
    if (!$sent) {
        $errorDetail = 'HTTP ' . $httpCode . ' ' . ($curlError ?: $responseBody);
    }
} else {
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\nAccept: application/json\r\n",
            'content' => http_build_query($payload),
            'timeout' => 15
        ]
    ]);
    $responseBody = @file_get_contents('https://api.web3forms.com/submit', false, $context);
    $decoded = json_decode($responseBody, true);
    $sent = ($responseBody !== false && (!isset($decoded['success']) || $decoded['success'] !== false));
    if (!$sent) {
        $errorDetail = $responseBody ?: 'file_get_contents failed';
    }
}

$_SESSION['contact_submissions'][] = $now;

// ===== LOG SUBMISSION =====
$logEntry = date('Y-m-d H:i:s') . " | $name | $email | $phone | $vehicle | $service | " .
            ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . " | " . ($sent ? 'SENT_WEB3FORMS' : 'FAILED_WEB3FORMS') .
            ($errorDetail ? " | $errorDetail" : '') . "\n";
$logDir = dirname(__DIR__) . '/data/';
if (!is_dir($logDir)) mkdir($logDir, 0755, true);
@file_put_contents($logDir . 'contact-log.txt', $logEntry, FILE_APPEND | LOCK_EX);

if ($sent) {
    jsonResponse(true, 'Message sent successfully! We will contact you within 24 hours.');
}

http_response_code(500);
if ($debugEnabled) {
    jsonResponse(false, 'Email could not be sent. Please try calling us at (859) 636-7294.', [
        'debug' => [
            'curl_available' => function_exists('curl_init'),
            'allow_url_fopen' => (bool) ini_get('allow_url_fopen'),
            'openssl_available' => extension_loaded('openssl'),
            'access_key_loaded' => !empty($accessKey),
            'response_body' => $responseBody,
            'error_detail' => $errorDetail
        ]
    ]);
}
jsonResponse(false, 'Email could not be sent. Please try calling us at (859) 636-7294.');
?>
