# Dockerfile
FROM php:latest

# Install GD extension and dependencies
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev
RUN docker-php-ext-configure gd --with-jpeg --with-freetype
RUN docker-php-ext-install gd

# Install zip/unzip extensions and dependencies
RUN apt-get install -y \
        libzip-dev \
        zip \
  && docker-php-ext-install zip

# Install mysqli extension
RUN docker-php-ext-install mysqli

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set the working directory
WORKDIR /var/www/html

# Start PHP server
CMD ["php", "-S", "0.0.0.0:80", "-t", "/var/www/html"]
