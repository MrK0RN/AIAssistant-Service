FROM python:3.9-slim
WORKDIR /app

RUN pip install telethon
COPY . .

CMD ["python", "bots/bot1/bot.py"]  # Заглушка (переопределяется в compose)