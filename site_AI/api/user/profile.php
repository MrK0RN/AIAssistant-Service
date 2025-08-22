
<?php
// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include database connection
include "../../system/pg.php";

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
    
    $userData = $user[0];
    
    // Return user profile data
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => (int)$userData['id'],
            'username' => $userData['login'],
            'email' => $userData['email'],
            'first_name' => $userData['first_name'],
            'last_name' => $userData['last_name'],
            'phone' => $userData['phone'],
            'full_name' => $userData['first_name'] . ' ' . $userData['last_name']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error in user profile: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Внутренняя ошибка сервера']);
}
?>
