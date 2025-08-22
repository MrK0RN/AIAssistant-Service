
<?php
ini_set('display_errors', '0');
// Check if user is authenticated


// Include database connection
include "../system/pg.php";

try {
    // Handle GET request (query parameters)
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $hash = trim($_GET['hash'] ?? '');
        $assistant_id = trim($_GET['aid'] ?? '');
        
        if (empty($hash) || empty($assistant_id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Не указаны обязательные параметры']);
            exit;
        }

        $exists = pgQuery("SELECT * FROM accounts WHERE hash = '$hash';");
        if (count($exists) === 0) {
            http_response_code(401);
            echo json_encode(['error' => 'Необходима авторизация']);
            exit;
        }
        $user_id = $exists[0]["id"];
        
        // Verify user hash (simple security check)
        $user = pgQuery("SELECT * FROM accounts WHERE id = '$user_id' AND hash = '$hash'");
        if (!$user || count($user) === 0) {
            http_response_code(403);
            echo json_encode(['error' => 'Неверная авторизация']);
            exit;
        }
        
        // Check if assistant belongs to user
        $assistant = pgQuery("SELECT * FROM assistant WHERE id = '$assistant_id' AND user_id = '$user_id'");
        if (!$assistant || count($assistant) === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Ассистент не найден']);
            exit;
        }
        
        // Delete the assistant
        $deleteResult = pgQuery("DELETE FROM assistant WHERE id = '$assistant_id' AND user_id = '$user_id'", false, false);
        
        if ($deleteResult !== false) {
            // Log the deletion
            error_log("Assistant deleted: {$assistant_id} by user {$user_id}");
            
            echo json_encode([
                'success' => true,
                'message' => 'Ассистент успешно удален'
            ]);
        } else {
            throw new Exception('Ошибка при удалении из базы данных');
        }
    }
    // Handle DELETE request (RESTful)
    else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Get assistant ID from URL path
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $pathParts = explode('/', trim($path, '/'));
        $assistant_id = end($pathParts);
        
        if (empty($assistant_id)) {
            http_response_code(400);
            echo json_encode(['error' => 'ID ассистента не указан']);
            exit;
        }
        
        // Check if assistant belongs to user
        $assistant = pgQuery("SELECT * FROM assistants WHERE id = '$assistant_id' AND user_id = '$user_id'");
        if (!$assistant || count($assistant) === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Ассистент не найден или не принадлежит пользователю']);
            exit;
        }
        
        // Delete the assistant
        $deleteResult = pgQuery("DELETE FROM assistants WHERE id = '$assistant_id' AND user_id = '$user_id'", false, false);
        
        if ($deleteResult !== false) {
            // Log the deletion
            error_log("Assistant deleted: {$assistant_id} by user {$user_id}");
            
            echo json_encode([
                'success' => true,
                'message' => 'Ассистент успешно удален'
            ]);
        } else {
            throw new Exception('Ошибка при удалении из базы данных');
        }
    }
    else {
        http_response_code(405);
        echo json_encode(['error' => 'Метод не разрешен']);
    }
    
} catch (Exception $e) {
    error_log("Assistant deletion error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка сервера: ' . $e->getMessage()]);
}
?>
