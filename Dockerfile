FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --no-scripts

FROM php:8.4-cli

WORKDIR /app

RUN docker-php-ext-install pdo_mysql

COPY --from=vendor /app/vendor ./vendor
COPY . .

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
