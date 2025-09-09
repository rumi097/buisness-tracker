# Use an official PHP image with an Apache web server
FROM php:8.2-apache

# Install the mysqli extension needed to connect to your MySQL database
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Copy all your backend files into the container's web root directory
COPY . /var/www/html/

# Allow outside traffic to access the server on port 80
EXPOSE 80