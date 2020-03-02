FROM appsvc/php:7.3-apache_20200101.1

# Installing php composer
RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer && \
        chmod +x /usr/local/bin/composer && \
        composer --version

#Remove php opcache file -- only for development
RUN rm -rf /usr/local/etc/php/conf.d/opcache-recommended.ini

COPY ./.docker/wait-for-it/wait-for-it.sh /usr/local/wait-for-it.sh
RUN chmod u+x /usr/local/wait-for-it.sh

COPY ./.docker/init.sh /usr/local/bin/init.sh
RUN chmod u+x /usr/local/bin/init.sh

ENTRYPOINT ["/usr/local/bin/init.sh"]
