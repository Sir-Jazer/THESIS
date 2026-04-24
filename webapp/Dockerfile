# Stage 1: Build assets
FROM node:20-alpine as nodebuild
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY public/ public/
COPY resources/ resources/
COPY vite.config.js tailwind.config.js postcss.config.js ./
ENV APP_URL=https://e-permit-system.onrender.com
RUN npm run build

# Stage 2: PHP + Apache
FROM php:8.2-apache

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev libonig-dev libxml2-dev libzip-dev libgd-dev zip unzip git curl \
    && docker-php-ext-install pdo pdo_mysql mbstring zip bcmath gd

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set Apache to serve from public/
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

# Copy app files
COPY . .

# Copy built assets from node stage
COPY --from=nodebuild /app/public/build public/build

# Copy public (except build) for static assets
COPY public/ public/

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache

# Copy entrypoint script
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 10000

CMD ["/entrypoint.sh"]
