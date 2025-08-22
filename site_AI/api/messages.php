
<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include database connection
include "../system/pg.php";

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
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get recent messages (mock data for now)
        $mockMessages = [
            [
                'id' => 1,
                'assistant_id' => 1,
                'assistant_name' => 'Поддержка клиентов',
                'client' => '@john_doe',
                'platform' => 'telegram',
                'last_message' => 'Спасибо за помощь!',
                'time' => date('Y-m-d H:i:s', strtotime('-5 minutes')),
                'status' => 'answered'
            ],
            [
                'id' => 2,
                'assistant_id' => 2,
                'assistant_name' => 'WhatsApp бот',
                'client' => '+7 999 123 45 67',
                'platform' => 'whatsapp',
                'last_message' => 'Когда будет доставка?',
                'time' => date('Y-m-d H:i:s', strtotime('-12 minutes')),
                'status' => 'pending'
            ],
            [
                'id' => 3,
                'assistant_id' => 1,
                'assistant_name' => 'Поддержка клиентов',
                'client' => '@jane_smith',
                'platform' => 'telegram',
                'last_message' => 'Как оформить возврат?',
                'time' => date('Y-m-d H:i:s', strtotime('-25 minutes')),
                'status' => 'in_progress'
            ]
        ];
        
        echo json_encode([
            'success' => true,
            'messages' => $mockMessages,
            'total_count' => count($mockMessages)
        ]);
        
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Метод не разрешен']);
    }
    
} catch (Exception $e) {
    error_log("Error in messages API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Внутренняя ошибка сервера']);
}
?>
