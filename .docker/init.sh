#!/usr/bin/env bash

echo "Checking Elasticsearch node 1 is ready"
/usr/local/wait-for-it.sh umle1data-0.local:9200 -s --timeout=60 -- echo "Elasticsearch node 1 is ready!"

echo "Checking Elasticsearch node 2 is ready"
/usr/local/wait-for-it.sh umle1data-1.local:9200 -s --timeout=60 -- echo "Elasticsearch node 2 is ready!"

# start apache
sed -i "s/{PORT}/80/g" /etc/apache2/apache2.conf
/usr/sbin/apache2ctl -D FOREGROUND