FROM php:8.1-fpm

# Install dependencies untuk gd, zip, dsb.
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql zip

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

# Install dependency Laravel
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts

EXPOSE 8080

# Kunci perbaikan: Mengganti php-fpm dengan php artisan serve
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8001"]