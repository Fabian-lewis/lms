# Use official PHP image with Apache
FROM php:8.1-apache

# Install necessary PHP extensions
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Set working directory inside the container
WORKDIR /var/www/html

# Copy LMS folder contents into the web root
COPY . /var/www/html/

# Ensure correct file permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
