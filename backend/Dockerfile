#Let's try 7.0 later
FROM php:5.6-fpm-alpine
ENV WORKDIR=/app/backend
RUN mkdir -p /app/backend /scripts
WORKDIR /app/backend
RUN apk add --update --no-cache \
		libmcrypt-dev \
		libjpeg-turbo-dev \
		pcre-dev \
		libpng-dev \
		freetype-dev \
		libxml2-dev \
		icu-dev \
		mysql-client \
		wget \
		unzip > /dev/null \
    && rm -rf /var/lib/apt/lists/* > /dev/null \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ > /dev/null \
    && docker-php-ext-install iconv intl pdo_mysql mbstring soap gd zip > /dev/null \
 	&& docker-php-source extract \
    && if [ -d "/usr/src/php/ext/mysql" ]; then docker-php-ext-install mysql > /dev/null; fi \
    && if [ -d "/usr/src/php/ext/mcrypt" ]; then docker-php-ext-install mcrypt > /dev/null; fi \
	&& if [ -d "/usr/src/php/ext/opcache" ]; then docker-php-ext-install opcache > /dev/null; fi \
    && docker-php-source delete > /dev/null
COPY scripts/ /scripts
COPY php.ini /usr/local/etc/php/
COPY --chown=www-data:www-data presta .
VOLUME /app/backend
CMD [ "sh", "-c", "/scripts/entrypoint.sh" ]
