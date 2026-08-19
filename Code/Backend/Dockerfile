FROM php:8.5-apache

# Install system dependencies required by PHP extensions and Composer
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libzip-dev \
    libssl-dev \
    pkg-config \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    zip

# Install Redis extension
RUN pecl install redis \
    && docker-php-ext-enable redis

# Install MongoDB extension
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# Enable Apache rewrite module
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy Composer files first for better Docker layer caching
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction \
    --no-progress

# Copy application source code
COPY . .

# Set ownership
RUN chown -R www-data:www-data /var/www/html

# Verify required extensions and Composer autoload
RUN php -m | grep -q pdo_mysql \
    && php -m | grep -q redis \
    && php -m | grep -q mongodb \
    && php -m | grep -q zip \
    && test -f vendor/autoload.php

# Apache listens on port 80 inside the container
EXPOSE 80

CMD ["apache2-foreground"]