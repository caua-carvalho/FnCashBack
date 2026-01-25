FROM php:8.3-apache

# Ativa mod_rewrite
RUN a2enmod rewrite

# Apache precisa escutar a porta do Render
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri \
  -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
  /etc/apache2/sites-available/*.conf \
  /etc/apache2/apache2.conf

# Copia aplicação
COPY src/ /var/www/html/

# Permissões
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Render injeta PORT automaticamente
EXPOSE 80
