FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    postgresql-dev \
    icu-dev \
    oniguruma-dev \
    libzip-dev \
    supervisor

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    intl \
    mbstring \
    zip \
    bcmath \
    opcache

# Configure PHP-FPM
RUN echo "pm.max_children = 50" >> /usr/local/etc/php-fpm.d/www.conf && \
    echo "pm.start_servers = 5" >> /usr/local/etc/php-fpm.d/www.conf && \
    echo "pm.min_spare_servers = 5" >> /usr/local/etc/php-fpm.d/www.conf && \
    echo "pm.max_spare_servers = 10" >> /usr/local/etc/php-fpm.d/www.conf && \
    echo "pm.max_requests = 1000" >> /usr/local/etc/php-fpm.d/www.conf

# Configure PHP for production
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=256'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.max_accelerated_files=20000'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.save_comments=1'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'realpath_cache_size=4096K'; \
    echo 'realpath_cache_ttl=600'; \
    echo 'memory_limit=512M'; \
    echo 'max_execution_time=300'; \
    echo 'max_input_time=300'; \
    echo 'post_max_size=50M'; \
    echo 'upload_max_filesize=50M'; \
} > /usr/local/etc/php/conf.d/php-production.ini

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-progress

# Copy application code
COPY . .

# Set permissions
RUN chown -R www-data:www-data /app/var && \
    chmod -R 755 /app/var && \
    chown -R www-data:www-data /app/public && \
    chmod -R 755 /app/public

# Create supervisor configuration
RUN echo '[supervisord]' > /etc/supervisord.conf && \
    echo 'nodaemon=true' >> /etc/supervisord.conf && \
    echo 'user=root' >> /etc/supervisord.conf && \
    echo 'logfile=/var/log/supervisord.log' >> /etc/supervisord.conf && \
    echo 'pidfile=/var/run/supervisord.pid' >> /etc/supervisord.conf && \
    echo '' >> /etc/supervisord.conf && \
    echo '[program:php-fpm]' >> /etc/supervisord.conf && \
    echo 'command=php-fpm' >> /etc/supervisord.conf && \
    echo 'stdout_logfile=/var/log/php-fpm.log' >> /etc/supervisord.conf && \
    echo 'stderr_logfile=/var/log/php-fpm.log' >> /etc/supervisord.conf && \
    echo 'autorestart=true' >> /etc/supervisord.conf && \
    echo 'startretries=3' >> /etc/supervisord.conf

# Expose port
EXPOSE 9000

# Default command
CMD ["supervisord", "-c", "/etc/supervisord.conf"]