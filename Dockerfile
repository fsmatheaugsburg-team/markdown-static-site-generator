FROM ubuntu:latest

RUN  apt-get update \
  && DEBIAN_FRONTEND=noninteractive apt-get install -y \
    apache2 \
    ca-certificates \
    php7.2 \
    php7.2-bcmath \
    php7.2-mbstring \
    git \
    unzip

COPY start-script.sh /root/

RUN chmod +x /root/start-script.sh

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php'); if (hash_file('sha384', 'composer-setup.php') === '48e3236262b34d30969dca3c37281b3b4bbe3221bda826ac6a9a62d6444cdb0dcd0615698a5cbe587c3f0fe57a54d8f5') { include 'composer-setup.php'; } else { echo 'Installer corrupt'; }; unlink('composer-setup.php');";

EXPOSE 80

# uncomment for subdirectory testing
# RUN mkdir /var/www/html/subdir/
# COPY . /var/www/html/subdir

COPY . /var/www/html/


RUN chown -R www-data:www-data /var/www/html/

CMD /root/start-script.sh
