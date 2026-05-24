# ---- Dockerfile untuk CIPHER (Laravel 12 backend) ----
# Base image: PHP 8.3 dengan Apache
FROM php:8.3-apache

# 1. Install dependency sistem + ekstensi PHP yang dibutuhkan Laravel
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring zip gd bcmath \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Aktifkan mod_rewrite Apache (wajib untuk routing Laravel)
RUN a2enmod rewrite

# 3. Arahkan Apache ke folder /public milik Laravel
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 4. Ambil Composer dari image resmi
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# 5. Copy file dependency dulu (biar layer cache Docker efektif)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# 6. Copy seluruh source code
COPY . .

# 7. Selesaikan instalasi Composer
RUN composer dump-autoload --optimize --no-dev

# 8. Set permission folder yang perlu ditulis Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 9. Apache jalan di port 80 (Render otomatis memetakan ke HTTPS publik)
EXPOSE 80

# 10. Saat container start: jalankan migrasi lalu nyalakan Apache
#     'migrate --force' diperlukan karena production tidak interaktif.
CMD php artisan migrate --force && \
    php artisan config:cache && \
    php artisan route:cache && \
    apache2-foreground
