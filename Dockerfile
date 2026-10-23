FROM php:8.2-apache

# Bật extension mysqli + curl
RUN docker-php-ext-install mysqli && \
    apt-get update && apt-get install -y libcurl4-openssl-dev && \
    docker-php-ext-install curl

# Bật mod_rewrite cho .htaccess
RUN a2enmod rewrite headers

# Copy toàn bộ source vào web root
COPY . /var/www/html/

# Cho phép .htaccess hoạt động
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Quyền file
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

EXPOSE 80
