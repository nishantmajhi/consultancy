FROM php:8.3-apache

# Set timezone to Kathmandu
ENV TZ=Asia/Kathmandu
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Set the working directory
WORKDIR /var/www/html

# Install the necessary libraries
RUN apt-get update && apt-get install -y \
    libonig-dev \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    mbstring \
    zip \
    pdo \
    pdo_mysql

# Copy over the PHP project
COPY . .

# Change ownership of our applications
RUN chown -R www-data:www-data /var/www/html

# Set proper permissions
RUN chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80

# Enable Apache mod_rewrite
RUN a2enmod rewrite
