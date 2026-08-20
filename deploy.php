<?php
/**
 * LuxWrap Studio - Auto Deploy Script
 * Descarga automáticamente los últimos cambios desde GitHub
 * 
 * URL: https://luxwrapstudio.com/deploy.php?secret=TU_SECRET_KEY
 * 
 * SEGURIDAD: Cambia DEPLOY_SECRET antes de subir a producción
 */

// =====================================================
// CONFIGURACIÓN - CAMBIAR ESTOS VALORES
// =====================================================

// Clave secreta para proteger el endpoint (CAMBIAR OBLIGATORIO)
define('DEPLOY_SECRET', 'luxwrap_deploy_2026_' . md5('change-this-secret'));

// Ruta donde está el sitio en el servidor
define('SITE_PATH', '/home/luxwrapstudio/public_html'); // CAMBIAR según tu hosting

// Rama a usar
define('GIT_BRANCH', 'main');

// Log file
define('LOG_FILE', __DIR__ . '/deploy.log');

// =====================================================
// NO MODIFICAR DEBAJO DE ESTA LÍNEA
// =====================================================

// Headers
header('Content-Type: application/json; charset=utf-8');

// Verificar token de seguridad
if (!isset($_GET['secret']) || $_GET['secret'] !== DEPLOY_SECRET) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Access denied']));
}

// Función para escribir en el log
function writeLog($message) {
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents(LOG_FILE, "[$timestamp] $message\n", FILE_APPEND);
}

// Función para ejecutar comandos
function executeCommand($command) {
    writeLog("Ejecutando: $command");
    exec($command . ' 2>&1', $output, $return);
    $output_str = implode("\n", $output);
    writeLog("Output: $output_str");
    writeLog("Return code: $return");
    return ['output' => $output_str, 'return' => $return];
}

// Iniciar despliegue
writeLog("===== INICIO DE DESPLIEGUE =====");
writeLog("IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

$results = [];

// Cambiar al directorio del sitio
chdir(SITE_PATH);

// 1. Verificar que es un repo git
if (!is_dir('.git')) {
    writeLog("ERROR: No es un repositorio Git");
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'El directorio no es un repositorio Git. Ejecuta primero: git clone git@github.com:beto2l/luxwrapstudio.git .'
    ]);
    exit;
}

// 2. Hacer git fetch + reset para forzar la actualización
writeLog("Descargando últimos cambios...");

// Primero fetch
$result = executeCommand('git fetch origin ' . GIT_BRANCH);
$results[] = ['step' => 'fetch', 'output' => $result['output'], 'success' => $result['return'] === 0];

// Luego reset al último commit remoto
$result = executeCommand('git reset --hard origin/' . GIT_BRANCH);
$results[] = ['step' => 'reset', 'output' => $result['output'], 'success' => $result['return'] === 0];

if ($result['return'] !== 0) {
    writeLog("ERROR: No se pudo actualizar");
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al descargar cambios',
        'details' => $results
    ]);
    exit;
}

// 3. Configurar permisos
writeLog("Configurando permisos...");
executeCommand('chmod -R 755 assets admin scripts 2>/dev/null');
executeCommand('chmod -R 777 uploads data 2>/dev/null');
executeCommand('chmod 644 data/*.json 2>/dev/null');

// 4. Limpiar caché si existe
if (is_dir(SITE_PATH . '/cache')) {
    writeLog("Limpiando caché...");
    executeCommand('rm -rf cache/*');
}

// 5. Obtener último commit
$lastCommit = executeCommand('git log -1 --pretty=format:"%h - %s (%cr)"');

writeLog("===== DESPLIEGUE COMPLETADO =====");

// Respuesta exitosa
http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Sitio actualizado correctamente',
    'timestamp' => date('Y-m-d H:i:s'),
    'last_commit' => $lastCommit['output'],
    'steps' => $results
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
