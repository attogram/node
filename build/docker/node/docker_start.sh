#!/bin/bash

FILE=/first-run
if test -f "$FILE"; then
    echo "First run node"
    if [ "$CHAIN_ID" = "01" ]; then
        echo "01" > /var/www/phpcoin/chain_id
    else
        echo "00" > /var/www/phpcoin/chain_id
    fi
    wget https://phpcoin.net/scripts/install_node.sh -O /install_node.sh
    chmod +x install_node.sh
    /install_node.sh --docker
    rm $FILE
    rm /install_node.sh
else
    rm -rf /var/www/phpcoin/tmp/*
    service mariadb start
    service nginx start
    service php8.1-fpm start
fi
php /var/www/phpcoin/cli/util.php clear-peers
bash
