Drupal Ethereum
===============
 

**Introduction**

Drupal Ethereum Module enhances the Drupal ecosystem with Ethereum functionality. 

The basic **Ethereum module** provides a basic framework to interact with the Blockchain via the [Ethereum-PHP](https://github.com/digitaldonkey/ethereum-php) library and is prepared to talk to different Ethereum network nodes (like development, testing, live).

<small>This module provides a List of networks (ethereum.ethereum_networks.yml) and a current server setting (ethereum.settings.yml).</small>

[**Ethereum Smartcontract**](https://github.com/digitaldonkey/ethereum/tree/8.x-1.x/ethereum_smartcontract) submodule allows you to manage smart contracts and use Drupal configuration to connect to the contract depending on the Network the contract has been deployed to. 

<small>This module provides a schema to manage smart contracts (ethereum_smartcontract.schema.yml)</small>

[**Ethereum TXsigner**](to be merged) module is responsible for the frontend part of user interaction. It is currently limited to Web3 enabled Browsers, like [Metamask](https://metamask.io/) browser extension, [Brave Browser](https://brave.com/), [Mist browser](https://github.com/ethereum/mist/releases), but also supports [Metamask Mascara](https://github.com/MetaMask/mascara) (but Mascara is early Alpha by now). 

<small>This module provides a web3 instance so you will have the same Web3 version and not depend what is injected by whichever browser/extension is used.</small>

[**Ethereum User connector**](https://github.com/digitaldonkey/ethereum/blob/8.x-1.x/ethereum_user_connector/Readme.md) is a submodule providing a very simple registry contract (See the [Prove of concept (POC) video](https://www.youtube.com/watch?v=Y5Sa7QtpXSE). This is a early example how the actual applications would be Able to use the infrastructure provided by Drupal Ethereum module.

<small>This module is an example of a Smartcontract. Currently refactoring to match the post POC infrastructure described above.</small>

**Community**

* [Gitter](https://gitter.im/drupal_ethereum)
* [Issues](https://github.com/digitaldonkey/ethereum/issues)
* [Drupal page](https://www.drupal.org/project/ethereum)
* [Drupal Ethereum user group](https://groups.drupal.org/ethereum)
* [PHP Library Issues](https://github.com/digitaldonkey/ethereum-php/issues)

You might watch my Drupal conference talks from [Vienna](https://events.drupal.org/vienna2017/sessions/drupal-and-ethereum-blockchain) or [Baltimore](https://events.drupal.org/baltimore2017/sessions/drupal-and-ethereum-blockchain).
 
## Drupal Ethereum Getting started

**TLDR; Quick set-up**

* Run `composer install` to get all required dependencies
* Make sure Drupal has a Ethereum Node to read from by configuring *Configure Ethereum connection* (/admin/config/ethereum/network). **Saving the form settings** will validate the current settings and let you know if something is wrong. 
* You can start testing with Infura networks provided, run a test client like [Ganache](http://truffleframework.com/ganache) (former [testrpc](http://truffleframework.com/ganache/)) or set up you own Ethereum node.

If you used [composer](https://www.lullabot.com/articles/goodbye-drush-make-hello-composer) to install Drupal core and the Ethereum module everything should work out of the box using <a href="infura.io">Infura's free service</a> to connect to the Ethereum network. 
 
### Long version

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

Add the following to the "repositories" section of your root `composer.json` file:

```
"web3": {
    "type": "package",
    "package": {
        "name": "ethereum/web3.js",
        "version": "1.0.0-beta.35",
        "type": "drupal-library",
        "dist": {
            "url": "https://github.com/ethereum/web3.js/archive/v1.0.0-beta.35.zip",
            "type": "zip"
        }
    }
},
```

Then you should be able to install the module as usual (through Composer):

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

## Development and testing
