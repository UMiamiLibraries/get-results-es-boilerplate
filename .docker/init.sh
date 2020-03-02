#!/usr/bin/env bash

echo "Checking Elasticsearch node 1 is ready"
/usr/local/wait-for-it.sh esdata-0.local:9200 -s --timeout=120 -- echo "Elasticsearch node 1 is ready!"

echo "Checking Elasticsearch node 2 is ready"
/usr/local/wait-for-it.sh esdata-1.local:9200 -s --timeout=120 -- echo "Elasticsearch node 2 is ready!"

# start apache
/usr/sbin/apache2ctl -D FOREGROUND
