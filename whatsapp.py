import asyncio
import aiohttp
from typing import List, Dict, Optional

class WhatsAppBusinessBot:
    def __init__(self, api_url: str, phone_id: str, token: str, assistant_id: str):
        self.api_url = api_url
        self.phone_id = phone_id
        self.token = token
        self.assistant_id = assistant_id
        self.base_url = f"https://graph.facebook.com/v18.0/{phone_id}/messages"
        self.headers = {
            "Authorization": f"Bearer {token}",
            "Content-Type": "application/json"
        }
        self.session = aiohttp.ClientSession()

    async def close(self):
        await self.session.close()

    async def fetch_unread_messages(self) -> List[Dict]:
        """Получение непрочитанных сообщений через API"""
        try:
            url = f"{self.base_url}/conversations?fields=unread_count,messages{{text,from}}"
            async with self.session.get(url, headers=self.headers) as resp:
                if resp.status == 200:
                    data = await resp.json()
                    return [
                        {
                            "waid": msg['from'],
                            "text": msg['text']['body'],
                            "message_id": msg['id']
                        }
                        for conv in data.get('data', [])
                        if conv['unread_count'] > 0
                        for msg in conv.get('messages', [])
                        if 'text' in msg
                    ]
                return []
        except Exception as e:
            print(f"⚠ Fetch Error: {str(e)[:50]}")
            return []

    async def process_message(self, message: Dict) -> bool:
        """Полный цикл обработки одного сообщения"""
        try:
            # Шаг 1: Отправка на обработку в API
            async with self.session.post(
                self.api_url,
                json={
                    "text": message['text'],
                    "sender": message['waid'],
                    "assistant_id": self.assistant_id
                },
                timeout=10
            ) as resp:
                if resp.status != 200:
                    return False
                
                reply = (await resp.json()).get("message")
                if not reply:
                    return False

            # Шаг 2: Отправка ответа
            payload = {
                "messaging_product": "whatsapp",
                "recipient_type": "individual",
                "to": message['waid'],
                "context": {"message_id": message['message_id']},
                "type": "text",
                "text": {"body": reply}
            }

            async with self.session.post(
                self.base_url,
                headers=self.headers,
                json=payload
            ) as resp:
                if resp.status == 200:
                    print(f"✓ Ответ отправлен на {message['waid'][-4:]}")
                    return True
                error = await resp.json()
                print(f"✗ Ошибка отправки: {error}")
                return False

        except Exception as e:
            print(f"⚠ Process Error: {str(e)[:50]}")
            return False

    async def run_polling(self, interval: int = 10):
        """Асинхронный поллинг новых сообщений"""
        print("🟢 Бот запущен в режиме поллинга")
        try:
            while True:
                messages = await self.fetch_unread_messages()
                if messages:
                    tasks = [self.process_message(msg) for msg in messages]
                    results = await asyncio.gather(*tasks, return_exceptions=True)
                    print(f"Обработано {sum(1 for r in results if r)}/{len(messages)} сообщений")
                
                await asyncio.sleep(interval)
                
        except asyncio.CancelledError:
            print("🛑 Поллинг остановлен")
        finally:
            await self.close()

# Пример использования
async def main():
    bot = WhatsAppBusinessBot(
        api_url="https://your-ai-api.com/process",
        phone_id="1234567890",
        token="YOUR_META_TOKEN",
        assistant_id="AI_ASSISTANT_123"
    )
    
    try:
        await bot.run_polling(interval=15)
    except KeyboardInterrupt:
        print("\nЗавершение работы...")

if __name__ == "__main__":
    asyncio.run(main())