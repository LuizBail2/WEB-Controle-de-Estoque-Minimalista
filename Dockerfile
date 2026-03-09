FROM php:8.3-apache

# Dependências do sistema + extensões PHP comuns no Laravel
RUN apt-get update && apt-get install -y \
    git unzip zip libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql zip gd \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Apache aponta para /public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/000-default.conf /etc/apache2/apache2.conf

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# (Opcional) Copiar o projeto no build (em dev vamos usar volume no compose)
# COPY . .

# Permissões básicas (em dev, volume pode sobrescrever; mas ajuda)
RUN chown -R www-data:www-data /var/www

EXPOSE 80