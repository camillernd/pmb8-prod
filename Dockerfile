# Image de base officielle PHP 8.1 avec Apache
FROM php:8.1-apache

# Mise à jour & installation des dépendances système nécessaires à PMB
RUN apt-get update && apt-get install -y \
    libbz2-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libxpm-dev \
    libgd-dev \
    libicu-dev \
    libxml2-dev \
    libxslt1-dev \
    libzip-dev \
    libonig-dev \
    libsqlite3-dev \
    zlib1g-dev \
    libcurl4-openssl-dev \
    poppler-utils \
    wkhtmltopdf \
    exiftool \
    zip \
    htop && \
    docker-php-ext-install \
    bz2 \
    curl \
    dom \
    exif \
    fileinfo \
    gd \
    iconv \
    intl \
    mbstring \
    mysqli \
    soap \
    sockets \
    xml \
    xsl \
    zip && \
    pecl install apcu && docker-php-ext-enable apcu

# Paramétrage PHP recommandé pour PMB 8
RUN echo "date.timezone = Europe/Paris" > /usr/local/etc/php/conf.d/99-pmb.ini \
    && echo "display_errors = Off" >> /usr/local/etc/php/conf.d/99-pmb.ini \
    && echo "expose_php = Off" >> /usr/local/etc/php/conf.d/99-pmb.ini \
    && echo "max_execution_time = 3600" >> /usr/local/etc/php/conf.d/99-pmb.ini \
    && echo "max_input_vars = 100000" >> /usr/local/etc/php/conf.d/99-pmb.ini \
    && echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/99-pmb.ini \
    && echo "post_max_size = 1G" >> /usr/local/etc/php/conf.d/99-pmb.ini \
    && echo "upload_max_filesize = 1G" >> /usr/local/etc/php/conf.d/99-pmb.ini

# Copie des fichiers PMB dans l'image
COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html