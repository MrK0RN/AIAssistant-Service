
<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include database connection
include "../system/pg.php";

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не разрешен']);
    exit;
}

// Get hash parameter
$hash = $_GET['hash'] ?? '';

// Validate hash parameter
if (empty($hash)) {
    http_response_code(400);
    echo json_encode(['error' => 'Hash параметр обязателен']);
    exit;
}

try {
    // Validate user by hash
    $user = pgQuery("SELECT * FROM accounts WHERE hash = '$hash'");
    
    if (count($user) === 0) {
        http_response_code(401);
        echo json_encode(['error' => 'Неверная авторизация']);
        exit;
    }
    
    $user_id = $user[0]["id"];
    
    // Get statistics
    $totalAssistants = pgQuery("SELECT COUNT(*) as count FROM assistant WHERE user_id = $user_id", false, false);
    $activeAssistants = pgQuery("SELECT COUNT(*) as count FROM assistant WHERE user_id = $user_id AND status = 'active'", false, false);
    $inactiveAssistants = pgQuery("SELECT COUNT(*) as count FROM assistant WHERE user_id = $user_id AND status = 'inactive'", false, false);
    
    $totalCount = $totalAssistants ? (int)$totalAssistants[0]['count'] : 0;
    $activeCount = $activeAssistants ? (int)$activeAssistants[0]['count'] : 0;
    $inactiveCount = $inactiveAssistants ? (int)$inactiveAssistants[0]['count'] : 0;
    
    // Platform statistics
    $platformStats = pgQuery("SELECT platform, COUNT(*) as count FROM assistant WHERE user_id = $user_id GROUP BY platform");
    
    $platforms = [];
    foreach ($platformStats as $stat) {
        $platforms[$stat['platform']] = (int)$stat['count'];
    }
    
    // Return statistics
    echo json_encode([
        'success' => true,
        'statistics' => [
            'total_assistants' => $totalCount,
            'active_assistants' => $activeCount,
            'inactive_assistants' => $inactiveCount,
            'platform_breakdown' => $platforms,
            'success_rate' => $totalCount > 0 ? round(($activeCount / $totalCount) * 100, 1) : 0
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error in statistics API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Внутренняя ошибка сервера']);
}
?>
