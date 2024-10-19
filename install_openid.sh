#!/bin/bash

# Install composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === 'dac665fdc30fdd8ec78b38b9800061b4150413ff2e3b6f88543c636f7cd84f6db9189d43a81e5503cda447da73c7e5b6') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"

# Install openid dependency
php composer.phar require jumbojett/openid-connect-php

# Back & Patch .htaccess to use index-openid.php
cp .htaccess .htaccess.backup
sed -i 's/DirectoryIndex index\.php/DirectoryIndex openid-index\.php/' .htaccess

# Remove compose
php -r "unlink('composer.phar');"
