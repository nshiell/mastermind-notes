FROM ubuntu:bionic
MAINTAINER Nicholas Shiell <nicholas@nshiell.com>

RUN \
    apt-get update && \
    apt-get install -y software-properties-common python-software-properties && \
    DEBIAN_FRONTEND=noninteractive apt-get install -y tzdata && \
    LC_ALL=C.UTF-8 add-apt-repository ppa:ondrej/php && \
    apt-get update && \
    apt-get install -y --no-install-recommends apache2 \
    php7.2 php7.2-common php7.2-cli php7.2-fpm \
    php-pear \
    locales \
    php7.2-json php7.2-opcache php7.2-readline \
    php7.2-cli \
    php7.2-curl \
    php7.2-dev \
    php7.2-fpm \
    php7.2-gd \
    php7.2-gmp \
    php7.2-intl \
    php7.2-json \
    php7.2-mbstring \
    php7.2-oauth \
    php7.2-opcache \
    php7.2-soap \
    php7.2-xml \
    php7.2-zip \
    php7.2-yaml

RUN \
    apt-get install -y autoconf g++ make openssl libssl-dev libcurl4-openssl-dev && \
    apt-get install -y libcurl4-openssl-dev pkg-config && \
    apt-get install -y libsasl2-dev make

RUN \
    locale-gen en_US && \
    pecl channel-update pecl.php.net && pecl install \
    channel://pecl.php.net/geospatial-0.2.0 && pecl install mongodb-1.3.4 && \
    echo "extension=mongodb.so" > /etc/php/7.2/mods-available/mongodb.ini && \
    pecl install xdebug-2.6.0 && \
    phpenmod -v 7.2 mongodb zip memcache xdebug && \
    apt-key adv --keyserver hkp://keyserver.ubuntu.com:80 --recv \
    2930ADAE8CAF5059EE73BB4B58712A2291FA4AD5 && \
    echo "deb [ arch=amd64,arm64 ] https://repo.mongodb.org/apt/ubuntu xenial/mongodb-org/3.6 multiverse" | \
    tee /etc/apt/sources.list.d/mongodb-org-3.6.list && \
    apt-get update && \
    apt-get install -y mongodb-org pkg-config libapache2-mod-php7.2 && \
    a2enmod php7.2 && \
    a2enmod rewrite && \
    apt-get install php7.2-mongodb

RUN mkdir /app && \
    rm -R /var/lib/mongodb && ln -s /app/docker/mongodb /var/lib/mongodb && \
    rm -R /var/log         && ln -s /app/docker/log     /var/log

ENV APACHE_RUN_USER www-data
ENV APACHE_RUN_GROUP www-data

CMD /app/docker/go.sh