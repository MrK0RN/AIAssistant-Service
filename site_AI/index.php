<?php 
//ini_set('display_errors', '0');
include_once("system/pg.php");
session_start();
//var_dump($_SESSION['authed']);
if (isset($_SESSION["authed"]) && $_SESSION["authed"]) {
    $uid = $_SESSION["user_id"];
    $username = $_SESSION["username"];
} else {
    echo "<script>window.location.href='landing.html'</script>";
    exit;
}

$nav = $_GET['nav'] ?? 'dashboard';
$hash = pgQuery("SELECT * FROM accounts WHERE id = $uid")[0]["hash"];
$numbots_active = pgQuery("SELECT * FROM assistant WHERE user_id = $uid AND status = 'active';", true);
$numbots = json_encode(pgQuery("SELECT * FROM assistant WHERE user_id = $uid;"));

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Assistant Dashboard</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="styles.css" rel="stylesheet">
    <?php echo '<script id="hidden-data-hash" type="application/text">'.$hash.'</script>'?>;
    <?php echo '<script id="hidden-data-bots" type="application/json">'.$numbots.'</script>'?>;
    <?php echo '<script id="hidden-data-nav" type="application/text">'.$nav.'</script>'?>;
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <button class="btn btn-link sidebar-toggle me-3">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="logo">
                        <i class="fas fa-robot text-primary me-2"></i>
                        <span class="fw-bold">AI Dashboard</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-link theme-toggle" title="Переключить тему">
                        <i class="fas fa-moon"></i>
                    </button>
                    <button class="btn btn-link notifications" title="Уведомления">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">3</span>
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-link dropdown-toggle" data-bs-toggle="dropdown">
                            <?php echo "<img id=\"userAvatar\" src=\"https://ui-avatars.com/api/?name=".$username."&background=007bff&color=fff&size=32\" class=\"rounded-circle me-2\" width=\"32\" height=\"32\">";?>
                            <span id="userName"><?php echo $username; ?></span>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" data-section="account"><i class="fas fa-user me-2"></i>Учетная запись</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/api/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Выйти</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="main-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <nav class="sidebar-nav">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="#" data-section="welcome">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Дашборд</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-section="messages">
                            <i class="fas fa-comments"></i>
                            <span>Сообщения</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-section="assistants">
                            <i class="fas fa-robot"></i>
                            <span>Мои ассистенты</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-section="integrations">
                            <i class="fas fa-plug"></i>
                            <span>Интеграции</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-section="knowledge">
                            <i class="fas fa-database"></i>
                            <span>База знаний</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-section="statistics">
                            <i class="fas fa-chart-bar"></i>
                            <span>Статистика</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-section="account">
                            <i class="fas fa-user"></i>
                            <span>Учетная запись</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-section="pricing">
                            <i class="fas fa-credit-card"></i>
                            <span>Тарифы</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-section="help">
                            <i class="fas fa-question-circle"></i>
                            <span>Помощь</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Welcome Section -->
            <section id="welcome" class="content-section active">
                <div class="container-fluid">
                    <div class="welcome-header mb-4">
                        <h1 class="h3 mb-2">Добро пожаловать!</h1>
                        <p class="text-muted">Сегодня <span id="current-date"></span></p>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-6 col-lg-3">
                            <div class="stats-card-modern">
                                <div class="stats-card-content">
                                    <div class="stats-icon-modern bg-primary">
                                        <i class="fas fa-robot"></i>
                                    </div>
                                    <div class="stats-info">
                                        <h3 class="stats-number"><?=$numbots_active?></h3>
                                        <p class="stats-label">Активные боты</p>
                                        <small class="stats-detail">Telegram, WhatsApp, Instagram</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="stats-card-modern">
                                <div class="stats-card-content">
                                    <div class="stats-icon-modern bg-info">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <div class="stats-info">
                                        <h3 class="stats-number">1,247</h3>
                                        <p class="stats-label">Всего сообщений</p>
                                        <small class="stats-detail text-success">+12% от прошлого месяца</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="stats-card-modern">
                                <div class="stats-card-content">
                                    <div class="stats-icon-modern bg-success">
                                        <i class="fas fa-comments"></i>
                                    </div>
                                    <div class="stats-info">
                                        <h3 class="stats-number">892</h3>
                                        <p class="stats-label">Всего диалогов</p>
                                        <small class="stats-detail text-success">+8% от прошлой недели</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="stats-card-modern">
                                <div class="stats-card-content">
                                    <div class="stats-icon-modern bg-warning">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="stats-info">
                                        <h3 class="stats-number">250ms</h3>
                                        <p class="stats-label">Время ответа</p>
                                        <small class="stats-detail text-success">Оптимальное время</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="card dashboard-main-card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-robot me-2 text-primary"></i>
                                        Статус ботов
                                    </h5>
                                    <button class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-sync-alt me-1"></i>
                                        Обновить
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="bot-status-container">
                                        <div class="empty-state">
                                            <div class="empty-icon">
                                                <i class="fas fa-robot"></i>
                                            </div>
                                            <h6>Нет настроенных ботов</h6>
                                            <p class="text-muted mb-3">Создайте своего первого бота для начала работы</p>
                                            <button class="btn btn-outline-primary" onclick="app.navigateToSection('assistants')">
                                                <i class="fas fa-plus me-2"></i>
                                                Мои ассистенты
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card dashboard-main-card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-database me-2 text-success"></i>
                                        База знаний
                                    </h5>
                                    <button class="btn btn-primary btn-sm">
                                        <i class="fas fa-upload me-1"></i>
                                        Загрузить
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="knowledge-base-container">
                                        <div class="upload-area">
                                            <div class="upload-icon">
                                                <i class="fas fa-cloud-upload-alt"></i>
                                            </div>
                                            <h6>Перетащите файлы сюда или</h6>
                                            <button class="btn btn-link p-0 text-primary">выберите файлы</button>
                                            <small class="text-muted d-block mt-2">PDF, DOCX, TXT до 10МБ</small>
                                        </div>

                                        <div class="mt-4">
                                            <h6 class="mb-3">Загруженные файлы</h6>
                                            <div class="empty-files">
                                                <div class="empty-icon-small">
                                                    <i class="fas fa-file-alt"></i>
                                                </div>
                                                <p class="text-muted mb-3">Нет загруженных файлов</p>
                                                <button class="btn btn-outline-success btn-sm">
                                                    <i class="fab fa-google-drive me-1"></i>
                                                    Подключить Google Docs
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4 mt-2">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-line me-2"></i>
                                        Последняя активность
                                    </h5>
                                    <small class="text-muted">Недавние события в системе</small>
                                </div>
                                <div class="card-body">
                                    <div class="activity-list">
                                        <div class="activity-item">
                                            <div class="activity-icon bg-primary">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div class="activity-content">
                                                <h6>Новое сообщение от пользователя в Telegram</h6>
                                                <p class="text-muted mb-0">Сообщение • 2 минуты назад</p>
                                            </div>
                                        </div>
                                        <div class="activity-item">
                                            <div class="activity-icon bg-success">
                                                <i class="fas fa-robot"></i>
                                            </div>
                                            <div class="activity-content">
                                                <h6>Создан новый WhatsApp бот</h6>
                                                <p class="text-muted mb-0">Бот • 15 минут назад</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Messages Section -->
            <section id="messages" class="content-section">
                <div class="container-fluid">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="h3 mb-0">Сообщения</h2>
                        <div class="d-flex gap-2">
                            <div class="input-group" style="width: 300px;">
                                <input type="text" class="form-control" placeholder="Поиск по сообщениям..." id="messageSearch">
                                <button class="btn btn-outline-secondary" type="button">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-2">
                            <select class="form-select" id="dateFilter">
                                <option value="">Все даты</option>
                                <option value="today">Сегодня</option>
                                <option value="week">Эта неделя</option>
                                <option value="month">Этот месяц</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" id="channelFilter">
                                <option value="">Все каналы</option>
                                <option value="telegram">Telegram</option>
                                <option value="whatsapp">WhatsApp</option>
                                <option value="instagram">Instagram</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" id="statusFilter">
                                <option value="">Все статусы</option>
                                <option value="new">Новые</option>
                                <option value="completed">Завершенные</option>
                            </select>
                        </div>
                    </div>

                    <div class="card">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Клиент</th>
                                        <th>Канал</th>
                                        <th>Последнее сообщение</th>
                                        <th>Время</th>
                                        <th>Статус</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody id="messagesTable">
                                    <!-- Messages will be populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Assistants Section -->
            <section id="assistants" class="content-section">
                <div class="container-fluid">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="h3 mb-0">Мои ассистенты</h2>
                        <button class="btn btn-primary" onclick="window.location.href='add_assistant.php'"
                            <i class="fas fa-plus me-2"></i>Добавить ассистента
                        </button>
                    </div>
                    <div class="row g-4" id="assistantsList">
                    </div>

                </div>
            </section>

            <!-- Integrations Section -->
            <section id="integrations" class="content-section">
                <div class="container-fluid">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="h3 mb-0">Интеграции</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addIntegrationModal">
                            <i class="fas fa-plus me-2"></i>Добавить интеграцию
                        </button>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div id="integrationsList" class="integration-list">
                                <!-- Integrations will be populated by JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Knowledge Base Section -->
            <section id="knowledge" class="content-section">
                <div class="container-fluid">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="h3 mb-0">База знаний</h2>
                        <div class="btn-group" id="knowledgeActions" style="display: none;">
                            <button class="btn btn-primary" onclick="app.createNewDocument()">
                                <i class="fas fa-plus me-2"></i>Создать документ
                            </button>
                            <button class="btn btn-outline-primary" onclick="document.getElementById('knowledgeFileInput').click()">
                                <i class="fas fa-upload me-2"></i>Загрузить файл
                            </button>
                        </div>
                    </div>

                    <!-- Assistant Selection -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="knowledgeAssistantSelect" class="form-label">Выберите ассистента</label>
                            <select class="form-select" id="knowledgeAssistantSelect">
                                <option value="">Выберите ассистента для управления базой знаний...</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="knowledgeSearchContainer" style="display: none;">
                            <label class="form-label">Поиск</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" class="form-control" id="knowledgeSearchInput" placeholder="Поиск по базе знаний...">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-folder-open me-2"></i>
                                        Файлы и документы
                                        <span id="selectedAssistantName" class="text-muted small ms-2"></span>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div id="knowledgeFilesList">
                                        <div id="knowledgeNoAssistant" class="text-center py-5">
                                            <i class="fas fa-robot fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">Выберите ассистента</h5>
                                            <p class="text-muted">Для управления базой знаний сначала выберите ассистента из списка выше</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-cloud-upload-alt me-2"></i>
                                        Быстрая загрузка
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div id="knowledgeUploadArea" class="knowledge-upload-area text-center p-4 border rounded">
                                        <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                        <h6 class="text-muted">Перетащите файлы сюда</h6>
                                        <p class="text-muted small mb-3">или нажмите для выбора</p>
                                        <small class="text-muted">
                                            Поддерживаемые форматы:<br>
                                            TXT, MD, PDF, DOC, DOCX, JSON, CSV<br>
                                            Максимальный размер: 10MB
                                        </small>
                                    </div>
                                    <input type="file" id="knowledgeFileInput" class="d-none" accept=".txt,.md,.pdf,.doc,.docx,.json,.csv">
                                </div>
                            </div>

                            <div class="card mt-3">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Статистика
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div id="knowledgeStats">
                                        <!-- Stats will be loaded here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Knowledge Base Modals -->
            <div class="modal fade" id="knowledgeFileModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="knowledgeFileModalTitle">Просмотр файла</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div id="knowledgeFileContent">
                                <!-- File content will be loaded here -->
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                            <button type="button" class="btn btn-primary" id="saveKnowledgeFileBtn" style="display: none;">Сохранить</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="createDocumentModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Создать новый документ</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="newDocumentName" class="form-label">Название документа</label>
                                <input type="text" class="form-control" id="newDocumentName" placeholder="Введите название документа">
                            </div>
                            <div class="mb-3">
                                <label for="newDocumentContent" class="form-label">Содержимое</label>
                                <textarea class="form-control" id="newDocumentContent" rows="12" placeholder="Введите содержимое документа..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                            <button type="button" class="btn btn-primary" onclick="app.saveNewDocument()">Создать документ</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Analytics Section -->
            <section id="analytics" class="content-section">
                <div class="container-fluid">
                    <h2 class="h3 mb-4">База знаний</h2>

                    <div class="row">
                        <div class="col-md-3 mb-4">
                            <label for="assistantSelect" class="form-label">Выберите ассистента</label>
                            <select class="form-select" id="assistantSelect">
                                <option value="">Выберите ассистента...</option>
                                <option value="support">Саппорт-бот</option>
                                <option value="sales">Продажный ассистент</option>
                                <option value="consultant">Консультант</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Загрузка файлов</h5>
                                </div>
                                <div class="card-body">
                                    <div class="upload-zone" id="uploadZone">
                                        <div class="upload-content">
                                            <i class="fas fa-cloud-upload-alt mb-3"></i>
                                            <p class="mb-2">Перетащите файлы сюда или нажмите для выбора</p>
                                            <p class="text-muted small">Поддерживаются форматы: PDF, DOCX, TXT</p>
                                            <input type="file" id="fileInput" class="d-none" multiple accept=".pdf,.docx,.txt">
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <button class="btn btn-outline-primary" id="googleDocsBtn">
                                            <i class="fab fa-google-drive me-2"></i>Google Docs
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Загруженные документы</h5>
                                </div>
                                <div class="card-body">
                                    <div id="documentsList">
                                        <!-- Documents will be populated by JavaScript -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Statistics Section -->
            <section id="statistics" class="content-section">
                <div class="container-fluid">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="h3 mb-0">Статистика</h2>
                        <div class="d-flex gap-2">
                            <select class="form-select" style="width: auto;" id="statsDateFilter">
                                <option value="today">Сегодня</option>
                                <option value="week">Неделя</option>
                                <option value="month" selected>Месяц</option>
                                <option value="year">Год</option>
                            </select>
                            <button class="btn btn-outline-primary" id="exportBtn">
                                <i class="fas fa-download me-2"></i>Экспорт CSV
                            </button>
                        </div>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <div class="stats-card">
                                <div class="stats-icon bg-primary">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <div>
                                    <h4 class="mb-1">156</h4>
                                    <p class="text-muted mb-0">Новых диалогов</p>
                                    <small class="text-success">
                                        <i class="fas fa-arrow-up"></i> +12% с прошлого месяца
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card">
                                <div class="stats-icon bg-warning">
                                    <i class="fas fa-question"></i>
                                </div>
                                <div>
                                    <h4 class="mb-1">23</h4>
                                    <p class="text-muted mb-0">Вопросов для БД</p>
                                    <small class="text-danger">
                                        <i class="fas fa-arrow-down"></i> -5% с прошлого месяца
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card">
                                <div class="stats-icon bg-success">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div>
                                    <h4 class="mb-1">89</h4>
                                    <p class="text-muted mb-0">Активных пользователей</p>
                                    <small class="text-success">
                                        <i class="fas fa-arrow-up"></i> +8% с прошлого месяца
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Активность по каналам</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="statisticsChart" height="100"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Популярные вопросы</h5>
                                </div>
                                <div class="card-body">
                                    <div class="popular-questions">
                                        <div class="question-item">
                                            <span class="question-rank">1</span>
                                            <div>
                                                <p class="mb-1">Как настроить интеграцию?</p>
                                                <small class="text-muted">45 вопросов</small>
                                            </div>
                                        </div>
                                        <div class="question-item">
                                            <span class="question-rank">2</span>
                                            <div>
                                                <p class="mb-1">Проблемы с подключением</p>
                                                <small class="text-muted">32 вопроса</small>
                                            </div>
                                        </div>
                                        <div class="question-item">
                                            <span class="question-rank">3</span>
                                            <div>
                                                <p class="mb-1">Стоимость тарифов</p>
                                                <small class="text-muted">28 вопросов</small>
                                            </div>
                                        </div>
                                        <div class="question-item">
                                            <span class="question-rank">4</span>
                                            <div>
                                                <p class="mb-1">Функции ассистента</p>
                                                <small class="text-muted">21 вопрос</small>
                                            </div>
                                        </div>
                                        <div class="question-item">
                                            <span class="question-rank">5</span>
                                            <div>
                                                <p class="mb-1">Техническая поддержка</p>
                                                <small class="text-muted">19 вопросов</small>
                                                                           </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Account Section -->
            <section id="account" class="content-section">
                <div class="container-fluid">
                    <h2 class="h3 mb-4">Учетная запись</h2>

                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Настройки профиля</h5>
                                </div>
                                <div class="card-body">
                                    <form id="profileForm">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="firstName" class="form-label">Имя</label>
                                                <input type="text" class="form-control" id="firstName" placeholder="Загрузка...">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="lastName" class="form-label">Фамилия</label>
                                                <input type="text" class="form-control" id="lastName" placeholder="Загрузка...">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" placeholder="Загрузка...">
                                        </div>
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">Телефон</label>
                                            <input type="tel" class="form-control" id="phone" placeholder="Телефон не указан">
                                        </div>
                                        <div class="mb-3">
                                            <label for="username" class="form-label">Имя пользователя</label>
                                            <input type="text" class="form-control" id="username" placeholder="Загрузка..." readonly>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                                    </form>
                                </div>
                            </div>

                            <div class="card mt-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Изменить пароль</h5>
                                </div>
                                <div class="card-body">
                                    <form id="passwordForm">
                                        <div class="mb-3">
                                            <label for="currentPassword" class="form-label">Текущий пароль</label>
                                            <input type="password" class="form-control" id="currentPassword">
                                        </div>
                                        <div class="mb-3">
                                            <label for="newPassword" class="form-label">Новый пароль</label>
                                            <input type="password" class="form-control" id="newPassword">
                                        </div>
                                        <div class="mb-3">
                                            <label for="confirmPassword" class="form-label">Подтвердите пароль</label>
                                            <input type="password" class="form-control" id="confirmPassword">
                                        </div>
                                        <button type="submit" class="btn btn-warning">Изменить пароль</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Текущий тариф</h5>
                                </div>
                                <div class="card-body">
                                    <div class="text-center mb-3">
                                        <div class="plan-icon mb-2">
                                            <i class="fas fa-star text-warning" style="font-size: 2rem;"></i>
                                        </div>
                                        <h4 class="mb-1">Профессиональный</h4>
                                        <p class="text-muted">2 990 ₽/месяц</p>
                                    </div>

                                    <ul class="list-unstyled mb-4">
                                        <li class="mb-2">• До 10 ассистентов</li>
                                        <li class="mb-2">• Все интеграции</li>
                                        <li class="mb-2">• База знаний 50 ГБ</li>
                                        <li class="mb-2">• Приоритетная поддержка</li>
                                    </ul>

                                    <div class="mb-3">
                                        <small class="text-muted">Следующее списание: 15 января 2025</small>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button class="btn btn-outline-primary" data-section="pricing">Сменить тариф</button>
                                        <button class="btn btn-outline-danger">Отменить подписку</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Pricing Section -->
            <section id="pricing" class="content-section">
                <div class="container-fluid">
                    <div class="text-center mb-5">
                        <h2 class="h3 mb-3">Выберите подходящий тариф</h2>
                        <p class="text-muted">Все тарифы включают 14-дневный бесплатный период</p>
                    </div>

                    <div class="row g-4 justify-content-center">
                        <div class="col-lg-4">
                            <div class="card pricing-card">
                                <div class="card-body">
                                    <div class="text-center mb-4">
                                        <h4>Базовый</h4>
                                        <div class="price mb-2">
                                            <span class="h2">990</span>
                                            <span class="text-muted">₽/мес</span>
                                        </div>
                                        <p class="text-muted">Для небольших проектов</p>
                                    </div>

                                    <ul class="list-unstyled mb-4">
                                        <li class="mb-2">• 3 ассистента</li>
                                        <li class="mb-2">• Telegram, WhatsApp</li>
                                        <li class="mb-2">• База знаний 5 ГБ</li>
                                        <li class="mb-2">• Email поддержка</li>
                                        <li class="mb-2">• Базовая аналитика</li>
                                    </ul>

                                    <div class="d-grid">
                                        <button class="btn btn-outline-primary">Выбрать план</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card pricing-card popular">
                                <div class="card-body">
                                    <div class="popular-badge">Популярный</div>
                                    <div class="text-center mb-4">
                                        <h4>Профессиональный</h4>
                                        <div class="price mb-2">
                                            <span class="h2">2 990</span>
                                            <span class="text-muted">₽/мес</span>
                                        </div>
                                        <p class="text-muted">Для растущего бизнеса</p>
                                    </div>

                                    <ul class="list-unstyled mb-4">
                                        <li class="mb-2">• 10 ассистентов</li>
                                        <li class="mb-2">• Все мессенджеры</li>
                                        <li class="mb-2">• CRM интеграции</li>
                                        <li class="mb-2">• База знаний 50 ГБ</li>
                                        <li class="mb-2">• Приоритетная поддержка</li>
                                        <li class="mb-2">• Расширенная аналитика</li>
                                    </ul>

                                    <div class="d-grid">
                                        <button class="btn btn-primary">Выбрать план</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card pricing-card">
                                <div class="card-body">
                                    <div class="text-center mb-4">
                                        <h4>Бизнес</h4>
                                        <div class="price mb-2">
                                            <span class="h2">5 990</span>
                                            <span class="text-muted">₽/мес</span>
                                        </div>
                                        <p class="text-muted">Для крупных компаний</p>
                                    </div>

                                    <ul class="list-unstyled mb-4">
                                        <li class="mb-2">• Безлимит ассистентов</li>
                                        <li class="mb-2">• Все интеграции</li>
                                        <li class="mb-2">• База знаний 500 ГБ</li>
                                        <li class="mb-2">• Белые метки</li>
                                        <li class="mb-2">• 24/7 поддержка</li>
                                        <li class="mb-2">• Персональный менеджер</li>
                                        <li class="mb-2">• API доступ</li>
                                    </ul>

                                    <div class="d-grid">
                                        <button class="btn btn-outline-primary">Выбрать план</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Help Section -->
            <section id="help" class="content-section">
                <div class="container-fluid">
                    <h2 class="h3 mb-4">Помощь</h2>

                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Часто задаваемые вопросы</h5>
                                </div>
                                <div class="card-body">
                                    <div class="accordion" id="faqAccordion">
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                                    Как создать нового ассистента?
                                                </button>
                                            </h2>
                                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                                <div class="accordion-body">
                                                    Для создания нового ассистента перейдите в раздел "Мои ассистенты" и нажмите кнопку "Добавить ассистента". Заполните необходимые поля: имя, мастер-промт, тональность и язык общения. После сохранения ассистент будет готов к работе.
                                                </div>
                                            </div>
                                        </div>

                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                                    Как подключить Telegram-бота?
                                                </button>
                                            </h2>
                                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                                <div class="accordion-body">
                                                    1. Создайте бота через @BotFather в Telegram<br>
                                                    2. Получите токен бота<br>
                                                    3. В разделе "Интеграции" найдите Telegram и нажмите "Настроить"<br>
                                                    4. Введите полученный токен<br>
                                                    5. Настройте webhook URL<br>
                                                    6. Сохраните настройки
                                                </div>
                                            </div>
                                        </div>

                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                                    Как загрузить документы в базу знаний?
                                                </button>
                                            </h2>
                                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                                <div class="accordion-body">
                                                    Перейдите в раздел "База знаний", выберите ассистента, для которого хотите загрузить документы. Перетащите файлы в зону загрузки или нажмите для выбора файлов. Поддерживаются форматы PDF, DOCX, TXT. Также можно подключить Google Docs.
                                                </div>
                                            </div>
                                        </div>

                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                                    Как изменить тариф?
                                                </button>
                                            </h2>
                                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                                <div class="accordion-body">
                                                    Перейдите в раздел "Тарифы", выберите подходящий план и нажмите "Выбрать план". Система перенаправит вас на страницу оплаты. Изменения вступят в силу сразу после успешной оплаты.
                                                </div>
                                            </div>
                                        </div>

                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                                    Как получить техническую поддержку?
                                                </button>
                                            </h2>
                                            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                                <div class="accordion-body">
                                                    Вы можете обратиться к нам через email support@aidashboard.ru или через наш Telegram-бота @aidashboard_support_bot. Время ответа зависит от вашего тарифного плана: до 24 часов для базового тарифа, до 4 часов для профессионального, и до 1 часа для бизнес-тарифа.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Свяжитесь с нами</h5>
                                </div>
                                <div class="card-body">
                                    <div class="contact-item mb-3">
                                        <i class="fas fa-envelope me-3 text-primary"></i>
                                        <div>
                                            <h6 class="mb-1">Email</h6>
                                            <a href="mailto:support@aidashboard.ru">support@aidashboard.ru</a>
                                        </div>
                                    </div>

                                    <div class="contact-item mb-3">
                                        <i class="fab fa-telegram me-3 text-primary"></i>
                                        <div>
                                            <h6 class="mb-1">Telegram</h6>
                                            <a href="https://t.me/aidashboard_support_bot">@aidashboard_support_bot</a>
                                        </div>
                                    </div>

                                    <div class="contact-item mb-3">
                                        <i class="fas fa-clock me-3 text-primary"></i>
                                        <div>
                                            <h6 class="mb-1">Время работы</h6>
                                            <p class="text-muted mb-0">Пн-Пт: 9:00-18:00 (МСК)</p>
                                        </div>
                                    </div>

                                    <hr>

                                    <div class="mb-3">
                                        <h6>Быстрая помощь</h6>
                                        <p class="text-muted small">Если у вас срочный вопрос, используйте чат-поддержку в правом нижнем углу.</p>
                                    </div>

                                    <button class="btn btn-primary btn-sm w-100" id="chatSupportBtn">
                                        <i class="fas fa-comments me-2"></i>Открыть чат
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Modals 
    <!-- Add Assistant Modal 
    <div class="modal fade" id="addAssistantModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assistantModalTitle">Добавить ассистента</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" action="add_assistant.php">
                <div class="modal-body">
                    <!-- Step 1: Integration Type Selection 
                    <div id="step1-integration" class="assistant-step">
                        <h6 class="mb-3">Выберите тип интеграции:</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="integration-option" data-type="whatsapp">
                                    <div class="card h-100 border-2 integration-card-option">
                                        <div class="card-body text-center">
                                            <i class="fab fa-whatsapp fa-2x text-success mb-3"></i>
                                            <h6>WhatsApp</h6>
                                            <p class="text-muted small">Business API</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="integration-option" data-type="telegram_client">
                                    <div class="card h-100 border-2 integration-card-option">
                                        <div class="card-body text-center">
                                            <i class="fab fa-telegram fa-2x text-info mb-3"></i>
                                            <h6>Telegram Client</h6>
                                            <p class="text-muted small">Client API интеграция</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="integration-option" data-type="telegram_bot">
                                    <div class="card h-100 border-2 integration-card-option">
                                        <div class="card-body text-center">
                                            <i class="fab fa-telegram fa-2x text-info mb-3"></i>
                                            <h6>Telegram Bot</h6>
                                            <p class="text-muted small">Bot API интеграция</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="integration-option" data-type="instagram">
                                    <div class="card h-100 border-2 integration-card-option">
                                        <div class="card-body text-center">
                                            <i class="fab fa-instagram fa-2x text-danger mb-3"></i>
                                            <h6>Instagram</h6>
                                            <p class="text-muted small">API интеграция</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Integration Settings 
                    <div id="step2-settings" class="assistant-step d-none">
                        <h6 class="mb-3">Настройки интеграции:</h6>

                        <!-- WhatsApp Settings 
                        <div id="whatsapp-settings" class="integration-settings d-none">
                            <div class="mb-3">
                                <label for="whatsappApiKey" class="form-label">WhatsApp API Key *</label>
                                <input name="whatsappApiKey" type="password" class="form-control" id="whatsappApiKey" 
                                       placeholder="Введите API ключ WhatsApp">
                                <div class="form-text">API ключ от WhatsApp Business</div>
                            </div>
                        </div>

                        <!-- Telegram Client Settings 
                        <div id="telegram-client-settings" class="integration-settings d-none">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="apiId" class="form-label">API ID *</label>
                                        <input name="TGapiId" type="text" class="form-control" id="apiId" 
                                               placeholder="1234567">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phoneNumber" class="form-label">Номер телефона *</label>
                                        <input name="TGphoneNumber" type="tel" class="form-control" id="phoneNumber" placeholder="+7XXXXXXXXXX">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="apiHash" class="form-label">API Hash *</label>
                                <input name="TGapiHash" type="password" class="form-control" id="apiHash" 
                                       placeholder="a1b2c3d4e5f6g7h8i9j0">
                                <div class="form-text">Получите на <a href="https://my.telegram.org" target="_blank">my.telegram.org</a></div>
                            </div>
                        </div>

                        <!-- Telegram Bot Settings -->
                        <div id="telegram-bot-settings" class="integration-settings d-none">
                            <div class="mb-3">
                                <label for="botToken" class="form-label">Bot Token *</label>
                                <input name="TGbotToken" type="password" class="form-control" id="botToken" 
                                       placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz">
                                <div class="form-text">Получите токен у @BotFather в Telegram</div>
                            </div>
                        </div>

                        <!-- Instagram Settings -->
                        <div id="instagram-settings" class="integration-settings d-none">
                            <div class="mb-3">
                                <label for="instagramApiKey" class="form-label">Instagram API Key *</label>
                                <input name="instagramApiKey" type="password" class="form-control" id="instagramApiKey" 
                                       placeholder="Введите API ключ Instagram">
                                <div class="form-text">API ключ от Instagram Basic Display</div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Assistant Configuration
                    <div id="step3-assistant" class="assistant-step d-none">
                        <h6 class="mb-3">Настройки ассистента:</h6>
                        <!--<form id="addAssistantForm">
                            <div class="mb-3">
                                <label for="assistantName" class="form-label">Имя ассистента *</label>
                                <input name="assistantName" type="text" class="form-control" id="assistantName" required
                                       placeholder="Например: Поддержка клиентов">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="assistantTone" class="form-label">Тональность *</label>
                                    <select name="assistantTone" class="form-select" id="assistantTone" required>
                                        <option value="">Выберите тональность</option>
                                        <option value="friendly">Дружелюбный</option>
                                        <option value="formal">Формальный</option>
                                        <option value="expert">Экспертный</option>
                                        <option value="professional">Профессиональный</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="assistantLanguage" class="form-label">Язык *</label>
                                    <select name="assistantLanguage" class="form-select" id="assistantLanguage" required>
                                        <option value="">Выберите язык</option>
                                        <option value="ru">Русский</option>
                                        <option value="en">Английский</option>
                                        <option value="es">Испанский</option>
                                        <option value="de">Немецкий</option>
                                        <option value="fr">Французский</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="assistantPrompt" class="form-label">Мастер-промт *</label>
                                <textarea name="assistantPrompt" class="form-control" id="assistantPrompt" rows="5" required
                                          placeholder="Опишите роль и поведение ассистента..."></textarea>
                                <div class="form-text">
                                    <small>Пресеты: 
                                        <button type="button" class="btn btn-link btn-sm p-0" onclick="setPromptPreset('support')">Поддержка</button>,
                                        <button type="button" class="btn btn-link btn-sm p-0" onclick="setPromptPreset('sales')">Продажи</button>,
                                        <button type="button" class="btn btn-link btn-sm p-0" onclick="setPromptPreset('consultant')">Консультант</button>
                                    </small>
                                </div>
                            </div>
                    <!--</form>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-outline-secondary d-none" id="prevStepBtn" onclick="prevAssistantStep()">
                        <i class="fas fa-arrow-left me-2"></i>Назад
                    </button>
                    <button type="button" class="btn btn-primary" id="nextStepBtn" onclick="nextAssistantStep()">
                        Далее <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                    <button type="submit" class="btn btn-success d-none" id="createAssistantBtn" onclick="saveAssistant()">
                        <i class="fas fa-plus me-2"></i>Создать ассистента
                    </button>
                </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Assistant Settings Modal -->
    <div class="modal fade" id="assistantSettingsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Настройки ассистента</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Статус</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="assistantStatus" checked>
                                    <label class="form-check-label" for="assistantStatus">
                                        Активен
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="settingsTone" class="form-label">Тональность</label>
                                <select class="form-select" id="settingsTone">
                                    <option value="friendly">Дружелюбный</option>
                                    <option value="formal">Формальный</option>
                                    <option value="expert">Экспертный</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="settingsPrompt" class="form-label">Мастер-промт</label>
                        <textarea class="form-control" id="settingsPrompt" rows="5"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Расписание работы</label>
                        <div class="schedule-settings">
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="workStart" class="form-label">Начало работы</label>
                                    <input type="time" class="form-control" id="workStart" value="09:00">
                                </div>
                                <div class="col-md-6">
                                    <label for="workEnd" class="form-label">Конец работы</label>
                                    <input type="time" class="form-control" id="workEnd" value="18:00">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-primary">Сохранить изменения</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Chat Test Modal -->
    <div class="modal fade" id="chatTestModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Тестирование ассистента</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="chat-container">
                        <div class="chat-messages" id="chatMessages">
                            <div class="message assistant-message">
                                <div class="message-content">
                                    Привет! Я ваш ассистент. Как дела? Чем могу помочь?
                                </div>
                                <small class="text-muted">Ассистент • сейчас</small>
                            </div>
                        </div>
                        <div class="chat-input">
                            <div class="input-group">
                                <input type="text" class="form-control" id="chatInput" placeholder="Введите сообщение...">
                                <button class="btn btn-primary" id="sendChatBtn">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Integration Settings Modal -->
    <div class="modal fade" id="integrationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Настройки интеграции</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="integrationSettings">
                        <!-- Settings will be populated by JavaScript -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-primary">Сохранить</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Instruction Modal -->
    <div class="modal fade" id="instructionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Инструкция по подключению</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="instruction-content">
                        <h6>Пошаговая инструкция:</h6>
                        <ol>
                            <li>Получите API ключи от сервиса</li>
                            <li>Скопируйте webhook URL из настроек</li>
                            <li>Настройте интеграцию в панели администратора</li>
                            <li>Проверьте соединение</li>
                        </ol>
                        <p class="text-muted">Подробные инструкции доступны в документации.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                    <a href="#" class="btn btn-primary">Открыть документацию</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Conversation Modal -->
    <div class="modal fade" id="conversationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Переписка</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="conversation-container">
                        <div class="conversation-messages" id="conversationMessages">
                            <!-- Messages will be populated by JavaScript -->
                        </div>
                        <div class="conversation-input">
                            <div class="input-group">
                                <input type="text" class="form-control" id="conversationInput" placeholder="Введите ответ...">
                                <button class="btn btn-primary" id="sendConversationBtn">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Integration Modal -->
    <div class="modal fade" id="addIntegrationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Добавить интеграцию</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-4">Выберите тип интеграции для добавления:</p>

                    <div class="integration-types">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="integration-type-card" data-type="whatsapp">
                                    <div class="card h-100 border-2">
                                        <div class="card-body text-center">
                                            <i class="fab fa-whatsapp fa-3x text-success mb-3"></i>
                                            <h6>WhatsApp</h6>
                                            <p class="text-muted small">Business API интеграция</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="integration-type-card" data-type="telegram_client">
                                    <div class="card h-100 border-2">
                                        <div class="card-body text-center">
                                            <i class="fab fa-telegram fa-3x text-info mb-3"></i>
                                            <h6>Telegram Client</h6>
                                            <p class="text-muted small">Client API интеграция</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="integration-type-card" data-type="telegram_bot">
                                    <div class="card h-100 border-2">
                                        <div class="card-body text-center">
                                            <i class="fab fa-telegram fa-3x text-primary mb-3"></i>
                                            <h6>Telegram Bot</h6>
                                            <p class="text-muted small">Bot API интеграция</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="integration-type-card" data-type="instagram">
                                    <div class="card h-100 border-2">
                                        <div class="card-body text-center">
                                            <i class="fab fa-instagram fa-3x text-danger mb-3"></i>
                                            <h6>Instagram</h6>
                                            <p class="text-muted small">API интеграция</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="integrationConfigForm" class="mt-4 d-none">
                        <hr>
                        <h6>Настройки интеграции</h6>
                        <form id="newIntegrationForm">
                            <div class="mb-3">
                                <label for="integrationName" class="form-label">Название</label>
                                <input type="text" class="form-control" id="integrationName" required>
                            </div>
                            <div class="mb-3">
                                <label for="integrationDescription" class="form-label">Описание</label>
                                <textarea class="form-control" id="integrationDescription" rows="2" placeholder="Краткое описание интеграции"></textarea>
                            </div>

                            <!-- WhatsApp Settings -->
                            <div id="whatsapp-config" class="integration-config d-none">
                                <div class="mb-3">
                                    <label for="whatsappApiKey" class="form-label">WhatsApp API Key *</label>
                                    <input type="password" class="form-control" id="whatsappApiKey" 
                                           placeholder="Введите API ключ WhatsApp Business">
                                    <div class="form-text">Получите API ключ от WhatsApp Business</div>
                                </div>
                            </div>

                            <!-- Telegram Client Settings -->
                            <div id="telegram_client-config" class="integration-config d-none">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="telegramClientApiId" class="form-label">API ID *</label>
                                            <input type="text" class="form-control" id="telegramClientApiId" 
                                                   placeholder="1234567">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="telegramClientPhone" class="form-label">Номер телефона *</label>
                                            <input type="tel" class="form-control" id="telegramClientPhone" 
                                                   placeholder="+7XXXXXXXXXX">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="telegramClientApiHash" class="form-label">API Hash *</label>
                                    <input type="password" class="form-control" id="telegramClientApiHash" 
                                           placeholder="a1b2c3d4e5f6g7h8i9j0">
                                    <div class="form-text">Получите на <a href="https://my.telegram.org" target="_blank">my.telegram.org</a></div>
                                </div>
                            </div>

                            <!-- Telegram Bot Settings -->
                            <div id="telegram_bot-config" class="integration-config d-none">
                                <div class="mb-3">
                                    <label for="telegramBotToken" class="form-label">Bot Token *</label>
                                    <input type="password" class="form-control" id="telegramBotToken" 
                                           placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz">
                                    <div class="form-text">Получите токен у @BotFather в Telegram</div>
                                </div>
                            </div>

                            <!-- Instagram Settings -->
                            <div id="instagram-config" class="integration-config d-none">
                                <div class="mb-3">
                                    <label for="instagramApiKey" class="form-label">Instagram API Key *</label>
                                    <input type="password" class="form-control" id="instagramApiKey" 
                                           placeholder="Введите API ключ Instagram">
                                    <div class="form-text">Получите API ключ от Instagram Basic Display</div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-primary" id="addIntegrationBtn" disabled>
                        <i class="fas fa-plus me-2"></i>Добавить интеграцию
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Notification Container -->
    <div id="notification-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 350px;"></div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom JS -->
    <script src="script.js"></script>
    <script>
        // Store navigation parameter for immediate use
        window.initialNavigation = (() => {
            const nav = document.getElementById('hidden-data-nav').textContent.trim();
            const cleanNav = nav.replace(/['"]/g, '');
            return cleanNav && cleanNav !== 'dashboard' ? cleanNav : null;
        })();

        // Function to handle navigation when app is ready
        window.handleInitialNavigation = function() {
            if (window.initialNavigation && window.app) {
                console.log('Navigating to section on load:', window.initialNavigation);
                window.app.navigateToSection(window.initialNavigation);
                window.initialNavigation = null; // Clear after use
            }
        };
    </script>
</body>
</html>