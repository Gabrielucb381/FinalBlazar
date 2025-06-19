# Usa PHP 8.4 FPM com Alpine (leve)
FROM php:8.4-fpm-alpine

# Instala dependências do sistema e PHP
RUN apk update && apk add --no-cache \
    bash \
    nano \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    wget \
    autoconf \
    g++ \
    make \
    linux-headers \
    curl-dev \
    openssl-dev \
    pkgconf \
    libzip-dev

# Instala Composer
RUN wget https://getcomposer.org/installer -O composer-installer.php \
    && php composer-installer.php --install-dir=/usr/local/bin --filename=composer \
    && rm composer-installer.php

# Instala extensões PHP
RUN pecl install mongodb xdebug \
    && docker-php-ext-enable mongodb xdebug \
    && docker-php-ext-install zip

# Copia código para dentro do container
COPY . /var/www/html

# Define diretório de trabalho
WORKDIR /var/www/html

# Expõe a porta padrão do PHP-FPM (9000)
EXPOSE 9000

# Start: PHP-FPM foreground
CMD ["php-fpm"]
