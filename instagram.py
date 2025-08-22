import os
import json
import requests
from flask import Flask, request
from dotenv import load_dotenv

# Загружаем переменные окружения из .env файла
load_dotenv()

app = Flask(__name__)

# --- Конфигурация из .env файла ---
VERIFY_TOKEN = os.getenv("WEBHOOK_VERIFY_TOKEN")
PAGE_ACCESS_TOKEN = os.getenv("FACEBOOK_PAGE_ACCESS_TOKEN")
ASSISTANT_API_URL = os.getenv("ASSISTANT_API_URL")
ASSISTANT_ID = os.getenv("ASSISTANT_ID") # Если ваш API ассистента требует assistant_id

if not all([VERIFY_TOKEN, PAGE_ACCESS_TOKEN, ASSISTANT_API_URL, ASSISTANT_ID]):
    raise ValueError("Не все необходимые переменные окружения установлены! Проверьте WEBHOOK_VERIFY_TOKEN, FACEBOOK_PAGE_ACCESS_TOKEN, ASSISTANT_API_URL, ASSISTANT_ID.")

# --- Функция для отправки сообщений в Instagram через Graph API ---
def send_instagram_message(recipient_id, message_text):
    """
    Отправляет текстовое сообщение в Instagram Direct через Facebook Graph API.
    """
    url = f"https://graph.facebook.com/v19.0/{os.getenv('FACEBOOK_PAGE_ID')}/messages"
    # Для Instagram Direct API используется Messenger API, но с определенными настройками.
    # Recipient ID - это Instagram Scoped ID, который вы получаете из входящего вебхука.
    payload = {
        "recipient": {"id": recipient_id},
        "message": {"text": message_text},
        "messaging_type": "RESPONSE",
        "access_token": PAGE_ACCESS_TOKEN
    }
    headers = {"Content-Type": "application/json"}

    try:
        response = requests.post(url, headers=headers, json=payload)
        response.raise_for_status() # Выбросит исключение для 4xx/5xx ошибок
        print(f"✅ Сообщение успешно отправлено для {recipient_id}: {message_text[:50]}...")
        return response.json()
    except requests.exceptions.RequestException as e:
        print(f"❌ Ошибка при отправке сообщения в Instagram: {e}")
        if response is not None:
            print(f"Ответ API: {response.text}")
        return None

# --- Функция для взаимодействия с вашим API ассистента ---
def get_assistant_response(user_text, thread_id):
    """
    Отправляет текст пользователя на ваш API ассистента и возвращает ответ.
    """
    payload = {
        "text": user_text,
        "thread": thread_id, # Используем thread_id как идентификатор диалога
        "assistant_id": ASSISTANT_ID
    }
    headers = {"Content-Type": "application/json"}

    try:
        response = requests.post(ASSISTANT_API_URL, headers=headers, json=payload, timeout=10)
        response.raise_for_status()
        api_data = response.json()
        return api_data.get("message", "Извините, не удалось получить ответ от ассистента.")
    except requests.exceptions.RequestException as e:
        print(f"❌ Ошибка при запросе к API ассистента: {e}")
        return "Извините, произошла ошибка при обработке вашего запроса."

# --- Вебхук эндпоинт ---
@app.route("/webhook", methods=["GET", "POST"])
def webhook():
    if request.method == "GET":
        # Верификация вебхука
        mode = request.args.get("hub.mode")
        token = request.args.get("hub.verify_token")
        challenge = request.args.get("hub.challenge")

        if mode and token:
            if mode == "subscribe" and token == VERIFY_TOKEN:
                print("✅ WEBHOOK_VERIFIED")
                return challenge, 200
            else:
                return "VERIFICATION_FAILED", 403
        return "Missing parameters", 400

    elif request.method == "POST":
        # Обработка входящих сообщений
        data = request.json
        print(f"Received webhook event: {json.dumps(data, indent=2)}")

        # Проверяем, что это событие от Instagram Direct
        if data.get("object") == "instagram":
            for entry in data.get("entry", []):
                for messaging_event in entry.get("messaging", []):
                    # Проверяем, что это сообщение, а не что-то другое
                    if messaging_event.get("message"):
                        sender_id = messaging_event["sender"]["id"] # ID отправителя
                        message_text = messaging_event["message"].get("text")

                        if message_text:
                            print(f"📩 Получено сообщение от {sender_id}: {message_text}")
                            # Получаем ответ от вашего API ассистента
                            assistant_reply = get_assistant_response(message_text, sender_id) # sender_id как thread_id

                            # Отправляем ответ обратно в Instagram
                            if assistant_reply:
                                send_instagram_message(sender_id, assistant_reply)
                            else:
                                print(f"❗ Не удалось получить ответ от ассистента для {sender_id}")
                        else:
                            print(f"❗ Получено нетекстовое сообщение от {sender_id}")

        return "EVENT_RECEIVED", 200 # Всегда возвращаем 200 OK, чтобы Facebook не повторял отправку

# Запуск Flask-приложения
if __name__ == "__main__":
    # Убедитесь, что вы используете gunicorn или другой WSGI-сервер для продакшена.
    # Для тестирования:
    print("🚀 Запускаем Flask-сервер...")
    app.run(host="0.0.0.0", port=int(os.environ.get("PORT", 5000)))