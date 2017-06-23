Drupal Ethereum
===============
 

**Features**

Drupal Ethereum Module enhances the Drupal ecosystem with Ethereum functionality.
The module is the base and includes submodules like the [Ethereum User connector](https://github.com/digitaldonkey/ethereum/blob/8.x-1.x/ethereum_user_connector/Readme.md).

In the mid term scope the module will be a "framework" for configurable applications consisting of deploy-able _Smart Contracts_ and connected Drupal functionality. 



**Quick set-up**

* To get all required dependencies you need to run `composer install`  
* Make sure Drupal has a Ethereum Node to read from by configuring *Configure Ethereum connection* (/admin/config/ethereum/network). **Saving the form settings** will validate the current settings and let you know if something is wrong. 
* There are 3 different network settings provided. Default Ethereum node is set to Infura.io <a href="kovan.etherscan.io">Kovan test-network</a>.
* You may set up you own Ethereum node as described below. 
 
More below at "Drupal Ethereum Getting started" and in the <a href="https://github.com/digitaldonkey/ethereum/blob/8.x-1.x/ethereum_user_connector/Readme.md">user connector Readme</a>. 
 
**Dependencies** 

<a href="https://en.wikipedia.org/wiki/Ethereum">Ethereum</a> is a blockchain based, decentralized ledger system developed by the <a href="https://www.ethereum.org/">Ethereum foundation</a>. In order to connect to the Ethereum network you may use a service or set up you own network node.

If you used composer to install Drupal core and the Ethereum module everything should work out of the box using <a href="infura.io">Infura's free service</a> to connect to the Ethereum network. 

<a href="https://www.lullabot.com/articles/goodbye-drush-make-hello-composer">Don't know composer</a>? You should. 

 
 
## Drupal Ethereum Getting started

You may checkout the <a href="https://www.youtube.com/watch?v=Y5Sa7QtpXSE">POC video and transcript</a> describing how to do a paywall with Drupal and Drupal Ethereum including all steps.


**Install Drupal** 

The quickest way to do this is to follow the steps below. You will need PHP [Composer](https://getcomposer.org/) and [drush](http://www.drush.org/en/master/) installed on your system. Alternatively, you can <a href="https://www.drupal.org/docs/8/install">install Drupal manually</a>. 

**Download latest Drupal**

```
composer create-project drupal-composer/drupal-project:~8.0 drupal --stability dev --no-interaction
```

**Create a Database**

```
mysql -uroot  --execute="CREATE DATABASE \`drupalEthereumTest.local\`;"
```

**Drupal with drush**

```
# Create a configuration export directory. This is not required, but very usefull.
mkdir drupal/config
# Change to web root 

cd drupal/web/
# Scripted drupal installation. Revalidate the database. It will be overwritten.
drush site-install standard --db-url="mysql://root:root@localhost:3306/drupalEthereumTest.local" --account-name="tho" --account-pass="password" --site-name="drupalEthereum.local" --account-mail="email@donkeymedia.eu" --site-mail="email@donkeymedia.eu" --config-dir="../config" --notify="global"
```

_DON'T FORGET TO CHANGE YOUR PASSWORD AFTER FIRST LOGIN._

**Download and enable additional Drupal modules**

[RestUI](https://www.drupal.org/project/restui) is a user interface for Drupal 8's REST module.

```
# Composer commands need to be run from the drupal directory
cd ..
composer require drupal/restui drupal/admin_toolbar
``` 
Enable the modules

```
cd web/
drush en admin_toolbar_tools -y
# If enabling REST API produces an error you may do it later in the Drupal UI.
drush en restui -y
```

**Add Drupal Ethereum Module**

```
# Composer commands need to be run from the drupal directory
cd ..
composer require drupal/ethereum
cd web/
drush en ethereum -y
```

Visit /admin/config/ethereum/network, save the configuration settings then check the status page at /admin/reports/ethereum to verify that it's working.


## Running your own Ethereum node

If you want to use geth (go-ethereum client), <a href="https://github.com/ethereum/go-ethereum/wiki/Building-Ethereum">install it</a> then start the geth server with rpc.
On a Mac this looks like this (be aware that the "*" allows connections from any host):

``` 
 geth --testnet  --rpc --rpccorsdomain="*"
``` 


**Development environment for *smart contracts* using Testrpc**

If you want to experiment with developing your own smart contract, it is recommended to use testrpc, which provides a fast Ethereum node for local testing. 

You may modify the currently provided *<a href="https://github.com/digitaldonkey/register_drupal_ethereum">Login Smart Contract</a>* by cloning/forking it. The repository contains a very little Truffle App which helps you to get started with test driven smart contract development. 

**Connecting with Testrpc**

``` 
# Start testrpc
testrpc

# Clone sign-up-contract:
git clone https://github.com/digitaldonkey/register_drupal_ethereum
cd register_drupal_ethereum

# Deploy contract to testrpc
# This will provide you the smart contract address, which you might want to add Drupals settings. 
truffle migrate

# Start local development environement
truffle serve
```  

##Add Drupal Ethereum Module (Old Sandbox version)


Until there is a dev release we need to manually add the dependencies to the composer.json file. 

Edit drupal/composer.json and add the 3 modules and PHP library repositories. 

```
"repositories": [
    {
        "type": "composer",
        "url": "https://packages.drupal.org/8"
    },
    {
        "type": "git",
        "url": "https://github.com/digitaldonkey/ethereum.git"
    },
    {
        "type": "git",
        "url": "https://github.com/digitaldonkey/ethereum-php.git"
    }
```


