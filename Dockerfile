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
    supervisor \
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

# Phân quyền cho thư mục storage và bootstrap/cache
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Tạo file cấu hình Nginx
RUN echo 'server { \
    listen 80; \
    server_name _; \
    root /var/www/public; \
    index index.php index.html; \
    \
    client_max_body_size 100M; \
    \
    location / { \
        try_files $uri $uri/ /index.php?$query_string; \
    } \
    \
    location ~ \.php$ { \
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_index index.php; \
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
        include fastcgi_params; \
        fastcgi_read_timeout 300; \
        fastcgi_buffers 16 16k; \
        fastcgi_buffer_size 32k; \
    } \
    \
    location ~ /\.ht { \
        deny all; \
    } \
}' > /etc/nginx/sites-available/default

# Tạo file cấu hình Supervisor để quản lý nginx, php-fpm và queue-worker
RUN echo '[supervisord] \n\
nodaemon=true \n\
\n\
[program:php-fpm] \n\
command=/usr/local/sbin/php-fpm -F \n\
autostart=true \n\
autorestart=true \n\
\n\
[program:nginx] \n\
command=/usr/sbin/nginx -g "daemon off;" \n\
autostart=true \n\
autorestart=true \n\
\n\
[program:laravel-worker] \n\
process_name=%(program_name)s_%(process_num)02d \n\
command=php /var/www/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 \n\
autostart=true \n\
autorestart=true \n\
stopasgroup=true \n\
killasgroup=true \n\
user=root \n\
numprocs=2' > /etc/supervisor/conf.d/supervisord.conf

# Tạo script khởi động
RUN echo '#!/bin/bash \n\
set -e \n\
\n\
# Generate APP_KEY nếu chưa có \n\
if [ -z "$APP_KEY" ]; then \n\
    php artisan key:generate --force \n\
fi \n\
\n\
# Clear cache cũ trước \n\
php artisan config:clear \n\
php artisan cache:clear \n\
php artisan route:clear \n\
php artisan view:clear \n\
\n\
# Cache lại config và routes cho production \n\
php artisan config:cache \n\
php artisan route:cache \n\
php artisan view:cache \n\
\n\
# Chạy migrations \n\
php artisan migrate --force \n\
\n\
# Khởi động supervisor (quản lý cả nginx và php-fpm) \n\
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf' > /var/www/start.sh \
    && chmod +x /var/www/start.sh

# Expose port 80
EXPOSE 80

# Khởi động với script
CMD ["/var/www/start.sh"]