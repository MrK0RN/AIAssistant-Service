
<?php
ini_set('display_errors', '0');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include "../system/pg.php";

// Extract hash from request
$hash = '';
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $hash = $_GET['hash'] ?? '';
} else {
    $input = json_decode(file_get_contents('php://input'), true);
    // For POST requests, check both FormData and JSON
    if (isset($_POST['hash'])) {
        $hash = $_POST['hash'];
    } else {
        $input = json_decode(file_get_contents('php://input'), true);
        $hash = $input['hash'] ?? '';
    }
}

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

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            handleGetFiles($user_id);
            break;
        case 'POST':
            handleUploadFile($user_id);
            break;
        case 'DELETE':
            handleDeleteFile($user_id);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Метод не разрешен']);
    }
} catch (Exception $e) {
    error_log("Knowledge Base API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Внутренняя ошибка сервера']);
}

function handleGetFiles($user_id) {
    $assistant_id = $_GET['assistant_id'] ?? null;
    
    if ($assistant_id) {
        // Verify assistant belongs to user
        $assistant_check = pgQuery("SELECT id FROM assistant WHERE id = $assistant_id AND user_id = $user_id");
        if (!$assistant_check || count($assistant_check) === 0) {
            http_response_code(403);
            echo json_encode(['error' => 'Доступ к ассистенту запрещен']);
            return;
        }
        
        $files = pgQuery("SELECT * FROM knowledge_base WHERE user_id = $user_id AND assistant_id = $assistant_id ORDER BY created_at DESC");
    } else {
        // Return empty if no assistant selected
        $files = [];
    }
    
    // Ensure $files is an array
    if ($files === false || $files === null) {
        $files = [];
    }
    
    echo json_encode([
        'success' => true,
        'files' => $files,
        'total_count' => count($files),
        'assistant_id' => $assistant_id
    ]);
}

function handleUploadFile($user_id) {
    // Get assistant_id from request
    $assistant_id = null;
    if (isset($_POST['assistant_id'])) {
        $assistant_id = intval($_POST['assistant_id']);
    } else {
        $input = json_decode(file_get_contents('php://input'), true);
        $assistant_id = $input['assistant_id'] ?? null;
    }
    
    if (!$assistant_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Требуется указать ассистента']);
        return;
    }
    
    // Verify assistant belongs to user
    $assistant_check = pgQuery("SELECT id FROM assistant WHERE id = $assistant_id AND user_id = $user_id");
    if (!$assistant_check || count($assistant_check) === 0) {
        http_response_code(403);
        echo json_encode(['error' => 'Доступ к ассистенту запрещен']);
        return;
    }
    
    // Проверяем, был ли файл загружен через FormData
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['file'];
        
        // Проверка размера файла (максимум 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(['error' => 'Файл слишком большой. Максимальный размер: 10MB']);
            return;
        }
        
        // Разрешенные типы файлов
        $allowed_types = ['txt', 'md', 'pdf', 'doc', 'docx', 'json', 'csv'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_types)) {
            http_response_code(400);
            echo json_encode(['error' => 'Неподдерживаемый тип файла. Разрешены: ' . implode(', ', $allowed_types)]);
            return;
        }
        
        // Создаем директорию для хранения файлов пользователя
        $upload_dir = "../uploads/knowledge_base/user_$user_id/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Генерируем уникальное имя файла
        $unique_filename = uniqid() . '_' . $file['name'];
        $file_path = $upload_dir . $unique_filename;
        
        // Перемещаем файл
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            // Читаем содержимое файла для индексации
            $content = '';
            if (in_array($file_extension, ['txt', 'md', 'json', 'csv'])) {
                $content = file_get_contents($file_path);
            }
            
            // Сохраняем информацию в базу данных
            $name = pg_escape_string($file['name']);
            $type = pg_escape_string($file_extension);
            $size = $file['size'];
            $path = pg_escape_string($file_path);
            $content_escaped = pg_escape_string($content);
            
            $result = pgQuery("INSERT INTO knowledge_base (user_id, assistant_id, name, type, size, file_path, content, created_at) 
                              VALUES ($user_id, $assistant_id, '$name', '$type', $size, '$path', '$content_escaped', NOW()) 
                              RETURNING id", false, true);
            
            if ($result && count($result) > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Файл успешно загружен',
                    'file_id' => $result[0]['id']
                ]);
            } else {
                unlink($file_path); // Удаляем файл если не удалось сохранить в БД
                http_response_code(500);
                echo json_encode(['error' => 'Ошибка сохранения файла в базу данных']);
            }
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Ошибка загрузки файла']);
        }
    } else {
        // Обработка текстового контента из FormData или JSON
        $name = null;
        $content = null;
        $type = 'text';
        $file_id = null;
        $action = null;
        
        // Check if data came from FormData (POST parameters)
        if (isset($_POST['name']) && !empty($_POST['name'])) {
            $name = $_POST['name'];
            $content = $_POST['content'] ?? '';
            $type = $_POST['type'] ?? 'text';
            $file_id = $_POST['file_id'] ?? null;
            $action = $_POST['action'] ?? null;
            
            error_log("FormData received - name: " . $name . ", content length: " . strlen($content));
        } else {
            // Fallback to JSON input
            $input = json_decode(file_get_contents('php://input'), true);
            if ($input) {
                $name = $input['name'] ?? null;
                $content = $input['content'] ?? '';
                $type = $input['type'] ?? 'text';
                $file_id = $input['file_id'] ?? null;
                $action = $input['action'] ?? null;
                
                error_log("JSON received - name: " . ($name ?? 'null') . ", content length: " . strlen($content));
            } else {
                error_log("No POST data or JSON input found");
            }
        }
        
        if (!$name || trim($name) === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Требуется поле name']);
            return;
        }
        
        // Trim the name
        $name = trim($name);
        
        // Allow empty content for new documents
        if ($content === null) {
            $content = '';
        }
        
        // Check if this is an update operation
        if ($file_id && $action === 'update') {
            $file_id = intval($file_id);
            $name = pg_escape_string($name);
            $content = pg_escape_string($content);
            
            $result = pgQuery("UPDATE knowledge_base SET name = '$name', content = '$content', updated_at = NOW() 
                              WHERE id = $file_id AND user_id = $user_id");
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Файл успешно обновлен'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Ошибка обновления файла']);
            }
            return;
        }
        
        $name = pg_escape_string($name);
        $content = pg_escape_string($content);
        $type = pg_escape_string($type);
        
        $result = pgQuery("INSERT INTO knowledge_base (user_id, assistant_id, name, type, content, created_at) 
                          VALUES ($user_id, $assistant_id, '$name', '$type', '$content', NOW()) 
                          RETURNING id", false, true);
        
        if ($result && count($result) > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Документ успешно создан',
                'file_id' => $result[0]['id']
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Ошибка создания документа']);
        }
    }
}

function handleDeleteFile($user_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['file_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Требуется file_id']);
        return;
    }
    
    $file_id = intval($input['file_id']);
    
    // Получаем информацию о файле
    $file_info = pgQuery("SELECT * FROM knowledge_base WHERE id = $file_id AND user_id = $user_id");
    
    if (count($file_info) === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Файл не найден']);
        return;
    }
    
    $file = $file_info[0];
    
    // Удаляем физический файл, если он существует
    if ($file['file_path'] && file_exists($file['file_path'])) {
        unlink($file['file_path']);
    }
    
    // Удаляем запись из базы данных
    $result = pgQuery("DELETE FROM knowledge_base WHERE id = $file_id AND user_id = $user_id");
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Файл успешно удален'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Ошибка удаления файла']);
    }
}
?>
