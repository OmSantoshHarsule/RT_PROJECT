# Base PHP 8.2 with Apache
FROM php:8.2-apache

# Install PHP extensions and system dependencies
RUN apt-get update && apt-get install -y \
        unzip \
        curl \
        git \
    && docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer -o composer-setup.php \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && rm composer-setup.php

# Set working directory
WORKDIR /var/www/html

# Copy composer.json first for dependency installation (Docker layer caching)
COPY composer.json composer.json

# Install PHP dependencies (PHPMailer + Guzzle)
RUN composer install --no-interaction --optimize-autoloader

# Copy the rest of the app
COPY . .

# Expose Apache port
EXPOSE 80

