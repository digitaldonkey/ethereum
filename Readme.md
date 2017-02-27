Drupal Ethereum
===============

This module is in early development state. 

### Features

This module provides the base to connect Ethereum Blockchain with Drupal.

* Provides a PHP abstraction to the Ethereum JsonRPC interface. 
* Depends on PHP Library ... 

### Setup

* To get all required dependencies you need to run `composer install`  
* Make sure Drupal has a Ethereum Node to read from by configuring *Configure Ethereum connection* (/admin/config/ethereum/network)
* There are 3 different network settings provided. Default Ethereum node is set to Infura.io Testnet. You set up you own  Etehreum node and provide jSonRPC access.
 
### Running your own Ethereum node

If you want to use geth (go-ethereum client), here is how to <a href="https://github.com/ethereum/go-ethereum/wiki/Building-Ethereum">install geth on many OS</a>.
Then you start the geth server with rpc. On my mac like this. Keep in mind that the "*" allows connections from any host. 

``` 
 geth --testnet  --rpc --rpccorsdomain="*"
``` 


### Development environment for *smart contracts* using Testrpc

If you want to check out developing your own smart contract it is recommended to use testrpc, which provides a fast Ethereum node for local testing. 

You may modify the currently provided *Login Smart Contract* by cloning it https://github.com/digitaldonkey/register_drupal_ethereum. The repository contains a very little Truffle App which helps you to get started with test driven smart contract development. 
   
 SCREENSHOT

**Connecting with Testrpc**


``` 
# Start testrpc
testrpc

# Clone lign contract:
git clone https://github.com/digitaldonkey/register_drupal_ethereum
cd register_drupal_ethereum

# Deploy contract to testrpc
# This will provide you the smart contract address, which you might want to add Drupals settings. 
truffle migrate

# Start local development environement
truffle serve
``` 


### Dependencies 

Drupal core > 8.1 

Composer is required in order to download the <a href="https://github.com/bluedroplet/ethereum-php-lib">ethereum-php-lib</a>. 

If you used composer to install drupal core and the ethereum module everything should work out of the box.

<a href="https://www.lullabot.com/articles/goodbye-drush-make-hello-composer">Don't know composer</a>? You should. 
 
You have to edit composer.json file in the drupal root folder and add the "bluedroplet/ethereum-php-lib" to the require section:
 
``` 
 "require": {

         ...         
         
         "bluedroplet/ethereum-php-lib": "dev-master"
  },
```
