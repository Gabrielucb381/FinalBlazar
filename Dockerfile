# Usa PHP 8.4 FPM com Alpine
FROM php:8.4-fpm-alpine

# Instala dependências do sistema e utilitários comuns
RUN apk update && apk add --no-cache \
    bash \
    nano \
    curl \
    git \
    wget \
    zip \
    unzip \
    libpng-dev \
    libxml2-dev \
    libzip-dev \
    autoconf \
    g++ \
    make \
    linux-headers \
    openssl-dev \
    pkgconf \
    file \
    dpkg-dev \
    libmagic \
    re2c

# Instala Composer
RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

# (Opcional) Instala Symfony CLI — remova se não for usar em produção
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

# Instala extensões PHP via PECL e core
RUN pecl install mongodb xdebug \
    && docker-php-ext-enable mongodb xdebug \
    && docker-php-ext-install zip

# Cria pasta da app e define permissões seguras
WORKDIR /var/www/html

# Copia o projeto
COPY . .

# Define usuário (opcional, mas recomendado em produção para não rodar como root)
# RUN addgroup -g 1000 www && adduser -G www -u 1000 -D www
# USER www

# Comando padrão: inicia FPM em foreground
CMD ["php-fpm"]
