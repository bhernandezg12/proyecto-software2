FROM php:8.3-fpm
WORKDIR /app

# Dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libmariadb-dev-compat \
    libmariadb-dev \
    && docker-php-ext-configure pdo_mysql \
    && docker-php-ext-install pdo_mysql

# Extensión MongoDB
RUN pecl install mongodb \
    && echo "extension=mongodb.so" > /usr/local/etc/php/conf.d/mongodb.ini

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar proyecto
COPY . .

# Instalar dependencias PHP
RUN composer install --no-dev --optimize-autoloader

# Permisos Laravel
RUN chmod -R 777 storage bootstrap/cache

EXPOSE 8000
