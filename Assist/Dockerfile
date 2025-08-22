FROM python:3.13-slim as builder

WORKDIR /Assist

# Копируем и устанавливаем зависимости
COPY requirements.txt .
RUN pip install --user -r requirements.txt

# Второй этап - итоговый образ
FROM python:3.13-slim
WORKDIR /Assist

# Копируем только необходимое из builder
COPY --from=builder /root/.local /root/.local
COPY . .

# Убедимся, что скрипты в PATH
ENV PATH=/root/.local/bin:$PATH
ENV PYTHONUNBUFFERED=1

# Оптимальные переменные для Python
ENV PYTHONDONTWRITEBYTECODE=1 \
    PIP_NO_CACHE_DIR=1

# Запуск бота
CMD ["python", "-u", "app.py"]