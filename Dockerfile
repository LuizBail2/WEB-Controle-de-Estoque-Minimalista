FROM php:8.3-apache

# Dependências do sistema + extensões PHP comuns no Laravel
RUN apt-get update && apt-get install -y \
    git unzip zip libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql zip gd opcache \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# OPcache — acelera muito o PHP (guarda o bytecode compilado).
# Config pensada para DESENVOLVIMENTO: ainda detecta mudanças no código
# na hora (validate_timestamps=1 + revalidate_freq=0), mas sem recompilar
# tudo a cada request.
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.enable_cli=0'; \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.max_accelerated_files=20000'; \
    echo 'opcache.validate_timestamps=1'; \
    echo 'opcache.revalidate_freq=0'; \
    } > /usr/local/etc/php/conf.d/zz-opcache.ini

# Apache aponta para /public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/000-default.conf /etc/apache2/apache2.conf

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Permissões básicas
RUN chown -R www-data:www-data /var/www

EXPOSE 80
