FROM php:8.4-fpm-alpine

# Arguments defined in docker-compose.yml
ARG user
ARG uid

# Install system dependencies
RUN apk update && apk add --no-cache \
    git \
    curl \
    libzip-dev \
    libpq-dev \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    bash

# Clear cache
RUN rm -rf /var/lib/apk/lists/*

# Install PHP extensions
RUN docker-php-ext-install pgsql pdo_pgsql mbstring exif pcntl bcmath gd zip

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory (this is where your Laravel app will be located)
WORKDIR /var/www

# Copy the application source code into the container
COPY . .

# Install Composer dependencies
RUN composer install --no-dev --prefer-dist --optimize-autoloader

# # Install npm dependencies
# # Build frontend
# RUN npm install && npm run build

# Disable access logs for php-fpm
RUN echo "access.log = /dev/null" >> /usr/local/etc/php-fpm.d/www.conf

# Create system user to run Composer and Artisan Commands
# Set permission for new user to required directories
RUN addgroup -S $user && adduser -S $user -u $uid -G $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user && \
    chown -R $user:$user /var/www/storage && \
    chown -R $user:$user /var/www/public

# Set working directory (again, just to ensure)
WORKDIR /var/www

# Set the user to the one defined in the ARGs
USER $user

# Expose port (optional, for HTTP server like Nginx or Apache)
EXPOSE 9000

# Start PHP-FPM (optional, depending on your configuration)
CMD ["php-fpm"]
