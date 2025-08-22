#!/bin/bash
set -e

echo "Запускаем систему и актуалиризируем ее..."
apt update && apt upgrade -y
apt-get install -y sudo
echo "Система запущена и обновлена."

echo "Устанавливаем PostgreSQL и PostgreSQL Contrib..."
sudo apt install -y postgresql postgresql-contrib
echo "PostgreSQL Установлен."

echo "Запускаем сервис PostgreSQL"
systemctl start postgresql
systemctl enable postgresql # Ensure it starts on boot
echo "PostgreSQL сервис запущен и доступен."

sh db.sh
