import subprocess
import shutil
from pathlib import Path
import json

def add_bot(api_id, api_hash, phone_number, bot_name=None):
    if not bot_name:
        bot_name = f"bot_{api_id}"

    # Создаем директорию бота
    bot_dir = Path(f"bots/{bot_name}")
    bot_dir.mkdir(parents=True, exist_ok=True)

    # Конфиг бота
    config = {
        "API_ID": api_id,
        "API_HASH": api_hash,
        "PHONE_NUMBER": phone_number,
        "BOT_NAME": bot_name
    }

    # Сохраняем config.py
    (bot_dir / "config.py").write_text(
        f"API_ID = {api_id}\n"
        f'API_HASH = "{api_hash}"\n'
        f'PHONE_NUMBER = "{phone_number}"\n'
        f'BOT_NAME = "{bot_name}"\n'
    )

    # Копируем код бота (шаблон)
    bot_code = """
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
    """
    (bot_dir / "bot.py").write_text(bot_code)

    # Обновляем docker-compose.yml
    update_docker_compose(bot_name)

    # Запускаем контейнер
    subprocess.run([
        "docker-compose", "up", "-d", "--no-deps", f"bot_{bot_name}"
    ], check=True)

def update_docker_compose(bot_name):
    compose_file = Path("docker-compose.yml")
    service_name = f"bot_{bot_name}"

    if not compose_file.exists():
        compose_file.write_text("""version: '3.8'
services:
""")

    with open(compose_file, "a") as f:
        f.write(f"""
  {service_name}:
    build:
      context: .
      dockerfile: docker/Dockerfile
    container_name: {service_name}
    command: python /app/bots/{bot_name}/bot.py
    volumes:
      - ./bots/{bot_name}:/app/bots/{bot_name}
    restart: unless-stopped
""")