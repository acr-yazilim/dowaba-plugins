# Dowaba × OpenCart 4.x lokal test ortamı için PHP 8.2 + Apache base image.
#
# OpenCart 4.0.2.3 source dosyaları compose'da volume mount edilir
# (/tmp/dwb-oc4-src/opencart-4.0.2.3/upload → /var/www/html).
#
# OC4 minimum gereksinimler: PHP 8.0+, MySQL 5.7+, GD, mbstring, curl, zip, openssl.

FROM php:8.2-apache

# ---- System packages + PHP extensions ----
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
        libcurl4-openssl-dev \
        libzip-dev \
        libicu-dev \
        libwebp-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        unzip \
        default-mysql-client \
 && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install -j"$(nproc)" \
        mysqli \
        pdo_mysql \
        gd \
        zip \
        intl \
        mbstring \
        opcache \
 && a2enmod rewrite headers \
 && rm -rf /var/lib/apt/lists/*

# ---- PHP runtime config ----
RUN { \
    echo 'upload_max_filesize=64M'; \
    echo 'post_max_size=64M'; \
    echo 'memory_limit=256M'; \
    echo 'max_execution_time=180'; \
    echo 'date.timezone=Europe/Istanbul'; \
} > /usr/local/etc/php/conf.d/dowaba.ini

# ---- Apache config ----
# OC4 .htaccess kullanır — AllowOverride All gerekli
RUN { \
    echo '<Directory /var/www/html>'; \
    echo '  Options Indexes FollowSymLinks'; \
    echo '  AllowOverride All'; \
    echo '  Require all granted'; \
    echo '</Directory>'; \
} > /etc/apache2/conf-available/dowaba-overrides.conf \
 && a2enconf dowaba-overrides

# OpenCart source volume mount'tan gelecek
WORKDIR /var/www/html

EXPOSE 80
