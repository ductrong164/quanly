FROM php:8.2-apache

# Cài đặt extension kết nối MySQL cho PHP
RUN docker-php-ext-install pdo pdo_mysql

# Bật rewrite module cho Apache
RUN a2enmod headers rewrite

# Copy toàn bộ mã nguồn vào thư mục web server
COPY . /var/www/html/
