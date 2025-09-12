# Use an official PHP image with Apache web server
FROM php:8.2-apache

# Install extensions for PostgreSQL
RUN docker-php-ext-install pgsql pdo_pgsql && docker-php-ext-enable pgsql pdo_pgsql

# Copy all your backend files into the container's web root directory
COPY . /var/www/html/

# Expose port 80 to the world
EXPOSE 80