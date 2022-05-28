FROM php:7-apache

COPY config/apache.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

# For Jaeger
RUN docker-php-ext-install sockets

CMD ["apache2-foreground"]
