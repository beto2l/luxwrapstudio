<?php
/**
 * LuxWrap Studio - Portfolio API
 * Returns portfolio data as JSON for the frontend
 */

require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=300'); // 5 min cache

$data = getPortfolioData();

// Sort projects by order
if (!empty($data['projects'])) {
    usort($data['projects'], function($a, $b) {
        return ($a['order'] ?? 999) - ($b['order'] ?? 999);
    });
    
    // Sort images within each project
    foreach ($data['projects'] as &$project) {
        if (!empty($project['images'])) {
            usort($project['images'], function($a, $b) {
                return ($a['order'] ?? 999) - ($b['order'] ?? 999);
            });
        }
    }
}

echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
