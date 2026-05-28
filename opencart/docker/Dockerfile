# Dowaba × OpenCart 4.x lokal test ortamı için PHP 8.2 + Apache base image.
#
# OpenCart 4.0.2.3 source dosyaları compose'da volume mount edilir
# (/tmp/dwb-oc4-src/opencart-4.0.2.3/upload → /var/www/html).
#
# OC4 minimum gereksinimler: PHP 8.0+, MySQL 5.7+, GD, mbstring, curl, zip, openssl.

# 2026-05-28: PHP version'ı build-arg ile parametrize. Plugin compat test için
# 7.0, 7.4, 8.0, 8.1, 8.2 hepsi build edilebilir → image-tag farklı, aynı OC3 source
# mount edilir, sadece runtime PHP değişir.
#   docker build --build-arg PHP_VERSION=7.0 -t dwb-oc-php70 .
ARG PHP_VERSION=8.2
FROM php:${PHP_VERSION}-apache

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
 && PHP_MAJOR=$(php -r 'echo PHP_MAJOR_VERSION;') \
 && PHP_MINOR=$(php -r 'echo PHP_MINOR_VERSION;') \
 && if [ "$PHP_MAJOR" -ge 8 ] || ([ "$PHP_MAJOR" -eq 7 ] && [ "$PHP_MINOR" -ge 4 ]); then \
        docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp; \
    else \
        docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ --with-webp-dir=/usr/include/; \
    fi \
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
