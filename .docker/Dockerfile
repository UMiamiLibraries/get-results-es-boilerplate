FROM appsvc/php:7.2-apache_20191031.7

RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer && \
        chmod +x /usr/local/bin/composer && \
        composer --version

#Remove php opcache file -- only for development
RUN rm -rf /usr/local/etc/php/conf.d/opcache-recommended.ini

COPY ./.docker/init.sh /usr/local/bin/init.sh
RUN chmod u+x /usr/local/bin/init.sh

ENTRYPOINT ["/usr/local/bin/init.sh"]