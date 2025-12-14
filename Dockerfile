# Sử dụng image PHP 8.2 chính thức
FROM php:8.2-fpm

# Cài đặt các thư viện hệ thống cần thiết cho Laravel
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nginx \
    && rm -rf /var/lib/apt/lists/*

# Cài đặt các PHP extensions (pdo_mysql cho TiDB Cloud/MySQL)
RUN docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd

# Cài đặt Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Thiết lập thư mục làm việc
WORKDIR /var/www

# Copy toàn bộ code vào container
COPY . /var/www

# Cài đặt dependencies của Laravel
RUN composer install --no-dev --optimize-autoloader

# Phân quyền cho thư mục storage
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Cấu hình Nginx (Tạo file cấu hình ảo ngay trong lệnh này)
RUN echo "server { \
    listen 80; \
    index index.php index.html; \
    root /var/www/public; \
    location / { \
        try_files \$uri \$uri/ /index.php?\$query_string; \
    } \
    location ~ \.php$ { \
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_index index.php; \
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name; \
        include fastcgi_params; \
    } \
}" > /etc/nginx/sites-available/default

# Script khởi động: Chạy Migration -> Start PHP-FPM -> Start Nginx
CMD php artisan migrate --force && service nginx start && php-fpm