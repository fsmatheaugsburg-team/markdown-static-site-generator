FROM ubuntu:latest

RUN  apt-get update \
  && DEBIAN_FRONTEND=noninteractive apt-get install -y \
    apache2 \
    mysql-server \
    php7.2 \
    php7.2-bcmath

COPY start-script.sh /root/

RUN chmod +x /root/start-script.sh

EXPOSE 80

# uncomment for subdirectory testing
# RUN mkdir /var/www/html/subdir/
# COPY . /var/www/html/subdir

COPY . /var/www/html/


RUN chown -R www-data:www-data /var/www/html/

CMD /root/start-script.sh
