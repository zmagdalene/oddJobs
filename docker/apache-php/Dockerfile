# Use the official PHP image with Apache
FROM php:8.4-apache

# Enable the Apache rewrite module
RUN a2enmod rewrite

# Install and enable Xdebug
RUN pecl install xdebug
RUN docker-php-ext-enable xdebug

# Install the PDO MySQL extension
RUN docker-php-ext-install pdo_mysql && docker-php-ext-enable pdo_mysql

# Copy custom Apache configuration file
COPY ./config/httpd.conf /etc/apache2/sites-available/000-default.conf

# Copy the development php.ini configuration file
RUN mv $PHP_INI_DIR/php.ini-development $PHP_INI_DIR/php.ini

# Copy custom Xdebug and error reporting configuration files
COPY ./config/xdebug.ini          /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
COPY ./config/error_reporting.ini /usr/local/etc/php/conf.d/error_reporting.ini
