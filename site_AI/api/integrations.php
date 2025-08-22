
<?php
ini_set('display_errors', '0');
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Include database connection
include "../system/pg.php";

// Get hash parameter for authentication
$hash = $_GET['hash'] ?? $data['hash'] ?? '';

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
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            handleGetIntegrations($user_id);
            break;
        case 'POST':
            handleCreateIntegration($user_id);
            break;
        case 'PUT':
            handleUpdateIntegration($user_id);
            break;
        case 'DELETE':
            handleDeleteIntegration($user_id);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Метод не разрешен']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Error in integrations.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Внутренняя ошибка сервера']);
}

function handleGetIntegrations($user_id) {
    // Get user's integrations
    $integrations = pgQuery("SELECT * FROM integrations WHERE user_id = $user_id ORDER BY created_at DESC");
    
    // Format integrations data
    $formattedIntegrations = [];
    foreach ($integrations as $integration) {
        $config = json_decode($integration['config'], true);
        
        $formattedIntegrations[] = [
            'id' => (int)$integration['id'],
            'name' => $integration['name'],
            'platform' => $integration['platform'],
            'config' => $config,
            'status' => $integration['status'],
            'created_at' => $integration['created_at'],
            'updated_at' => $integration['updated_at']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'integrations' => $formattedIntegrations,
        'total_count' => count($formattedIntegrations)
    ]);
}

function handleCreateIntegration($user_id) {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // Validate required fields
    if (empty($data['name']) || empty($data['platform']) || empty($data['config'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Название, платформа и конфигурация обязательны']);
        exit;
    }
    
    $name = $data['name'];
    $platform = $data['platform'];
    $config = json_encode($data['config']);
    $status = $data['status'] ?? 'active';
    
    // Create integration
    $result = pgQuery("INSERT INTO integrations (user_id, name, platform, config, status) VALUES ($user_id, '$name', '$platform', '$config', '$status') RETURNING id, name, platform, config, status, created_at, updated_at", false, true);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Интеграция успешно создана',
            'integration' => [
                'id' => (int)$result[0]['id'],
                'name' => $result[0]['name'],
                'platform' => $result[0]['platform'],
                'config' => json_decode($result[0]['config'], true),
                'status' => $result[0]['status'],
                'created_at' => $result[0]['created_at'],
                'updated_at' => $result[0]['updated_at']
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Не удалось создать интеграцию']);
    }
}

function handleUpdateIntegration($user_id) {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (empty($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID интеграции обязателен']);
        exit;
    }
    
    $integration_id = (int)$data['id'];
    
    // Check if integration belongs to user
    $existing = pgQuery("SELECT * FROM integrations WHERE id = $integration_id AND user_id = $user_id");
    if (count($existing) === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Интеграция не найдена']);
        exit;
    }
    
    // Build update query
    $updates = [];
    if (!empty($data['name'])) {
        $updates[] = "name = '" . $data['name'] . "'";
    }
    if (!empty($data['platform'])) {
        $updates[] = "platform = '" . $data['platform'] . "'";
    }
    if (!empty($data['config'])) {
        $config = json_encode($data['config']);
        $updates[] = "config = '$config'";
    }
    if (!empty($data['status'])) {
        $updates[] = "status = '" . $data['status'] . "'";
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['error' => 'Нет данных для обновления']);
        exit;
    }
    
    $updates[] = "updated_at = CURRENT_TIMESTAMP";
    $updateQuery = "UPDATE integrations SET " . implode(', ', $updates) . " WHERE id = $integration_id AND user_id = $user_id RETURNING id, name, platform, config, status, created_at, updated_at";
    
    $result = pgQuery($updateQuery, false, true);
    
    if ($result && count($result) > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Интеграция успешно обновлена',
            'integration' => [
                'id' => (int)$result[0]['id'],
                'name' => $result[0]['name'],
                'platform' => $result[0]['platform'],
                'config' => json_decode($result[0]['config'], true),
                'status' => $result[0]['status'],
                'created_at' => $result[0]['created_at'],
                'updated_at' => $result[0]['updated_at']
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Не удалось обновить интеграцию']);
    }
}

function handleDeleteIntegration($user_id) {
    $integration_id = $_GET['id'] ?? '';
    
    if (empty($integration_id)) {
        http_response_code(400);
        echo json_encode(['error' => 'ID интеграции обязателен']);
        exit;
    }
    
    // Check if integration belongs to user
    $existing = pgQuery("SELECT * FROM integrations WHERE id = $integration_id AND user_id = $user_id");
    if (count($existing) === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Интеграция не найдена']);
        exit;
    }
    
    // Delete integration
    $result = pgQuery("DELETE FROM integrations WHERE id = $integration_id AND user_id = $user_id");
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Интеграция успешно удалена'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Не удалось удалить интеграцию']);
    }
}
?>
