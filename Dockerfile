FROM php:8.2-apache

# Cài đặt extension PDO SQLite nếu code cần đến
RUN apt-get update && apt-get install -y libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite

# Sao chép toàn bộ code vào thư mục gốc của máy chủ
COPY . /var/www/html/

# Cấp quyền đọc/ghi tuyệt đối cho thư mục database và file sqlite
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html \
    && chmod -R 777 /var/www/html/database
