# Utiliser l'image PHP officielle avec Apache
FROM php:8.2-apache

# Définir la limite de mémoire allouée pour PHP à 512 Mo (ou plus si nécessaire)
RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/memory-limit.ini

# Installer les dépendances nécessaires
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libpq-dev \
    libonig-dev \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libxpm-dev \
    unzip \
    git \
    libgmp-dev \
    && docker-php-ext-install \
    intl \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    mbstring \
    zip \
    bcmath \
    gmp \
    exif \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp --with-xpm \
    && docker-php-ext-install -j$(nproc) gd

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copier les fichiers du projet
COPY . /var/www/html

# Définir le répertoire de travail
WORKDIR /var/www/html

# Installer les dépendances PHP
#mettre a jourd les dépendances
#RUN composer update

# Configurer Apache
RUN a2enmod rewrite
RUN a2enmod headers
RUN a2enmod ssl

COPY docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf

RUN echo "ServerName payool.net" >> /etc/apache2/apache2.conf

# Donner les permissions correctes
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Exposer le port 80
EXPOSE 80 443
