<?php
/**
 * LuxWrap Studio - Configuración General
 * Version 2.0
 */

require_once __DIR__ . '/env-loader.php';

// ===== CONFIGURACIÓN DE ADMIN =====
define('ADMIN_USER', luxwrap_env('ADMIN_USER', 'admin'));
// Generar nuevo hash: php -r "echo password_hash('TuNuevaContraseña', PASSWORD_BCRYPT);"
define('ADMIN_PASSWORD_HASH', luxwrap_env('ADMIN_PASSWORD_HASH', '$2y$10$YQh5/vQ6Z8Hy3GkLmJZbOeGhM7fDJ8rK1N5vW9xB2cA4pE6sR0iKy'));

// ===== CONFIGURACIÓN DE EMAIL =====
define('CONTACT_EMAIL', luxwrap_env('CONTACT_EMAIL', 'luxwrapstudioky@gmail.com'));
define('SITE_NAME', luxwrap_env('SITE_NAME', 'LuxWrap Studio'));
define('SITE_URL', luxwrap_env('SITE_URL', 'https://luxwrapstudio.com'));

// ===== GOOGLE reCAPTCHA v3 =====
// Obtener keys en: https://www.google.com/recaptcha/admin
// Seleccionar reCAPTCHA v3
// Agregar tu dominio (luxwrapstudio.com)
define('RECAPTCHA_SITE_KEY', luxwrap_env('RECAPTCHA_SITE_KEY', ''));
define('RECAPTCHA_SECRET_KEY', luxwrap_env('RECAPTCHA_SECRET_KEY', ''));
define('RECAPTCHA_ENABLED', filter_var(luxwrap_env('RECAPTCHA_ENABLED', false), FILTER_VALIDATE_BOOLEAN)); // Cambiar a true cuando tengas las keys

// ===== CONFIGURACIÓN DE ARCHIVOS =====
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp', 'gif']);
define('ALLOWED_MIME_TYPES', [
    'image/jpeg',
    'image/png',
    'image/webp',
    'image/gif'
]);
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/');
define('DATA_DIR', dirname(__DIR__) . '/data/');
define('PORTFOLIO_JSON', DATA_DIR . 'portfolio.json');

// ===== SEGURIDAD =====
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_TIMEOUT', 3600); // 1 hora
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutos

// ===== RATE LIMITING (Contacto) =====
define('CONTACT_RATE_LIMIT', 3); // máximo 3 envíos
define('CONTACT_RATE_WINDOW', 3600); // por hora

// ===== FUNCIONES HELPER =====

/**
 * Genera un token CSRF
 */
function generateCSRFToken() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida un token CSRF
 */
function validateCSRFToken($token) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitiza input string
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Lee el archivo portfolio.json
 */
function getPortfolioData() {
    if (!file_exists(PORTFOLIO_JSON)) {
        return ['projects' => []];
    }
    $json = file_get_contents(PORTFOLIO_JSON);
    $data = json_decode($json, true);
    return $data ?: ['projects' => []];
}

/**
 * Guarda datos al portfolio.json
 */
function savePortfolioData($data) {
    $dir = dirname(PORTFOLIO_JSON);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return file_put_contents(
        PORTFOLIO_JSON,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );
}

/**
 * Genera un ID único para proyectos
 */
function generateProjectId() {
    return uniqid('proj_', true);
}

/**
 * Verifica si el usuario admin está autenticado
 */
function isAdminAuthenticated() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        return false;
    }
    
    // Verificar timeout de sesión
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Respuesta JSON
 */
function jsonResponse($success, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $data));
    exit;
}
?>
