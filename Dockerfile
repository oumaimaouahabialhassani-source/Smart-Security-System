# syntax=docker/dockerfile:1

FROM php:8.3-cli-bookworm

# System dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
        git curl unzip ca-certificates \
        libzip-dev libonig-dev \
        default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions required by Laravel + MySQL
RUN docker-php-ext-install pdo_mysql mbstring zip bcmath pcntl

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Node.js 20 (to build Vite/Tailwind assets)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Install PHP dependencies first (better layer caching)
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --prefer-dist --no-interaction

# Install Node dependencies
COPY package.json package-lock.json* ./
RUN npm install

# Copy application source
COPY . .

# Finalize autoloader and build front-end assets
RUN composer dump-autoload --optimize \
    && npm run build

# Permissions for Laravel writable directories
RUN chmod -R 775 storage bootstrap/cache

# Entrypoint handles DB wait, migrations, and serving
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8000
ENTRYPOINT ["entrypoint.sh"]
