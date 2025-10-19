FROM php:8.4-fpm-alpine

# 2. Встановлюємо робочу директорію
WORKDIR /var/www/html

# 3. Встановлюємо залежності (для Postgres та RabbitMQ)
# Встановлюємо системні залежності:
# postgresql-dev -- потрібен для pdo_pgsql
# rabbitmq-c-dev -- потрібен для amqp (pecl)
RUN apk add --no-cache \
    postgresql-dev \
    rabbitmq-c-dev \
    build-base \
    autoconf \
    php84-dev \
    php84-pear \
    && docker-php-ext-install pdo pdo_pgsql \
    && pecl install amqp \
    && docker-php-ext-enable amqp

# 4. Встановлюємо Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 5. Встановлюємо ab для тестування
RUN apk add --no-cache apache2-utils
