FROM php:7.4-fpm

# 시스템 패키지 설치
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev \
    libzip-dev \
    libicu-dev \
    libxml2-dev \
    libmcrypt-dev \
    libmemcached-dev \
    zlib1g-dev \
    libonig-dev \
    unzip \
    zip \
    && rm -rf /var/lib/apt/lists/*

# PHP 확장 설치
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mysqli \
        gd \
        zip \
        mbstring \
        intl \
        xml \
        bcmath \
        opcache \
        pcntl \
        sockets \
        calendar \
        exif \
        fileinfo

# Redis 확장
RUN pecl install redis-5.3.7 \
    && docker-php-ext-enable redis

# Composer 설치
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

RUN usermod -u 1000 www-data

CMD ["php-fpm"]
