import os

import requests

from proxy import GlobalSocksProxy
import asyncio
from dotenv import load_dotenv


def _get_current_ip():
    """Получение текущего IP (без прокси)"""
    try:
        response = requests.get('https://api.ipify.org?format=json', timeout=5)
        return response.json().get('ip')
    except:
        return None

load_dotenv()

credentials = os.getenv("CREDS")
token = os.getenv("TOKEN")
platform = os.getenv("PLATFORM")
proxy_url = os.getenv("PROXY")
API_ENDPOINT = os.getenv("API_ENDPOINT")
ASSISTANT_ID = os.getenv("ASSISTANT_ID")
print(f"TOKEN: {token}")
print(f"PLATFORM: {platform}")
print(f"PROXY: {proxy_url}")
print(f"API_ENDPOINT: {API_ENDPOINT}")
print(f"ASSISTANT_ID: {ASSISTANT_ID}")

if not (token or credentials):
    print("No credentials provided")
    exit()

if proxy_url:
    proxy = GlobalSocksProxy(proxy_url)
    if proxy.activate_proxy():
        print("Proxy activated successfully")

        # Проверка работы прокси
        success, ip_or_error = proxy.test_proxy()
        if success:
            print(f"Proxy works! Your IP: {ip_or_error}")
            print(f"Original IP: {proxy.original_ip}")
        else:
            print(f"Proxy test failed: {ip_or_error}")
            exit()
else:
    print("No proxy provided")

retries = 5

if API_ENDPOINT:
    health_endpoint = API_ENDPOINT
    l = len(API_ENDPOINT) - 1
    while health_endpoint[l] != "/":
        l-=1
        health_endpoint = health_endpoint[:-1]
    print(health_endpoint + "health")
    flag = True
    for i in range(retries):
        if requests.get(health_endpoint + "health").status_code == 200:
            print("API is working")
            flag = False
            break
        else:
            print("API is unreacheble")
    if flag: exit()

else:
    print("No api endpoint provided")
    exit()

match platform:
    case "instagram":
        from instagram import InstagramAsyncBot

        bot = InstagramAsyncBot(credentials, API_ENDPOINT, ASSISTANT_ID)
        asyncio.run(bot.run_bot())
    case "telegram_client":
        from telegram_client import TelegramAsyncBot

        bot = TelegramAsyncBot(
            credentials=credentials,
            api_url=API_ENDPOINT,
            assistant_id=ASSISTANT_ID
        )
        asyncio.run(bot.run_bot())
    case "telegram_bot":
        from telegram_bot import TelegramAsyncBot
        bot = TelegramAsyncBot(
            token=credentials,
            api_url=API_ENDPOINT,
            assistant_id=ASSISTANT_ID
        )
        asyncio.run(bot.run())
    case "whatsapp":
        from whatsapp import WhatsAppBusinessBot

        bot = WhatsAppBusinessBot(
            api_url=API_ENDPOINT,
            credentials=credentials,
            assistant_id=ASSISTANT_ID
        )
        try:
            bot.run_polling(interval=15)
        except KeyboardInterrupt:
            print("\nЗавершение работы...")
    case _:
        print("No platform provided")
        exit()