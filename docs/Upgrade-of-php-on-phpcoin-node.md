Mostly all older nodes are running on Ubuntu 20.04 with php 7.4 and they need to be updated.
The best way, if it is possible, is just to upgrade Ubuntu to the next LTS version.
Then php will be automatically upgraded to version 8 and servers will be configured accordingly.

That can be done through standard upgrade procedure:
```
apt update
apt upgrade
do-release-upgrade
```
If system upgrade is not possible, php can be still upgraded manually.

It depends only on which web server is used to serve phpcoin nodes.

## Common upgrade procedure

In most cases, if node is installed through official script, web server is apache and upgrade can be do with following procedure:

1. First update packages repository:
```
apt update
```
2. Not mandatory but it is recommended to update installed packages:
```
apt upgrade
```
3. Install some standard packages to work with custom repositories:
```
apt install ca-certificates apt-transport-https
apt install software-properties-common
```
4. Next, add custom repository which provides newest php packages:
```
add-apt-repository ppa:ondrej/php
```
After accept install and update, install newer versions of needed packages:
```
apt install libapache2-mod-php8.1 php8.1-mysql php8.1-gmp php8.1-bcmath php8.1-curl
```
5. After installation is completed check correct version by running command:
```
php -v
```
### Confgure apache

At the end, disable old version:
```
a2dismod php7.4
```
enable new php version:
```
a2enmod php8.1
```
and finally restart web server
```
service apache2 restart
```

### Configure nginx

In case that web server is nginx then procedure is something different:

Complete the same steps 1-4 as above.

Then, install newer php packages:
```
apt install php8.1-fpm php8.1-mysql php8.1-gmp php8.1-bcmath php8.1-curl
```
Check version of php
```
php -v
```
Next, locate and update the nginx config file. It is usually
`/etc/nginx/sites-enabled/phpcoin` for mainnet or
`/etc/nginx/sites-enabled/phpcoin-testnet`
for testnet

Replace line with php config, from:
```
fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
```
with:
```
fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
```
At the end, restart nginx.
```
service nginx restart
```

## Docker upgrade

If node is installed in docker then upgrade can be done by just pulling a new image and recreating the container.

**Important: By recreating the container, config files which contain private keys may be lost, so it is recommended first to backup them (copy) from the container.**

That can be done with command:
```
docker cp phpcoin:/var/www/phpcoin/config/config.inc.php phpcoin-config.inc.php
```
or for testnet:
```
docker cp phpcoin-test:/var/www/phpcoin/config/config.inc.php phpcoin-test-config.inc.php
```
If docker container is created with config volume then it will be preserved on container recreate but in any case it is also recommended to backup config file as above.

Then stop and remove container:
```
docker stop phpcoin
docker rm phpcoin
```
or for testnet:
```
docker stop phpcoin-test
docker rm phpcoin-test
```

Next, pull newest image from docker hub:
```
docker pull phpcoin/node
```
and recreate node with commands:for mainnet:
```
docker run -itd --name phpcoin -p 81:80 -e EXT_PORT=81 -v phpcoin-config:/var/www/phpcoin/config phpcoin/node
```
or for testnet:
```
docker run -itd --name phpcoin-test -p 91:80 -e EXT_PORT=91 -e NETWORK=testnet -v phpcoin-config-test:/var/www/phpcoin/config phpcoin/node
```
If container was not created with volume then you need to copy back config file to container:
```
docker cp phpcoin-config.inc.php phpcoin:/var/www/phpcoin/config/config.inc.php
```
for testnet:
```
docker cp phpcoin-test-config.inc.php phpcoin-test:/var/www/phpcoin/config/config.inc.php
```