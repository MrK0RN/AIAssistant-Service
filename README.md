```markdown
# 🤖 Многофункциональная система автоматизации и AI-ассистентов

Комплексная система для автоматизации процессов регистрации, управления AI-ассистентами и интеграции с популярными мессенджерами.

## 🌟 Основные возможности

### 🤖 AI-ассистенты
- Создание и управление OpenAI ассистентами
- Работа с векторными хранилищами и файлами
- Многопоточные чаты с отслеживанием использования токенов
- Интеграция с базой данных PostgreSQL

### 📱 Мессенджер-боты
- **Telegram**: поддержка как клиентских сессий, так и ботов
- **WhatsApp Business**: интеграция через Facebook Graph API  
- **Instagram Direct**: обработка сообщений через вебхуки
- Унифицированная система прокси для всех платформ

### ⚙️ Автоматизация
- Автоматическая регистрация на платформах
- Работа с почтовыми ящиками
- Генерация тестовых данных
- Управление сессиями через Selenium

## 🏗️ Архитектура системы

### Основные компоненты

```
├── 🤖 AI Core (OpenAI ассистенты)
├── 📱 Messenger Integrations
├── 🗄️ Database Layer (PostgreSQL)
├── 🔄 Proxy Management
├── ⚙️ Automation Tools
└── 🐳 Docker Orchestration
```

## 🚀 Быстрый старт

### Предварительные требования

- Docker и Docker Compose
- PostgreSQL 15+
- Python 3.9+
- OpenAI API ключ

### Установка и запуск

1. **Клонирование и настройка**
```bash
git clone <repository>
cd <project-directory>
```

2. **Настройка переменных окружения**
```bash
cp .env.example .env
# Заполните необходимые переменные в .env файле
```

3. **Запуск системы**
```bash
# Запуск всей системы
docker-compose up -d

# Или поэтапный запуск
sh start.sh
```

4. **Проверка работоспособности**
```bash
curl http://localhost:5000/health
```

## 📋 Конфигурация

### Ключевые переменные окружения

```env
# OpenAI
OPENAI_API_KEY=your_openai_api_key_here

# База данных
DB_HOST=db
DB_NAME=openai_assistants_db_2
DB_USER=openai_assistants_db_2
DB_PASSWORD=cu3ndh3behc
DB_PORT=5432

# Прокси (опционально)
PROXY=socks5://user:pass@proxy_host:port

# Платформенные настройки
PLATFORM=telegram_bot
CREDS=your_credentials_here
API_ENDPOINT=http://open_ai:5000/chat
ASSISTANT_ID=your_assistant_id
```

## 🎯 Использование API

### Управление ассистентами

**Создание ассистента**
```bash
curl -X POST http://localhost:5000/assistants \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Мой ассистент",
    "instructions": "Ты полезный ассистент",
    "model": "gpt-4o-mini",
    "temperature": 0.7,
    "file_paths": ["files/document.pdf"]
  }'
```

**Чат с ассистентом**
```bash
curl -X POST http://localhost:5000/chat \
  -H "Content-Type: application/json" \
  -d '{
    "assistant_id": 1,
    "text": "Привет! Как дела?",
    "thread": "user_123"
  }'
```

### Управление интеграциями

**Добавление новой интеграции**
```bash
curl -X POST http://localhost:5001/integration \
  -H "Content-Type: application/json" \
  -d '{
    "id": 45,
    "platform": "telegram_bot",
    "credentials": {
      "bot_token": "7101634669:AAHFJkrKTUcYl5nS9LFmg8K7cq_UVxK7a5c"
    },
    "url": "http://open_ai:5000/chat",
    "aid": 6,
    "proxy": "socks5://user:pass@proxy:port"
  }'
```

## 🗄️ Структура базы данных

Система использует PostgreSQL со следующими основными таблицами:

- `assistants` - информация об AI-ассистентах
- `openai_files` - загруженные файлы
- `vector_stores` - векторные хранилища
- `chats` - истории чатов и использование токенов
- `vector_store_files` - связь файлов с хранилищами
- `assistant_vector_stores` - связь ассистентов с хранилищами

## 🔧 Утилиты и скрипты

### Основные скрипты

- **`start.sh`** - основной скрипт запуска системы
- **`db.sh`** - настройка базы данных
- **`docker_install.sh`** - установка Docker

### Модули автоматизации

- **`auto_reg.py`** - автоматическая регистрация
- **`login.py`** - управление авторизацией
- **`reger.py`** - основной процесс регистрации
- **`data_gen.py`** - генерация тестовых данных

## 🌐 Интеграции с мессенджерами

### Поддерживаемые платформы

1. **Telegram**
   - Боты через Bot API
   - Клиентские сессии через Telethon
   - Поддержка прокси и двухфакторной аутентификации

2. **WhatsApp Business**
   - Интеграция через Facebook Graph API
   - Обработка входящих сообщений
   - Отправка ответов через официальный API

3. **Instagram Direct**
   - Вебхук обработка сообщений
   - Интеграция с Facebook Graph API
   - Поддержка медиафайлов

## ⚡ Производительность и масштабирование

- Асинхронная обработка сообщений
- Поддержка пула соединений с БД
- Кэширование часто используемых данных
- Балансировка нагрузки через Docker

## 🔒 Безопасность

- Шифрование чувствительных данных
- Валидация входящих запросов
- Система прав доступа
- Логирование всех операций

## 🐛 Отладка и мониторинг

### Логирование
```bash
# Просмотр логов
docker-compose logs -f

# Логи конкретного сервиса
docker-compose logs open_ai
```

### Мониторинг здоровья
```bash
# Проверка здоровья API
curl http://localhost:5000/health

# Проверка здоровья базы данных
curl http://localhost:5000/health/db
```

## 📊 Статистика и аналитика

Система предоставляет метрики:
- Использование токенов по чатам
- Статистика сообщений по платформам
- Время ответа ассистентов
- Успешность обработки запросов

## 🚦 Развертывание в продакшене

### Рекомендации для production

1. **Настройка reverse proxy** (nginx)
2. **Включение SSL/TLS** сертификатов
3. **Настройка бэкапов** базы данных
4. **Мониторинг** и алертинг
5. **Балансировщик нагрузки** для горизонтального масштабирования

### Переменные для production
```env
NODE_ENV=production
LOG_LEVEL=info
DATABASE_URL=postgresql://user:pass@host:port/db
REDIS_URL=redis://host:port
```

## 🤝 Contributing

1. Форкните репозиторий
2. Создайте feature branch (`git checkout -b feature/amazing-feature`)
3. Закоммитьте изменения (`git commit -m 'Add amazing feature'`)
4. Запушьте branch (`git push origin feature/amazing-feature`)
5. Откройте Pull Request

## 📄 Лицензия

Этот проект лицензирован под MIT License - смотрите файл [LICENSE](LICENSE) для деталей.

## 🆘 Поддержка

Если у вас возникли вопросы или проблемы:

1. Проверьте [документацию](docs/)
2. Посмотрите [issues](https://github.com/your-repo/issues)
3. Создайте новый issue с описанием проблемы

---

**Примечание**: Перед использованием в продакшене обязательно:
- Настройте надежные пароли
- Включите все security меры
- Протестируйте на staging окружении
- Настройте мониторинг и алертинг
```
