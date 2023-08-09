FROM ubuntu:18.04


ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -my \
  curl \
  wget \
  php-curl \
  php-fpm \
  php-gd \
  php-xsl \
  php-mysqlnd \
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

# Alte Version von Composer beziehen, da die neuere Fassung mit mit Symfony 3.4 nicht kompatibel ist
RUN composer self-update 2.2.9

# Git clone
RUN git clone https://github.com/DigiTrace/AM-System
ADD Dockerstuff/parameters.yml AM-System/app/config/parameters.yml
ADD Dockerstuff/Database.sql AM-System/Database.sql

RUN cd AM-System && composer update

#EXPOSE 8080
#CMD ["/bin/bash","-c", " tail -f /dev/null "]
#CMD  ["tail -f /dev/null"]

RUN mkdir -p /var/run/mysqld
RUN chown -R mysql:mysql /var/lib/mysql /var/run/mysqld &&  service mysql start  && mysql -uroot -pverysecure < AM-System/Database.sql && cd AM-System && vendor/bin/simple-phpunit

#Final
EXPOSE 8080

ENTRYPOINT ["/bin/bash","-c", " chown -R mysql:mysql /var/lib/mysql /var/run/mysqld && service mysql start && cd AM-System && php bin/console server:run 0.0.0.0:8080 "]
CMD ["/bin/bash","-c", " chown -R mysql:mysql /var/lib/mysql /var/run/mysqld && service mysql start && cd AM-System && php bin/console server:run 0.0.0.0:8080 "]
