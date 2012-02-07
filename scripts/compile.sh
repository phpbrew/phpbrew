#!/bin/bash
onion -d compile \
    --lib src \
    --lib vendor/pear \
    --classloader \
    --bootstrap scripts/phpbrew-emb.php \
    --executable \
    --output phpbrew.phar
mv phpbrew.phar phpbrew
chmod +x phpbrew
