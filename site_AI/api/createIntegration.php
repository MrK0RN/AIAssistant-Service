
<?php
ini_set('display_errors', '0');
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

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

$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Get hash parameter
$hash = $data['hash'] ?? '';

// Validate hash parameter
if (empty($hash)) {
    http_response_code(400);
    echo json_encode(['error' => 'Hash параметр обязателен']);
    exit;
}

try {
    // Validate user by hash
    $user = pgQuery("SELECT * FROM accounts WHERE hash = '$hash'");
    
    if (!$user || count($user) === 0) {
        http_response_code(401);
        echo json_encode(['error' => 'Неверная авторизация']);
        exit;
    }
    
    $user_id = $user[0]["id"];
    
    // Validate required fields
    if (empty($data['name']) || empty($data['platform'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Название и платформа обязательны']);
        exit;
    }
    
    $name = $data['name'];
    $platform = $data['platform'];
    $config = $data['config'] ?? [];
    $status = $data['status'] ?? 'active';
    
    // Validate platform-specific configuration
    $validationResult = validatePlatformConfig($platform, $config);
    if (!$validationResult['valid']) {
        http_response_code(400);
        echo json_encode(['error' => $validationResult['message']]);
        exit;
    }
    
    $configJson = json_encode($config);
    
    // Create integration
    $result = pgQuery("INSERT INTO integrations (user_id, name, platform, config, status) VALUES ($user_id, '$name', '$platform', '$configJson', '$status') RETURNING id, name, platform, config, status, created_at, updated_at", false, true);
    
    if ($result && count($result) > 0) {
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
    
} catch (Exception $e) {
    error_log("Error in createIntegration.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Внутренняя ошибка сервера']);
}

function validatePlatformConfig($platform, $config) {
    switch ($platform) {
        case 'telegram_bot':
            if (empty($config['bot_token'])) {
                return ['valid' => false, 'message' => 'Bot Token обязателен для Telegram Bot'];
            }
            break;
            
        case 'telegram_client':
            if (empty($config['api_id']) || empty($config['api_hash']) || empty($config['phone_number'])) {
                return ['valid' => false, 'message' => 'API ID, API Hash и номер телефона обязательны для Telegram Client'];
            }
            break;
            
        case 'whatsapp':
            if (empty($config['api_key'])) {
                return ['valid' => false, 'message' => 'API Key обязателен для WhatsApp'];
            }
            break;
            
        case 'instagram':
            if (empty($config['access_token'])) {
                return ['valid' => false, 'message' => 'Access Token обязателен для Instagram'];
            }
            break;
            
        default:
            return ['valid' => false, 'message' => 'Неподдерживаемая платформа'];
    }
    
    return ['valid' => true, 'message' => 'Конфигурация валидна'];
}
?>
