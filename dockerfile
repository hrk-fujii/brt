FROM php:7.4-fpm

# composerが使えるようにする
RUN apt-get update \ 
  && apt-get install -y libzip-dev
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN docker-php-ext-install zip

# mysql拡張
RUN docker-php-ext-install pdo pdo_mysql