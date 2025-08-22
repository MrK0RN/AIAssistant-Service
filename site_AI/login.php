<?php 
ini_set('display_errors', '0');
include_once("system/pg.php");
session_start();
//var_dump($_SESSION['authed']);
if (isset($_SESSION["authed"]) && $_SESSION["authed"]) {
    echo "<script>window.location.href='index.php'</script>";
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - AI Assistant Dashboard</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="auth.css" rel="stylesheet">
</head>
<body class="auth-body">
    <div class="auth-container">
        <!-- Background decoration -->
        <div class="auth-bg-decoration">
            <div class="decoration-circle circle-1"></div>
            <div class="decoration-circle circle-2"></div>
            <div class="decoration-circle circle-3"></div>
        </div>

        <div class="container-fluid h-100">
            <div class="row h-100 align-items-center justify-content-center">
                <div class="col-md-6 col-lg-5 col-xl-4">
                    <div class="auth-card">
                        <!-- Header -->
                        <div class="auth-header text-center mb-4">
                            <div class="auth-logo mb-3">
                                <i class="fas fa-robot"></i>
                            </div>
                            <h1 class="auth-title">Добро пожаловать</h1>
                            <p class="auth-subtitle">Войдите в свой аккаунт</p>
                        </div>

                        <!-- Login Form -->
                        <form id="loginForm" class="auth-form">
                            <div class="mb-3">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="login" placeholder="Email или имя пользователя" required>
                                    <label for="login">Email или имя пользователя</label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-floating">
                                    <input type="password" class="form-control" id="password" placeholder="Пароль" required>
                                    <label for="password">Пароль</label>
                                </div>
                            </div>

                            <div class="mb-4 d-flex justify-content-between align-items-center">
                                <a href="#" class="auth-link" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">
                                    Забыли пароль?
                                </a>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Войти
                            </button>

                            <div class="text-center">
                                <p class="auth-link-text">
                                    Нет аккаунта? 
                                    <a href="register.html" class="auth-link">Зарегистрироваться</a>
                                </p>
                            </div>
                        </form>

                        <!-- Social Login -->
                        <div class="auth-divider">
                            <span>или</span>
                        </div>

                        <div class="social-auth">
                            <button class="btn btn-outline-secondary w-100 mb-2">
                                <i class="fab fa-google me-2"></i>
                                Войти с Google
                            </button>
                            <button class="btn btn-outline-secondary w-100">
                                <i class="fab fa-github me-2"></i>
                                Войти с GitHub
                            </button>
                        </div>
                    </div>

                    <!-- Demo Credentials -->
                    <div class="demo-credentials mt-4">
                        <div class="card" style="background: rgba(255, 255, 255, 0.9); border-radius: 1rem;">
                            <div class="card-body text-center">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Демо доступ
                                </h6>
                                <p class="mb-2"><strong>Email:</strong> demo@example.com</p>
                                <p class="mb-0"><strong>Пароль:</strong> demo123</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right side - Benefits -->
                <div class="col-lg-6 d-none d-lg-block">
                    <div class="auth-showcase">
                        <div class="showcase-content">
                            <h2 class="showcase-title">Добро пожаловать обратно!</h2>
                            <p class="showcase-subtitle">
                                Продолжите управление вашими AI-ассистентами и развивайте бизнес с помощью автоматизации
                            </p>

                            <div class="showcase-features">
                                <div class="showcase-feature">
                                    <div class="feature-icon">
                                        <i class="fas fa-tachometer-alt"></i>
                                    </div>
                                    <div class="feature-content">
                                        <h6>Интуитивный дашборд</h6>
                                        <p>Все важные метрики и управление в одном месте</p>
                                    </div>
                                </div>

                                <div class="showcase-feature">
                                    <div class="feature-icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="feature-content">
                                        <h6>Экономия времени</h6>
                                        <p>Автоматизируйте до 80% обращений клиентов</p>
                                    </div>
                                </div>

                                <div class="showcase-feature">
                                    <div class="feature-icon">
                                        <i class="fas fa-shield-alt"></i>
                                    </div>
                                    <div class="feature-content">
                                        <h6>Безопасность данных</h6>
                                        <p>Ваши данные защищены современным шифрованием</p>
                                    </div>
                                </div>
                            </div>

                            <div class="showcase-testimonial mt-4">
                                <blockquote class="blockquote">
                                    <p>"Благодаря AI Assistant мы увеличили конверсию на 45% и сократили время ответа до минут"</p>
                                    <footer class="blockquote-footer mt-2">
                                        <strong>Анна Петрова</strong>, директор по маркетингу
                                    </footer>
                                </blockquote>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Восстановление пароля</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Введите ваш email адрес и мы отправим инструкции по восстановлению пароля</p>
                    <form id="forgotPasswordForm">
                        <div class="mb-3">
                            <div class="form-floating">
                                <input type="email" class="form-control" id="resetEmail" placeholder="Email" required>
                                <label for="resetEmail">Email адрес</label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-primary" onclick="handlePasswordReset()">
                        <i class="fas fa-paper-plane me-1"></i>
                        Отправить
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS 
    <script src="auth.js"></script>-->
    <script>
        // Login specific functionality
        document.addEventListener('DOMContentLoaded', () => {
            const loginForm = document.getElementById('loginForm');
            
            if (loginForm) {
                loginForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    await handleLogin();
                });
            }
        });

        async function handleLogin() {
            const login = document.getElementById('login').value;
            const password = document.getElementById('password').value;
            const submitBtn = document.querySelector('#loginForm button[type="submit"]');
            
            // Show loading state
            submitBtn.classList.add('btn-loading');
            submitBtn.disabled = true;
            
            try {
                // Make actual API call to login endpoint
                const response = await fetch('/api/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        login: login,
                        password: password
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    showMessage(data.message || 'Вход выполнен успешно!', 'success');
                    
                    // Redirect to dashboard immediately
                    window.location.href = data.redirect || '/index.php';
                } else {
                    const errors = data.errors || [data.message || 'Неверный логин или пароль'];
                    throw new Error(errors.join('; '));
                }
            } catch (error) {
                showMessage(error.message, 'error');
            } finally {
                submitBtn.classList.remove('btn-loading');
                submitBtn.disabled = false;
            }
        }

        async function handlePasswordReset() {
            const email = document.getElementById('resetEmail').value;
            const modal = bootstrap.Modal.getInstance(document.getElementById('forgotPasswordModal'));
            
            if (!email) {
                showMessage('Введите email адрес', 'error');
                return;
            }
            
            try {
                // Simulate API call
                await new Promise(resolve => setTimeout(resolve, 1000));
                
                showMessage('Инструкции по восстановлению пароля отправлены на ваш email', 'success');
                modal.hide();
                document.getElementById('resetEmail').value = '';
            } catch (error) {
                showMessage('Ошибка при отправке email', 'error');
            }
        }

        function showMessage(message, type = 'info') {
            // Remove existing alerts
            const existingAlerts = document.querySelectorAll('.alert');
            existingAlerts.forEach(alert => alert.remove());

            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            const form = document.getElementById('loginForm');
            form.insertBefore(alertDiv, form.firstChild);

            // Auto dismiss after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }
    </script>
</body>
</html>