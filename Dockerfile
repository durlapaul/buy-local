FROM php:8.5-fpm

WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    zip unzip git curl libpq-dev libpng-dev \
    && docker-php-ext-install pdo_mysql pcntl bcmath \
    && rm -rf /var/lib/apt/lists/*

RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Don't copy files here - they'll come from volume
# DON'T run composer install here - it conflicts with volume mount

# Set permissions for when app starts
RUN mkdir -p /var/www/storage /var/www/bootstrap/cache && \
    chmod -R 777 /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]