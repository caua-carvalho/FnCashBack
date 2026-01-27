FROM php:8.3-apache

# Ativa rewrite
RUN a2enmod rewrite

# Instala dependências do PostgreSQL + ffmpeg
RUN apt-get update \
    && apt-get install -y \
        libpq-dev \
        ffmpeg \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Define document root
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

# Ajusta configs do Apache
RUN sed -ri \
  -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
  /etc/apache2/sites-available/*.conf \
  /etc/apache2/apache2.conf

# Copia código
COPY src/ /var/www/html/

# Permissões (necessário para uploads + ffmpeg)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80
