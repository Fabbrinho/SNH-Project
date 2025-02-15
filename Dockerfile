FROM php:8.1-apache

# Install required dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /var/www/html

# Install PHPMailer
RUN composer require phpmailer/phpmailer

# Enable Apache modules
RUN a2enmod rewrite ssl headers

# Copy Apache configuration files
COPY apache-config/ssl.conf /etc/apache2/sites-available/ssl.conf
COPY apache-config/sec.conf /etc/apache2/conf-available/security.conf

# Enable custom configurations
RUN a2ensite ssl && a2enconf security
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Copy application files
COPY ./app /var/www/html/

# Creazione della cartella logs e impostazione dei permessi
RUN mkdir -p /var/www/html/logs && \
    chown -R www-data:www-data /var/www/html/logs && \
    chmod -R 755 /var/www/html/logs

# Expose ports (80 for HTTP, 443 for HTTPS)
EXPOSE 80 443

# Start Apache server
CMD rm -f /var/www/html/logs/*.log && apache2-foreground
