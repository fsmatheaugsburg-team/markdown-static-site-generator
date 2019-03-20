#!/bin/bash
# starting script for docker container

service mysql start
a2enmod rewrite
service apache2 start

echo "ran" >> /startup.log

bash
