FROM ubuntu:latest

ARG DEBIAN_FRONTEND=noninteractive

# Environment variables
ENV APP_ENV='prod'
ENV PUID='1000'
ENV PGID='1000'
ENV USER='koillection'

COPY ./ /var/www/koillection

# Add User and Group
RUN addgroup --gid "$PGID" "$USER" && \
    adduser --gecos '' --no-create-home --disabled-password --uid "$PUID" --gid "$PGID" "$USER" && \
# Install some basics dependencies
    apt-get update && \
    apt-get install -y curl lsb-release software-properties-common gnupg2 vim && \
# PHP
    add-apt-repository ppa:ondrej/php && \
# Nodejs
    curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg && \
    NODE_MAJOR=21 && \
    echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_$NODE_MAJOR.x nodistro main" | tee /etc/apt/sources.list.d/nodesource.list && \
# Install packages
    apt-get update && \
    apt-get install -y \
    ca-certificates \
    apt-transport-https \
    git \
    unzip \
    nginx-light \
    openssl \
    php8.2 \
    php8.2-apcu \
    php8.2-pgsql \
    php8.2-mysql \
    php8.2-mbstring \
    php8.2-gd \
    php8.2-xml \
    php8.2-zip \
    php8.2-fpm \
    php8.2-intl \
    nodejs && \
#Install composer dependencies
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    cd /var/www/koillection && \
    composer install --classmap-authoritative && \
    composer clearcache && \
# Dump translation files for javascript
    cd /var/www/koillection/ && \
    php bin/console app:translations:dump && \
# Install javascript dependencies and build assets \
    corepack enable && \
    cd /var/www/koillection/assets && \
    yarn --version && \
    yarn install && \
    yarn build && \
# Clean up
    yarn cache clean --all && \
    rm -rf /var/www/koillection/assets/.yarn/cache && \
    rm -rf /var/www/koillection/assets/.yarn/install-state.gz && \
    rm -rf /var/www/koillection/assets/node_modules && \
    apt-get purge -y lsb-release software-properties-common git nodejs apt-transport-https ca-certificates gnupg2 unzip && \
    apt-get autoremove -y && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* && \
# Set permissions \
    sed -i "s/user = www-data/user = $USER/g" /etc/php/8.2/fpm/pool.d/www.conf && \
    sed -i "s/group = www-data/group = $USER/g" /etc/php/8.2/fpm/pool.d/www.conf && \
    chown -R "$USER":"$USER" /var/www/koillection && \
    chmod +x /var/www/koillection/docker/entrypoint.sh && \
    mkdir /run/php && \
# Add nginx and PHP config files
    cp /var/www/koillection/docker/default.conf /etc/nginx/nginx.conf && \
    cp /var/www/koillection/docker/php.ini /etc/php/8.2/fpm/conf.d/php.ini

EXPOSE 443

VOLUME /uploads

WORKDIR /var/www/koillection

HEALTHCHECK CMD curl --fail http://localhost:443/ || exit 1

ENTRYPOINT ["sh", "/var/www/koillection/docker/entrypoint.sh" ]

CMD [ "nginx" ]
