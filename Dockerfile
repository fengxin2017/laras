FROM centos:8

ENV SWOOLE_VERSION 4.6.1

RUN rpm -Uvh https://dl.fedoraproject.org/pub/epel/epel-release-latest-8.noarch.rpm
RUN rpm -Uvh http://rpms.remirepo.net/enterprise/remi-release-8.rpm

#install libs
RUN yum install -y curl zip unzip  wget openssl-devel gcc-c++ make autoconf

#install php
RUN yum install -y php74-php-devel php74-php-openssl php74-php-mbstring php74-php-json php74-php-pdo_mysql php74-php-pear

RUN mv /usr/bin/php74 /usr/bin/php

# composer
RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/bin/composer

# use aliyun composer
RUN composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/

# swoole ext
RUN wget https://github.com/swoole/swoole-src/archive/v${SWOOLE_VERSION}.tar.gz -O swoole.tar.gz \
    && mkdir -p swoole \
    && tar -xf swoole.tar.gz -C swoole --strip-components=1 \
    && rm swoole.tar.gz \
    && ( \
    cd swoole \
    && /opt/remi/php74/root/usr/bin/phpize \
    && ./configure --enable-openssl --enable-http2 --with-php-config=/opt/remi/php74/root/usr/bin/php-config \
    && make \
    && make install \
    ) \
    && sed -i "2i extension=swoole.so" /etc/opt/remi/php74/php.ini \
    && rm -r swoole

RUN /opt/remi/php74/root/usr/bin/pecl install xlswriter \
    && sed -i "3i extension=xlswriter.so" /etc/opt/remi/php74/php.ini

# php-redis
RUN wget https://pecl.php.net/get/redis-4.0.1.tgz \
    && tar -zxvf redis-4.0.1.tgz \
    && rm redis-4.0.1.tgz \
    && ( \
    && cd redis-4.0.1 \
    && /opt/remi/php74/root/usr/bin/phpize \
    && ./configure --with-php-config=/opt/remi/php74/root/usr/bin/php-config \
    && make \
    && make install \
    ) \
    && sed -i "4i extension=redis.so" /etc/opt/remi/php74/php.ini \
    && rm -r redis-4.0.1

# Dir
WORKDIR /laras

EXPOSE 9501
EXPOSE 9502
EXPOSE 9503

# sudo docker run -it -p 9501:9501 -p 9502:9502 -p 9503:9503 --name laras --privileged -v ~/code/laras/:/laras xxx/xxx:latest