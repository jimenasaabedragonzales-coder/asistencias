FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite

# Como NO tienes carpeta public, la raiz es /var/www/html
ENV APACHE_DOCUMENT_ROOT=/var/www/html

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Permitir .htaccess
RUN echo '<Directory /var/www/html>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/override.conf \
    && a2enconf override

WORKDIR /var/www/html
COPY . /var/www/html/

# Permisos y evitar error si no tienes storage
RUN chown -R www-data:www-data /var/www/html \
    && mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache || true

EXPOSE 80
CMD ["apache2-foreground"]