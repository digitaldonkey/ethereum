Drupal Ethereum
===============

This module is in early development state. 

## Features

Currently there is a Admin page which enables you to connect Drupal to one <a href="http://ethereum.stackexchange.com/questions/269/what-exactly-is-an-ethereum-client-and-what-clients-are-there/279#279">Ethereum node</a> with open RPC connection. 

If you want to use geth (go-ethereum client) Here is how to <a href="https://github.com/ethereum/go-ethereum/wiki/Building-Ethereum">install geth on many OS</a>.
 
Then you start the geth server with rpc. On my mac like this. Keep in mind that the "*" allows connections from any host. 

``` 
 geth --testnet  --rpc --rpccorsdomain="*"
``` 


## Dependencies 

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
