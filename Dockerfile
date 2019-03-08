FROM ubuntu:17.10

RUN apt-get update && apt-get install -my \
  curl \
  wget \
  php-curl \
  php-fpm \
  php-gd \
  php-xsl \
  php-mysqlnd \
  php-mcrypt \
  php-cli \
  php-intl \
  php-bz2 \
  php-zip \
  php-mbstring \
  git \
  zip \
  debconf-utils

RUN ["/bin/bash", "-c", "debconf-set-selections <<< 'mysql-server mysql-server/root_password password verysecure'"]
RUN ["/bin/bash","-c","debconf-set-selections <<< 'mysql-server mysql-server/root_password_again password verysecure'"]

RUN apt-get install -y mysql-server


# Composer install
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php
RUN php -r "unlink('composer-setup.php');"
RUN mv composer.phar /usr/local/bin/composer
RUN chmod +x /usr/local/bin/composer
RUN ln -snf /usr/share/zoneinfo/Europe/Berlin /etc/localtime

# Git clone
RUN git clone https://github.com/DigiTrace/AM-System
ADD Dockerstuff/parameters.yml AM-System/app/config/parameters.yml
ADD Dockerstuff/Database.sql AM-System/Database.sql

RUN cd AM-System && composer update
RUN service mysql start && mysql -uroot -pverysecure < AM-System/Database.sql && cd AM-System && vendor/bin/simple-phpunit

#Final
EXPOSE 8080

ENTRYPOINT ["/bin/bash","-c", "service mysql start && cd AM-System && php bin/console server:run 0.0.0.0:8080 "]
CMD ["/bin/bash","-c", "service mysql start && cd AM-System && php bin/console server:run 0.0.0.0:8080 "]


