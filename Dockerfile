FROM php:8.3-apache

# Ativa mod_rewrite (comum para APIs)
RUN a2enmod rewrite

# Define diretório raiz da aplicação
WORKDIR /var/www/html

# Copia os arquivos do projeto
COPY src/ /var/www/html/

# Permissões corretas
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80
