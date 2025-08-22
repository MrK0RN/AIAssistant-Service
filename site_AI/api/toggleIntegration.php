
<?php

$json = file_get_contents('php://input');
$data = json_decode($json, true);
// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не разрешен']);
    exit;
}

// Include database connection
include "../system/pg.php";

// Get parameters
$hash = $data['hash'] ?? '';
$integration_id = $data['id'] ?? '';

// Validate parameters
if (empty($hash)) {
    http_response_code(400);
    echo json_encode(['error' => 'Hash параметр обязателен']);
    exit;
}

if (empty($integration_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID интеграции обязателен']);
    exit;
}

$user = pgQuery("SELECT * FROM accounts WHERE hash = '$hash'");
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Неверная авторизация']);
    exit;
}

$user_id = $user[0]["id"];

try {
    // Check if integration belongs to user and get current status
    $integration = pgQuery("SELECT * FROM integrations WHERE id = $integration_id AND user_id = $user_id");
    
    if (count($integration) === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Интеграция не найдена']);
        exit;
    }
    
    $current_status = $integration[0]['status'];
    $new_status = ($current_status === 'active') ? 'inactive' : 'active';
    
    // Update integration status
    $result = pgQuery("UPDATE integrations SET status = '$new_status', updated_at = CURRENT_TIMESTAMP WHERE id = $integration_id AND user_id = $user_id RETURNING id, name, platform, status", false, true);
    
    if ($result && count($result) > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Статус интеграции изменен',
            'integration' => [
                'id' => (int)$result[0]['id'],
                'name' => $result[0]['name'],
                'platform' => $result[0]['platform'],
                'status' => $result[0]['status']
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Не удалось изменить статус интеграции']);
    }
    
} catch (Exception $e) {
    error_log("Error in toggleIntegration.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Внутренняя ошибка сервера']);
}
?>
