# Dockerfile para App Lista de Compras
FROM php:8.2-apache

# Informações do mantenedor
LABEL maintainer="App Lista de Compras"
LABEL description="Aplicação de lista de compras com PHP 8 e SQLite"

# Instalar extensões necessárias do PHP
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    sqlite3 \
    && docker-php-ext-install pdo pdo_sqlite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Habilitar mod_rewrite do Apache
RUN a2enmod rewrite

# Configurar ServerName para suprimir warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Configurar DocumentRoot do Apache
ENV APACHE_DOCUMENT_ROOT=/var/www/html

# Atualizar configuração do Apache
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Configurar AllowOverride para .htaccess funcionar
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Copiar arquivos da aplicação
COPY . /var/www/html/

# Criar diretório do banco de dados e dar permissões
RUN mkdir -p /var/www/html/database && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    chmod -R 777 /var/www/html/database

# Expor porta 80
EXPOSE 80

# Comando padrão
CMD ["apache2-foreground"]
