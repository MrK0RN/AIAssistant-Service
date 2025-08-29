# 🤖 Многофункциональная платформа автоматизации и AI-ассистентов

Комплексная система для автоматизации процессов регистрации, работы с мессенджерами и управления AI-ассистентами через различные платформы.

## 🌟 Основные возможности

- **🤖 AI-ассистенты** - Создание и управление ассистентами на базе OpenAI
- **📱 Мультиплатформенность** - Поддержка Telegram, WhatsApp, Instagram
- **🔐 Автоматизация** - Регистрация и авторизация на различных платформах
- **🗄️ База данных** - Централизованное хранение данных в PostgreSQL
- **🌐 Прокси-поддержка** - Работа через SOCKS5 прокси
- **🐳 Docker-развертывание** - Полная контейнеризация системы

## 🏗️ Архитектура системы

### Основные компоненты:

- **`app.py`** - Главное Flask-приложение для управления ассистентами
- **`openai_service.py`** - Сервис для работы с OpenAI API
- **`database.py`** - Модуль работы с PostgreSQL
- **`global.py`** - Глобальный запуск ботов для разных платформ
- **Интеграции** - Telegram, WhatsApp, Instagram клиенты

### Поддерживаемые платформы:
- `telegram_client` - Клиент Telegram (user account)
- `telegram_bot` - Бот Telegram
- `whatsapp` - Интеграция с WhatsApp Business
- `instagram` - Интеграция с Instagram Direct

## 📦 Установка и запуск

### Быстрый старт:

```bash
# Клонирование репозитория
git clone <repository-url>
cd <project-directory>

# Запуск автоматической установки
chmod +x start.sh db.sh
./start.sh
```

### Ручная установка:

1. **Установка зависимостей:**
```bash
pip install -r requirements.txt
```

2. **Настройка базы данных:**
```bash
# Создание пользователя и БД
sudo -u postgres psql -c "CREATE USER openai_assistants_db_2 WITH PASSWORD 'cu3ndh3behc';"
sudo -u postgres psql -c "CREATE DATABASE openai_assistants_db_2;"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE openai_assistants_db_2 TO openai_assistants_db_2;"

# Инициализация структуры БД
PGPASSWORD=cu3ndh3behc psql -h localhost -U openai_assistants_db_2 -d openai_assistants_db_2 -f db_setup.sql
```

3. **Настройка переменных окружения:**
Создайте файл `.env`:
```env
OPENAI_API_KEY=your_openai_api_key
DB_HOST=localhost
DB_NAME=openai_assistants_db_2
DB_USER=openai_assistants_db_2
DB_PASSWORD=cu3ndh3behc
DB_PORT=5432
```

4. **Запуск Docker:**
```bash
docker-compose up -d
```

## 🔧 Конфигурация

### Основные настройки в `config.py`:

```python
db = {
    "DB_HOST": "db",
    "DB_NAME": "openai_assistants_db_2",
    "DB_USER": "openai_assistants_db_2",
    "DB_PASSWORD": "cu3ndh3behc",
    "DB_PORT": 5432
}
```

### Структура базы данных:

- **`assistants`** - AI-ассистенты OpenAI
- **`openai_files`** - Загруженные файлы
- **`vector_stores`** - Векторные хранилища
- **`chats`** - История чатов
- **`vector_store_files`** - Связи файлов с хранилищами

## 🚀 Использование API

### Создание ассистента:

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

### Чат с ассистентом:

```bash
curl -X POST http://localhost:5000/chat \
  -H "Content-Type: application/json" \
  -d '{
    "assistant_id": 1,
    "text": "Привет! Как дела?",
    "thread": "user123"
  }'
```

## 🤖 Интеграция с мессенджерами

### Telegram Bot:

```python
from telegram_bot import TelegramAsyncBot

bot = TelegramAsyncBot(
    token="YOUR_BOT_TOKEN",
    api_url="http://localhost:5000/chat",
    assistant_id="1"
)
asyncio.run(bot.run())
```

### WhatsApp:

```python
from whatsapp import WhatsAppBusinessBot

bot = WhatsAppBusinessBot(
    api_url="http://localhost:5000/chat",
    phone_id="YOUR_PHONE_ID",
    token="YOUR_META_TOKEN",
    assistant_id="1"
)
asyncio.run(bot.run_polling())
```

## 🔐 Настройка прокси

Система поддерживает работу через SOCKS5 прокси:

```python
from proxy import GlobalSocksProxy

proxy = GlobalSocksProxy("socks5://user:pass@proxy-host:port")
if proxy.activate_proxy():
    print("Прокси активирован")
```

## 📊 Мониторинг и управление

### Health check:
```bash
curl http://localhost:5000/health
```

### Список интеграций:
```bash
curl http://localhost:5001/integrations
```

### Статус интеграции:
```bash
curl http://localhost:5001/integration/status/42
```

## 🐛 Поиск и устранение неисправностей

### common issues:

1. **Ошибка подключения к БД:**
   - Проверьте запущен ли PostgreSQL
   - Убедитесь в правильности credentials в `.env`

2. **Проблемы с прокси:**
   - Проверьте доступность прокси-сервера
   - Убедитесь в правильности формата URL

3. **Ошибки OpenAI API:**
   - Проверьте валидность API ключа
   - Убедитесь в достаточном балансе

## 📝 Лицензия

Проект предоставляется как есть. Используйте ответственно в соответствии с правилами используемых платформ.

## 🤝 Вклад в проект

Для внесения изменений:

1. Форкните репозиторий
2. Создайте feature branch
3. Внесите изменения
4. Создайте Pull Request

## 📞 Поддержка

По вопросам использования и проблемам создавайте issues в репозитории проекта.

---

**Важно**: Используйте систему ответственно и в соответствии с правилами используемых платформ и законодательством вашей страны.
