# Sử dụng PHP 8.2 với Apache
FROM php:8.2-apache

# Cài đặt các dependencies cần thiết
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    nodejs \
    npm \
    libicu-dev \
    libpq-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl

# Cài đặt các PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    opcache \
    intl \
    xml \
    dom

# Cấu hình PHP
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Cài đặt Composer
COPY --from=composer:2.5.8 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# Cấu hình Apache
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Kích hoạt mod rewrite
RUN a2enmod rewrite headers

# Thiết lập thư mục làm việc
WORKDIR /var/www/html

# Copy composer files đầu tiên
COPY composer.json composer.lock ./

# Cài đặt dependencies
RUN composer install --no-dev --no-scripts --no-autoloader --verbose

# Copy toàn bộ source code
COPY . .

# Tạo autoloader và chạy scripts
RUN composer dump-autoload --optimize \
    && composer run-script post-autoload-dump

# Thiết lập quyền
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Tạo storage link
RUN php artisan storage:link || true

# Cache config và routes
RUN php artisan config:cache || true \
    && php artisan route:cache || true \
    && php artisan view:cache || true

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"] 