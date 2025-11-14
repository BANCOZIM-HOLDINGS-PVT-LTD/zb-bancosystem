# Multi-stage build for production optimization
FROM node:18-alpine AS frontend-builder

# Set working directory
WORKDIR /app

# Copy package files
COPY package*.json ./
COPY vite.config.ts ./
COPY tsconfig.json ./

# Install dependencies
RUN npm ci --only=production

# Copy source files
COPY resources/ ./resources/
COPY public/ ./public/

# Build frontend assets
RUN npm run build

# PHP Production Image
FROM php:8.2-fpm-alpine AS production

# Set environment variables
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_NO_INTERACTION=1
ENV COMPOSER_MEMORY_LIMIT=-1

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    postgresql-dev \
    mysql-client \
    redis \
    git \
    unzip \
    $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        zip \
        intl \
        mbstring \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        opcache \
        pcntl \
        bcmath \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Create application user
RUN addgroup -g 1000 -S www && \
    adduser -u 1000 -S www -G www

# Set working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy application code
COPY . .

# Copy built frontend assets from frontend-builder stage
COPY --from=frontend-builder /app/public/build ./public/build

# Create directories and set permissions
RUN mkdir -p storage/framework/{cache,sessions,views} \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache \
    && chown -R www:www /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Copy configuration files
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php-fpm-main.conf /usr/local/etc/php-fpm.conf
COPY docker/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Create necessary directories
RUN mkdir -p /var/log/nginx \
    && mkdir -p /var/log/supervisor \
    && mkdir -p /run/nginx \
    && mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/storage/framework/cache \
    && mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/views

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

# Copy entrypoint script
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Expose port
EXPOSE 80

# Start via entrypoint (handles caches then supervisord)
CMD ["/entrypoint.sh"]
