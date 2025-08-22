
from telethon import TelegramClient, events
from config import API_ID, API_HASH, PHONE_NUMBER, BOT_NAME
import asyncio

client = TelegramClient(BOT_NAME, API_ID, API_HASH)

@client.on(events.NewMessage(incoming=True))
async def handler(event):
    await event.reply(f"{BOT_NAME}: Получил сообщение!")

async def main():
    await client.start(PHONE_NUMBER)
    print(f"{BOT_NAME} запущен!")
    await client.run_until_disconnected()

if __name__ == "__main__":
    asyncio.run(main())
    