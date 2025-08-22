<?php
ini_set('display_errors', '0');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include database connection
include "../system/pg.php";

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!$data) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Некорректные данные']);
            exit;
        }

        // Validate hash
        $hash = trim($data['hash'] ?? '');
        if (empty($hash)) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Необходима авторизация']);
            exit;
        }

        $user = pgQuery("SELECT * FROM accounts WHERE hash = '$hash'");
        if (count($user) === 0) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Неверная авторизация']);
            exit;
        }

        $user_id = $user[0]["id"];

        // Validate required fields
        $requiredFields = ['name', 'tone', 'language', 'prompt', 'integration_id'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => "Поле {$field} обязательно"]);
                exit;
            }
        }

        $name = pg_escape_string($data['name']);
        $tone = pg_escape_string($data['tone']);
        $language = pg_escape_string($data['language']);
        $prompt = pg_escape_string($data['prompt']);
        $integration_id = (int)$data['integration_id'];

        // Validate that integration exists and belongs to user
        $integration = pgQuery("SELECT * FROM integrations WHERE id = $integration_id AND user_id = $user_id");
        if (count($integration) === 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Интеграция не найдена или не принадлежит пользователю']);
            exit;
        }

        $integration_type = $integration[0]['platform'];
        $integration_conf = $integration[0]['config'];

        // Insert assistant into database with integration_id instead of credentials
        $sql = "INSERT INTO assistant (user_id, name, tone, language, prompt, platform, credentials, integration_id, status) 
                VALUES ('$user_id', '$name', '$tone', '$language', '$prompt', '$integration_type', '$integration_conf', '$integration_id', 'active') 
                RETURNING id";

        $result = pgQuery($sql, false, true);

        if ($result && count($result) > 0) {
            $assistant_id = $result[0]["id"];

            echo json_encode([
                'success' => true,
                'message' => 'Ассистент успешно создан!',
                'assistant_id' => $assistant_id
            ]);
        } else {
            throw new Exception('Ошибка при сохранении в базу данных');
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Handle GET request for fetching assistants
        $hash = $_GET['hash'] ?? '';

        if (empty($hash)) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Необходима авторизация']);
            exit;
        }

        $user = pgQuery("SELECT * FROM accounts WHERE hash = '$hash'");
        if (count($user) === 0) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Неверная авторизация']);
            exit;
        }

        $user_id = $user[0]["id"];

        // Get user's assistants with integration data
        $sql = "SELECT a.*, i.name as integration_name, i.config as integration_config 
                FROM assistant a 
                LEFT JOIN integrations i ON a.integration_id = i.id 
                WHERE a.user_id = $user_id 
                ORDER BY a.id DESC";
        $assistants = pgQuery($sql);

        // Format assistants data
        $formattedAssistants = [];
        foreach ($assistants as $assistant) {
            $integration_config = $assistant['integration_config'] ? json_decode($assistant['integration_config'], true) : [];

            $formattedAssistants[] = [
                'id' => (int)$assistant['id'],
                'name' => $assistant['name'],
                'tone' => $assistant['tone'],
                'language' => $assistant['language'],
                'prompt' => $assistant['prompt'],
                'platform' => $assistant['platform'],
                'integration_type' => $assistant['platform'],
                'integration_id' => (int)$assistant['integration_id'],
                'integration_name' => $assistant['integration_name'],
                'integration_config' => $integration_config,
                'status' => $assistant['status']
            ];
        }

        echo json_encode([
            'success' => true,
            'assistants' => $formattedAssistants
        ]);

    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Метод не разрешен']);
    }

} catch (Exception $e) {
    error_log("Assistant API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Внутренняя ошибка сервера']);
}


?>