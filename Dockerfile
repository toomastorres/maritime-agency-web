# Imagen para correr el sitio + portal admin (PHP 8.2 + Apache).
FROM php:8.2-apache

# Extensiones PHP necesarias (MySQL via PDO) + utilidades
RUN docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite expires headers

# Composer (para instalar PHPMailer y demás dependencias)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . /var/www/html

# Dependencias PHP (PHPMailer). Si no hay composer.json no falla el build.
RUN if [ -f composer.json ]; then composer install --no-dev --no-interaction --prefer-dist || true; fi \
    && chown -R www-data:www-data /var/www/html

# El front controller (index.php) está en la raíz del proyecto.
ENV APACHE_DOCUMENT_ROOT=/var/www/html
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && printf '<Directory /var/www/html>\n  AllowOverride All\n  Require all granted\n</Directory>\n' \
       > /etc/apache2/conf-available/allow-override.conf \
    && a2enconf allow-override

EXPOSE 80
