FROM php:8.3-apache

# ----------------------------
# Dependências do sistema
# ----------------------------
RUN apt-get update \
    && apt-get install -y \
        libpq-dev \
        ffmpeg \
        git \
        unzip \
        curl \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# ----------------------------
# Instala Composer (oficial)
# ----------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ----------------------------
# Ativa mod_rewrite
# ----------------------------
RUN a2enmod rewrite

# ----------------------------
# Define document root
# ----------------------------
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri \
  -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
  /etc/apache2/sites-available/*.conf \
  /etc/apache2/apache2.conf

# ----------------------------
# Workdir
# ----------------------------
WORKDIR /var/www/html

# ----------------------------
# Copia apenas composer primeiro (cache otimizado)
# ----------------------------
COPY src/composer.json ./

# Instala dependências
RUN composer install \
    --no-interaction \
    --no-dev \
    --optimize-autoloader

# ----------------------------
# Copia restante do código
# ----------------------------
COPY src/ .

# ----------------------------
# Permissões
# ----------------------------
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80
