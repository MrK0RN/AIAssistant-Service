import asyncio
from aiogram import Bot, Dispatcher, types
from aiogram.filters import Command
from aiogram.types import Message
import aiohttp
import json

class TelegramAsyncBot:
    def __init__(self, token: str, api_url: str, assistant_id: str):
        self.token = token
        self.api_url = "http://open_ai:5000/chat"
        self.assistant_id = assistant_id
        self.bot = Bot(token=token)
        self.dp = Dispatcher()

    async def send_to_api(self, message: types.Message) -> str:
        """Отправка сообщения на API и получение ответа"""
        #try:
        async with aiohttp.ClientSession() as session:
            async with session.post(
                self.api_url,
                json={
                    "text": message.text,
                    "thread": message.chat.id,
                    "assistant_id": self.assistant_id
                },
                timeout=None
            ) as response:
                api_data = await response.json()
                print(str(api_data))
                return api_data.get("message", "❌ Нет поля 'message' в ответе API")
                '''
        except Exception as e:
            print(f"⚠ Ошибка API: {str(e)[:50]}")
            return None
            '''

    async def handle_message(self, message: Message):
        """Обработка входящих сообщений"""
        if message.from_user.is_bot:
            return  # Игнорируем сообщения от ботов
        print("text: "+message.text)
        reply = await self.send_to_api(message)
        if reply:
            await message.reply(reply)
            print(f"💬 Ответ для {message.chat.id}: {reply[:20]}...")

    async def on_startup(self):
        """Действия при запуске бота"""
        print("✅ Бот запущен и готов к работе!")
        self.dp.message.register(self.handle_message)

    async def run(self):
        """Запуск бота"""
        await self.on_startup()
        await self.dp.start_polling(self.bot)