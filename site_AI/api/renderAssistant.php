
<?php
ini_set('display_errors', '0');
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
    $username = $user[0]["login"];
    
    // Get user's assistants
    $assistants = pgQuery("SELECT * FROM assistant WHERE user_id = $user_id ORDER BY id DESC");
    
    // Format assistants data
    $formattedAssistants = [];
    foreach ($assistants as $assistant) {
        $credentials = json_decode($assistant['credentials'], true);
        
        $formattedAssistants[] = [
            'id' => (int)$assistant['id'],
            'name' => $assistant['name'],
            'tone' => $assistant['tone'],
            'language' => $assistant['language'],
            'prompt' => $assistant['prompt'],
            'platform' => $assistant['platform'],
            'integration_type' => $assistant['platform'],
            'credentials' => $credentials,
            'status' => $assistant['status'],
            'created_at' => $assistant['created_at'] ?? date('Y-m-d H:i:s')
        ];
    }
    
    // Return response
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => (int)$user_id,
            'username' => $username
        ],
        'assistants' => $formattedAssistants,
        'total_count' => count($formattedAssistants)
    ]);
    
} catch (Exception $e) {
    error_log("Error in renderAssistant.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Внутренняя ошибка сервера']);
}
?>
