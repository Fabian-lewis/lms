# Use official PHP image with Apache
FROM php:8.1-apache

# Install necessary PHP extensions
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Enable Apache rewrite module
RUN a2enmod rewrite

# Set working directory inside the container
WORKDIR /var/www/html

# Copy LMS folder contents into the web root
COPY . /var/www/html/

# Ensure correct file permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Set environment variable for Render port (default to 80 for local dev)
ENV PORT=80

# Expose the port (informational)
EXPOSE ${PORT}

# Dynamically update Apache config to use the Render-assigned port
CMD ["/bin/bash", "-c", "\
  sed -i \"s/Listen 80/Listen ${PORT}/\" /etc/apache2/ports.conf && \
  sed -i \"s/<VirtualHost \\*:80>/<VirtualHost *:${PORT}>/\" /etc/apache2/sites-available/000-default.conf && \
  apache2-foreground"]
