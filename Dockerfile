# Sử dụng PHP 8.1 với Apache
FROM php:8.1-apache

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
    npm

# Cài đặt các PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Cài đặt Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

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

# Cài đặt dependencies với verbose để xem lỗi chi tiết
RUN composer install --no-dev --optimize-autoloader --verbose

# Copy toàn bộ source code
COPY . .

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