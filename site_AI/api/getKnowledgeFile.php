
<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include "../system/pg.php";

// Get hash from request
$hash = $_GET['hash'] ?? '';

if (empty($hash)) {
    http_response_code(401);
    echo json_encode(['error' => 'Отсутствует хеш авторизации']);
    exit;
}

$user = pgQuery("SELECT * FROM accounts WHERE hash = '$hash'");

if (!$user || count($user) === 0) {
    http_response_code(401);
    echo json_encode(['error' => 'Неверная авторизация']);
    exit;
}

$user_id = $user[0]["id"];

// Get file ID
$file_id = $_GET['id'] ?? '';

if (empty($file_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID файла обязателен']);
    exit;
}

try {
    $file_id = intval($file_id);
    
    // Get file from knowledge base
    $file = pgQuery("SELECT * FROM knowledge_base WHERE id = $file_id AND user_id = $user_id");
    
    if (!$file || count($file) === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Файл не найден']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'file' => $file[0]
    ]);
    
} catch (Exception $e) {
    error_log("Get knowledge file error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка получения файла']);s WHERE hash = '$hash'");
if (!$user || count($user) === 0) {
    http_response_code(401);
    echo json_encode(['error' => 'Неверная авторизация']);
    exit;
}

$user_id = $user[0]["id"];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $file_id = $_GET['id'] ?? '';
        
        if (empty($file_id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Требуется ID файла']);
            exit;
        }
        
        $file_id = intval($file_id);
        
        $file = pgQuery("SELECT * FROM knowledge_base WHERE id = $file_id AND user_id = $user_id");
        
        if (count($file) === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Файл не найден']);
            exit;
        }
        
        $file_data = $file[0];
        
        // If file has a physical path and content is empty, try to read from file
        if ($file_data['file_path'] && empty($file_data['content']) && file_exists($file_data['file_path'])) {
            $file_data['content'] = file_get_contents($file_data['file_path']);
        }
        
        echo json_encode([
            'success' => true,
            'file' => $file_data
        ]);
        
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Метод не разрешен']);
    }
} catch (Exception $e) {
    error_log("Get Knowledge File API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка получения файла']);
}
?>
