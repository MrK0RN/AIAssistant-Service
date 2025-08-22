<?php 
session_start();
include_once("system/pg.php");
var_dump($_SESSION['authed']);
if (isset($_SESSION["authed"]) && $_SESSION["authed"]) {
    $uid = $_SESSION["user_id"];
    $username = $_SESSION["username"];
} else {
    //echo "<script>window.location.href='landing.html'</script>";
    exit;
}
$hash = pgQuery("SELECT * FROM accounts WHERE id = $uid")[0]["hash"];

// Get user's existing integrations
$user_integrations = pgQuery("SELECT * FROM integrations WHERE user_id = $uid AND status = 'active' ORDER BY created_at DESC");

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить ассистента - AI Assistant Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?php echo '<script id="hidden-data-hash" type="application/text">'.$hash.'</script>'?>;
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #06d6a0;
            --danger-color: #ef476f;
            --warning-color: #ffd166;
            --info-color: #118ab2;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .container-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px 0;
        }

        .main-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            max-width: 900px;
            margin: 0 auto;
        }

        .header-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .header-section h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 300;
        }

        .header-section p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }

        .form-section {
            padding: 40px;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
        }

        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            position: relative;
            transition: all 0.3s;
        }

        .step.active {
            background: var(--primary-color);
            color: white;
        }

        .step.completed {
            background: var(--success-color);
            color: white;
        }

        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            right: -30px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 2px;
            background: #e9ecef;
        }

        .step.completed:not(:last-child)::after {
            background: var(--success-color);
        }

        .form-step {
            display: none;
        }

        .form-step.active {
            display: block;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 16px;
            transition: all 0.3s;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .integration-card {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: left;
        }

        .integration-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .integration-card.selected {
            border-color: var(--primary-color);
            background: rgba(102, 126, 234, 0.05);
        }

        .integration-icon {
            font-size: 2rem;
            margin-bottom: 10px;
            margin-right: 15px;
        }

        .telegram { color: #0088cc; }
        .whatsapp { color: #25d366; }
        .instagram { color: #e4405f; }

        .btn-custom {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            color: white;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            color: white;
        }

        .btn-outline-custom {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            background: transparent;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-outline-custom:hover {
            background: var(--primary-color);
            color: white;
        }

        .prompt-presets {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .preset-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .preset-card:hover {
            border-color: var(--primary-color);
        }

        .preset-card.selected {
            border-color: var(--primary-color);
            background: rgba(102, 126, 234, 0.05);
        }

        .alert-custom {
            border: none;
            border-radius: 10px;
            padding: 15px 20px;
        }

        .back-link {
            position: absolute;
            top: 20px;
            left: 20px;
            color: white;
            text-decoration: none;
            font-size: 1.2rem;
            padding: 10px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transition: all 0.3s;
        }

        .back-link:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            transform: translateX(-5px);
        }

        .no-integrations {
            text-align: center;
            padding: 40px 20px;
        }

        .no-integrations i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 20px;
        }

        .no-integrations h4 {
            color: #6c757d;
            margin-bottom: 15px;
        }

        .no-integrations p {
            color: #9ba5ae;
            margin-bottom: 25px;
        }

        @media (max-width: 768px) {
            .form-section {
                padding: 20px;
            }

            .header-section h1 {
                font-size: 2rem;
            }

            .step-indicator {
                justify-content: space-around;
            }

            .step {
                margin: 0 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container-wrapper">
        <div class="container">
            <div class="main-card">
                <div class="header-section">
                    <a href="/index.php?nav=assistants" class="back-link">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1><i class="fas fa-robot me-3"></i>Создать ассистента</h1>
                    <p>Настройте своего AI-помощника для автоматизации общения</p>
                </div>

                <div class="form-section">
                    <?php if (count($user_integrations) === 0): ?>
                        <!-- No integrations message -->
                        <div class="no-integrations">
                            <i class="fas fa-plug"></i>
                            <h4>Нет активных интеграций</h4>
                            <p>Для создания ассистента необходимо сначала создать или сделать активной интеграцию.</p>
                            <a href="/index.php?nav=integrations" class="btn btn-custom">
                                <i class="fas fa-plus me-2"></i>Создать интеграцию
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Индикатор шагов -->
                        <div class="step-indicator">
                            <div class="step active" id="step1-indicator">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <div class="step" id="step2-indicator">
                                <i class="fas fa-plug"></i>
                            </div>
                            <div class="step" id="step3-indicator">
                                <i class="fas fa-brain"></i>
                            </div>
                            <div class="step" id="step4-indicator">
                                <i class="fas fa-check"></i>
                            </div>
                        </div>

                        <form id="assistantForm">
                            <!-- Шаг 1: Основная информация -->
                            <div class="form-step active" id="step1">
                                <h3 class="mb-4">Основная информация</h3>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Название ассистента</label>
                                            <input type="text" class="form-control" name="name" required 
                                                   placeholder="Например: Поддержка клиентов">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Язык общения</label>
                                            <select class="form-select" name="language" required>
                                                <option value="">Выберите язык</option>
                                                <option value="ru">Русский</option>
                                                <option value="en">English</option>
                                                <option value="es">Español</option>
                                                <option value="fr">Français</option>
                                                <option value="de">Deutsch</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Тон общения</label>
                                    <select class="form-select" name="tone" required>
                                        <option value="">Выберите стиль</option>
                                        <option value="professional">Профессиональный</option>
                                        <option value="friendly">Дружелюбный</option>
                                        <option value="formal">Официальный</option>
                                        <option value="casual">Неформальный</option>
                                        <option value="supportive">Поддерживающий</option>
                                    </select>
                                </div>

                                <div class="text-end">
                                    <button type="button" class="btn btn-custom" onclick="nextStep()">
                                        Далее <i class="fas fa-arrow-right ms-2"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Шаг 2: Выбор интеграции -->
                            <div class="form-step" id="step2">
                                <h3 class="mb-4">Выберите интеграцию</h3>

                                <div class="row">
                                    <?php foreach ($user_integrations as $integration): 
                                        $config = json_decode($integration['config'], true);
                                        $icon_class = '';
                                        $icon_color = '';
                                        
                                        switch($integration['platform']) {
                                            case 'telegram_bot':
                                            case 'telegram_client':
                                                $icon_class = 'fab fa-telegram';
                                                $icon_color = 'telegram';
                                                break;
                                            case 'whatsapp':
                                                $icon_class = 'fab fa-whatsapp';
                                                $icon_color = 'whatsapp';
                                                break;
                                            case 'instagram':
                                                $icon_class = 'fab fa-instagram';
                                                $icon_color = 'instagram';
                                                break;
                                        }
                                    ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="integration-card" data-integration-id="<?= $integration['id'] ?>" data-integration-type="<?= $integration['platform'] ?>">
                                                <div class="d-flex align-items-center">
                                                    <div class="integration-icon <?= $icon_color ?>">
                                                        <i class="<?= $icon_class ?>"></i>
                                                    </div>
                                                    <div>
                                                        <h5 class="mb-1"><?= htmlspecialchars($integration['name']) ?></h5>
                                                        <p class="text-muted mb-0 small"><?= ucfirst(str_replace('_', ' ', $integration['platform'])) ?></p>
                                                        <span class="badge bg-success">Активна</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <input type="hidden" name="integration_id" id="selected_integration_id">
                                <input type="hidden" name="integration_type" id="selected_integration_type">

                                <div class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-outline-custom" onclick="prevStep()">
                                        <i class="fas fa-arrow-left me-2"></i>Назад
                                    </button>
                                    <button type="button" class="btn btn-custom" onclick="nextStep()" id="step2-next" disabled>
                                        Далее <i class="fas fa-arrow-right ms-2"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Шаг 3: Настройка промпта -->
                            <div class="form-step" id="step3">
                                <h3 class="mb-4">Настройка поведения</h3>

                                <div class="form-group">
                                    <label class="form-label">Шаблоны промптов</label>
                                    <div class="prompt-presets">
                                        <div class="preset-card" data-preset="support">
                                            <h6>Поддержка клиентов</h6>
                                            <p class="small text-muted">Помощь в решении проблем и ответы на вопросы</p>
                                        </div>
                                        <div class="preset-card" data-preset="sales">
                                            <h6>Продажи</h6>
                                            <p class="small text-muted">Консультации по товарам и услугам</p>
                                        </div>
                                        <div class="preset-card" data-preset="booking">
                                            <h6>Бронирование</h6>
                                            <p class="small text-muted">Запись на услуги и резервация</p>
                                        </div>
                                        <div class="preset-card" data-preset="info">
                                            <h6>Информация</h6>
                                            <p class="small text-muted">Предоставление справочной информации</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Системный промпт</label>
                                    <textarea class="form-control" name="prompt" rows="6" required
                                              placeholder="Опишите, как должен вести себя ваш ассистент..."></textarea>
                                    <small class="form-text text-muted">
                                        Этот текст определяет поведение и знания вашего ассистента
                                    </small>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-outline-custom" onclick="prevStep()">
                                        <i class="fas fa-arrow-left me-2"></i>Назад
                                    </button>
                                    <button type="button" class="btn btn-custom" onclick="nextStep()">
                                        Далее <i class="fas fa-arrow-right ms-2"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Шаг 4: Подтверждение -->
                            <div class="form-step" id="step4">
                                <h3 class="mb-4">Подтверждение создания</h3>

                                <div class="alert alert-custom" style="background: rgba(6, 214, 160, 0.1); border-left: 4px solid var(--success-color);">
                                    <h5><i class="fas fa-check-circle text-success me-2"></i>Готово к созданию</h5>
                                    <p class="mb-0">Проверьте настройки и нажмите "Создать ассистента"</p>
                                </div>

                                <div id="summary" class="mt-4">
                                    <!-- Сюда будет загружена сводка -->
                                </div>

                                <div class="d-flex justify-content-between mt-4">
                                    <button type="button" class="btn btn-outline-custom" onclick="prevStep()">
                                        <i class="fas fa-arrow-left me-2"></i>Назад
                                    </button>
                                    <button type="submit" class="btn btn-custom">
                                        <i class="fas fa-magic me-2"></i>Создать ассистента
                                    </button>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentStep = 1;
        const maxSteps = 4;
        let selectedIntegrationId = null;

        // Шаблоны промптов
        const promptTemplates = {
            support: `Ты - профессиональный ассистент службы поддержки клиентов. Твоя задача:

• Вежливо приветствовать клиентов
• Внимательно выслушивать их проблемы
• Предлагать конкретные решения
• Эскалировать сложные вопросы к специалистам
• Всегда оставаться терпеливым и понимающим

Отвечай кратко, но информативно. Если не знаешь ответа, честно скажи об этом и предложи связаться с человеком.`,

            sales: `Ты - консультант по продажам. Твоя цель:

• Узнать потребности клиента
• Предложить подходящие товары/услуги
• Рассказать о преимуществах и особенностях
• Помочь с выбором
• Направить к оформлению заказа

Будь дружелюбным, но не навязчивым. Фокусируйся на пользе для клиента, а не на продаже.`,

            booking: `Ты - ассистент по бронированию и записи. Твои функции:

• Проверять доступность времени
• Записывать клиентов на услуги
• Подтверждать детали записи
• Информировать о правилах и условиях
• Напоминать о предстоящих визитах

Всегда уточняй важные детали и подтверждай информацию дважды.`,

            info: `Ты - информационный помощник. Твоя роль:

• Предоставлять актуальную справочную информацию
• Отвечать на часто задаваемые вопросы
• Направлять к нужным разделам и ресурсам
• Помогать с навигацией по услугам
• Собирать обратную связь

Давай точные и проверенные ответы. Если информация может измениться, указывай дату актуальности.`
        };

        // Функции навигации по шагам
        function nextStep() {
            if (validateCurrentStep()) {
                if (currentStep < maxSteps) {
                    updateStep(currentStep + 1);
                }
            }
        }

        function prevStep() {
            if (currentStep > 1) {
                updateStep(currentStep - 1);
            }
        }

        function updateStep(step) {
            // Скрыть текущий шаг
            document.querySelector(`#step${currentStep}`).classList.remove('active');
            document.querySelector(`#step${currentStep}-indicator`).classList.remove('active');
            document.querySelector(`#step${currentStep}-indicator`).classList.add('completed');

            // Показать новый шаг
            currentStep = step;
            document.querySelector(`#step${currentStep}`).classList.add('active');
            document.querySelector(`#step${currentStep}-indicator`).classList.add('active');

            // Если это последний шаг, показать сводку
            if (currentStep === 4) {
                showSummary();
            }
        }

        function validateCurrentStep() {
            const currentStepEl = document.querySelector(`#step${currentStep}`);
            const requiredFields = currentStepEl.querySelectorAll('[required]');

            for (let field of requiredFields) {
                if (!field.value.trim()) {
                    field.focus();
                    showAlert('Пожалуйста, заполните все обязательные поля', 'warning');
                    return false;
                }
            }

            if (currentStep === 2) {
                if (!selectedIntegrationId) {
                    showAlert('Выберите интеграцию', 'warning');
                    return false;
                }
            }

            return true;
        }

        // Обработка выбора интеграции
        const integrationCards = document.querySelectorAll('.integration-card');
        if (integrationCards.length > 0) {
            integrationCards.forEach(card => {
                card.addEventListener('click', function() {
                    // Убрать выделение с других карточек
                    document.querySelectorAll('.integration-card').forEach(c => c.classList.remove('selected'));

                    // Выделить выбранную карточку
                    this.classList.add('selected');

                    // Сохранить ID и тип выбранной интеграции
                    selectedIntegrationId = this.dataset.integrationId;
                    const selectedIntegrationIdEl = document.getElementById('selected_integration_id');
                    const selectedIntegrationTypeEl = document.getElementById('selected_integration_type');
                    const step2NextBtn = document.getElementById('step2-next');
                    
                    if (selectedIntegrationIdEl) selectedIntegrationIdEl.value = selectedIntegrationId;
                    if (selectedIntegrationTypeEl) selectedIntegrationTypeEl.value = this.dataset.integrationType;

                    // Активировать кнопку "Далее"
                    if (step2NextBtn) step2NextBtn.disabled = false;
                });
            });
        }

        // Обработка выбора шаблона промпта
        const presetCards = document.querySelectorAll('.preset-card');
        if (presetCards.length > 0) {
            presetCards.forEach(card => {
                card.addEventListener('click', function() {
                    // Убрать выделение с других карточек
                    document.querySelectorAll('.preset-card').forEach(c => c.classList.remove('selected'));

                    // Выделить выбранную карточку
                    this.classList.add('selected');

                    // Заполнить промпт
                    const presetType = this.dataset.preset;
                    const promptTextarea = document.querySelector('[name="prompt"]');
                    if (promptTextarea && promptTemplates[presetType]) {
                        promptTextarea.value = promptTemplates[presetType];
                    }
                });
            });
        }

        function showSummary() {
            const formData = new FormData(document.getElementById('assistantForm'));
            const summaryEl = document.getElementById('summary');

            const integrationType = formData.get('integration_type');
            const integrationNames = {
                'telegram_bot': 'Telegram Bot',
                'telegram_client': 'Telegram Client',
                'whatsapp': 'WhatsApp',
                'instagram': 'Instagram'
            };

            // Find selected integration name
            const selectedCard = document.querySelector('.integration-card.selected');
            const integrationName = selectedCard ? selectedCard.querySelector('h5').textContent : 'Неизвестная интеграция';

            summaryEl.innerHTML = `
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Сводка настроек</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Название:</strong> ${formData.get('name')}</p>
                                <p><strong>Язык:</strong> ${formData.get('language')}</p>
                                <p><strong>Тон:</strong> ${formData.get('tone')}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Интеграция:</strong> ${integrationName}</p>
                                <p><strong>Тип:</strong> ${integrationNames[integrationType] || integrationType}</p>
                                <p><strong>Промпт:</strong> ${formData.get('prompt').substring(0, 100)}...</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Обработка отправки формы
        const assistantForm = document.getElementById('assistantForm');
        if (assistantForm) {
            assistantForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const hashElement = document.getElementById('hidden-data-hash');
                if (!hashElement) {
                    showAlert('Ошибка: не найден элемент авторизации', 'danger');
                    return;
                }
                const hash = hashElement.textContent;

            const assistantData = {
                hash: hash,
                name: formData.get('name'),
                tone: formData.get('tone'),
                language: formData.get('language'),
                prompt: formData.get('prompt'),
                integration_id: formData.get('integration_id')
            };

            try {
                const response = await fetch('/api/assistants.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(assistantData)
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('Ассистент успешно создан!', 'success');
                    setTimeout(() => {
                        window.location.href = '/index.php?nav=assistants';
                    }, 2000);
                } else {
                    showAlert('Ошибка при создании ассистента: ' + (result.error || 'Неизвестная ошибка'), 'danger');
                }
            } catch (error) {
                showAlert('Ошибка соединения: ' + error.message, 'danger');
            }
        });
        }

        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            const container = document.querySelector('.form-section');
            container.insertBefore(alertDiv, container.firstChild);

            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }
    </script>
</body>
</html>
