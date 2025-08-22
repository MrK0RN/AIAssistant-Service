<?php
session_start();
ini_set('display_errors', '0');
// Убедимся, что запрос является POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не разрешен']);
    exit;
}

function get_hash(
    $password, $login,
    $salt = 'wdvwdv'
) {
    return substr(md5($login . $salt . $password), 0, 32);
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Проверим обязательные поля
$requiredFields = ['username', 'email', 'password', 'confirmPassword', 'firstName', 'lastName'];
foreach ($requiredFields as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['error' => 'Незаполненно поле '. $field]);
        exit;
    }
}

// Получаем данные из формы
$username = trim($data['username']);
$email = trim($data['email']);
$password = $data['password'];
$passwordConfirm = $data['confirmPassword'];
$first_name = $data['firstName'];
$last_name = $data['lastName'];
$telephone = $data['phone'];
$company = $data['confirmPassword'];

// Валидация данных
if ($password !== $passwordConfirm) {
    http_response_code(400);
    echo json_encode(['error' => 'Пароли не совпадают']);
    exit;
}

if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['error' => 'Пароль должен содержать минимум 8 символов']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Некорректный email']);
    exit;
}

if (strlen($username) < 3 || strlen($username) > 50) {
    http_response_code(400);
    echo json_encode(['error' => 'Имя пользователя должно быть от 3 до 50 символов']);
    exit;
}

// Подключение к PostgreSQL
try {

    include "../system/pg.php";
    // Проверка существующего пользователя
    $exists = pgQuery("SELECT id FROM accounts WHERE login = '$username' OR email = '$email'", true);
    
    if ($exists) {
        http_response_code(409);
        echo json_encode(['error' => 'Пользователь с таким именем или email уже существует']);
        exit;
    }
    
    // Хеширование пароля
    $passwordHash = substr(md5($password), 0, 32);
    $hash = get_hash($password, $username);
    // Создание нового пользователя
    $newUser = pgQuery("INSERT INTO accounts (login, email, password, phone, first_name, last_name, hash) VALUES ('$username', '$email', '$passwordHash', '$telephone', '$first_name', '$last_name', '$hash') RETURNING id, login, email;", false, true);
    
    // Автоматическая авторизация после регистрации
    $_SESSION['user_id'] = $newUser['id'];
    $_SESSION['username'] = $newUser['login'];
    $_SESSION['authenticated'] = true;
    $getr = 'hash='.$passwordHash.'&login='.$username;
    // Ответ об успешной регистрации
    echo json_encode([
        'success' => true,
        'message' => 'Регистрация успешна',
        'user' => [
            'id' => $newUser['id'],
            'username' => $newUser['login'],
            'email' => $newUser['email']
        ],
        'redirect' => '/auth.php?'.$getr
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>