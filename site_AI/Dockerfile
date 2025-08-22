FROM php:8.2-apache

# 1. Установка базовых настроек
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# 2. Установка зависимостей
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    libpq-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# 3. Установка PHP расширений
RUN docker-php-ext-install zip pdo pdo_pgsql pgsql

# 4. Настройка Apache
RUN a2enmod rewrite headers && \
    mkdir -p /var/lib/php/sessions && \
    chown www-data:www-data /var/lib/php/sessions && \
    chmod 755 /var/lib/php/sessions

# 5. Копирование файлов (сначала только структура)
COPY . /var/www/html/

# 6. Настройка прав ПОСЛЕ копирования файлов
RUN mkdir -p /var/www/html/system/logs && \
    chown -R www-data:www-data /var/www/html && \
    find /var/www/html -type d -exec chmod 755 {} \; && \
    find /var/www/html -type f -exec chmod 644 {} \; && \
    { [ -f /var/www/html/system/log.php ] && chmod 664 /var/www/html/system/log.php || true; } && \
    chmod -R 775 /var/www/html/system && \
    chmod 777 /var/www/html/system/logs

# 7. Конфигурация PHP
RUN echo "session.save_handler = files\n\
session.save_path = /var/lib/php/sessions\n\
error_log = /var/log/php_errors.log\n\
display_errors = On\n\
display_startup_errors = On\n\
error_reporting = E_ALL" > /usr/local/etc/php/conf.d/custom.ini

WORKDIR /var/www/html
EXPOSE 80