
<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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

// Get search query
$query = $_GET['q'] ?? '';

if (empty($query)) {
    http_response_code(400);
    echo json_encode(['error' => 'Поисковый запрос обязателен']);
    exit;
}

try {
    $escaped_query = pg_escape_string($query);
    
    // Search in knowledge base
    $results = pgQuery("SELECT id, name, type, content, created_at,
                              CASE 
                                WHEN content IS NOT NULL THEN 
                                  SUBSTRING(content FROM 1 FOR 200) || '...'
                                ELSE NULL
                              END as content_preview
                       FROM knowledge_base 
                       WHERE user_id = $user_id 
                       AND (name ILIKE '%$escaped_query%' 
                            OR content ILIKE '%$escaped_query%')
                       ORDER BY created_at DESC");
    
    if ($results === false) {
        $results = [];
    }
    
    echo json_encode([
        'success' => true,
        'results' => $results,
        'query' => $query,
        'total_count' => count($results)
    ]);
    
} catch (Exception $e) {
    error_log("Knowledge search error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка поиска']);s WHERE hash = '$hash'");
if (!$user || count($user) === 0) {
    http_response_code(401);
    echo json_encode(['error' => 'Неверная авторизация']);
    exit;
}

$user_id = $user[0]["id"];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $query = $_GET['q'] ?? '';
        $type = $_GET['type'] ?? '';
        
        if (empty($query)) {
            echo json_encode([
                'success' => true,
                'results' => [],
                'message' => 'Введите поисковый запрос'
            ]);
            exit;
        }
        
        $search_query = pg_escape_string($query);
        $sql = "SELECT id, name, type, content, created_at, 
                       ts_rank(to_tsvector('russian', name || ' ' || COALESCE(content, '')), plainto_tsquery('russian', '$search_query')) as rank
                FROM knowledge_base 
                WHERE user_id = $user_id 
                AND (name ILIKE '%$search_query%' OR content ILIKE '%$search_query%')";
        
        if (!empty($type)) {
            $type_escaped = pg_escape_string($type);
            $sql .= " AND type = '$type_escaped'";
        }
        
        $sql .= " ORDER BY rank DESC, created_at DESC LIMIT 20";
        
        $results = pgQuery($sql);
        
        // Обрезаем контент для превью
        foreach ($results as &$result) {
            if (strlen($result['content']) > 200) {
                $result['content_preview'] = substr($result['content'], 0, 200) . '...';
            } else {
                $result['content_preview'] = $result['content'];
            }
            // Подсвечиваем найденные фрагменты
            $result['content_preview'] = highlightSearchTerm($result['content_preview'], $query);
            $result['name'] = highlightSearchTerm($result['name'], $query);
        }
        
        echo json_encode([
            'success' => true,
            'results' => $results,
            'query' => $query,
            'total_found' => count($results)
        ]);
        
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Метод не разрешен']);
    }
} catch (Exception $e) {
    error_log("Search Knowledge API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка поиска']);
}

function highlightSearchTerm($text, $term) {
    return preg_replace('/(' . preg_quote($term, '/') . ')/i', '<mark>$1</mark>', $text);
}
?>
