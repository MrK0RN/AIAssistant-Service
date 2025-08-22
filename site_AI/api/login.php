<?php
//ini_set('display_errors', '0');
if (!function_exists("password_verifier")){
    function password_verifier($pass1, $pass2){
        $hashed = substr(md5($pass1), 0, 32);
        if ($hashed === $pass2) {
            return true;
        } 
        return false;
    }
}

session_start();

// Убедимся, что запрос является POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не разрешен']);
    exit;
}
$json = file_get_contents('php://input');
$data = json_decode($json, true);
// Проверим наличие логина и пароля
if (empty($data['login']) || empty($data['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Логин и пароль обязательны']);
    exit;
}

$login = trim($data['login']);
$password = $data['password'];

include_once "../system/pg.php";
// Поиск пользователя в баз

$user = pgQuery("SELECT * FROM accounts WHERE login = '$login' OR email = '$login';")[0];

if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Пользователь не найден']);
    exit;
}

// Проверка пароля
#var_dump($user);
if (password_verifier($password, $user['password'])) {
    // Успешная авторизация
    
    // Сохраняем данные пользователя в сессию
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['login'];
    $_SESSION['authed'] = true;
    $getr = 'hash='.$user['password'].'&login='.$user['login'];
    //echo $getr;
    // Ответ об успешной авторизации
    echo json_encode([
        'success' => true,
        'message' => 'Добро пожаловать, '.$user["last_name"]." ".$user["first_name"],
        'user' => [
            'id' => $user['id'],
            'username' => $user['login']
        ],
        'redirect' => '/auth.php?'.$getr
    ]);
} else {
    http_response_code(401);
    echo json_encode(['error' => 'Неверный пароль']);
}

?>