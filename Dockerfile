# Gunakan image resmi PHP dengan Apache
FROM php:8.1-apache

# Install PHP extensions yang diperlukan oleh Laravel
RUN docker-php-ext-install pdo pdo_mysql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory ke folder project
WORKDIR /var/www/html

# Copy seluruh source code Laravel ke dalam container
COPY . .

# Install dependencies dengan Composer
RUN composer install

# Set permission folder storage dan cache agar bisa ditulis
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port 80 untuk akses ke aplikasi Laravel
EXPOSE 80

# Jalankan Apache server
CMD ["apache2-foreground"]
