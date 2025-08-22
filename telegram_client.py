import asyncio
import aiohttp
from telethon import TelegramClient, events
from telethon.tl.types import PeerUser
import json
import config

class TelegramAsyncBot:
    def __init__(self, credentials, api_url, assistant_id):
        self.credentials = credentials.split("|")
        self.password = ''
        if len(self.credentials)>3:
            self.password = self.credentials[3]
        self.api_url = api_url
        self.assistant_id = assistant_id
        self.client = TelegramClient(
            'session_name',
            self.credentials[1],
            self.credentials[2]
        )

        if "proxy" in credentials:
            self.client.set_proxy(credentials["proxy"])

    async def get_activation_code_from_api(self):
        """Получение кода активации через API"""
        try:
            import aiohttp
            async with aiohttp.ClientSession() as session:
                async with session.get(
                    f"http://{config.web_url}/api/telegramCodeAPI.php",
                    params={
                        'hash': self.credentials.get('user_hash'),
                        'integration_id': self.credentials.get('integration_id')
                    }
                ) as response:
                    data = await response.json()
                    return data.get('code', '')
        except Exception as e:
            print(f"⚠ Ошибка получения кода через API: {e}")
            return ''

    async def login(self):
        """Асинхронный вход в Telegram"""
        try:
            # Пытаемся получить код активации через API
            async def code_callback():
                # Сначала проверяем в credentials
                if 'activation_code' in self.credentials:
                    return self.credentials['activation_code']

                # Затем пытаемся получить через API
                api_code = await self.get_activation_code_from_api()
                if api_code:
                    return api_code

                # В крайнем случае запрашиваем через input
                return input('Введите код: ')

            await self.client.start(
                phone=lambda: self.credentials[0],
                password=lambda: self.credentials.get('password', ''),
                code_callback=code_callback
            )
            print("✅ Успешный вход в Telegram!")
            return True
        except Exception as e:
            print(f"❌ Ошибка входа: {e}")
            return False

    async def send_to_api(self, text: str, user_id: int):
        """Отправка сообщения на API и получение ответа"""
        try:
            async with aiohttp.ClientSession() as session:
                async with session.post(
                    self.api_url,
                    json={
                        "text": text,
                        "thread": user_id,
                        "assistant_id": self.assistant_id
                    },
                    timeout=5
                ) as response:
                    api_data = await response.json()
                    return api_data.get("message", "❌ Нет поля 'message' в ответе API")
        except Exception as e:
            print(f"⚠ Ошибка API: {str(e)[:50]}")
            return None

    async def process_message(self, event):
        """Обработка входящего сообщения"""
        try:
            # Пропускаем исходящие сообщения и сообщения из групп/каналов
            if event.out or not isinstance(event.peer_id, PeerUser):
                return

            # Получаем ответ от API
            reply = await self.send_to_api(event.text, event.sender_id)
            if not reply:
                return

            # Отправляем ответ
            await event.respond(reply)
            print(f"💬 Ответ для {event.sender_id}: {reply[:20]}...")

        except Exception as e:
            print(f"⚠ Ошибка обработки сообщения: {str(e)[:50]}")

    async def get_unread_dialogs(self):
        """Получение непрочитанных диалогов"""
        try:
            dialogs = await self.client.get_dialogs()
            return [d for d in dialogs if d.unread_count > 0]
        except Exception as e:
            print(f"⚠ Ошибка получения диалогов: {e}")
            return []

    async def process_unread_messages(self):
        """Обработка непрочитанных сообщений"""
        try:
            unread_dialogs = await self.get_unread_dialogs()
            for dialog in unread_dialogs:
                messages = await self.client.get_messages(dialog.entity, limit=dialog.unread_count)
                for message in reversed(messages):  # Обрабатываем в хронологическом порядке
                    if not message.out and not message.read:
                        await self.process_message(message)
                        await message.mark_read()
        except Exception as e:
            print(f"⚠ Ошибка обработки непрочитанных: {e}")

    async def run_bot(self):
        """Основной цикл бота"""
        if not await self.login():
            return

        # Регистрируем обработчик новых сообщений
        self.client.add_event_handler(
            self.process_message,
            events.NewMessage(incoming=True)
        )

        print("🔍 Бот запущен. Ожидание сообщений...")

        # Запускаем поллинг и периодическую проверку непрочитанных
        async with self.client:
            while True:
                try:
                    await self.process_unread_messages()
                    await asyncio.sleep(10)  # Интервал проверки непрочитанных
                except Exception as e:
                    print(f"🚨 Критическая ошибка: {e}")
                    await asyncio.sleep(30)