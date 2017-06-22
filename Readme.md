Drupal Ethereum
===============
**Features**
Drupal Ethereum Module enhances the Drupal ecosystem integrating it with bleeding-edge Ethereum functionality.
The module is the base and includes submodules like the [Ethereum User connector](../ethereum_user_connector/Readme.md).
More below at "Drupal Ethereum Getting Started" and in the <a href="https://github.com/digitaldonkey/ethereum/blob/8.x-1.x/ethereum_user_connector/Readme.md">user connector Readme</a>. 
You may checkout the <a href="https://www.youtube.com/watch?v=Y5Sa7QtpXSE">POC video and transcript</a> describing how to do a paywall with Drupal and Drupal Ethereum including all steps.


In the mid term scope the module will be a "framework" for configurable applications consisting of deploy-able _Smart Contracts_ interaced with Drupal functionality. 
 
**Dependencies** 
<a href="https://en.wikipedia.org/wiki/Ethereum">Ethereum</a> is a blockchain based, decentralized ledger system developed by the <a href="https://www.ethereum.org/">Ethereum Foundation</a>. In order to connect to the Ethereum network you may use a service or set up you own network node.

If you used composer to install Drupal core and the ethereum module everything should work out of the box using <a href="infura.io">Infura's free service</a> to connect to Ethereum network. 

<a href="https://www.lullabot.com/articles/goodbye-drush-make-hello-composer">Don't know composer</a>? You should. 

[RestUI](https://www.drupal.org/project/restui) is a user interface for Drupal 8's REST module.

You will require a running *AMP server with PHP 5.6 + and [Composer](https://getcomposer.org/).  (There are also manual ways described in other Drupal documentation.) We will be using the packaged drupal7 install which is configured for Ubuntu on Ubuntu 16.04+ (17.04 suggested)
 
## Drupal Ethereum Getting started
**Install Dependencies** 

```
apt update && apt upgrade
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
apt install php7.0-cli curl zip unzip git php-mbstring drupal7 -y
```

- [ ] You will configure and note your MySQL and Drupal passwords

```
# install to /var/www
cd /var/www

# Download
composer create-project drupal-composer/drupal-project:~8.0 dreth --stability dev --no-interaction

# Create a Database
mysql -uroot -p --execute="CREATE DATABASE \`drEth.local\`;"
```

- [ ] enter password

```
cd dreth

# Create a configuration export directory. This is not required, but very usefull.
mkdir config

#install
composer install

# Change to web root 
cd web/

# Drupal with drush
# Scripted drupal installation. Revalidate the database. It will be overwritten.
# _DON'T FORGET TO CHANGE YOUR PASSWORD AFTER FIRST LOGIN._
../vendor/bin/drush site-install standard --db-url="mysql://root:<password>@localhost:3306/drEth.local" --account-name="admin" --account-pass="password" --site-name="drEth.local" --account-mail="email@techenterprises.me" --site-mail="email@techenterprises.me" --config-dir="../config" --notify="global" -y
```

- [ ] edit password

```
# Download plugins/modules
# Composer commands need to be run from the drupal directory
cd ..
composer require drupal/restui drupal/admin_toolbar drupal/ethereum
# Enable modules
# Drush commands need to be run from the web directory
cd web/
# If enabling REST API drops an error you may do it later in Drupal UI.
../vendor/bin/drush en admin_toolbar_tools restui ethereum -y
```

- [ ] Visit /admin/config/ethereum and save setting to validate functionality. 
- [ ] Check status page at /admin/reports/ethereum
