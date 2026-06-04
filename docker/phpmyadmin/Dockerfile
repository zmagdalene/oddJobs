# Use the official phpMyAdmin image as the base image
FROM phpmyadmin/phpmyadmin

# Install the PDO MySQL extension
RUN docker-php-ext-install pdo_mysql && docker-php-ext-enable pdo_mysql

# Expose port 80 to allow access to phpMyAdmin
EXPOSE 80